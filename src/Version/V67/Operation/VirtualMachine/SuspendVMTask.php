<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\VirtualMachine;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class SuspendVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'SuspendVM_Task';
    }

    public function execute(Mor $vm): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
        ])->firstReturnValue(), 'Task');
    }
}
