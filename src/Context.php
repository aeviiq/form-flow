<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Step\StepCollection;

class Context implements FlowContext
{
    /**
     * @var object
     */
    private $data;

    /**
     * @var StepCollection
     */
    private $steps;

    /**
     * @var int
     */
    private $currentStepNumber = 1;

    public function __construct(object $data, StepCollection $steps)
    {
        $this->data = $data;
        $this->steps = $steps;
    }

    public function getCurrentStepNumber(): int
    {
        // TODO revise this, could also get this information based on the steps..
        return $this->currentStepNumber;
    }

    public function getData(): object
    {
        return $this->data;
    }

    public function getSteps(): StepCollection
    {
        return $this->steps;
    }
}
