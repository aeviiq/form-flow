<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\Collection\ImmutableObjectCollection;

/**
 * @phpstan-extends ImmutableObjectCollection<int|string, StepInterface>
 *
 * @method StepInterface|null first()
 * @method StepInterface|null last()
 * @method self filter(\Closure $closure)
 */
final class StepCollection extends ImmutableObjectCollection
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

    protected function onConstruct(): void
    {
        $this->uasort(static function (StepInterface $a, StepInterface $b) {
            return $a->getNumber() <=> $b->getNumber();
        });
    }
}
