<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;

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
    private $expectedDataInstance;

    public function __construct(string $name, StepCollection $steps, string $expectedDataInstance)
    {
        if ('' === $name) {
            throw new InvalidArgumentException(\sprintf('The definition "$name" cannot be empty.'));
        }

        if (!\class_exists($expectedDataInstance) && !\interface_exists($expectedDataInstance)) {
            throw new InvalidArgumentException(\sprintf('The "$expectedDataInstance" must be an existing class or interface.'));
        }

        $this->name = $name;
        $this->expectedDataInstance = $expectedDataInstance;
        $this->steps = $steps;
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
        return $this->expectedDataInstance;
    }
}
