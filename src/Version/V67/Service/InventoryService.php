<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

final class InventoryService extends AbstractService
{
    public function virtualMachines(array $properties = []): array
    {
        return $this->client->vps()->list($properties);
    }

    public function hosts(array $properties = []): array
    {
        return $this->client->host()->list($properties);
    }
}
