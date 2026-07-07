<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\FileManager;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

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
