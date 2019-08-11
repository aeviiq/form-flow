<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Enum\TransitionEnum;
use Aeviiq\FormFlow\Event\CompleteEvent;
use Aeviiq\FormFlow\Event\Event;
use Aeviiq\FormFlow\Event\ResetEvent;
use Aeviiq\FormFlow\Event\StartEvent;
use Aeviiq\FormFlow\Event\TransitionBackwardsEvent;
use Aeviiq\FormFlow\Event\TransitionForwardsEvent;
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
            throw new LogicException('The flow is already started. In order to start it again, you need to reset() it.');
        }

        if ($data instanceof Context) {
            $context = $data;
            $data = $context->getData();
        }

        $this->checkExpectedInstance($data);
        $this->context = $context ?? new Context($data, $this->definition->getSteps()->count());
        $this->dispatchFlowEvents(new StartEvent($this), FormFlowEvents::STARTED);
    }

    /**
     * @throws LogicException When it is unable to transition forwards.
     */
    public function transitionForwards(): void
    {
        if (!$this->canTransitionForwards()) {
            throw new LogicException('Unable to transition forwards. Use FormFlow#canTransitionForwards() to ensure the flow is in a valid state before attempting to transition.');
        }

        $currentStepNumber = $this->getCurrentStepNumber();
        $this->dispatchFlowEvents(new TransitionForwardsEvent($this), FormFlowEvents::PRE_TRANSITION_FORWARDS, $currentStepNumber);

        // The flow could be blocked by any PRE_TRANSITION_FORWARD listener, thus we check again if we are still able to transition.
        if (!$this->canTransitionForwards()) {
            unset($this->forms[$currentStepNumber]);

            return;
        }

        if ($this->getCurrentStep() === $this->getLastStep()) {
            if ($this->canComplete()) {
                $this->complete();
            }

            return;
        }

        $this->getContext()->transitionForwards();
        $this->transitioned = true;

        $this->dispatchFlowEvents(new TransitionForwardsEvent($this), FormFlowEvents::TRANSITIONED_FORWARDS, $currentStepNumber);
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
            throw new LogicException('Unable to transition backwards. Use FormFlow#canTransitionBackwards() to ensure the flow is in a valid state before attempting to transition.');
        }

        $currentStepNumber = $this->getCurrentStepNumber();
        $this->dispatchFlowEvents(new TransitionBackwardsEvent($this), FormFlowEvents::PRE_TRANSITION_BACKWARDS, $currentStepNumber);

        // The flow could be blocked by any PRE_TRANSITION_BACKWARDS listener, thus we check again if we are still able to transition.
        if (!$this->canTransitionBackwards()) {
            unset($this->forms[$currentStepNumber]);

            return;
        }

        $this->transitioned = true;
        $this->getContext()->transitionBackwards();

        $this->dispatchFlowEvents(new TransitionBackwardsEvent($this), FormFlowEvents::TRANSITIONED_BACKWARDS, $currentStepNumber);
    }

    public function canTransitionBackwards(): bool
    {
        return $this->getContext()->canTransitionBackwards();
    }

    public function reset(): void
    {
        $this->resetFlow();
        $this->dispatchFlowEvents(new ResetEvent($this), FormFlowEvents::RESET);
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
        if (!$this->canComplete()) {
            throw new LogicException('Unable to complete. Use FormFlow#canComplete() to ensure the flow is in a valid state before attempting to complete.');
        }

        $data = $this->getData();
        $this->dispatchFlowEvents(new CompleteEvent($this, $data), FormFlowEvents::PRE_COMPLETE);
        if (!$this->canComplete()) {
            unset($this->forms[$this->getCurrentStepNumber()]);

            return;
        }

        $this->resetFlow();
        $this->transitioned = true;
        $this->completed = true;
        $this->dispatchFlowEvents(new CompleteEvent($this, $data), FormFlowEvents::COMPLETED);
    }

    public function canComplete(): bool
    {
        // TODO check if each step is completed.
        // TODO add conditions which could make this return false.
        return true;
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

    public function save(): void
    {
        if (!$this->isStarted()) {
            throw new LogicException('Unable to save the flow without a context. Did you FormFlow#start() the flow?');
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

    private function resetFlow(): void
    {
        $this->context = null;
        $this->storageManager->remove($this->getStorageKey());
        $this->forms = [];
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

                    return $this->hasTransitioned();
                }

                return false;
            case (TransitionEnum::BACKWARDS):
                if ($this->canTransitionBackwards()) {
                    // Load the current form and save any existing data before we transition backwards.
                    $form = $this->getForm();
                    $form->handleRequest($this->getRequest());
                    $form->isSubmitted() && $form->isValid();

                    $this->transitionBackwards();

                    return $this->hasTransitioned();
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
            throw new LogicException('Unable to determine the requested transition. Use the FormFlow#getTransitionKey() method to name your submit actions.');
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
            throw new LogicException('Unable to retrieve the request.');
        }

        return $request;
    }

    private function getContext(): Context
    {
        if (null === $this->context) {
            throw new LogicException('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
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

    private function dispatchFlowEvents(Event $event, string $eventName, ?int $currentStepNumber = null): void
    {
        if (null !== $currentStepNumber) {
            $this->eventDispatcher->dispatch($event, $this->createFlowStepListenerId($eventName, $currentStepNumber));
        }

        $this->eventDispatcher->dispatch($event, $this->createFlowListenerId($eventName));
        $this->eventDispatcher->dispatch($event, $eventName);
    }

    private function createFlowListenerId(string $listener): string
    {
        return \sprintf('%s.%s', $listener, $this->getName());
    }

    private function createFlowStepListenerId(string $listener, int $currentStepNumber): string
    {
        return \sprintf('%s.%s.step_%s', $listener, $this->getName(), $currentStepNumber);
    }

    private function initialize(): void
    {
        $key = $this->getStorageKey();
        if (!$this->storageManager->has($key)) {
            return;
        }

        $context = $this->storageManager->load($key);
        if (!($context instanceof Context)) {
            throw new UnexpectedValueException('The stored context is corrupted.');
        }

        $this->context = $context;
    }
}
