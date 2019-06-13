<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface FormFlow extends Blockable
{
    public function start(): void;

    public function next(): void;

    public function previous(): void;

    public function save(): void;

    public function reset(): void;

    public function finish(): void;

    public function getData(): Context;

    public function getSteps(): StepCollection;

    public function getCurrentStep(): Step;

    public function getNextStep(): Step;

    public function hasNextStep(): bool;

    public function getPreviousStep(): Step;

    public function hasPreviousStep(): bool;

    public function getStepsRemaining(): StepCollection;

    public function getStepsDone(): StepCollection;
}
