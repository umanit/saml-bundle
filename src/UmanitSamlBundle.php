<?php

declare(strict_types=1);

namespace Umanit\SamlBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UmanitSamlBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
