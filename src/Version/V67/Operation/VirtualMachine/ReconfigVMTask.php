<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

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
