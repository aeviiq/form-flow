<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

final class TransitionEvent extends Event
{
    private $blocked = false;

    public function isFlowBlocked(): bool
    {
        return $this->blocked;
    }

    public function blockFlow(): void
    {
        $this->blocked = true;
    }
}
