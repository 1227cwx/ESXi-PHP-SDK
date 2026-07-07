<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Value;

final class DataObject
{
    public function __construct(
        private readonly ?string $xsiType,
        private readonly array $properties = []
    ) {
    }

    public static function typed(string $xsiType, array $properties = []): self
    {
        return new self($xsiType, $properties);
    }

    public static function plain(array $properties = []): self
    {
        return new self(null, $properties);
    }

    public function xsiType(): ?string
    {
        return $this->xsiType;
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
