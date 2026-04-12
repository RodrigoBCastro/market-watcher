<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('market:sync-assets')->weekdays()->at('18:05');
Schedule::command('market:sync-context')->weekdays()->at('18:10');
Schedule::command('market:recalculate-indicators')->weekdays()->at('18:20');
Schedule::command('market:recalculate-scores')->weekdays()->at('18:35');
Schedule::command('market:generate-brief')->weekdays()->at('18:45');
