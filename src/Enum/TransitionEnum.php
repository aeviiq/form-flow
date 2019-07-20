<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Enum;

use MyCLabs\Enum\Enum;

final class TransitionEnum extends Enum
{
    public const RESET = 'reset';
    public const FORWARDS = 'forwards';
    public const BACKWARDS = 'backwards';
}
