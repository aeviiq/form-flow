<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests\Enum\Transition;

use Aeviiq\FormFlow\Enum\Transition\Status;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
    /**
     * @dataProvider invalidStatusCombinationProvider
     */
    public function testCreateWithInvalidCombination(int $status, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        new Status($status);
    }

    /**
     * @return array<mixed>
     */
    public function invalidStatusCombinationProvider(): array
    {
        return [
            'success and failure' => [
                Status::SUCCESS | Status::FAILURE,
                'A transition status can not be successful and failure at the same time.',
            ],
            'success and blocked' => [
                Status::SUCCESS | Status::BLOCKED,
                'A transition status can not be successful and blocked at the same time.',
            ],
            'success and invalid_form' => [
                Status::SUCCESS | Status::INVALID_FORM,
                'A transition status can not be successful and invalid form at the same time.',
            ],
            'completed and failure' => [
                Status::COMPLETED | Status::FAILURE,
                'A transition status can not be completed and failure at the same time.',
            ],
            'completed and invalid_form' => [
                Status::COMPLETED | Status::INVALID_FORM,
                'A transition status can not be completed and invalid form at the same time.',
            ],
            'valid_form and invalid_form' => [
                Status::VALID_FORM | Status::INVALID_FORM,
                'A transition status can not be valid form and invalid form at the same time.',
            ],
        ];
    }

    public function testIsSuccessful(): void
    {
        $status = new Status(Status::SUCCESS);
        self::assertTrue($status->isSuccessful());
    }

    public function testIsFailed(): void
    {
        $status = new Status(Status::FAILURE);
        self::assertTrue($status->isFailed());
    }

    public function testIsBlocked(): void
    {
        $status = new Status(Status::BLOCKED);
        self::assertTrue($status->isBlocked());
    }

    public function testIsCompleted(): void
    {
        $status = new Status(Status::COMPLETED);
        self::assertTrue($status->isCompleted());
    }

    public function testIsReset(): void
    {
        $status = new Status(Status::RESET);
        self::assertTrue($status->isReset());
    }

    public function testIsFormValid(): void
    {
        $status = new Status(Status::VALID_FORM);
        self::assertTrue($status->isFormValid());
    }
}
