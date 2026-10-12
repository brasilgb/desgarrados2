<?php

namespace App\Console\Commands;

use App\Actions\Administration\ManageRoleAssignments;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class CreateFirstAdministrator extends Command
{
    protected $signature = 'desgarrados:admin:create';

    protected $description = 'Cria a primeira conta de administrador durante uma janela operacional autorizada';

    public function handle(ManageRoleAssignments $assignments): int
    {
        if (! config('administration.admin_bootstrap_enabled')) {
            $this->error('Bootstrap bloqueado. Habilite DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED somente na janela autorizada.');

            return self::FAILURE;
        }
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('O bootstrap exige a conexão operacional MySQL/MariaDB.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nome completo'));
        $email = mb_strtolower(trim((string) $this->ask('E-mail')));
        $password = (string) $this->secret('Senha forte (mínimo de 8 caracteres)');
        $password_confirmation = (string) $this->secret('Repita a senha');
        $reason = trim((string) $this->ask('Responsável e justificativa para o bootstrap'));

        $validator = Validator::make(compact('name', 'email', 'password', 'password_confirmation', 'reason'), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['password.confirmed' => 'As senhas informadas não coincidem.']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        if (! $this->confirm('Confirmo que o titular controla este e-mail e foi designado como primeiro administrador', false)) {
            $this->warn('Bootstrap cancelado; nenhuma conta foi criada.');

            return self::FAILURE;
        }

        try {
            $role = Role::where('code', RoleCode::Administrator->value)->firstOrFail();
            $user = DB::transaction(function () use ($assignments, $role, $name, $email, $password, $reason): User {
                $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'is_active' => true]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $assignments->handle(null, $user, $role, true, $reason, true);

                return $user;
            }, 3);
        } catch (Throwable $exception) {
            $this->error('Bootstrap não concluído: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Administrador criado e auditado. ID {$user->id}; e-mail {$user->email}. Nenhuma senha foi exibida.");
        $this->warn('Desabilite DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED imediatamente.');

        return self::SUCCESS;
    }
}
