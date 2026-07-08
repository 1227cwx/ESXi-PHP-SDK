<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RemoveAllSnapshotsTask extends AbstractOperation
{
    public function name(): string
    {
        return 'RemoveAllSnapshots_Task';
    }

    public function execute(Mor $vm, bool $consolidate = true): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
            'consolidate' => $consolidate,
        ])->firstReturnValue(), 'Task');
    }
}
