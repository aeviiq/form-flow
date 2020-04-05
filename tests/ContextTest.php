<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\FormFlow\Context;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Step\StepInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ContextTest extends TestCase
{
    public function testConstruct(): void
    {
        $data = new stdClass();
        $context = new Context($data, 2);
        self::assertSame($data, $context->getData());
        self::assertSame(1, $context->getCurrentStepNumber());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The total number of steps must be 2 or more. "0" given.');
        new Context(new stdClass(), 0);
    }

    public function testSetCurrentStepNumberWithNegativeNumber(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Step number "-1" is invalid for this context.');
        $context->setCurrentStepNumber(-1);
    }

    public function testSetCyrrentStepNumberWithNonExistentStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Step number "3" is invalid for this context.');
        $context->setCurrentStepNumber(3);
    }

    public function testSetCompleted(): void
    {
        $context = new Context(new stdClass(), 2);
        $step = $this->createStep(1);

        $context->setCompleted($step);
        self::assertTrue($context->isCompleted($step));
    }

    public function testSetCompletedWithNegativeStepNumber(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "-1" is invalid for this context.');
        $context->setCompleted($this->createStep(-1));
    }

    public function testSetCompletedWithNonExistentStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "3" is invalid for this context.');
        $context->setCompleted($this->createStep(3));
    }

    public function testSetCompletedWithNumberGreaterThanCurrentStep(): void
    {
        $context = new Context(new stdClass(), 2);
        $step = $this->createStep(2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not complete a step that is greater than or equal to the current step.');
        $context->setCompleted($step);
    }

    public function testUnsetCompleted(): void
    {
        $context = new Context(new stdClass(), 2);
        $step = $this->createStep(1);

        $context->setCompleted($step);
        self::assertTrue($context->isCompleted($step));
        $context->unsetCompleted($step);
        self::assertFalse($context->isCompleted($step));
        $context->unsetCompleted($step);
        self::assertFalse($context->isCompleted($step));
    }

    public function testSetSoftSkipped(): void
    {
        $context = new Context(new stdClass(), 3);

        $step = $this->createStep(2);
        $context->setSoftSkipped($step);
        self::assertTrue($context->isSoftSkipped($step));
    }

    public function testSetSoftSkippedWithNegativeStepNumber(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "-1" is invalid for this context.');
        $context->setSoftSkipped($this->createStep(-1));
    }

    public function testSetSoftSkippedWithNonExistentStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "3" is invalid for this context.');
        $context->setSoftSkipped($this->createStep(3));
    }

    public function testSetSoftSkippedOnFirstStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('It is not yet possible to skip the first step of a form flow.');
        $context->setSoftSkipped($this->createStep(1));
    }

    public function testSetSoftSkippedOnLastStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('It is not possible to skip the last step of a form flow.');
        $context->setSoftSkipped($this->createStep(2));
    }

    public function testUnsetSoftSkipped(): void
    {
        $context = new Context(new stdClass(), 3);

        $step = $this->createStep(2);
        $context->setSoftSkipped($step);
        self::assertTrue($context->isSoftSkipped($step));
        $context->unsetSoftSkipped($step);
        self::assertFalse($context->isSoftSkipped($step));
        $context->unsetSoftSkipped($step);
        self::assertFalse($context->isSoftSkipped($step));
    }

    public function testSetHardSkipped(): void
    {
        $context = new Context(new stdClass(), 3);

        $step = $this->createStep(2);
        $context->setHardSkipped($step);
        self::assertTrue($context->isHardSkipped($step));
    }

    public function testSetHardSkippedWithNegativeStepNumber(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "-1" is invalid for this context.');
        $context->setHardSkipped($this->createStep(-1));
    }

    public function testSetHardSkippedWithNonExistentStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Step number "3" is invalid for this context.');
        $context->setHardSkipped($this->createStep(3));
    }

    public function testSetHardSkippedOnFirstStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('It is not yet possible to skip the first step of a form flow.');
        $context->setHardSkipped($this->createStep(1));
    }

    public function testSetHardSkippedOnLastStep(): void
    {
        $context = new Context(new stdClass(), 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('It is not possible to skip the last step of a form flow.');
        $context->setHardSkipped($this->createStep(2));
    }

    public function testUnsetHardSkipped(): void
    {
        $context = new Context(new stdClass(), 3);

        $step = $this->createStep(2);
        $context->setHardSkipped($step);
        self::assertTrue($context->isHardSkipped($step));
        $context->unsetHardSkipped($step);
        self::assertFalse($context->isHardSkipped($step));
        $context->unsetHardSkipped($step);
        self::assertFalse($context->isHardSkipped($step));
    }

    public function testIsSkipped(): void
    {
        $context = new Context(new stdClass(), 3);

        $step = $this->createStep(2);

        $context->setHardSkipped($step);
        self::assertTrue($context->isSkipped($step));

        $context->setSoftSkipped($step);
        self::assertTrue($context->isSkipped($step));

        $context->unsetHardSkipped($step);
        self::assertTrue($context->isSkipped($step));

        $context->unsetSoftSkipped($step);
        self::assertFalse($context->isSkipped($step));
    }

    private function createStep(int $stepNumber): StepInterface
    {
        $step = $this->createStub(StepInterface::class);
        $step->method('getNumber')->willReturn($stepNumber);

        return $step;
    }
}
