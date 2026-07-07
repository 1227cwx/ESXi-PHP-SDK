<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\Folder;

use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class CreateVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'CreateVM_Task';
    }

    public function execute(Mor $folder, DataObject $config, Mor $pool, ?Mor $host = null): Mor
    {
        return Mor::from($this->call([
            '_this' => $folder,
            'config' => $config,
            'pool' => $pool,
            'host' => $host,
        ])->firstReturnValue(), 'Task');
    }
}
