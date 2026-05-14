<?php

namespace App\Models;

// use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasAffiliateScope;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;



// implements MustVerifyEmail
class User extends Authenticatable 
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens;
    use HasAffiliateScope;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $primaryKey='id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function user_plan(){
        return $this->belongsTo(AffiliateUserPlan::class,'user_plan_id','id');
    }

    public function affiliate(){
        return $this->belongsTo(Affiliate::class,'affiliate_id','id');
    }

    public function role(){
        return $this->belongsTo(Role::class,'role_id','id');
    }

    public function upline(){
        return $this->belongsTo(User::class,'upline_id','id');
    }

    public function virtual_accounts(){
        return $this->hasMany(UserVirtualAccount::class,'user_id','id');
    }

    public function transactions(){
        return $this->hasMany(Transaction::class,'user_id','id');
    }

    public function latestTransaction()
    {
    return $this->hasOne(Transaction::class)->orderBy('created_at', 'desc');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'upline_id');
    }

    public function generateApiToken()
    {
        $this->api_token = Str::random(60);
        $this->save();
        return $this->api_token;
    }



    // public function getRoleDetailsAttribute(){
    //     return $this->role()->first();
    // }

    
}
