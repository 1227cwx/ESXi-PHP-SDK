<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\HostNetwork;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class RemovePortGroup extends AbstractOperation
{
    public function name(): string
    {
        return 'RemovePortGroup';
    }

    public function execute(Mor $networkSystem, string $name): void
    {
        $this->call([
            '_this' => $networkSystem,
            'pgName' => $name,
        ]);
    }
}
