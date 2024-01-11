<?php

declare(strict_types=1);

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

    public const UNHANDLED_FORM = 128;

    public static function isValid(int $value): bool
    {
        if (!parent::isValid($value)) {
            return false;
        }

        if (self::isFlagSet($value, self::SUCCESS) && self::isFlagSet($value, self::FAILURE)) {
            throw new InvalidArgumentException('A transition status can not be successful and failure at the same time.');
        }

        if (self::isFlagSet($value, self::SUCCESS) && self::isFlagSet($value, self::BLOCKED)) {
            throw new InvalidArgumentException('A transition status can not be successful and blocked at the same time.');
        }

        if (self::isFlagSet($value, self::SUCCESS) && self::isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be successful and invalid form at the same time.');
        }

        if (self::isFlagSet($value, self::COMPLETED) && self::isFlagSet($value, self::FAILURE)) {
            throw new InvalidArgumentException('A transition status can not be completed and failure at the same time.');
        }

        if (self::isFlagSet($value, self::COMPLETED) && self::isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be completed and invalid form at the same time.');
        }

        if (self::isFlagSet($value, self::VALID_FORM) && self::isFlagSet($value, self::INVALID_FORM)) {
            throw new InvalidArgumentException('A transition status can not be valid form and invalid form at the same time.');
        }

        if (self::isFlagSet($value, self::VALID_FORM) && self::isFlagSet($value, self::UNHANDLED_FORM)) {
            throw new InvalidArgumentException('A transition status can not be valid form and unhandled form at the same time.');
        }

        if (self::isFlagSet($value, self::INVALID_FORM) && self::isFlagSet($value, self::UNHANDLED_FORM)) {
            throw new InvalidArgumentException('A transition status can not be invalid form and unhandled form at the same time.');
        }

        return true;
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

    public function isUnhandledForm(): bool
    {
        return $this->contains(new self(self::UNHANDLED_FORM));
    }

    private static function isFlagSet(int $flags, int $flag): bool
    {
        return ($flag & $flags) === $flag;
    }
}
