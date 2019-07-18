<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface CompletableInterface
{
    public function isCompleted(): bool;

    public function complete(): void;
}
