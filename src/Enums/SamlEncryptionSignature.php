<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Enums;

use RobRichards\XMLSecLibs\XMLSecurityDSig;

enum SamlEncryptionSignature: string
{
    case XMLDSIGNS = XMLSecurityDSig::XMLDSIGNS;
    case SHA1 = XMLSecurityDSig::SHA1;
    case SHA256 = XMLSecurityDSig::SHA256;
    case SHA384 = XMLSecurityDSig::SHA384;
    case SHA512 = XMLSecurityDSig::SHA512;
    case RIPEMD160 = XMLSecurityDSig::RIPEMD160;
}
