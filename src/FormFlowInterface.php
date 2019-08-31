<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\Collection\StringCollection;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Step\StepInterface;
use Symfony\Component\Form\FormInterface;

interface FormFlowInterface extends StartableInterface, SteppableInterface, ResettableInterface
{
    /**
     * @return string The unique name of the form flow.
     */
    public function getName(): string;

    /**
     * @return string The html input name which should have the desired transition.
     */
    public function getTransitionKey(): string;

    /**
     * @throws LogicException When the context is not yet set.
     */
    public function getContext(): Context;

    public function save(): void;

    public function getData(): object;

    public function getGroups(): StringCollection;

    public function getCurrentStepForm(): FormInterface;

    public function getFormByStep(StepInterface $step): FormInterface;

    public function getFormByStepNumber(int $stepNumber): FormInterface;

    public function getCurrentStepNumber(): int;
}
