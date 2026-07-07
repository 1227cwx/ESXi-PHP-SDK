<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\ManagedEntity;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

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
