<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * Built-in entity types. The taxonomy is open: PiiMatch::$entityType is a
 * plain string, so custom types need no enum changes.
 */
enum EntityType: string
{
    case EMAIL = 'EMAIL';
    case PHONE = 'PHONE';
    case CREDIT_CARD = 'CREDIT_CARD';
    case IBAN = 'IBAN';
    case IP_ADDRESS = 'IP_ADDRESS';
    case MAC_ADDRESS = 'MAC_ADDRESS';
    case URL = 'URL';
    case CRYPTO_ADDRESS = 'CRYPTO_ADDRESS';
    case DATE_OF_BIRTH = 'DATE_OF_BIRTH';
    case API_KEY = 'API_KEY';
    case PRIVATE_KEY = 'PRIVATE_KEY';
    case VAT_ID = 'VAT_ID';
    case GEO_COORDINATE = 'GEO_COORDINATE';
    case SWIFT_BIC = 'SWIFT_BIC';
    case PERSON = 'PERSON';
    case LOCATION = 'LOCATION';
    case ADDRESS = 'ADDRESS';
}
