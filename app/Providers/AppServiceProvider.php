<?php

namespace App\Providers;

use App\Models\User;
use App\RoleCode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Ssr\SsrRenderFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Gate::define('access-administration', fn (User $user): bool => $user->email_verified_at !== null
            && $user->roles()->whereIn('code', array_map(fn (RoleCode $role): string => $role->value, RoleCode::cases()))->exists());
        Gate::define('manage-territory', fn (User $user): bool => $user->email_verified_at !== null
            && $user->hasRole(RoleCode::Administrator));
        // `composer dev` sobe servidor, fila, logs e Vite; sem o scheduler, publicações agendadas não saem no host.
        DevCommands::artisan('schedule:work', 'scheduler');
        // Sem props da página no log: o payload pode conter dados de sessão.
        Event::listen(fn (SsrRenderFailed $event) => Log::warning('SSR indisponível; resposta entregue sem pré-renderização.', [
            'component' => $event->component(), 'url' => $event->url(), 'type' => $event->type->value, 'error' => Str::limit($event->error, 300),
        ]));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
