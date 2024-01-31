<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\src;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SamlBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}