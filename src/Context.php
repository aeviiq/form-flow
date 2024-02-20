<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Step\StepInterface;

class Context
{
    /**
     * Do not add readonly to these properties until https://github.com/myclabs/DeepCopy/issues/174 is fixed.
     *
     * @param array<int, bool> $completedSteps
     * @param array<int, bool> $softSkippedSteps
     * @param array<int, bool> $hardSkippedSteps
     */
    public function __construct(
        private object $data,
        private int $totalNumberOfSteps,
        private int $currentStepNumber = 1,
        private array $completedSteps = [],
        private array $softSkippedSteps = [],
        private array $hardSkippedSteps = []
    )
    {
        if ($totalNumberOfSteps < 2) {
            throw new InvalidArgumentException(\sprintf('The total number of steps must be 2 or more. "%d" given.', $totalNumberOfSteps));
        }
    }

    public function getData(): object
    {
        return $this->data;
    }

    public function getCurrentStepNumber(): int
    {
        return $this->currentStepNumber;
    }

    public function setCurrentStepNumber(int $currentStepNumber): void
    {
        if ($currentStepNumber < 1 || $currentStepNumber > $this->totalNumberOfSteps) {
            throw new InvalidArgumentException(\sprintf('Step number "%s" is invalid for this context.', $currentStepNumber));
        }

        $this->currentStepNumber = $currentStepNumber;
    }

    public function setCompleted(StepInterface $step): void
    {
        $stepNumber = $step->getNumber();
        if ($stepNumber < 1 || $stepNumber > $this->totalNumberOfSteps) {
            throw new LogicException(sprintf('Step number "%d" is invalid for this context.', $stepNumber));
        }

        if ($this->currentStepNumber < $stepNumber) {
            throw new LogicException('Can not complete a step that is greater than or equal to the current step.');
        }

        $this->completedSteps[$stepNumber] = true;
    }

    public function unsetCompleted(StepInterface $step): void
    {
        unset($this->completedSteps[$step->getNumber()]);
    }

    public function isCompleted(StepInterface $step): bool
    {
        return $this->completedSteps[$step->getNumber()] ?? false;
    }

    /**
     * @throws LogicException When the step contains an illegal step number.
     */
    public function setSoftSkipped(StepInterface $step): void
    {
        $this->validateSkipRequest($step);
        $this->softSkippedSteps[$step->getNumber()] = true;
    }

    public function unsetSoftSkipped(StepInterface $step): void
    {
        unset($this->softSkippedSteps[$step->getNumber()]);
    }

    public function isSoftSkipped(StepInterface $step): bool
    {
        return $this->softSkippedSteps[$step->getNumber()] ?? false;
    }

    /**
     * @throws LogicException When the step contains an illegal step number.
     */
    public function setHardSkipped(StepInterface $step): void
    {
        $this->validateSkipRequest($step);
        $this->hardSkippedSteps[$step->getNumber()] = true;
    }

    public function unsetHardSkipped(StepInterface $step): void
    {
        unset($this->hardSkippedSteps[$step->getNumber()]);
    }

    public function isHardSkipped(StepInterface $step): bool
    {
        return $this->hardSkippedSteps[$step->getNumber()] ?? false;
    }

    public function isSkipped(StepInterface $step): bool
    {
        return $this->isSoftSkipped($step) || $this->isHardSkipped($step);
    }

    private function validateSkipRequest(StepInterface $step): void
    {
        $stepNumber = $step->getNumber();
        if ($stepNumber < 1 || $stepNumber > $this->totalNumberOfSteps) {
            throw new LogicException(sprintf('Step number "%d" is invalid for this context.', $stepNumber));
        }

        if ($stepNumber < 2) {
            throw new LogicException('It is not yet possible to skip the first step of a form flow.');
        }

        if ($stepNumber === $this->totalNumberOfSteps) {
            throw new LogicException('It is not possible to skip the last step of a form flow.');
        }
    }
}
