<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\FormFlow\Definition;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $definintion = new Definition('some-name', stdClass::class, new StepCollection([
            self::createStub(StepInterface::class),
            self::createStub(StepInterface::class),
        ]), ['group-1', 'group-2']);

        self::assertSame('some-name', $definintion->getName());
        self::assertSame(stdClass::class, $definintion->getExpectedDataInstance());
        self::assertSame(['group-1', 'group-2'], $definintion->getGroups()->toArray());
        self::assertSame('some-name', (string)$definintion);
    }

    public function testConstructWithInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The definition name cannot be empty');

        new Definition('', stdClass::class, new StepCollection([
            self::createStub(StepInterface::class),
            self::createStub(StepInterface::class),
        ]), ['group-1', 'group-2']);
    }

    public function testConstructWithInvalidExpectedInstance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expected instance must be an existing class or interface.');

        new Definition('some-name', 'some-non-existent-class', new StepCollection([
            self::createStub(StepInterface::class),
            self::createStub(StepInterface::class),
        ]), ['group-1', 'group-2']);
    }

    public function testConstructWithInvalidStepCount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A flow must consist of at least 2 steps.');

        new Definition('some-name', stdClass::class, new StepCollection([
            self::createStub(StepInterface::class),
        ]), ['group-1', 'group-2']);
    }
}
