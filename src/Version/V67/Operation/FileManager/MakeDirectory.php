<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\FileManager;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class MakeDirectory extends AbstractOperation
{
    public function name(): string
    {
        return 'MakeDirectory';
    }

    public function execute(Mor $fileManager, string $name, ?Mor $datacenter = null, bool $createParentDirectories = true): void
    {
        $this->call([
            '_this' => $fileManager,
            'name' => $name,
            'datacenter' => $datacenter,
            'createParentDirectories' => $createParentDirectories,
        ]);
    }
}
