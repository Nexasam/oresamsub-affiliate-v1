<?php

namespace App\Models;

use App\Models\AdminColorSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Affiliate extends Model
{
    //on creating an ffiliate, something like the plans, categories and even product plans should be created or a checklist that ensures all those are created
    use HasFactory;
    protected $guarded = [];

    public function site_colors(){
        return $this->hasMany(AdminColorSetting::class,'id','affiliate_id');
    }
}
