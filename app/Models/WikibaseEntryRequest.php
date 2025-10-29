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
        "email",
        "notify_on_publish",
        "notification_sent_at",
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
        "occupation",
        "is_standalone_person",
        "commemorates",
        "operator",
        "opening_date",
        "opening_date_qualifier",
        "address",
        "website",
    ];

    protected $casts = [
        "victims" => "array",
        "perpetrators" => "array",
        "sources" => "array",
        "created_entities" => "array",
        "status_history" => "array",
        "notify_on_publish" => "boolean",
        "notification_sent_at" => "datetime",
        "place_of_detention" => "array",
        "is_standalone_person" => "boolean",
        "commemorates" => "string",
        "operator" => "string",
    ];

    protected $table = "wikidata_entry_requests";
}
