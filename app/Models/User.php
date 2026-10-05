<?php

namespace App\Models;

use App\Actions\Administration\ProtectLastAdministrator;
use App\RoleCode;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['granted_by', 'created_at']);
    }

    public function hasRole(RoleCode $role): bool
    {
        return $this->roles()->where('code', $role->value)->exists();
    }

    /** @param array<string, mixed> $options */
    public function save(array $options = []): bool
    {
        if ($this->exists && $this->isDirty('email_verified_at') && $this->email_verified_at === null) {
            return DB::transaction(function () use ($options): bool {
                (new ProtectLastAdministrator)->handle($this, 'email');

                return parent::save($options);
            }, 3);
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            (new ProtectLastAdministrator)->handle($this, 'password');
            foreach ($this->roles()->get() as $role) {
                RoleAssignmentAudit::create([
                    'actor_id' => $this->id,
                    'target_user_id' => $this->id,
                    'role_code' => $role->code->value,
                    'action' => 'revoked',
                    'reason' => 'Revogação por exclusão da conta.',
                ]);
            }

            return parent::delete();
        }, 3);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
