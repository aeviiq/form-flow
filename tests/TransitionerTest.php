<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        return new class() implements EventDispatcherInterface
        {
            public $dispatchedEvents = [];

            public function addListener($eventName, $listener, $priority = 0): void
            {
            }

            public function addSubscriber(EventSubscriberInterface $subscriber): void
            {
            }

            public function removeListener($eventName, $listener): void
            {
            }

            public function removeSubscriber(EventSubscriberInterface $subscriber): void
            {
            }

            public function getListeners($eventName = null)
            {
            }

            public function dispatch($event, string $eventName = null)
            {
                $this->dispatchedEvents[] = $eventName;
            }

            public function getListenerPriority($eventName, $listener)
            {
            }

            public function hasListeners($eventName = null)
            {
            }
        };
    }
}
