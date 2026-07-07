<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\HostNetwork;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

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
