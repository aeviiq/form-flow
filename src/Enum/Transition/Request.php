<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Enum\Transition;

use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use MyCLabs\Enum\Enum;

final class Request extends Enum
{
    public const FORWARDS = 'forwards';

    public const BACKWARDS = 'backwards';

    public const COMPLETE = 'complete';

    public const RESET = 'reset';

    /**
     * @var int
     */
    private $requestedStepNumber;

    public function __construct(string $value, int $requestedStepNumber = 0)
    {
        parent::__construct($value);
        if ($requestedStepNumber < 0) {
            throw new InvalidArgumentException(\sprintf('A requested step number must be above 0. "%s" given.', $requestedStepNumber));
        }

        if (self::FORWARDS === $value && $requestedStepNumber < 2) {
            throw new InvalidArgumentException(\sprintf('A requested step number must be above 1 when going forwards. "%s" given.', $requestedStepNumber));
        }

        if (self::BACKWARDS === $value && $requestedStepNumber < 1) {
            throw new InvalidArgumentException(\sprintf('A requested step number must be above 0 when going backwards. "%s" given.', $requestedStepNumber));
        }

        $this->requestedStepNumber = $requestedStepNumber;
    }

    public function getRequestedStepNumber(): int
    {
        return $this->requestedStepNumber;
    }
}
