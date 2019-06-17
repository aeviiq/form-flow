<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\Collection\ObjectCollection;
use Aeviiq\FormFlow\Exception\LogicException;

/**
 * @method \ArrayIterator|Step[] getIterator
 * @method Step first
 * @method Step last
 */
final class StepCollection extends ObjectCollection
{
    public function __construct(array $elements)
    {
        parent::__construct($elements);
        if ($this->count() < 2) {
            throw new LogicException(\sprintf('A step collection must contain at least 2 steps.'));
        }

        $numbers = [];
        foreach ($this as $step) {
            $numbers[] = $step->getNumber();
        }

        if (\count(\array_unique($numbers)) !== $this->count()) {
            throw new LogicException(\sprintf('Each step must have a unique number.'));
        }
    }

    public function getStepByNumber(int $number): Step
    {
        return $this->getOneBy(static function (Step $step) use ($number): bool {
            return $step->getNumber() === $number;
        });
    }

    public function hasStepWithNumber(int $number): bool
    {
        return null !== $this->getOneOrNullBy(static function (Step $step) use ($number): bool {
            return $step->getNumber() === $number;
        });
    }

    public function filterStepsSmallerThanNumber(int $number): StepCollection
    {
        return $this->filter(static function (Step $step) use ($number): bool {
            return $step->getNumber() < $number;
        });
    }

    public function filterStepsGreaterThanOrEqualToNumber(int $number): StepCollection
    {
        return $this->filterStepsGreaterThanNumber($number - 1);
    }

    public function filterStepsGreaterThanNumber(int $number): StepCollection
    {
        return $this->filter(static function (Step $step) use ($number): bool {
            return $step->getNumber() > $number;
        });
    }

    protected function allowedInstance(): string
    {
        return Step::class;
    }
}
