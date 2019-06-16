<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

class Context
{
    /**
     * @var int
     */
    private $currentStepNumber = 1;

    /**
     * @var object
     */
    private $data;

    public function __construct(object $data)
    {
        $this->data = $data;
    }

    public function getCurrentStepNumer(): int
    {
        return $this->currentStepNumber;
    }

    public function transitionForwards(): void
    {
        ++$this->currentStepNumber;
    }

    public function transitionBackwards(): void
    {
        --$this->currentStepNumber;
    }

    public function getData(): object
    {
        return $this->data;
    }
}
