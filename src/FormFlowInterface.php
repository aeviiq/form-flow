<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Step\Step;
use Aeviiq\FormFlow\Step\StepCollection;
use Symfony\Component\Form\FormInterface;

interface FormFlowInterface extends StartableInterface, BlockableInterface, CompletableInterface
{
    public function getName(): string;

    /**
     * @return bool Whether the flow is capable to go to the next step.
     */
    public function canNext(): bool;

    /**
     * TODO this should throw an exception if $this->isBlocked() or if the form is not valid.
     * @throws TODO set exceptions and their reason (for ALL inside this INTERFACE methods).
     * @throws TODO set exceptions and their reason (for ALL inside this INTERFACE methods).
     * @throws TODO set exceptions and their reason (for ALL inside this INTERFACE methods).
     */
    public function next(): void;

    public function previous(): void;

    public function save(): void;

    public function reset(): void;

    public function getData(): object;

    public function isFormValid(): bool;

    public function getForm(): FormInterface;

    public function getCurrentStepNumber(): int;

    public function getSteps(): StepCollection;

    public function getCurrentStep(): Step;

    public function getNextStep(): Step;

    public function hasNextStep(): bool;

    public function getPreviousStep(): Step;

    public function hasPreviousStep(): bool;

    public function getFirstStep(): Step;

    public function getLastStep(): Step;
}
