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
    ];

    protected $casts = [
        'victims' => 'array',
    ];

}
