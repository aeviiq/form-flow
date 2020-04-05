<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Exception;

use Aeviiq\FormFlow\FormFlowInterface;

final class TransitionException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var FormFlowInterface
     */
    private $flow;

    public function __construct(FormFlowInterface $flow, string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->flow = $flow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFlow(): FormFlowInterface
    {
        return $this->flow;
    }
}
