<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';
}
