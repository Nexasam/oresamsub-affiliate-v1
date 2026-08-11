<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateProcessingProfile extends Model
{
    protected $guarded = [];
    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array'];
    }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function parentBusiness(): BelongsTo { return $this->belongsTo(ParentBusiness::class); }
    public function parentProviderConnection(): BelongsTo { return $this->belongsTo(ParentProviderConnection::class); }
    public function changeRequests(): HasMany { return $this->hasMany(AffiliateProcessingChangeRequest::class); }
}
