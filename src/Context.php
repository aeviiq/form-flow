<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

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

    public function __construct(object $data)
    {
        $this->data = $data;
    }

    public function getCurrentStepNumber(): int
    {
        return $this->currentStepNumber;
    }

    public function getData(): object
    {
        return $this->data;
    }
}
