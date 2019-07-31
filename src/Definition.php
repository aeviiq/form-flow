<?php declare(strict_types=1);

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

    /**
     * @throws InvalidArgumentException When any of the given parameters are invalid.
     */
    public function __construct(string $name, StepCollection $steps, string $expectedInstance)
    {
        if ('' === $name) {
            throw new InvalidArgumentException('The definition name cannot be empty.');
        }

        if ($steps->count() < 2) {
            throw new InvalidArgumentException('A flow must consist of at least 2 steps.');
        }

        if (!\class_exists($expectedInstance) && !\interface_exists($expectedInstance)) {
            throw new InvalidArgumentException('The expected instance must be an existing class or interface.');
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

    public function getExpectedDataInstance(): string
    {
        return $this->expectedInstance;
    }
}
