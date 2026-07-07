<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\HostNetwork;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

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
