<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Http;

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\ClientFactory;
use WebmanVps\Esxi\Exception\EsxiException;

final class HyperfGuzzleTransport implements TransportInterface
{
    private \GuzzleHttp\Client $client;

    public function __construct(
        private readonly string $endpoint,
        array $options = []
    ) {
        $factory = new ClientFactory(new SimpleContainer());
        $this->client = $factory->create([
            'verify' => (bool) ($options['ssl_verify'] ?? false),
            'timeout' => (float) ($options['timeout'] ?? 60),
            'connect_timeout' => (float) ($options['connect_timeout'] ?? 10),
            'http_errors' => false,
        ]);
    }

    public function postXml(string $xml, array $headers = [], ?string $cookie = null): HttpResponse
    {
        $headers = array_replace([
            'Content-Type' => 'text/xml; charset=utf-8',
        ], $headers);

        if ($cookie !== null && $cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        try {
            $response = $this->client->request('POST', $this->endpoint, [
                'headers' => $headers,
                'body' => $xml,
            ]);
        } catch (GuzzleException $exception) {
            throw new EsxiException('ESXi HTTPS request failed: ' . $exception->getMessage(), 0, $exception);
        }

        return new HttpResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            (string) $response->getBody()
        );
    }
}
