<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\Collection\StringCollection;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Step\StepCollection;

final class Definition
{
    public const DEFAULT_GROUP = 'Default';

    private StringCollection $groups;

    /**
     * @param array<int, string> $groups
     *
     * @throws InvalidArgumentException When any of the given parameters is invalid.
     */
    public function __construct(private readonly string $name, private readonly string $expectedInstance, private readonly StepCollection $steps, array $groups = [self::DEFAULT_GROUP])
    {
        if ('' === $name) {
            throw new InvalidArgumentException('The definition name cannot be empty.');
        }

        if (!\class_exists($expectedInstance) && !\interface_exists($expectedInstance)) {
            throw new InvalidArgumentException('The expected instance must be an existing class or interface.');
        }

        if ($steps->count() < 2) {
            throw new InvalidArgumentException('A flow must consist of at least 2 steps.');
        }

        $this->groups = new StringCollection($groups);
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

    public function getGroups(): StringCollection
    {
        return $this->groups;
    }
}
