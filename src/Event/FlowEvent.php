<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Event;

use Aeviiq\FormFlow\Flow;
use Symfony\Component\EventDispatcher\Event;

final class FlowEvent extends Event
{
    /**
     * @var Flow
     */
    private $flow;

    public function __construct(Flow $flow)
    {
        $this->flow = $flow;
    }

    public function getFlow(): Flow
    {
        return $this->flow;
    }
}
