<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class RebootGuest extends AbstractOperation
{
    public function name(): string
    {
        return 'RebootGuest';
    }

    public function execute(Mor $vm): void
    {
        $this->call([
            '_this' => $vm,
        ]);
    }
}
