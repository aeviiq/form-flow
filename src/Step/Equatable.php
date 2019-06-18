<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

interface Equatable
{
    public function isEqualTo(Step $step): bool;
}
