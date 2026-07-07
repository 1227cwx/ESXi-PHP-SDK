<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\Diagnostic;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class QueryDescriptions extends AbstractOperation
{
    public function name(): string
    {
        return 'QueryDescriptions';
    }

    public function execute(Mor $diagnosticManager, ?Mor $host = null): array
    {
        $value = $this->call([
            '_this' => $diagnosticManager,
            'host' => $host,
        ])->firstReturnValue();

        return is_array($value) ? $value : [];
    }
}
