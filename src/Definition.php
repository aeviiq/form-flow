<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

final class Definition
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var StepCollection
     */
    private $steps;

    /**
     * @var string
     */
    private $name;

    public function __construct(Context $context, StepCollection $steps, string $name)
    {
        $this->context = $context;
        $this->steps = $steps;
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSteps(): StepCollection
    {
        return $this->steps;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
