<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Property;

use Cwx1227\Esxi\Soap\SoapResponse;
use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RetrievePropertiesEx extends AbstractOperation
{
    public function name(): string
    {
        return 'RetrievePropertiesEx';
    }

    public function execute(Mor $propertyCollector, array $filterSpecs, int $maxObjects = 500): SoapResponse
    {
        return $this->call([
            '_this' => $propertyCollector,
            'specSet' => $filterSpecs,
            'options' => DataObject::typed('RetrieveOptions', [
                'maxObjects' => $maxObjects,
            ]),
        ]);
    }
}
