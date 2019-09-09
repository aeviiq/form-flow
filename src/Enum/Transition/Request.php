<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Enum\Transition;

use Aeviiq\Enum\AbstractEnum;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\FormFlowInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class Request extends AbstractEnum
{
    public const FORWARDS = 'forwards';

    public const BACKWARDS = 'backwards';

    public const COMPLETE = 'complete';

    public const RESET = 'reset';

    /**
     * @var int
     */
    private $requestedStepNumber;

    private function __construct(string $value, int $requestedStepNumber)
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

    public static function createByHttpRequestAndFlow(HttpRequest $httpRequest, FormFlowInterface $flow): self
    {
        $transition = (string)$httpRequest->get($flow->getTransitionKey(), '');
        if (self::isValid($transition)) {
            if (self::FORWARDS === $transition) {
                $stepNumber = $flow->getCurrentStepNumber() + 1;
            } elseif (self::BACKWARDS === $transition) {
                $stepNumber = $flow->getCurrentStepNumber() - 1;
            }

            return new self($transition, $stepNumber ?? 0);
        }

        $transitions = \explode('_', $transition);
        if (isset($transitions[0], $transitions[1])) {
            [$action, $stepNumber] = $transitions;
            if (self::isValid($action) && \is_numeric($stepNumber) && $stepNumber > 0 && $stepNumber <= $flow->getSteps()->count()) {
                return new self($action, (int)$stepNumber);
            }
        }

        throw new InvalidArgumentException(\sprintf('"%s" is an invalid transition request for flow "%s".', $transition, $flow->getName()));
    }

    public function getRequestedStepNumber(): int
    {
        return $this->requestedStepNumber;
    }
}
