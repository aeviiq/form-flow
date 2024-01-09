<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

final class TransitionEvent extends Event
{
    private bool $blocked = false;

    public function isTransitionBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * Blocks further transition, causing TransitionEvent::isTransitionBlocked() to return true.
     * Once blocked, it can not be unblocked.
     */
    public function blockTransition(): void
    {
        $this->blocked = true;
    }
}
