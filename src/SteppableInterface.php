<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;

interface SteppableInterface
{
    public function getSteps(): StepCollection;

    public function getCurrentStep(): StepInterface;

    public function getNextStep(): StepInterface;

    public function hasNextStep(): bool;

    public function getPreviousStep(): StepInterface;

    public function hasPreviousStep(): bool;

    public function getFirstStep(): StepInterface;

    public function getLastStep(): StepInterface;
}
