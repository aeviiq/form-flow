<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

interface SkippableInterface
{
    public function isSkipped(): bool;

    public function skip(): void;
}
