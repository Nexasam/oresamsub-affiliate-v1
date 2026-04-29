<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVirtualAccount extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

       /**
     * each card belongs to a user 
    **/
    public function funding_option()
    {
        return $this->belongsTo(AffiliateFundingOption::class, 'funding_option_id', 'id');
    }

    
}
