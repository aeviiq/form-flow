<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Symfony\Component\HttpFoundation\RequestStack;

interface RequestStackAwareInterface
{
    public function setRequestStack(RequestStack $requestStack): void;
}
