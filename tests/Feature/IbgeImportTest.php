<?php

use App\Actions\Territory\ImportIbgeTerritory;
use App\Models\Municipality;
use App\Models\Region;
use App\Models\State;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function fakeIbge(array|ResponseSequence $municipalities): void
{
    Http::preventStrayRequests();
    Http::fake([
        ImportIbgeTerritory::BASE_URL.'/estados' => Http::response([['id' => 43, 'sigla' => 'RS', 'nome' => 'Rio Grande do Sul']]),
        ImportIbgeTerritory::BASE_URL.'/municipios' => is_array($municipalities) ? Http::response($municipalities) : $municipalities,
    ]);
}

test('official import is idempotent and preserves identifiers slugs references and inactive records', function () {
    fakeIbge(Http::sequence()
        ->push([['id' => 4314902, 'nome' => 'Porto Alegre']])
        ->push([['id' => 4314902, 'nome' => 'Nome oficial atualizado']]));
    $this->artisan('territory:import-ibge')->assertSuccessful();
    $city = Municipality::sole();
    $region = Region::factory()->create();
    $city->regions()->attach($region);
    $city->update(['slug' => 'slug-preservado', 'active' => false]);
    $former = Municipality::factory()->for($city->state)->create(['ibge_code' => '4300001']);

    $this->artisan('territory:import-ibge')->assertSuccessful();

    $this->assertDatabaseCount('states', 1);
    $this->assertDatabaseCount('municipalities', 2);
    $this->assertDatabaseHas('municipalities', ['id' => $city->id, 'name' => 'Nome oficial atualizado', 'slug' => 'slug-preservado', 'active' => false]);
    $this->assertModelExists($former);
    expect($city->regions()->count())->toBe(1);
});

test('invalid official payload produces no partial territorial writes', function () {
    fakeIbge([['id' => 9999999, 'nome' => 'Sem UF correspondente']]);

    expect(fn () => app(ImportIbgeTerritory::class)->handle())->toThrow(ValidationException::class);

    $this->assertDatabaseCount('states', 0);
    $this->assertDatabaseCount('municipalities', 0);
});

test('failed IBGE requests leave existing territory unchanged', function () {
    $state = State::factory()->create();
    Http::preventStrayRequests();
    Http::fake([ImportIbgeTerritory::BASE_URL.'/*' => Http::response([], 503)]);

    expect(fn () => app(ImportIbgeTerritory::class)->handle())->toThrow(RequestException::class);

    $this->assertModelExists($state);
    $this->assertDatabaseCount('states', 1);
    $this->assertDatabaseCount('municipalities', 0);
});
