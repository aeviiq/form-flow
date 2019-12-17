<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Enum\Transition;

use Aeviiq\Enum\AbstractFlag;
use Aeviiq\Enum\Exception\InvalidArgumentException;

final class Status extends AbstractFlag
{
    public const SUCCESS = 1;

    public const FAILURE = 2;

    public const BLOCKED = 4;

    public const COMPLETED = 8;

    public const RESET = 16;

    public const VALID_FORM = 32;

    public const INVALID_FORM = 64;

    /**
     * @throws InvalidArgumentException When the given value contains an invalid flag combination.
     */
    public function __construct(int $value)
    {
        if ($this->isFlagSet($value, self::SUCCESS) && $this->isFlagSet($value, self::FAILURE)) {
            throw new InvalidArgumentException('A transition status can not be successful and failure at the same time.');
        }

        if ($this->isFlagSet($value, self::SUCCESS) && $this->isFlagSet($value, self::BLOCKED)) {
            throw new InvalidArgumentException('A transition status can not be successful and blocked at the same time.');
        }

        if ($this->isFlagSet($value, self::SUCCESS) && $this->isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be successful and invalid form at the same time.');
        }

        if ($this->isFlagSet($value, self::COMPLETED) && $this->isFlagSet($value, self::FAILURE)) {
            throw new InvalidArgumentException('A transition status can not be completed and failure at the same time.');
        }

        if ($this->isFlagSet($value, self::COMPLETED) && $this->isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be completed and invalid form at the same time.');
        }

        if ($this->isFlagSet($value, self::VALID_FORM) && $this->isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be valid form and invalid form at the same time.');
        }


        parent::__construct($value);
    }

    public function isSuccessful(): bool
    {
        return $this->contains(new self(self::SUCCESS));
    }

    public function isFailed(): bool
    {
        return $this->contains(new self(self::FAILURE));
    }

    public function isBlocked(): bool
    {
        return $this->contains(new self(self::BLOCKED));
    }

    public function isCompleted(): bool
    {
        return $this->contains(new self(self::COMPLETED));
    }

    public function isReset(): bool
    {
        return $this->contains(new self(self::RESET));
    }

    public function isFormValid(): bool
    {
        return $this->contains(new self(self::VALID_FORM));
    }
}
