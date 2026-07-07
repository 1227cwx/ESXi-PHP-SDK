<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\VirtualMachine;

use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class ReconfigVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'ReconfigVM_Task';
    }

    public function execute(Mor $vm, DataObject $configSpec): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
            'spec' => $configSpec,
        ])->firstReturnValue(), 'Task');
    }
}
