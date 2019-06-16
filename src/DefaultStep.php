<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

final class DefaultStep implements Step
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

    public function __construct(
        int $number,
        string $formType,
        ?string $label = null,
        string $nextLabel = 'Next',
        string $previousLabel = 'Previous'
    ) {
        $this->number = $number;
        $this->formType = $formType;
        $this->label = $label ?? 'Step ' . $this->number;
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
}
