<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikidataEntryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "description",
        "type",
        "coordinates",
        "status",
        "wikidata_id",
        "submitted_by",
        "approved_by",
        "rejected_by",
        "rejection_reason",
        "victims",
        "perpetrators",
        "time_point",
        "time_period_start",
        "time_period_end",
        "victim_count",
        "survivor_count",
        "death_count",
        "arrest_count",
        "cause",
    ];

    protected $casts = [
        'victims' => 'array',
        'perpetrators' => 'array',
    ];

}
