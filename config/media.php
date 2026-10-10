<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mídia editorial
    |--------------------------------------------------------------------------
    |
    | Originais ficam no disco privado; os derivados também, servidos apenas
    | pela rota que confere a visibilidade da publicação a cada requisição.
    |
    */

    'disk' => env('MEDIA_DISK', 'media'),

    'max_upload_kb' => 10 * 1024,

    'mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],

    'min_dimension' => 200,

    'max_dimension' => 8000,

    // Limite contra imagens-bomba: largura × altura decodificadas pelo GD.
    'max_pixels' => 40_000_000,

    'webp_quality' => 82,

    // Larguras máximas; imagens menores nunca são ampliadas.
    'variants' => [
        'cover' => 1600,
        'content' => 1200,
        'card' => 640,
    ],

    // Navegadores e proxies podem reter a mídia pública por pouco tempo; ocultar a publicação a retira em seguida.
    'public_max_age' => 300,

];
