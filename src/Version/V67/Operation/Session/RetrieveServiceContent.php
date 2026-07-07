<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Session;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RetrieveServiceContent extends AbstractOperation
{
    public function name(): string
    {
        return 'RetrieveServiceContent';
    }

    public function execute(): array
    {
        $value = $this->call([
            '_this' => new Mor('ServiceInstance', 'ServiceInstance'),
        ])->firstReturnValue();

        return is_array($value) ? $value : [];
    }
}
