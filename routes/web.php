<?php

use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\TerritoryController;
use App\Http\Middleware\EditorialNoIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', [TerritoryController::class, 'home'])->name('home');
Route::inertia('/ebooks/pagina-de-vendas', 'ebooks/pagina-de-vendas')->name('ebooks.sales');
Route::inertia('/ebooks/pagina-de-obrigado', 'ebooks/pagina-de-obrigado')->name('ebooks.thanks');
Route::get('/estados', [TerritoryController::class, 'index'])->name('territory.index');
Route::get('/estados/{state:slug}', [TerritoryController::class, 'state'])->name('territory.state');
Route::get('/estados/{state:slug}/municipios/{municipality:slug}', [TerritoryController::class, 'municipality'])
    ->scopeBindings()->name('territory.municipality');
Route::get('/minha-terra', [TerritoryController::class, 'myLand'])->name('territory.myLand');

Route::middleware([EditorialNoIndex::class, 'auth', 'verified', 'can:access-administration'])->prefix('administracao')->name('admin.')->group(function () {
    Route::get('/', [AdministrationController::class, 'index'])->name('index');
    Route::get('/papeis', [AdministrationController::class, 'roles'])->name('roles');
    Route::post('/usuarios/{user}/papeis/{role}', [AdministrationController::class, 'grant'])->name('grant');
    Route::delete('/usuarios/{user}/papeis/{role}', [AdministrationController::class, 'revoke'])->name('revoke');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

require __DIR__.'/editorial.php';
