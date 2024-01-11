<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow;

interface StartableInterface
{
    public function isStarted(): bool;

    public function start(object $data): void;
}
