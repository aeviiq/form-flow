<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\Collection\AbstractObjectCollection;
use Aeviiq\FormFlow\Completable;
use Aeviiq\FormFlow\Skippable;

/**
 * @method \ArrayIterator|Step[] getIterator
 * @method Step|null first
 * @method Step|null last
 */
final class StepCollection extends AbstractObjectCollection
{
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

    public function filterCompletedSteps(): StepCollection
    {
        return $this->filter(static function (Completable $step) {
            return $step->isCompleted();
        });
    }

    public function filterIncompleteSteps(): StepCollection
    {
        return $this->filter(static function (Completable $step) {
            return !$step->isCompleted();
        });
    }

    public function filterSkippedSteps(): StepCollection
    {
        return $this->filter(static function (Skippable $step) {
            return $step->isSkipped();
        });
    }

    public function filterUnskippedSteps(): StepCollection
    {
        return $this->filter(static function (Skippable $step) {
            return !$step->isSkipped();
        });
    }

    // TODO revise if the ones below are still needed.

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
