<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Diagnostic;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class BrowseDiagnosticLog extends AbstractOperation
{
    public function name(): string
    {
        return 'BrowseDiagnosticLog';
    }

    public function execute(Mor $diagnosticManager, string $key, int $start = 0, int $lines = 200, ?Mor $host = null): array
    {
        $value = $this->call([
            '_this' => $diagnosticManager,
            'host' => $host,
            'key' => $key,
            'start' => $start,
            'lines' => $lines,
        ])->firstReturnValue();

        return is_array($value) ? $value : [];
    }
}
