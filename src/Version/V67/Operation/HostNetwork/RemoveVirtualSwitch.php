<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\HostNetwork;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RemoveVirtualSwitch extends AbstractOperation
{
    public function name(): string
    {
        return 'RemoveVirtualSwitch';
    }

    public function execute(Mor $networkSystem, string $name): void
    {
        $this->call([
            '_this' => $networkSystem,
            'vswitchName' => $name,
        ]);
    }
}
