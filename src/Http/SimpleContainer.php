<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Http;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class SimpleContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        throw new class("Service not found: {$id}") extends \RuntimeException implements NotFoundExceptionInterface {
        };
    }

    public function has(string $id): bool
    {
        return false;
    }
}
