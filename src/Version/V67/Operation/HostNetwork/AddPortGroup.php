<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\HostNetwork;

use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class AddPortGroup extends AbstractOperation
{
    public function name(): string
    {
        return 'AddPortGroup';
    }

    public function execute(Mor $networkSystem, DataObject $spec): void
    {
        $this->call([
            '_this' => $networkSystem,
            'portgrp' => $spec,
        ]);
    }
}
