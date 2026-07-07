<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class ResetVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'ResetVM_Task';
    }

    public function execute(Mor $vm): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
        ])->firstReturnValue(), 'Task');
    }
}
