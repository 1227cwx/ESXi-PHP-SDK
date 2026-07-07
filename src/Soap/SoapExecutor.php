<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Soap;

use WebmanVps\Esxi\Exception\EsxiException;
use WebmanVps\Esxi\Http\TransportInterface;

final class SoapExecutor
{
    private string $cookie = '';

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $soapAction = 'urn:vim25/6.7.3',
        private readonly SoapXmlBuilder $builder = new SoapXmlBuilder(),
        private readonly SoapXmlParser $parser = new SoapXmlParser()
    ) {
    }

    public function call(string $method, array $params = []): SoapResponse
    {
        $httpResponse = $this->transport->postXml(
            $this->builder->buildEnvelope($method, $params),
            ['SOAPAction' => '"' . $this->soapAction . '"'],
            $this->cookie
        );

        $this->captureSessionCookie($httpResponse->headers());

        return $this->parser->parse(
            $httpResponse->body(),
            $httpResponse->statusCode(),
            $httpResponse->headers()
        );
    }

    public function cookie(): string
    {
        return $this->cookie;
    }

    private function captureSessionCookie(array $headers): void
    {
        foreach ($headers as $name => $values) {
            if (strtolower((string) $name) !== 'set-cookie') {
                continue;
            }

            foreach ((array) $values as $cookieHeader) {
                if (preg_match('/(vmware_soap_session="[^"]+")/', (string) $cookieHeader, $matches) === 1) {
                    $this->cookie = $matches[1];
                    return;
                }
            }
        }
    }
}
