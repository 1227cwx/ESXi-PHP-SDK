<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualDiskManager;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class CopyVirtualDiskTask extends AbstractOperation
{
    public function name(): string
    {
        return 'CopyVirtualDisk_Task';
    }

    public function execute(
        Mor $virtualDiskManager,
        string $sourceName,
        string $destinationName,
        ?Mor $sourceDatacenter = null,
        ?Mor $destinationDatacenter = null,
        ?DataObject $destinationSpec = null,
        bool $force = false
    ): Mor {
        return Mor::from($this->call([
            '_this' => $virtualDiskManager,
            'sourceName' => $sourceName,
            'sourceDatacenter' => $sourceDatacenter,
            'destName' => $destinationName,
            'destDatacenter' => $destinationDatacenter,
            'destSpec' => $destinationSpec,
            'force' => $force,
        ])->firstReturnValue(), 'Task');
    }
}
