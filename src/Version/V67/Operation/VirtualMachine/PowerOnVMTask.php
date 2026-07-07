<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\VirtualMachine;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class PowerOnVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'PowerOnVM_Task';
    }

    public function execute(Mor $vm, ?Mor $host = null): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
            'host' => $host,
        ])->firstReturnValue(), 'Task');
    }
}
