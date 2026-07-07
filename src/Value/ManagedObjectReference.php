<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Value;

use JsonSerializable;

final class ManagedObjectReference implements JsonSerializable
{
    public function __construct(
        private readonly string $type,
        private readonly string $value
    ) {
    }

    public static function from(mixed $value, ?string $defaultType = null): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            $type = $value['type'] ?? $value['_type'] ?? $defaultType;
            $id = $value['value'] ?? $value['_value'] ?? $value['_'] ?? null;

            if (is_string($type) && is_scalar($id)) {
                return new self($type, (string) $id);
            }
        }

        if (is_string($value) && $defaultType !== null) {
            return new self($defaultType, $value);
        }

        throw new \InvalidArgumentException('Invalid ManagedObjectReference value.');
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
