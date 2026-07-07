<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

final class LogService extends AbstractService
{
    public function descriptions(mixed $host = null): array
    {
        return $this->ok($this->client->queryDescriptions->execute(
            $this->client->service('diagnosticManager'),
            $host === null ? null : $this->client->resolveHost($host)
        ));
    }

    public function browse(string $key = 'hostd', int $start = 0, int $lines = 200, mixed $host = null): array
    {
        return $this->ok($this->client->browseDiagnosticLog->execute(
            $this->client->service('diagnosticManager'),
            $key,
            $start,
            $lines,
            $host === null ? null : $this->client->resolveHost($host)
        ));
    }
}
