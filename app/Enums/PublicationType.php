<?php

namespace App\Enums;

enum PublicationType: string
{
    case Article = 'article';
    case News = 'news';
    case Chronicle = 'chronicle';
    case Causo = 'causo';
    case CulturalHistory = 'cultural_history';
    case PersonalStory = 'personal_story';
    case MigrationStory = 'migration_story';
    case Memory = 'memory';
}
