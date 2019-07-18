<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Event;

use Aeviiq\FormFlow\FormFlowInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class FlowEvent extends Event
{
    /**
     * @var FormFlowInterface
     */
    private $formFlow;

    public function __construct(FormFlowInterface $formFlow)
    {
        $this->formFlow = $formFlow;
    }

    public function getFormFlow(): FormFlowInterface
    {
        return $this->formFlow;
    }
}
