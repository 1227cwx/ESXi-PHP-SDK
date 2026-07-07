<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\FileManager;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class CopyDatastoreFileTask extends AbstractOperation
{
    public function name(): string
    {
        return 'CopyDatastoreFile_Task';
    }

    public function execute(
        Mor $fileManager,
        string $sourceName,
        string $destinationName,
        ?Mor $sourceDatacenter = null,
        ?Mor $destinationDatacenter = null,
        bool $force = false
    ): Mor {
        return Mor::from($this->call([
            '_this' => $fileManager,
            'sourceName' => $sourceName,
            'sourceDatacenter' => $sourceDatacenter,
            'destinationName' => $destinationName,
            'destinationDatacenter' => $destinationDatacenter,
            'force' => $force,
        ])->firstReturnValue(), 'Task');
    }
}
