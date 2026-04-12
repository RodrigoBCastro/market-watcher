<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('market:sync-assets')->weekdays()->at('19:05');
Schedule::command('market:sync-context')->weekdays()->at('19:10');
Schedule::command('market:recalculate-indicators')->weekdays()->at('19:20');
Schedule::command('market:recalculate-scores')->weekdays()->at('19:35');
Schedule::command('market:generate-brief')->weekdays()->at('19:45');
Schedule::command('market:evaluate-open-trades')->weekdays()->at('19:55');
Schedule::command('market:generate-weekly-calls')->weeklyOn(1, '07:20');
