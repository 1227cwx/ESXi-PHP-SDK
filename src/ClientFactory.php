<?php

declare(strict_types=1);

namespace WebmanVps\Esxi;

use WebmanVps\Esxi\Exception\EsxiException;
use WebmanVps\Esxi\Http\HyperfGuzzleTransport;
use WebmanVps\Esxi\Soap\SoapExecutor;
use WebmanVps\Esxi\Version\V67\V67Client;

final class ClientFactory
{
    public function make(array $config): V67Client
    {
        foreach (['host', 'username', 'password'] as $required) {
            if (!isset($config[$required]) || $config[$required] === '') {
                throw new \InvalidArgumentException("Missing ESXi client config: {$required}");
            }
        }

        $version = (string) ($config['version'] ?? '67');

        return match ($version) {
            '67', '6.7', '6-7' => $this->makeV67($config),
            default => throw new EsxiException("Unsupported ESXi API version: {$version}"),
        };
    }

    private function makeV67(array $config): V67Client
    {
        $host = preg_replace('#^https?://#i', '', trim((string) $config['host'])) ?: (string) $config['host'];
        $host = rtrim($host, '/');
        $endpoint = $config['endpoint'] ?? 'https://' . $host . '/sdk';
        $executor = new SoapExecutor(
            new HyperfGuzzleTransport($endpoint, $config),
            $config['soap_action'] ?? 'urn:vim25/6.7.3'
        );

        $client = new V67Client(
            $executor,
            (string) $config['username'],
            (string) $config['password'],
            $config
        );

        if (($config['auto_login'] ?? true) !== false) {
            $client->login($config['locale'] ?? null);
        }

        return $client;
    }
}
