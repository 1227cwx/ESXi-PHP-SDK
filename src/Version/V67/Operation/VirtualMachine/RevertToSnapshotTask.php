<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RevertToSnapshotTask extends AbstractOperation
{
    public function name(): string
    {
        return 'RevertToSnapshot_Task';
    }

    public function execute(Mor $snapshot, bool $suppressPowerOn = false): Mor
    {
        return Mor::from($this->call([
            '_this' => $snapshot,
            'host' => null,
            'suppressPowerOn' => $suppressPowerOn,
        ])->firstReturnValue(), 'Task');
    }
}
