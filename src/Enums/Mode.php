<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Enums;

enum Mode: string
{
    case SP_INITIATED = 'sp_initiated';
    case IDP_INITIATED = 'idp_initiated';
}
