<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\VirtualMachine;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class AcquireTicket extends AbstractOperation
{
    public function name(): string
    {
        return 'AcquireTicket';
    }

    public function execute(Mor $vm, string $ticketType): array
    {
        $value = $this->call([
            '_this' => $vm,
            'ticketType' => $ticketType,
        ])->firstReturnValue();

        return is_array($value) ? $value : [];
    }
}
