<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Property;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class CreateContainerView extends AbstractOperation
{
    public function name(): string
    {
        return 'CreateContainerView';
    }

    public function execute(Mor $viewManager, Mor $container, array $types, bool $recursive = true): Mor
    {
        return Mor::from($this->call([
            '_this' => $viewManager,
            'container' => $container,
            'type' => array_values($types),
            'recursive' => $recursive,
        ])->firstReturnValue(), 'ContainerView');
    }
}
