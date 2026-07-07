<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Http;

final class HttpResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headerLine(string $name): string
    {
        $name = strtolower($name);

        foreach ($this->headers as $headerName => $values) {
            if (strtolower((string) $headerName) === $name) {
                return implode(', ', (array) $values);
            }
        }

        return '';
    }
}
