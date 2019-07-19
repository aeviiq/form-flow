<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

interface EquatableInterface
{
    public function isEqualTo(StepInterface $step): bool;
}
