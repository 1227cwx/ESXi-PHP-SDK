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
        if (trim($key) === '') {
            throw new \InvalidArgumentException('Invalid parameter: key must be a non-empty string.');
        }
        if ($start < 0) {
            throw new \InvalidArgumentException('Invalid parameter: start must be zero or a positive integer.');
        }
        if ($lines <= 0) {
            throw new \InvalidArgumentException('Invalid parameter: lines must be a positive integer.');
        }

        return $this->ok($this->client->browseDiagnosticLog->execute(
            $this->client->service('diagnosticManager'),
            $key,
            $start,
            $lines,
            $host === null ? null : $this->client->resolveHost($host)
        ));
    }
}
