<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TransitionerTest extends TestCase
{
    public function testTransition(): void
    {

    }

    public function testTransitionForwards(): void
    {

    }

    public function testTransitionForwardsFiresEvents(): void
    {
    }

    public function testTransitionForwardsCanBeBlocked(): void
    {

    }

    public function testTransitionBackwards(): void
    {

    }

    public function testTransitionBackwardsFiresEvents(): void
    {
    }

    public function testTransitionBackwardsCanBeBlocked(): void
    {

    }

    public function testTransitionComplete(): void
    {

    }

    public function testTransitionReset(): void
    {

    }

    public function testWithoutRequestStack(): void
    {
    }

    private function createMockedEventDispatcher(): EventDispatcherInterface
    {
        return new class() implements EventDispatcherInterface {
            public $dispatchedEvents = [];

            public function dispatch(object $event, string $eventName = null): object
            {
                $this->dispatchedEvents[] = $eventName;

                return $event;
            }
        };
    }
}
