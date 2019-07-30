<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\Factory\AbstractServiceFactory;

final class Registry extends AbstractServiceFactory implements RegistryInterface
{
    // TODO think if this service factory is indeed the best choice for this purpose.
    protected function getTargetInterface(): string
    {
        return FormFlowInterface::class;
    }
}
