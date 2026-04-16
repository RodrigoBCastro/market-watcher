<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//Schedule::command('market:sync-asset-master')->weekdays()->at('18:50');
//Schedule::command('market:bootstrap-data-universe')->weekdays()->at('18:55');
Schedule::command('market:generate-weekly-calls')->weeklyOn(1, '07:20');
Schedule::command('market:sync-data-universe')->weekdays()->at('19:00');
Schedule::command('market:recalculate-eligible-universe')->weekdays()->at('19:30');
Schedule::command('market:recalculate-trading-universe')->weekdays()->at('19:45');
Schedule::command('market:sync-context')->weekdays()->at('19:50');
Schedule::command('market:recalculate-indicators')->weekdays()->at('19:55');
Schedule::command('market:recalculate-scores')->weekdays()->at('20:15');
Schedule::command('market:generate-brief')->weekdays()->at('20:20');
Schedule::command('market:evaluate-open-trades')->weekdays()->at('20:25');
Schedule::command('market:portfolio-mark-to-market')->weekdays()->at('20:30');
Schedule::command('market:refresh-alerts')->weekdays()->at('20:35');
Schedule::command('market:snapshot-equity')->weekdays()->at('20:40');
