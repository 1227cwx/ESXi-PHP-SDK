<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\HostDatastoreBrowser;

use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class SearchDatastoreSubFoldersTask extends AbstractOperation
{
    public function name(): string
    {
        return 'SearchDatastoreSubFolders_Task';
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
