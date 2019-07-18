<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\FormFlow\CompletableInterface;
use Aeviiq\FormFlow\SkippableInterface;

interface StepInterface extends CompletableInterface, SkippableInterface, EquatableInterface, \Serializable
{
    public function getNumber(): int;

    public function getFormType(): string;

    public function getLabel(): string;

    public function getNextLabel(): string;

    public function getPreviousLabel(): string;

    public function getRouteName(): ?string;
}
