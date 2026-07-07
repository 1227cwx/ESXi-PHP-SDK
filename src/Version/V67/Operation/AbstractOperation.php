<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation;

use Cwx1227\Esxi\Contract\OperationInterface;
use Cwx1227\Esxi\Soap\SoapExecutor;
use Cwx1227\Esxi\Soap\SoapResponse;

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
