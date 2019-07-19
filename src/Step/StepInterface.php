<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

// TODO implement these interfaces
interface StepInterface extends EquatableInterface, \Serializable//, CompletableInterface, SkippableInterface
{
    public function getNumber(): int;

    public function getFormType(): string;

    public function getLabel(): string;

    public function getNextLabel(): string;

    public function getPreviousLabel(): string;

    public function getRouteName(): ?string;
}
