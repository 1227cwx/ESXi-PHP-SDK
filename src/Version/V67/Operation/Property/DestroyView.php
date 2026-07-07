<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Property;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class DestroyView extends AbstractOperation
{
    public function name(): string
    {
        return 'DestroyView';
    }

    public function execute(Mor $view): void
    {
        $this->call([
            '_this' => $view,
        ]);
    }
}
