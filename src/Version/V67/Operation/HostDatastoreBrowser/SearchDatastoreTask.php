<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\HostDatastoreBrowser;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class SearchDatastoreTask extends AbstractOperation
{
    public function name(): string
    {
        return 'SearchDatastore_Task';
    }

    public function execute(Mor $browser, string $datastorePath, ?DataObject $searchSpec = null): Mor
    {
        return Mor::from($this->call([
            '_this' => $browser,
            'datastorePath' => $datastorePath,
            'searchSpec' => $searchSpec,
        ])->firstReturnValue(), 'Task');
    }
}
