<?php

use Inertia\Testing\AssertableInertia as Assert;

test('ebook pages are publicly accessible', function (string $routeName, string $component) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'sales' => ['ebooks.sales', 'ebooks/pagina-de-vendas'],
    'thanks' => ['ebooks.thanks', 'ebooks/pagina-de-obrigado'],
]);
