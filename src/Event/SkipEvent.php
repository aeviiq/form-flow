<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

final class SkipEvent extends Event
{
    /**
     * @var bool
     */
    private $softSkipped = false;

    /**
     * @var bool
     */
    private $hardSkipped = false;

    public function isSoftSkipped(): bool
    {
        return $this->softSkipped;
    }

    /**
     * Soft skipped steps will still be accessible by the user directly. Meaning that a backwards transition will
     * allow the user to edit the skipped skip.
     *
     * Typical use cases for this are prefilled billing information in a checkout flow.
     */
    public function softSkip(): void
    {
        $this->softSkipped = true;
    }

    /**
     * Hard skipped steps will not be accessible by the user directly. Meaning that a backwards transition will
     * skip the step as well.
     *
     * Typical use cases for this are optional steps, depending on the choices of the user.
     *
     * A hard skipped skip can be reached again, if the defined condition which caused it to be hard skipped, changes.
     */
    public function isHardSkipped(): bool
    {
        return $this->hardSkipped;
    }

    public function hardSkip(): void
    {
        $this->hardSkipped = true;
    }
}
