<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_name',
        'brand_tagline',
        'primary_color',
        'secondary_color',
        'logo_path',
        'payment_details',
        'updated_by',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'updated_by' => 'integer',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}