<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Http;

interface TransportInterface
{
    public function postXml(string $xml, array $headers = [], ?string $cookie = null): HttpResponse;
}
