<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Operation\Session;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;
use Cwx1227\Esxi\Version\V67\Operation\AbstractOperation;

final class Login extends AbstractOperation
{
    public function name(): string
    {
        return 'Login';
    }

    public function execute(Mor $sessionManager, string $username, string $password, ?string $locale = null): ?array
    {
        $value = $this->call([
            '_this' => $sessionManager,
            'userName' => $username,
            'password' => $password,
            'locale' => $locale,
        ])->firstReturnValue();

        return is_array($value) ? $value : null;
    }
}
