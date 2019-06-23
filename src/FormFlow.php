<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Exception\UnexpectedValueException;
use Aeviiq\FormFlow\Step\Step;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\StorageManager\StorageManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

final class FormFlow implements Flow
{
    private static $storageKey = 'form_flow.storage.%s';

    /**
     * @var StorageManager
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
     * @var Context|null
     */
    private $context;

    /**
     * @var bool
     */
    private $blocked = false;

    // TODO inject other dependencies (e.g. events, loading of data, etc.)
    public function __construct(
        StorageManager $storageManager,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory,
        Definition $definition
    ) {
        $this->definition = $definition; // TODO maybe move to start or some other method..?
        $this->storageManager = $storageManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
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

        $this->checkExpectedInstance($data);
        $this->context = new Context($data, $this->definition->getSteps());
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function block(): void
    {
        $this->blocked = true;
    }

    public function isCompleted(): bool
    {
        return $this->getSteps()->filterIncompleteSteps()->isEmpty();
    }

    public function complete(): void
    {
        $this->reset();

        // TODO exception if any step, other then the last one is not yet completed.
        // TODO: Implement complete() method.
    }

    public function canNext(): bool
    {
        if ($this->isBlocked()) {
            return false;
        }

        if ($this->getCurrentStep()->isSkipped()) {
            return true;
        }

        // TODO check if the form is valid
        // TODO: Implement canNext() method.
    }

    public function next(): void
    {
        if (!$this->canNext()) {
            // Exception
        }
        // TODO: Implement next() method.
    }

    public function previous(): void
    {
        // TODO: Implement previous() method.
    }

    public function save(): void
    {
        if (!$this->isStarted()) {
            throw new LogicException(\sprintf('Unable to save the flow without a context. Did you start the flow?'));
        }

        $this->storageManager->save($this->getStorageKey(), $this->getContext());
    }

    public function reset(): void
    {
        // TODO more reset functionality.
        $this->context = null;
        $this->storageManager->remove($this->getStorageKey());
    }

    public function getData(): object
    {
        return $this->getContext()->getData();
    }

    public function getCurrentStepNumber(): int
    {
        return $this->getContext()->getCurrentStepNumber();
    }

    public function getSteps(): StepCollection
    {
        return $this->getContext()->getSteps();
    }

    public function getCurrentStep(): Step
    {
        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber());
    }

    public function getNextStep(): Step
    {
        if (!$this->hasPreviousStep()) {
            throw new LogicException(\sprintf('There are no more next steps left.'));
        }

        return $this->getSteps()
            ->filterStepsGreaterThanNumber($this->getCurrentStepNumber())
            ->filterUnskippedSteps()
            ->last();
    }

    public function hasNextStep(): bool
    {
        return $this->getSteps()
            ->filterStepsGreaterThanNumber($this->getCurrentStepNumber())
            ->filterUnskippedSteps()
            ->isEmpty();
    }

    public function getPreviousStep(): Step
    {
        if (!$this->hasPreviousStep()) {
            throw new LogicException(\sprintf('TODO'));
        }

        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber() - 1);
    }

    public function hasPreviousStep(): bool
    {
        return $this->getSteps()->hasStepWithNumber($this->getCurrentStepNumber() - 1);
    }

    public function getFirstStep(): Step
    {
        return $this->getSteps()->first();
    }

    public function getLastStep(): Step
    {
        return $this->getSteps()->last();
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
        $expectedInstance = $this->definition->getExpectedInstance();
        if (!($data instanceof $expectedInstance)) {
            throw new InvalidArgumentException(\sprintf('The data must be an instanceof %s, %s given.', $expectedInstance, \get_class($data)));
        }
    }

    private function getStorageKey(): string
    {
        return \sprintf(static::$storageKey, $this->definition->getName());
    }

    private function initialize(): void
    {
        $key = $this->getStorageKey();
        if (!$this->storageManager->has($key)) {
            return;
        }

        $context = $this->storageManager->load($key);
        if (!($context instanceof Context)) {
            throw new UnexpectedValueException(\sprintf('The stored context is corrupted. This could be because it is changed by reference.'));
        }

        $this->context = $context;
    }
}
