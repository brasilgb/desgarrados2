<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_id', 'target_user_id', 'action', 'changes'])]
class AdministrationAudit extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
