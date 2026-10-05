<?php

use App\Models\Municipality;
use App\Models\Region;
use App\Models\State;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

test('public territory pages expose active catalog data and prioritize RS', function () {
    State::factory()->create(['name' => 'Acre', 'slug' => 'acre', 'abbreviation' => 'AC']);
    $state = State::factory()->create(['name' => 'Rio Grande do Sul', 'slug' => 'rio-grande-do-sul', 'abbreviation' => 'RS']);
    $city = Municipality::factory()->for($state)->create(['name' => 'Porto Alegre']);
    Municipality::factory()->for($state)->create(['active' => false]);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->component('welcome')->where('featuredState.slug', $state->slug)->where('municipalityCount', 1));
    $this->get(route('territory.index'))->assertInertia(fn (Assert $page) => $page->component('territory/states')->where('states.0.abbreviation', 'RS')->where('states.0.municipalities_count', 1));
    $this->get(route('territory.state', $state->slug))->assertInertia(fn (Assert $page) => $page->has('municipalities.data', 1)->where('municipalities.data.0.name', $city->name));
});

test('municipality cannot be accessed through another state', function () {
    $city = Municipality::factory()->create();
    $other = State::factory()->create();

    $this->get(route('territory.municipality', [$other->slug, $city->slug]))->assertNotFound();
});

test('inactive territory is not publicly accessible', function () {
    $state = State::factory()->create(['active' => false]);
    $city = Municipality::factory()->for($state)->create();

    $this->get(route('territory.state', $state->slug))->assertNotFound();
    $this->get(route('territory.municipality', [$state->slug, $city->slug]))->assertNotFound();
});

test('a municipality can belong to overlapping cultural and geographic regions', function () {
    $city = Municipality::factory()->create();
    $regions = Region::factory()->count(2)->create();
    $city->regions()->attach($regions->modelKeys());

    $this->get(route('territory.municipality', [$city->state->slug, $city->slug]))->assertInertia(fn (Assert $page) => $page->has('regions', 2));
    expect($city->regions()->count())->toBe(2);
});

test('referenced municipalities cannot be deleted', function () {
    $city = Municipality::factory()->create();
    $region = Region::factory()->create();
    $city->regions()->attach($region);

    $city->delete();
})->throws(QueryException::class);

test('IBGE municipality codes cannot be duplicated', function () {
    Municipality::factory()->create(['ibge_code' => '4314902']);

    Municipality::factory()->create(['ibge_code' => '4314902']);
})->throws(QueryException::class);

test('my land selection works anonymously without writing users or preferences', function () {
    $state = State::factory()->create();

    $this->get(route('territory.myLand', ['state' => $state->slug]))->assertInertia(fn (Assert $page) => $page->component('territory/my-land')->where('selectedState', $state->slug));

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('role_user', 0);
});

test('municipality search is paginated and treats wildcard input literally', function () {
    $state = State::factory()->create();
    Municipality::factory()->for($state)->count(25)->create();

    $this->get(route('territory.state', $state->slug))->assertInertia(fn (Assert $page) => $page->has('municipalities.data', 24)->where('municipalities.last_page', 2));
    $this->get(route('territory.state', [$state->slug, 'q' => '%']))->assertInertia(fn (Assert $page) => $page->has('municipalities.data', 0));
});
