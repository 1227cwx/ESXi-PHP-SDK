<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class CreateSnapshotTask extends AbstractOperation
{
    public function name(): string
    {
        return 'CreateSnapshot_Task';
    }

    public function execute(Mor $vm, string $name, string $description = '', bool $memory = false, bool $quiesce = false): Mor
    {
        return Mor::from($this->call([
            '_this' => $vm,
            'name' => $name,
            'description' => $description,
            'memory' => $memory,
            'quiesce' => $quiesce,
        ])->firstReturnValue(), 'Task');
    }
}
