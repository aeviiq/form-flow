<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests\Step;

use Aeviiq\Collection\Exception\LogicException;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use PHPUnit\Framework\TestCase;

final class StepCollectionTest extends TestCase
{
    /**
     * @var StepInterface
     */
    private $step1;

    /**
     * @var StepInterface
     */
    private $step2;

    /**
     * @var StepInterface
     */
    private $step3;

    /**
     * @var StepCollection
     */
    private $collection;

    public function testGetStepByNumber(): void
    {
        self::assertSame($this->step1, $this->collection->getStepByNumber(1));
        self::assertSame($this->step2, $this->collection->getStepByNumber(2));
        self::assertSame($this->step3, $this->collection->getStepByNumber(3));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No results found, one expected.');
        $this->collection->getStepByNumber(4);
    }

    public function testHasStepWithNumber(): void
    {
        self::assertTrue($this->collection->hasStepWithNumber(1));
        self::assertTrue($this->collection->hasStepWithNumber(2));
        self::assertTrue($this->collection->hasStepWithNumber(3));
        self::assertFalse($this->collection->hasStepWithNumber(4));
    }

    public function testFilterStepsSmallerThanNumber(): void
    {
        self::assertSame([0 => $this->step1, 1 => $this->step2], $this->collection->filterStepsSmallerThanNumber(3)->toArray());
    }

    public function testFilterStepsGreaterThanOrEqualToNumber(): void
    {
        self::assertSame([1 => $this->step2, 2 => $this->step3], $this->collection->filterStepsGreaterThanOrEqualToNumber(2)->toArray());
    }

    public function testFilterStepsGreaterThanNumber(): void
    {
        self::assertSame([2 => $this->step3], $this->collection->filterStepsGreaterThanNumber(2)->toArray());
    }

    protected function setUp(): void
    {
        $this->step1 = $this->createStepInstance(1);
        $this->step2 = $this->createStepInstance(2);
        $this->step3 = $this->createStepInstance(3);
        $this->collection = new StepCollection([$this->step1, $this->step2, $this->step3]);
    }

    private function createStepInstance(int $number): StepInterface
    {
        $step = self::createStub(StepInterface::class);
        $step->method('getNumber')->willReturn($number);

        return $step;
    }
}
