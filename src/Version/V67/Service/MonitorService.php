<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

final class MonitorService extends AbstractService
{
    public function vm(mixed $vm): array
    {
        return $this->ok($this->client->vps()->rawInfo($vm, [
            'name',
            'runtime.powerState',
            'summary.quickStats.overallCpuUsage',
            'summary.quickStats.overallCpuDemand',
            'summary.quickStats.hostMemoryUsage',
            'summary.quickStats.guestMemoryUsage',
            'summary.quickStats.uptimeSeconds',
            'summary.storage.committed',
            'summary.storage.uncommitted',
            'summary.guest.ipAddress',
            'summary.guest.toolsStatus',
        ]));
    }

    public function host(mixed $host = null): array
    {
        return $this->client->host()->metrics($host);
    }
}
