<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests\Step;

use Aeviiq\FormFlow\Step\Step;
use Aeviiq\FormFlow\Step\StepInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormTypeInterface;

final class StepTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    public static function equalProvider(): array
    {
        $formClass = get_class(self::createStub(FormTypeInterface::class));

        return [
            'same' => [
                self::createStepInstance(1, $formClass, 'some-label', 'next-label', 'previous-label'),
                true,
            ],
            'other_number' => [
                self::createStepInstance(0, $formClass, 'some-label', 'next-label', 'previous-label'),
                false,
            ],
            'other_form' => [
                self::createStepInstance(1, 'other', 'some-label', 'next-label', 'previous-label'),
                false,
            ],
            'other_label' => [
                self::createStepInstance(1, $formClass, 'other-label', 'next-label', 'previous-label'),
                false,
            ],
            'other_next_label' => [
                self::createStepInstance(1, $formClass, 'some-label', 'other-label', 'previous-label'),
                false,
            ],
            'other_previous_label' => [
                self::createStepInstance(1, $formClass, 'some-label', 'next-label', 'other-label'),
                false,
            ],
        ];
    }

    private static function createStepInstance(int $number, string $formType, string $label, string $nextLabel, string $previousLabel): StepInterface
    {
        $step = self::createStub(StepInterface::class);
        $step->method('getNumber')->willReturn($number);
        $step->method('getFormType')->willReturn($formType);
        $step->method('getLabel')->willReturn($label);
        $step->method('getNextLabel')->willReturn($nextLabel);
        $step->method('getPreviousLabel')->willReturn($previousLabel);

        return $step;
    }
    
    public function testConstruct(): void
    {
        $form = self::createStub(FormTypeInterface::class);

        $step = new Step(1, get_class($form), 'some-label', 'next-label', 'previous-label');
        self::assertSame(1, $step->getNumber());
        self::assertSame(get_class($form), $step->getFormType());
        self::assertSame('some-label', $step->getLabel());
        self::assertSame('next-label', $step->getNextLabel());
        self::assertSame('previous-label', $step->getPreviousLabel());
    }

    public function testConstructWithInvalidStepNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number must be above 0. "0" given.');
        new Step(0, get_class(self::createStub(FormTypeInterface::class)), 'some-label', 'next-label', 'previous-label');
    }

    public function testConstructWithInvalidFormType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('"invalid-class" must be an instance of "%s".', FormTypeInterface::class));
        new Step(1, 'invalid-class', 'some-label', 'next-label', 'previous-label');
    }

    public function testConstructWithInvalidLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The label cannot be empty.');
        new Step(1, get_class(self::createStub(FormTypeInterface::class)), '', 'next-label', 'previous-label');
    }

    public function testConstructWithInvalidNextLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The next label cannot be empty.');
        new Step(1, get_class(self::createStub(FormTypeInterface::class)), 'some-label', '', 'previous-label');
    }

    public function testConstructWithInvalidPreviousLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The previous label cannot be empty.');
        new Step(1, get_class(self::createStub(FormTypeInterface::class)), 'some-label', 'next-label', '');
    }

    /**
     * @dataProvider equalProvider
     */
    public function testIsEqualTo(StepInterface $compare, bool $match): void
    {
        $step = $step = new Step(1, get_class(self::createStub(FormTypeInterface::class)), 'some-label', 'next-label', 'previous-label');

        self::assertSame($step->isEqualTo($compare), $match);
    }
}
