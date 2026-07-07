<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\HostNetwork;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class AddVirtualSwitch extends AbstractOperation
{
    public function name(): string
    {
        return 'AddVirtualSwitch';
    }

    public function execute(Mor $networkSystem, string $name, ?DataObject $spec = null): void
    {
        $this->call([
            '_this' => $networkSystem,
            'vswitchName' => $name,
            'spec' => $spec,
        ]);
    }
}
