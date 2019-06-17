<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

interface Step extends Completable, Skippable, \Serializable
{
    public function getNumber(): int;

    public function getFormType(): string;

    public function getLabel(): string;

    public function getNextLabel(): string;

    public function getPreviousLabel(): string;
}
