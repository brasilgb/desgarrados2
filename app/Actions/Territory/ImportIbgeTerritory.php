<?php

namespace App\Actions\Territory;

use App\Models\Municipality;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportIbgeTerritory
{
    public const BASE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    /** @return array{states: int, municipalities: int} */
    public function handle(): array
    {
        return Cache::lock('ibge-territory-import', 300)->block(1, function (): array {
            $states = Http::acceptJson()->connectTimeout(5)->timeout(60)->retry(2, 250)->get(self::BASE_URL.'/estados')->throw()->json();
            $municipalities = Http::acceptJson()->connectTimeout(5)->timeout(60)->retry(2, 250)->get(self::BASE_URL.'/municipios')->throw()->json();
            Validator::make(['states' => $states, 'municipalities' => $municipalities], [
                'states' => ['required', 'array', 'min:1'],
                'states.*.id' => ['required', 'integer', 'between:10,99', 'distinct'],
                'states.*.sigla' => ['required', 'string', 'regex:/^[A-Z]{2}$/', 'distinct'],
                'states.*.nome' => ['required', 'string', 'max:100'],
                'municipalities' => ['required', 'array', 'min:1'],
                'municipalities.*.id' => ['required', 'integer', 'between:1000000,9999999', 'distinct'],
                'municipalities.*.nome' => ['required', 'string', 'max:150'],
            ])->validate();
            /** @var list<array{id: int, sigla: string, nome: string}> $states */
            /** @var list<array{id: int, nome: string}> $municipalities */
            $stateCodes = array_map(fn (array $state): string => (string) $state['id'], $states);
            foreach ($municipalities as $municipality) {
                if (! in_array(substr((string) $municipality['id'], 0, 2), $stateCodes, true)) {
                    throw ValidationException::withMessages(['municipalities' => 'O IBGE retornou município sem estado correspondente.']);
                }
            }

            return DB::transaction(function () use ($states, $municipalities): array {
                $timestamp = now();
                foreach ($states as $state) {
                    $record = State::firstOrNew(['ibge_code' => (string) $state['id']]);
                    if (! $record->exists) {
                        $record->slug = Str::slug($state['nome']);
                    }
                    $record->fill(['abbreviation' => $state['sigla'], 'name' => $state['nome'], 'synced_at' => $timestamp])->save();
                }
                $stateIds = State::pluck('id', 'ibge_code')->all();
                $existingSlugs = Municipality::pluck('slug', 'ibge_code')->all();
                $rows = [];
                foreach ($municipalities as $municipality) {
                    $code = (string) $municipality['id'];
                    $rows[] = [
                        'ibge_code' => $code,
                        'state_id' => $stateIds[substr($code, 0, 2)],
                        'name' => $municipality['nome'],
                        'slug' => $existingSlugs[$code] ?? Str::slug($municipality['nome']).'-'.$code,
                        'active' => true,
                        'synced_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    Municipality::upsert($chunk, ['ibge_code'], ['state_id', 'name', 'synced_at', 'updated_at']);
                }

                return ['states' => count($states), 'municipalities' => count($municipalities)];
            }, 3);
        });
    }
}
