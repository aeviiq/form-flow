<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\LogicException;

final class Definition
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var StepCollection
     */
    private $steps;

    /**
     * @var string
     */
    private $name;

    public function __construct(Context $context, StepCollection $steps, string $name)
    {
        $this->context = $context;
        $this->steps = $steps;
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSteps(): StepCollection
    {
        return $this->steps;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstStep(): Step
    {
        return $this->steps->first();
    }

    public function getLastStep(): Step
    {
        return $this->steps->last();
    }

    public function getCurrentStep(): Step
    {
        return $this->steps->getStepByNumber($this->context->getCurrentStepNumer());
    }

    public function getNextStep(): Step
    {
        if (!$this->hasNextStep()) {
            throw new LogicException(\sprintf('The flow does not have any more next steps.'));
        }

        return $this->steps->getStepByNumber($this->context->getCurrentStepNumer() + 1);
    }

    public function hasNextStep(): bool
    {
        return $this->steps->hasStepWithNumber($this->context->getCurrentStepNumer() + 1);
    }

    public function getPreviousStep(): Step
    {
        if (!$this->hasPreviousStep()) {
            throw new LogicException(\sprintf('The flow does not have any more previous steps.'));
        }

        return $this->steps->getStepByNumber($this->context->getCurrentStepNumer() - 1);
    }

    public function hasPreviousStep(): bool
    {
        return $this->steps->hasStepWithNumber($this->context->getCurrentStepNumer() - 1);
    }

    public function getStepsRemaining(): StepCollection
    {
        return $this->steps->filterStepsGreaterThanOrEqualToNumber($this->context->getCurrentStepNumer());
    }

    public function getStepsDone(): StepCollection
    {
        return $this->steps->filterStepsSmallerThanNumber($this->context->getCurrentStepNumer());
    }
}
