<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Step\StepCollection;

final class Definition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var StepCollection
     */
    private $steps;

    /**
     * @var string The instance that the data for this definition is expected to be.
     */
    private $expectedInstance;

    public function __construct(string $name, StepCollection $steps, string $expectedInstance)
    {
        if ('' === $name) {
            throw new InvalidArgumentException(\sprintf('The definition "$name" cannot be empty.'));
        }

        if (!\class_exists($expectedInstance) && !\interface_exists($expectedInstance)) {
            throw new InvalidArgumentException(\sprintf('The "$expectedInstance" must be an existing class or interface.'));
        }

        if ($steps->count() < 2) {
            throw new InvalidArgumentException(\sprintf('The "$steps" must contain at least 2 steps.'));
        }

        $this->name = $name;
        $this->steps = $steps;
        $this->expectedInstance = $expectedInstance;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getSteps(): StepCollection
    {
        return $this->steps;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpectedInstance(): string
    {
        return $this->expectedInstance;
    }
}
