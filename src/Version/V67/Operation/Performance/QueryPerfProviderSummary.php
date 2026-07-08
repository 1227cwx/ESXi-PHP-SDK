<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Performance;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class QueryPerfProviderSummary extends AbstractOperation
{
    public function name(): string
    {
        return 'QueryPerfProviderSummary';
    }

    public function execute(Mor $performanceManager, Mor $entity): array
    {
        $value = $this->call([
            '_this' => $performanceManager,
            'entity' => $entity,
        ])->firstReturnValue();

        return is_array($value) ? $value : [];
    }
}
