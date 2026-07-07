<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Property;

use Cwx1227\Esxi\Soap\SoapResponse;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class ContinueRetrievePropertiesEx extends AbstractOperation
{
    public function name(): string
    {
        return 'ContinueRetrievePropertiesEx';
    }

    public function execute(Mor $propertyCollector, string $token): SoapResponse
    {
        return $this->call([
            '_this' => $propertyCollector,
            'token' => $token,
        ]);
    }
}
