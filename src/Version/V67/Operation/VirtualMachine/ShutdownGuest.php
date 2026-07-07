<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\VirtualMachine;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class ShutdownGuest extends AbstractOperation
{
    public function name(): string
    {
        return 'ShutdownGuest';
    }

    public function execute(Mor $vm): void
    {
        $this->call([
            '_this' => $vm,
        ]);
    }
}
