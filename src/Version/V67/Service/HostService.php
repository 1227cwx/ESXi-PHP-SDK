<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

final class HostService extends AbstractService
{
    public function info(): array
    {
        return $this->ok($this->client->about());
    }

    public function list(array $properties = []): array
    {
        $properties = $properties ?: [
            'name',
            'summary.hardware',
            'summary.quickStats',
            'summary.runtime.connectionState',
            'summary.runtime.powerState',
            'summary.runtime.inMaintenanceMode',
        ];

        return $this->client->retrieveByContainerView('HostSystem', $properties);
    }

    public function metrics(mixed $host = null): array
    {
        return $this->ok($this->client->retrieveObjectProperties($this->client->resolveHost($host), 'HostSystem', [
            'name',
            'summary.hardware',
            'summary.quickStats',
            'summary.runtime',
        ]));
    }

    public function networkConfig(mixed $host = null): array
    {
        return $this->client->retrieveObjectProperties($this->client->resolveHost($host), 'HostSystem', [
            'name',
            'config.network.vswitch',
            'config.network.portgroup',
            'config.network.pnic',
            'config.network.vnic',
        ]);
    }
}
