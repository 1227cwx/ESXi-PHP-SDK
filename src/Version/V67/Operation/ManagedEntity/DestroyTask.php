<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\ManagedEntity;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class DestroyTask extends AbstractOperation
{
    public function name(): string
    {
        return 'Destroy_Task';
    }

    public function execute(Mor $entity): Mor
    {
        return Mor::from($this->call([
            '_this' => $entity,
        ])->firstReturnValue(), 'Task');
    }
}
