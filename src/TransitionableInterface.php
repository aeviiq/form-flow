<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

interface TransitionableInterface
{
    public function transitionForwards(): void;

    public function canTransitionForwards(): bool;

    public function transitionBackwards(): void;

    public function canTransitionBackwards(): bool;
}
