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

    protected function assertAllowedParams(array $params, array $allowed, string $prefix = ''): void
    {
        $allowed = array_flip($allowed);
        foreach ($params as $key => $_) {
            if (!array_key_exists($key, $allowed)) {
                $name = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                throw new \InvalidArgumentException("Invalid parameter: {$name} is not supported.");
            }
        }
    }
}
