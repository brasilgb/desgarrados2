<?php

namespace App\Console\Commands;

use App\Actions\Territory\ImportIbgeTerritory;
use Illuminate\Console\Command;
use Throwable;

class ImportTerritory extends Command
{
    protected $signature = 'territory:import-ibge';

    protected $description = 'Sincroniza estados e municípios oficiais sem excluir registros existentes';

    public function handle(ImportIbgeTerritory $importer): int
    {
        try {
            $result = $importer->handle();
            $this->info("IBGE: {$result['states']} estados e {$result['municipalities']} municípios sincronizados.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Importação não concluída: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
