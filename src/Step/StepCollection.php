<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\Collection\AbstractImmutableObjectCollection;

/**
 * @method \ArrayIterator|Step[] getIterator
 * @method Step|null first
 * @method Step|null last
 */
final class StepCollection extends AbstractImmutableObjectCollection
{
    public function getStepByNumber(int $number): StepInterface
    {
        return $this->getOneBy(static function (StepInterface $step) use ($number): bool {
            return $step->getNumber() === $number;
        });
    }

    public function hasStepWithNumber(int $number): bool
    {
        return null !== $this->getOneOrNullBy(static function (StepInterface $step) use ($number): bool {
            return $step->getNumber() === $number;
        });
    }

    public function filterStepsSmallerThanNumber(int $number): StepCollection
    {
        return $this->filter(static function (StepInterface $step) use ($number): bool {
            return $step->getNumber() < $number;
        });
    }

    public function filterStepsGreaterThanOrEqualToNumber(int $number): StepCollection
    {
        return $this->filterStepsGreaterThanNumber($number - 1);
    }

    public function filterStepsGreaterThanNumber(int $number): StepCollection
    {
        return $this->filter(static function (StepInterface $step) use ($number): bool {
            return $step->getNumber() > $number;
        });
    }

    protected function allowedInstance(): string
    {
        return StepInterface::class;
    }
}
