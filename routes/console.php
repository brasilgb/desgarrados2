<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Trava expira em 10 min: um processo encerrado à força não suspende a publicação agendada por 24 h.
Schedule::command('editorial:publish-due')->everyMinute()->withoutOverlapping(10);
// Rede de segurança da fila: retoma imagens pendentes há mais de 10 min ou travadas em processamento.
Schedule::command('media:process --stale')->everyFiveMinutes()->withoutOverlapping(30);
