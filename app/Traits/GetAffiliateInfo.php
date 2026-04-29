<?php

namespace App\Traits;

trait GetAffiliateInfo{
  
    public function getId(): string
    {
    return session('affiliate')->id;
    }

    public function getName(): string
    {
    return session('affiliate')->name;
    }

    public function getSlug(): string
    {
    return session('affiliate')->slug;
    }

    public function getAffiliatePlanId(): string
    {
        return session('affiliate')->affiliate_plan_id;

    }

}