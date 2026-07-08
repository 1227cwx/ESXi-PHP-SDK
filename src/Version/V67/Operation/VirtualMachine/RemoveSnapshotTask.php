<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RemoveSnapshotTask extends AbstractOperation
{
    public function name(): string
    {
        return 'RemoveSnapshot_Task';
    }

    public function execute(Mor $snapshot, bool $removeChildren = false, bool $consolidate = true): Mor
    {
        return Mor::from($this->call([
            '_this' => $snapshot,
            'removeChildren' => $removeChildren,
            'consolidate' => $consolidate,
        ])->firstReturnValue(), 'Task');
    }
}
