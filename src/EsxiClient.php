<?php

declare(strict_types=1);

namespace Cwx1227\Esxi;

use Cwx1227\Esxi\Version\V67\V67Client;

final class EsxiClient
{
    public static function make(array $config): V67Client
    {
        return (new ClientFactory())->make($config);
    }

    public static function connect(string $host, string $username, string $password, array $options = []): V67Client
    {
        return self::make(array_replace($options, [
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'version' => $options['version'] ?? '67',
        ]));
    }
}
