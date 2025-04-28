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
        "status",
        "wikidata_id",
        "submitted_by",
        "approved_by",
        "rejected_by",
        "rejection_reason",
    ];

    /**
     * Get the user who submitted the request
     */
    public function submitter()
    {
        return $this->belongsTo(User::class, "submitted_by");
    }

    /**
     * Get the admin who approved the request
     */
    public function approver()
    {
        return $this->belongsTo(User::class, "approved_by");
    }

    /**
     * Get the admin who rejected the request
     */
    public function rejecter()
    {
        return $this->belongsTo(User::class, "rejected_by");
    }
}
