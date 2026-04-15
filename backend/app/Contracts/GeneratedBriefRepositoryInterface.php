<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\GeneratedBrief;
use Illuminate\Support\Collection;

interface GeneratedBriefRepositoryInterface
{
    /**
     * Upserts the brief header and atomically replaces all items.
     * Each item must contain a 'symbol' key (ticker string).
     * Ticker→asset_id resolution is handled internally in bulk.
     *
     * @param  array<string, mixed>            $header
     * @param  array<int, array<string, mixed>> $items  combined ranked_ideas + avoid_list
     */
    public function upsertWithItems(string $briefDate, array $header, array $items): GeneratedBrief;

    public function listRecent(int $limit): Collection;

    public function findByDateWithItems(string $date): GeneratedBrief;
}
