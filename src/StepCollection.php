<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\Collection\ObjectCollection;

/**
 * @method \ArrayIterator|Step[] getIterator
 * @method Step|null first
 * @method Step|null last
 */
final class StepCollection extends ObjectCollection
{
    protected function allowedInstance(): string
    {
        return Step::class;
    }
}
