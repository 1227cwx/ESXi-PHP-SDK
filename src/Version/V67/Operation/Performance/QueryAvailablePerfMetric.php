<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Performance;

use Cwx1227\Esxi\Soap\XmlDecoder;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class QueryAvailablePerfMetric extends AbstractOperation
{
    public function name(): string
    {
        return 'QueryAvailablePerfMetric';
    }

    public function execute(Mor $performanceManager, Mor $entity, ?string $beginTime = null, ?string $endTime = null, ?int $intervalId = null): array
    {
        $response = $this->call([
            '_this' => $performanceManager,
            'entity' => $entity,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'intervalId' => $intervalId,
        ]);

        $items = [];
        foreach ($response->returnElements() as $element) {
            $items[] = XmlDecoder::decode($element);
        }

        return $items;
    }
}
