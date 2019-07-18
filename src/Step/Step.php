<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

final class Step implements StepInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $formType;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $nextLabel;

    /**
     * @var string
     */
    private $previousLabel;

    /**
     * @var bool
     */
    private $completed = false;

    /**
     * @var bool
     */
    private $skipped = false;

    public function __construct(
        int $number,
        string $formType,
        string $label,
        string $nextLabel,
        string $previousLabel
    ) {
        $this->number = $number;
        $this->formType = $formType;
        $this->label = $label;
        $this->nextLabel = $nextLabel;
        $this->previousLabel = $previousLabel;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getNextLabel(): string
    {
        return $this->nextLabel;
    }

    public function getPreviousLabel(): string
    {
        return $this->previousLabel;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function complete(): void
    {
        $this->completed = true;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function skip(): void
    {
        $this->skipped = true;
    }

    public function isEqualTo(Step $step): bool
    {
        if ($this->getNumber() !== $step->getNumber()) {
            return false;
        }

        if ($this->getFormType() !== $step->getFormType()) {
            return false;
        }

        if ($this->getLabel() !== $step->getLabel()) {
            return false;
        }

        if ($this->getNextLabel() !== $step->getNextLabel()) {
            return false;
        }

        if ($this->getPreviousLabel() !== $step->getPreviousLabel()) {
            return false;
        }

        return true;
    }

    public function serialize(): string
    {
        return serialize([
            $this->number,
            $this->formType,
            $this->label,
            $this->nextLabel,
            $this->previousLabel,
            $this->completed,
            $this->skipped,
        ]);
    }

    public function unserialize($serialized): void
    {
        list(
            $this->number,
            $this->formType,
            $this->label,
            $this->nextLabel,
            $this->previousLabel,
            $this->completed,
            $this->skipped,
            ) = $serialized;
    }
}
