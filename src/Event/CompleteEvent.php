<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

use Aeviiq\FormFlow\FormFlowInterface;

final class CompleteEvent extends Event
{
    /**
     * @var object
     */
    private $data;

    public function __construct(FormFlowInterface $formFlow, object $data)
    {
        parent::__construct($formFlow);
        $this->data = $data;
    }

    public function getData(): object
    {
        return $this->data;
    }
}
