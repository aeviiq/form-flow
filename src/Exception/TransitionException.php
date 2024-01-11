<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Exception;

use Aeviiq\FormFlow\FormFlowInterface;

final class TransitionException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(private readonly FormFlowInterface $flow, string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFlow(): FormFlowInterface
    {
        return $this->flow;
    }
}
