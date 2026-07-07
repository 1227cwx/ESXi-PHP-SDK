<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation;

use WebmanVps\Esxi\Contract\OperationInterface;
use WebmanVps\Esxi\Soap\SoapExecutor;
use WebmanVps\Esxi\Soap\SoapResponse;

abstract class AbstractOperation implements OperationInterface
{
    public function __construct(
        protected readonly SoapExecutor $soap
    ) {
    }

    protected function call(array $params = []): SoapResponse
    {
        return $this->soap->call($this->name(), $params);
    }
}
