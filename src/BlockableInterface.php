<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface BlockableInterface
{
    public function isBlocked(): bool;

    /**
     * Blocks the subject, causing Blockable::isBlocked() to return true.
     * Once something is blocked, it should not be possible to unblock.
     * This is to prevent complex cases and ensure when something is blocked, any
     * further process is guaranteed to be blocked.
     */
    public function block(): void;
}
