<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormTypeInterface;

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

    /**
     * @throws InvalidArgumentException When any of the given parameters are invalid.
     */
    public function __construct(
        int $number,
        string $formType,
        string $label,
        string $nextLabel,
        string $previousLabel
    ) {
        if ($number < 1) {
            throw new InvalidArgumentException(\sprintf('The number must be above 1. "%d" given.', $number));
        }

        if (!\is_a($formType, FormTypeInterface::class, true)) {
            throw new InvalidArgumentException(\sprintf('"%s" must be an instance of "%s".', $formType, FormTypeInterface::class));
        }

        if ('' === $label) {
            throw new InvalidArgumentException('The label cannot be empty.');
        }

        if ('' === $nextLabel) {
            throw new InvalidArgumentException('The next label cannot be empty.');
        }

        if ('' === $previousLabel) {
            throw new InvalidArgumentException('The previous label cannot be empty.');
        }

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

    public function isEqualTo(StepInterface $step): bool
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
}
