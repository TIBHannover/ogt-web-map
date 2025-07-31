<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikibaseEntryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "description",
        "image_url",
        "image_filename",
        "type",
        "coordinates",
        "coordinates_qualifier",
        "status",
        "status_token",
        "status_history",
        "status_updated_at",
        "comment",
        "wikidata_id",
        "created_entities",
        "submitted_by",
        "approved_by",
        "rejected_by",
        "rejection_reason",
        "victims",
        "perpetrators",
        "time_point",
        "time_point_qualifier",
        "time_period_start",
        "time_period_start_qualifier",
        "time_period_end",
        "time_period_end_qualifier",
        "victim_count",
        "victim_count_qualifier",
        "survivor_count",
        "survivor_count_qualifier",
        "death_count",
        "death_count_qualifier",
        "arrest_count",
        "arrest_count_qualifier",
        "cause",
        "selected_causes",
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
        'status_history' => 'array',
    ];

    protected $table = 'wikidata_entry_requests';
}