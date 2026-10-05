<?php

declare(strict_types=1);
/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Carência da cobrança: confere de hora em hora quem passou do prazo (RN47).
Schedule::command('billing:enforce-grace')->hourly()->withoutOverlapping();
