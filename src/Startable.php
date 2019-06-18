<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface Startable
{
    public function isStarted(): bool;

    // TODO revise this data here.. maybe use setData for the flow..?
    public function start(object $data): void;
}
