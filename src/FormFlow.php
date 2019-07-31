<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Enum\TransitionEnum;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Exception\UnexpectedValueException;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use Aeviiq\StorageManager\StorageManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class FormFlow implements FormFlowInterface
{
    /**
     * @var string
     */
    private static $storageKey = 'form_flow.storage.%s';

    /**
     * @var StorageManagerInterface
     */
    private $storageManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var RequestStack|null
     */
    private $requestStack;

    /**
     * @var Context|null
     */
    private $context;

    /**
     * @var bool
     */
    private $transitioned = false;

    /**
     * @var bool
     */
    private $completed = false;

    /**
     * @var FormInterface[]
     */
    private $forms = [];

//    /**
//     * @var bool
//     */
//    private $blocked = false;

    public function __construct(
        StorageManagerInterface $storageManager,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory,
        Definition $definition
    ) {
        $this->storageManager = $storageManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->definition = $definition;
        $this->initialize();
    }

    public function isStarted(): bool
    {
        return null !== $this->context;
    }

    public function start(object $data): void
    {
        if ($this->isStarted()) {
            throw new LogicException(\sprintf('The flow is already started. In order to start it again, you need to reset() it.'));
        }

        if ($data instanceof Context) {
            $context = $data;
            $data = $context->getData();
        }

        $this->checkExpectedInstance($data);
        $this->context = $context ?? new Context($data, $this->definition->getSteps()->count());
    }

    /**
     * @throws LogicException When it is unable to transition forwards.
     */
    public function transitionForwards(): void
    {
        if (!$this->canTransitionForwards()) {
            throw new LogicException('Unable to transition forwards. Use canTransitionForwards() to ensure the flow is in a valid state before attempting to transition.');
        }

        $this->transitioned = true;

        if ($this->getCurrentStep() === $this->getLastStep()) {
            $this->complete();

            return;
        }

        $this->getContext()->transitionForwards();
    }

    public function canTransitionForwards(): bool
    {
        if (!$this->isFormValid()) {
            return false;
        }

        if ($this->getCurrentStep() === $this->getLastStep()) {

            return true;
        }

        return $this->getContext()->canTransitionForwards();
    }

    /**
     * @throws LogicException When it is unable to transition backwards.
     */
    public function transitionBackwards(): void
    {
        if (!$this->canTransitionBackwards()) {
            throw new LogicException('Unable to transition backwards. Use canTransitionBackwards() to ensure the flow is in a valid state before attempting to transition.');
        }

        $this->transitioned = true;

        $this->getContext()->transitionBackwards();
    }

    public function canTransitionBackwards(): bool
    {
        return $this->getContext()->canTransitionBackwards();
    }

    public function reset(): void
    {
        $this->context = null;
        $this->storageManager->remove($this->getStorageKey());
    }

    public function getName(): string
    {
        return $this->definition->getName();
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    public function getTransitionKey(): string
    {
        return \sprintf('flow_%s_%s', $this->getName(), 'transition');
    }

    public function transition(): bool
    {
        if (!$this->isRequestedTransitionValid()) {
            return false;
        }

        $result = $this->doTransition();
        if (!$this->isCompleted()) {
            $this->save();
        }

        return $result;
    }

    public function hasTransitioned(): bool
    {
        return $this->transitioned;
    }

    public function complete(): void
    {
        // TODO check if each step is completed.
        $this->reset();
        $this->completed = true;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }


//    public function isBlocked(): bool
//    {
//        return $this->blocked;
//    }
//
//    public function block(): void
//    {
//        $this->blocked = true;
//    }

//    public function isCompleted(): bool
//    {
//        return $this->getSteps()->filterIncompleteSteps()->isEmpty();
//    }
//
//    public function complete(): void
//    {
//        $this->reset();
//
//        // TODO exception if any step, other then the last one is not yet completed.
//        // TODO: Implement complete() method.
//    }

    public function save(): void
    {
        if (!$this->isStarted()) {
            throw new LogicException(\sprintf('Unable to save the flow without a context. Did you start() the flow?'));
        }

        $this->storageManager->save($this->getStorageKey(), $this->getContext());
    }

    public function getData(): object
    {
        return $this->getContext()->getData();
    }

    public function isFormValid(): bool
    {
        $form = $this->getForm();
        if (!$form->isSubmitted()) {
            $form->handleRequest($this->getRequest());
        }

        return $form->isSubmitted() && $form->isValid();
    }

    public function getForm(): FormInterface
    {
        return $this->getFormForStep($this->getCurrentStep());
    }

    public function getCurrentStepNumber(): int
    {
        return $this->getContext()->getCurrentStepNumber();
    }

    public function getSteps(): StepCollection
    {
        return $this->definition->getSteps();
    }

    public function getCurrentStep(): StepInterface
    {
        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber());
    }

    public function getNextStep(): StepInterface
    {
        if (!$this->hasNextStep()) {
            throw new LogicException('There is no next step.');
        }

        return $this->getSteps()
            ->filterStepsGreaterThanNumber($this->getCurrentStepNumber())
            ->filterUnskippedSteps()
            ->last();
    }

    public function hasNextStep(): bool
    {
        return !$this->getSteps()
            ->filterStepsGreaterThanNumber($this->getCurrentStepNumber())
            ->filterUnskippedSteps()
            ->isEmpty();
    }

    public function getPreviousStep(): StepInterface
    {
        if (!$this->hasPreviousStep()) {
            throw new LogicException('There is no previous step.');
        }

        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber() - 1);
    }

    public function hasPreviousStep(): bool
    {
        return $this->getSteps()->hasStepWithNumber($this->getCurrentStepNumber() - 1);
    }

    public function getFirstStep(): StepInterface
    {
        return $this->getSteps()->first();
    }

    public function getLastStep(): StepInterface
    {
        return $this->getSteps()->last();
    }

    private function getFormForStep(StepInterface $step): FormInterface
    {
        $stepNumber = $step->getNumber();
        if (!isset($this->forms[$stepNumber])) {
            $this->forms[$stepNumber] = $this->formFactory->create(
                $step->getFormType(),
                $this->getData()
            );
        }

        return $this->forms[$stepNumber];
    }

    private function doTransition(): bool
    {
        switch ($this->getTransition()->getValue()) {
            case (TransitionEnum::FORWARDS):
                if ($this->canTransitionForwards()) {
                    $this->transitionForwards();

                    return true;
                }

                return false;
            case (TransitionEnum::BACKWARDS):
                if ($this->canTransitionBackwards()) {
                    // Load the current form and save any existing data before we transition backwards.
                    $form = $this->getForm();
                    $form->handleRequest($this->getRequest());
                    $form->isSubmitted() && $form->isValid();

                    $this->transitionBackwards();

                    return true;
                }

                return false;
            // TODO implement reset later
//            case (TransitionEnum::RESET):
//                $this->reset();
//
//                return true;
            default:
                return false;
        }
    }

    private function isRequestedTransitionValid(): bool
    {
        return TransitionEnum::isValid($this->getRequestedTransitionFromRequest());
    }

    private function getTransition(): TransitionEnum
    {
        if (!$this->isRequestedTransitionValid()) {
            throw new LogicException('Unable to determine the requested transition. Use the getTransitionKey() method to name your submit actions.');
        }

        return new TransitionEnum($this->getRequestedTransitionFromRequest());
    }

    private function getRequestedTransitionFromRequest(): string
    {
        return $this->getRequest()->get($this->getTransitionKey(), '');
    }

    private function getRequest(): Request
    {
        if (null === $this->requestStack || null === $request = $this->requestStack->getCurrentRequest()) {
            throw new LogicException(\sprintf('Unable to retrieve the request.'));
        }

        return $request;
    }

    private function getContext(): Context
    {
        if (null === $this->context) {
            throw new LogicException(\sprintf('The flow is missing it\'s context. Did you start() the flow?'));
        }

        return $this->context;
    }

    private function checkExpectedInstance(object $data): void
    {
        $expectedInstance = $this->definition->getExpectedDataInstance();
        if (!($data instanceof $expectedInstance)) {
            throw new InvalidArgumentException(\sprintf('The data must be an instanceof %s, %s given.', $expectedInstance, \get_class($data)));
        }
    }

    private function getStorageKey(): string
    {
        return \sprintf(static::$storageKey, $this->getName());
    }

    private function initialize(): void
    {
        $key = $this->getStorageKey();
        if (!$this->storageManager->has($key)) {
            return;
        }

        $context = $this->storageManager->load($key);
        if (!($context instanceof Context)) {
            throw new UnexpectedValueException(\sprintf('The stored context is corrupted.'));
        }

        $this->context = $context;
    }
}
