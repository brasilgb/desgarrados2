<?php

namespace App\Console\Commands;

use App\Models\AuditEntry;
use App\Models\MediaAsset;
use App\Models\Publication;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Ssr\BundleDetector;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\HasHealthCheck;
use Throwable;

/**
 * Diagnóstico somente por CLI: nenhum valor de configuração, credencial ou caminho é impresso.
 */
class OperationalHealth extends Command
{
    protected $signature = 'ops:health {--json : Saída em JSON} {--strict : Avisos também resultam em falha} {--scheduler-max-age=300 : Segundos tolerados desde a última execução do publicador}';

    protected $description = 'Verifica banco, SSR, scheduler, publicações vencidas e configuração de produção.';

    /** @var list<array{check: string, status: 'ok'|'warning'|'failure', detail: string}> */
    private array $results = [];

    public function handle(Gateway $gateway): int
    {
        $this->database();
        $this->ssr($gateway);
        $this->scheduler();
        $this->media();
        $this->production();

        $statuses = array_column($this->results, 'status');
        $failed = in_array('failure', $statuses, true) || ($this->option('strict') && in_array('warning', $statuses, true));
        if ($this->option('json')) {
            $this->line((string) json_encode(['healthy' => ! $failed, 'checks' => $this->results], JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Verificação', 'Estado', 'Detalhe'], $this->results);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function database(): void
    {
        try {
            DB::select('select 1');
            $this->result('database', 'ok', 'Conexão respondeu.');
        } catch (Throwable) {
            $this->result('database', 'failure', 'Sem conexão com o banco.');
        }
    }

    private function ssr(Gateway $gateway): void
    {
        if (! config('inertia.ssr.enabled')) {
            $this->result('ssr', 'warning', 'SSR desativado; páginas serão renderizadas no navegador.');

            return;
        }
        if (config('inertia.ssr.ensure_bundle_exists', true) && app(BundleDetector::class)->detect() === null) {
            $this->result('ssr', 'warning', 'Bundle SSR ausente; execute npm run build:ssr.');

            return;
        }
        $healthy = $gateway instanceof HasHealthCheck && $gateway->isHealthy();
        // A aplicação continua servindo HTML sem pré-renderização; por isso é aviso, não falha.
        $this->result('ssr', $healthy ? 'ok' : 'warning', $healthy ? 'Renderer respondeu ao /health.' : 'Renderer indisponível; fallback para renderização no navegador.');
    }

    private function scheduler(): void
    {
        try {
            $heartbeat = Cache::get(PublishScheduledPublications::HEARTBEAT_KEY);
            $maxAge = max(60, (int) $this->option('scheduler-max-age'));
            if (! is_array($heartbeat)) {
                $this->result('scheduler', 'warning', 'Publicador agendado ainda não registrou execução.');
            } else {
                $age = (int) Carbon::parse($heartbeat['at'])->diffInSeconds(now(), true);
                $this->result('scheduler', $age <= $maxAge ? 'ok' : 'failure', "Última execução há {$age}s (limite {$maxAge}s).");
            }
            $overdue = Publication::where('scheduled_for', '<', now()->subMinutes(5))->count();
            $this->result('scheduled_publications', $overdue === 0 ? 'ok' : 'failure', "Agendamentos vencidos há mais de 5 min: {$overdue}.");
            $failures = AuditEntry::whereIn('action', ['schedule_failed', 'schedule_blocked'])->where('created_at', '>=', now()->subDay())->count();
            $this->result('publication_failures', $failures === 0 ? 'ok' : 'warning', "Bloqueios ou falhas de publicação nas últimas 24 h: {$failures}.");
        } catch (Throwable) {
            $this->result('scheduler', 'failure', 'Não foi possível consultar o estado do publicador.');
        }
    }

    private function media(): void
    {
        $bytes = fn (string $value): int => (int) $value * match (strtolower(substr(trim($value), -1))) {
            'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1
        };
        $php = min($bytes((string) ini_get('upload_max_filesize')), $bytes((string) ini_get('post_max_size')));
        $app = (int) config('media.max_upload_kb') * 1024;
        // Measured in this CLI process; the web server may load a different php.ini.
        $this->result('media_upload_limit', $php >= $app ? 'ok' : 'warning', $php >= $app
            ? 'Limites do PHP comportam uploads de 10 MB.'
            : 'Limites do PHP abaixo de 10 MB (upload_max_filesize/post_max_size); uploads maiores serão recusados.');
        try {
            $failed = MediaAsset::where('processing_status', 'failed')->count();
            $stale = MediaAsset::whereIn('processing_status', ['pending', 'processing'])->where('updated_at', '<', now()->subMinutes(15))->count();
            $this->result('media_processing', $failed + $stale === 0 ? 'ok' : 'warning', "Imagens com falha: {$failed}; pendentes há mais de 15 min: {$stale}.");
        } catch (Throwable) {
            $this->result('media_processing', 'failure', 'Não foi possível consultar o processamento de mídia.');
        }
    }

    private function production(): void
    {
        if (! app()->isProduction()) {
            $this->result('production_config', 'ok', 'Ambiente não produtivo; verificações de produção omitidas.');

            return;
        }
        $problems = array_keys(array_filter([
            'APP_DEBUG ativo' => (bool) config('app.debug'),
            'APP_URL sem HTTPS' => ! str_starts_with((string) config('app.url'), 'https://'),
            'SESSION_SECURE_COOKIE desativado' => ! config('session.secure'),
            'cookie de sessão acessível ao JavaScript' => ! config('session.http_only'),
            'configuração não cacheada' => ! app()->configurationIsCached(),
            'storage sem escrita' => ! is_writable(storage_path('framework')) || ! is_writable(storage_path('logs')),
            'bootstrap/cache sem escrita' => ! is_writable(base_path('bootstrap/cache')),
        ]));
        $this->result('production_config', $problems === [] ? 'ok' : 'warning', $problems === [] ? 'Configuração de produção conferida.' : implode('; ', $problems).'.');
    }

    /** @param 'ok'|'warning'|'failure' $status */
    private function result(string $check, string $status, string $detail): void
    {
        $this->results[] = ['check' => $check, 'status' => $status, 'detail' => $detail];
    }
}
