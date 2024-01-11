<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

use Aeviiq\FormFlow\FormFlowInterface;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    public function __construct(private readonly FormFlowInterface $flow)
    {
    }

    public function getFlow(): FormFlowInterface
    {
        return $this->flow;
    }
}
