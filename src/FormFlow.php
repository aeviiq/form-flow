<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Step\Step;
use Aeviiq\FormFlow\Step\StepCollection;

final class FormFlow implements Flow
{
    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var Context|null
     */
    private $context;

    // TODO inject other dependencies (e.g. events, loading of data, etc.)
    public function __construct(Definition $definition)
    {
        $this->definition = $definition; // TODO maybe move to start or some other method..?
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

        $expectedInstance = $this->definition->getExpectedInstance();
        if (!($data instanceof $expectedInstance)) { // TODO to own method.
            throw new InvalidArgumentException(\sprintf('The data must be an instanceof %s, %s given.', $expectedInstance, \get_class($data)));
        }

        $this->definition->getSteps();

        $this->context = new Context($data, $this->definition->getSteps());
    }

    public function isBlocked(): bool
    {
        // TODO: Implement isBlocked() method.
    }

    public function block(): void
    {
        // TODO: Implement block() method.
    }

    public function isCompleted(): bool
    {
        return $this->getSteps()->filterIncompleteSteps()->isEmpty();
    }

    public function complete(): void
    {
        // TODO exception if any step, other then the last one is not yet completed.
        // TODO: Implement complete() method.
    }

    public function canNext(): bool
    {
        // TODO: Implement canNext() method.
    }

    public function next(): void
    {
        // TODO: Implement next() method.
    }

    public function previous(): void
    {
        // TODO: Implement previous() method.
    }

    public function save(): void
    {
        // TODO: Implement save() method.
    }

    public function reset(): void
    {
        // TODO more reset functionality.
        $this->context = null;
        // TODO: Implement reset() method.
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

    private function initialize(): void
    {
        // TODO initialize, load context, etc.
        $this->context = null; // TODO load the context.
    }
}
