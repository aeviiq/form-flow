<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;

class Context
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
        if ($totalNumberOfSteps < 2) {
            throw new InvalidArgumentException(\sprintf('The total number of steps must be above 2. "%d" given.', $totalNumberOfSteps));
        }

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

    public function setCurrentStepNumber(int $currentStepNumber): void
    {
        if ($this->currentStepNumber < 1 || $currentStepNumber > $this->totalNumberOfSteps) {
            throw new InvalidArgumentException(\sprintf('Step number "%s" is invalid for this context.', $currentStepNumber));
        }

        $this->currentStepNumber = $currentStepNumber;
    }
}
