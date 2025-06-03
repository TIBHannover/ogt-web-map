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
        "image_url",
        "image_filename",
        "type",
        "coordinates",
        "status",
        "wikidata_id",
        "created_entities",
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
        "sources",
        "gender",
        "nationality",
        "place_of_birth",
        "place_of_death",
        "place_of_detention",
    ];

    protected $casts = [
        'victims' => 'array',
        'perpetrators' => 'array',
        'sources' => 'array',
        'created_entities' => 'array',
        'gender' => 'array',
        'nationality' => 'array',
        'place_of_birth' => 'array',
        'place_of_death' => 'array',
        'place_of_detention' => 'array',
    ];

}
