<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Operation\Session;

use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\AbstractOperation;

final class Logout extends AbstractOperation
{
    public function name(): string
    {
        return 'Logout';
    }

    public function execute(Mor $sessionManager): void
    {
        $this->call([
            '_this' => $sessionManager,
        ]);
    }
}
