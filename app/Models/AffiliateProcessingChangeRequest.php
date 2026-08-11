<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateProcessingChangeRequest extends Model
{
    protected $guarded = [];
    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'reviewed_at' => 'datetime'];
    }

    public function profile(): BelongsTo { return $this->belongsTo(AffiliateProcessingProfile::class, 'affiliate_processing_profile_id'); }
    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function parentBusiness(): BelongsTo { return $this->belongsTo(ParentBusiness::class); }
}
