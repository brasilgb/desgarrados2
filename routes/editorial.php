<?php

use App\Http\Controllers\Editorial\MediaController;
use App\Http\Controllers\Editorial\MediaFileController;
use App\Http\Controllers\Editorial\PublicationController;
use App\Http\Controllers\Editorial\PublicationFeatureController;
use App\Http\Controllers\Editorial\PublicPublicationController;
use App\Http\Controllers\Editorial\RegionController;
use App\Http\Controllers\Editorial\RevisionMediaController;
use App\Http\Controllers\Editorial\RightsEvidenceController;
use App\Http\Controllers\Editorial\TaxonomyController;
use App\Http\Middleware\EditorialNoIndex;
use Illuminate\Support\Facades\Route;

Route::get('/historias', [PublicPublicationController::class, 'index'])->name('stories.index');
Route::get('/historias/secoes/{section}', [PublicPublicationController::class, 'index'])->name('stories.section');
Route::get('/historias/{slug}', [PublicPublicationController::class, 'show'])->name('stories.show');
Route::middleware([EditorialNoIndex::class, 'auth', 'verified', 'can:access-administration'])->prefix('administracao/editorial')->name('editorial.')->group(function () {
    Route::get('/', [PublicationController::class, 'index'])->name('index');
    Route::get('/nova', [PublicationController::class, 'create'])->name('create');
    Route::post('/publicacoes', [PublicationController::class, 'store'])->name('store');
    Route::get('/busca/{kind}', [TaxonomyController::class, 'lookup'])->name('lookup');
    Route::get('/taxonomia/{kind}', [TaxonomyController::class, 'index'])->name('taxonomy');
    Route::post('/taxonomia/{kind}/{id?}', [TaxonomyController::class, 'save'])->name('taxonomy.save');
    Route::delete('/taxonomia/{kind}/{id}', [TaxonomyController::class, 'delete'])->name('taxonomy.delete');
    Route::get('/regioes', [RegionController::class, 'index'])->name('regions');
    Route::post('/regioes/{region?}', [RegionController::class, 'save'])->name('regions.save');
    Route::get('/destaques', [PublicationFeatureController::class, 'index'])->name('features');
    Route::post('/destaques/{feature?}', [PublicationFeatureController::class, 'save'])->name('features.save');
    Route::delete('/destaques/{feature}', [PublicationFeatureController::class, 'delete'])->name('features.delete');
    Route::get('/publicacoes/{publication}', [PublicationController::class, 'show'])->name('show');
    Route::get('/publicacoes/{publication}/revisoes/{revision}/previa', [PublicationController::class, 'preview'])->name('preview');
    Route::post('/publicacoes/{publication}/revisoes', [PublicationController::class, 'revision'])->name('revision');
    Route::post('/publicacoes/{publication}/transicao', [PublicationController::class, 'transition'])->name('transition');
    Route::patch('/publicacoes/{publication}/metadados', [PublicationController::class, 'metadata'])->name('metadata');
    Route::post('/publicacoes/{publication}/revogar-direitos', [PublicationController::class, 'revokeRights'])->name('rights.revoke');
    Route::post('/revisoes/{revision}/comprovantes', [RightsEvidenceController::class, 'store'])->name('rights-evidence.store');
    Route::get('/comprovantes/{evidence}', [RightsEvidenceController::class, 'download'])->name('rights-evidence.download');
});

// Derivatives only; the controller decides public or private access on every request.
Route::get('/midia/{uuid}/{variant}.webp', [MediaFileController::class, 'show'])
    ->whereUuid('uuid')->whereIn('variant', ['cover', 'content', 'card'])->name('media.show');
Route::middleware([EditorialNoIndex::class, 'auth', 'verified', 'can:access-administration'])->prefix('administracao/editorial')->name('editorial.')->group(function () {
    Route::post('/midia', [MediaController::class, 'store'])->name('media.store');
    Route::get('/midia/{media}/original', [MediaFileController::class, 'original'])->name('media.original');
    Route::patch('/midia/{media}/direitos', [MediaController::class, 'rights'])->name('media.rights');
    Route::post('/midia/{media}/bloqueio', [MediaController::class, 'block'])->name('media.block');
    Route::delete('/midia/{media}/bloqueio', [MediaController::class, 'unblock'])->name('media.unblock');
    Route::delete('/midia/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/publicacoes/{publication}/midia', [RevisionMediaController::class, 'store'])->name('revision-media.store');
    Route::patch('/midia-da-revisao/{item}', [RevisionMediaController::class, 'update'])->name('revision-media.update');
    Route::delete('/midia-da-revisao/{item}', [RevisionMediaController::class, 'destroy'])->name('revision-media.destroy');
    Route::post('/midia-da-revisao/{item}/mover', [RevisionMediaController::class, 'move'])->name('revision-media.move');
});
