<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Performance;

use Cwx1227\Esxi\Soap\XmlDecoder;
use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class QueryPerf extends AbstractOperation
{
    public function name(): string
    {
        return 'QueryPerf';
    }

    public function execute(Mor $performanceManager, array $querySpecs): array
    {
        $response = $this->call([
            '_this' => $performanceManager,
            'querySpec' => array_values($querySpecs),
        ]);

        $items = [];
        foreach ($response->returnElements() as $element) {
            $items[] = XmlDecoder::decode($element);
        }

        return $items;
    }
}
