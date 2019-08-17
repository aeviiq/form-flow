<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Enum\Transition;

use MyCLabs\Enum\Enum;

final class Request extends Enum
{
    public const FORWARDS = 'forwards';

    public const BACKWARDS = 'backwards';

    public const COMPLETE = 'complete';

    public const RESET = 'reset';

    private $requestedStepNumber;

    public function __construct(string $value, int $requestedStepNumber = 1)
    {
        parent::__construct($value);
        $this->requestedStepNumber = $requestedStepNumber;
    }

    public function getRequestedStepNumber(): int
    {
        return $this->requestedStepNumber;
    }
}