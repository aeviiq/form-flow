<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface Step
{
    public function getNumber(): int;

    public function getLabel(): string;

    public function getNextLabel(): string;

    public function getPreviousLabel(): string;

    public function getFormType(): string;
}
