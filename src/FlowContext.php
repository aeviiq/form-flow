<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Step\StepCollection;

interface FlowContext
{
    public function getCurrentStepNumber(): int;

    public function getData(): object;

    public function getSteps(): StepCollection;
}
