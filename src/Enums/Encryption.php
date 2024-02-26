<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Enums;

use RobRichards\XMLSecLibs\XMLSecurityKey;

enum Encryption: string
{
    case TRIPLEDES_CBC = XMLSecurityKey::TRIPLEDES_CBC;
    case AES128_CBC = XMLSecurityKey::AES128_CBC;
    case AES192_CBC = XMLSecurityKey::AES192_CBC;
    case AES256_CBC = XMLSecurityKey::AES256_CBC;
    case AES128_GCM = XMLSecurityKey::AES128_GCM;
    case AES192_GCM = XMLSecurityKey::AES192_GCM;
    case AES256_GCM = XMLSecurityKey::AES256_GCM;
    case RSA_1_5 = XMLSecurityKey::RSA_1_5;
    case RSA_OAEP_MGF1P = XMLSecurityKey::RSA_OAEP_MGF1P;
    case RSA_OAEP = XMLSecurityKey::RSA_OAEP;
    case DSA_SHA1 = XMLSecurityKey::DSA_SHA1;
    case RSA_SHA1 = XMLSecurityKey::RSA_SHA1;
    case RSA_SHA256 = XMLSecurityKey::RSA_SHA256;
    case RSA_SHA384 = XMLSecurityKey::RSA_SHA384;
    case RSA_SHA512 = XMLSecurityKey::RSA_SHA512;
    case HMAC_SHA1 = XMLSecurityKey::HMAC_SHA1;
}
