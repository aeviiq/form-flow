<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

class Context implements TransitionableInterface
{
    /**
     * @var object
     */
    private $data;

    /**
     * @var int
     */
    private $currentStepNumber = 1;

    /**
     * @var int
     */
    private $totalNumberOfSteps;

    public function __construct(object $data, int $totalNumberOfSteps)
    {
        $this->data = $data;
        $this->totalNumberOfSteps = $totalNumberOfSteps;
    }

    public function getData(): object
    {
        return $this->data;
    }

    public function getCurrentStepNumber(): int
    {
        return $this->currentStepNumber;
    }

    public function getTotalStepCount(): int
    {
        return $this->totalNumberOfSteps;
    }

    public function transitionForwards(): void
    {
        if ($this->canTransitionForwards()) {
            ++$this->currentStepNumber;
        }
    }

    public function canTransitionForwards(): bool
    {
        return $this->getCurrentStepNumber() < $this->getTotalStepCount();
    }

    public function transitionBackwards(): void
    {
        if ($this->canTransitionBackwards()) {
            --$this->currentStepNumber;
        }
    }

    public function canTransitionBackwards(): bool
    {
        return $this->getCurrentStepNumber() > 1;
    }
}
