<?php

namespace App\Enums;

enum MediaRights: string
{
    case Own = 'own';
    case Authorized = 'authorized';
    case Licensed = 'licensed';
    case CreativeCommons = 'creative_commons';
    case PublicDomain = 'public_domain';
}
