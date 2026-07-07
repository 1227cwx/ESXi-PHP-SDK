<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Folder;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RegisterVMTask extends AbstractOperation
{
    public function name(): string
    {
        return 'RegisterVM_Task';
    }

    public function execute(Mor $folder, string $path, ?string $name, bool $asTemplate, ?Mor $pool = null, ?Mor $host = null): Mor
    {
        return Mor::from($this->call([
            '_this' => $folder,
            'path' => $path,
            'name' => $name,
            'asTemplate' => $asTemplate,
            'pool' => $pool,
            'host' => $host,
        ])->firstReturnValue(), 'Task');
    }
}
