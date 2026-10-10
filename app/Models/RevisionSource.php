<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['publication_revision_id', 'title', 'url', 'attribution', 'accessed_at'])]
class RevisionSource extends Model {}
