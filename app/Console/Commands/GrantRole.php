<?php

namespace App\Console\Commands;

use App\Actions\Administration\ManageRoleAssignments;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class GrantRole extends Command
{
    protected $signature = 'roles:grant {user : ID de usuário existente} {role : Código do papel} {--actor= : ID do administrador responsável} {--reason= : Justificativa} {--bootstrap : Concessão explícita do primeiro administrador}';

    protected $description = 'Concede um papel com auditoria sem criar contas ou senhas';

    public function handle(ManageRoleAssignments $assignments): int
    {
        try {
            $target = User::findOrFail($this->argument('user'));
            $role = Role::where('code', $this->argument('role'))->firstOrFail();
            $actor = $this->option('actor') ? User::findOrFail($this->option('actor')) : null;
            $assignments->handle($actor, $target, $role, true, (string) $this->option('reason'), (bool) $this->option('bootstrap'));
            $this->info('Concessão registrada. Nenhuma conta foi criada.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
