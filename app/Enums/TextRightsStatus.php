<?php

namespace App\Enums;

enum TextRightsStatus: string
{
    case Original = 'original';
    case Authorized = 'authorized';
    case PublicDomain = 'public_domain';
    case Licensed = 'licensed';
    case Unverified = 'unverified';
}
