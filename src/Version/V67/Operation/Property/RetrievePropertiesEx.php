<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\Property;

use WebmanVps\Esxi\Soap\SoapResponse;
use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

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
