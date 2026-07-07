<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\Property;

use WebmanVps\Esxi\Soap\SoapResponse;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

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
