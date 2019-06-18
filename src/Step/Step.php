<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow\Step;

use Aeviiq\FormFlow\Completable;
use Aeviiq\FormFlow\Skippable;

interface Step extends Completable, Skippable, Equatable, \Serializable
{
    public function getNumber(): int;

    public function getFormType(): string;

    public function getLabel(): string;

    public function getNextLabel(): string;

    public function getPreviousLabel(): string;
}
