<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface Flow extends Blockable
{
    public function start(): void;

    /**
     * @return bool Whether the flow is capable to go to the next step.
     */
    public function canNext(): bool;

    /**
     * TODO this should throw an exception if $this->isBlocked() or if the form is not valid.
     * @throws TODO set exceptions and their reason.
     */
    public function next(): void;

    public function previous(): void;

    public function save(): void;

    public function reset(): void;

    public function finish(): void;

    public function getData(): object;

    public function getDefinition(): Definition;
}
