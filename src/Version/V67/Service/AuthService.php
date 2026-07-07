<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

final class AuthService extends AbstractService
{
    public function login(?string $locale = null): array
    {
        $this->client->login($locale);

        return $this->ok([
            'session' => $this->client->session(),
            'about' => $this->client->about(),
        ]);
    }

    public function logout(): array
    {
        $this->client->logout();

        return $this->ok();
    }

    public function session(): array
    {
        return $this->ok([
            'session' => $this->client->session(),
            'about' => $this->client->about(),
        ]);
    }
}
