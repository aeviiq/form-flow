<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\LogicException;
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

    public function getForm(): FormInterface;

    public function getCurrentStepNumber(): int;
}
