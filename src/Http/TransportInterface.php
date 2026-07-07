<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Http;

interface TransportInterface
{
    public function postXml(string $xml, array $headers = [], ?string $cookie = null): HttpResponse;
}
