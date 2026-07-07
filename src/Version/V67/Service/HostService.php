<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

final class HostService extends AbstractService
{
    public function info(): array
    {
        return $this->ok($this->client->about());
    }

    public function config(mixed $host = null): array
    {
        return $this->ok($this->client->retrieveObjectProperties($this->client->resolveHost($host), 'HostSystem', [
            'name',
            'hardware.systemInfo',
            'hardware.cpuInfo',
            'hardware.memorySize',
            'config.product',
            'config.network',
            'config.storageDevice',
            'config.fileSystemVolume',
            'summary.hardware',
            'summary.config',
            'runtime.connectionState',
            'runtime.powerState',
            'runtime.inMaintenanceMode',
        ]));
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

    public function performance(mixed $host = null): array
    {
        $row = $this->client->retrieveObjectProperties($this->client->resolveHost($host), 'HostSystem', [
            'name',
            'summary.hardware.cpuMhz',
            'summary.hardware.numCpuCores',
            'summary.hardware.memorySize',
            'summary.quickStats.overallCpuUsage',
            'summary.quickStats.overallMemoryUsage',
            'summary.quickStats.uptime',
        ]);

        $cpuMhz = (int) ($row['summary.hardware.cpuMhz'] ?? 0);
        $cpuCores = (int) ($row['summary.hardware.numCpuCores'] ?? 0);
        $cpuTotalMhz = $cpuMhz * $cpuCores;
        $cpuUsedMhz = (int) ($row['summary.quickStats.overallCpuUsage'] ?? 0);
        $memoryTotalBytes = (int) ($row['summary.hardware.memorySize'] ?? 0);
        $memoryUsedMb = (int) ($row['summary.quickStats.overallMemoryUsage'] ?? 0);
        $memoryUsedBytes = $memoryUsedMb * 1024 * 1024;

        return $this->ok([
            'name' => $row['name'] ?? null,
            'cpu' => [
                'total_mhz' => $cpuTotalMhz,
                'used_mhz' => $cpuUsedMhz,
                'used_percent' => $cpuTotalMhz > 0 ? round($cpuUsedMhz / $cpuTotalMhz * 100, 2) : null,
            ],
            'memory' => [
                'total_bytes' => $memoryTotalBytes,
                'used_bytes' => $memoryUsedBytes,
                'used_mb' => $memoryUsedMb,
                'used_percent' => $memoryTotalBytes > 0 ? round($memoryUsedBytes / $memoryTotalBytes * 100, 2) : null,
            ],
            'storage' => $this->client->storage()->usage()['data'] ?? [],
            'raw' => $row,
        ]);
    }

    public function monitor(mixed $host = null): array
    {
        return $this->performance($host);
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

    public function network(mixed $host = null): array
    {
        return $this->ok($this->networkConfig($host));
    }

    public function virtualMachines(array $properties = []): array
    {
        return $this->client->vps()->list($properties);
    }

    public function storage(array $properties = []): array
    {
        return $this->client->storage()->list($properties);
    }

    public function files(string $datastorePath, array $params = [], bool $wait = true): array
    {
        return $this->client->storage()->files($datastorePath, $params, $wait);
    }

    public function tasks(int $limit = 50): array
    {
        return $this->client->task()->recent($limit);
    }

    public function logDescriptions(mixed $host = null): array
    {
        return $this->client->logs()->descriptions($host);
    }

    public function logs(string $key = 'hostd', int $start = 0, int $lines = 200, mixed $host = null): array
    {
        return $this->client->logs()->browse($key, $start, $lines, $host);
    }
}
