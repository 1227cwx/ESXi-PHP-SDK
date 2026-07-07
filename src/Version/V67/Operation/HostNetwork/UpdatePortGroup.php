<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\HostNetwork;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class UpdatePortGroup extends AbstractOperation
{
    public function name(): string
    {
        return 'UpdatePortGroup';
    }

    public function execute(Mor $networkSystem, string $name, DataObject $spec): void
    {
        $this->call([
            '_this' => $networkSystem,
            'pgName' => $name,
            'portgrp' => $spec,
        ]);
    }
}
