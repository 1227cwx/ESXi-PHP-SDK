<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Version\V67\V67Client;

abstract class AbstractService
{
    public function __construct(
        protected readonly V67Client $client
    ) {
    }

    protected function ok(array $data = []): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }
}
