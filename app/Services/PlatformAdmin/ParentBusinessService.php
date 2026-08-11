<?php

namespace App\Services\PlatformAdmin;

use App\Models\ParentBusiness;
use Illuminate\Support\Facades\DB;

class ParentBusinessService
{
    public const LEVEL_NAMES = ['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'];

    public function create(array $data): ParentBusiness
    {
        return DB::transaction(function () use ($data) {
            $parent = ParentBusiness::create($data['business']);
            $parent->parentAdmins()->create($data['admin']);

            foreach (self::LEVEL_NAMES as $index => $name) {
                $parent->resellerLevels()->create([
                    'name' => $name,
                    'position' => $index + 1,
                    'status' => 'active',
                ]);
            }

            return $this->hydrate($parent);
        });
    }

    public function hydrate(ParentBusiness $parent): ParentBusiness
    {
        return $parent->load([
            'parentAdmins' => fn ($query) => $query->orderBy('id'),
        ])->loadCount(['affiliates', 'providerConnections', 'resellerLevels']);
    }

    public function present(ParentBusiness $parent): array
    {
        return [
            'id' => $parent->id,
            'name' => $parent->name,
            'slug' => $parent->slug,
            'contact_email' => $parent->contact_email,
            'contact_phone' => $parent->contact_phone,
            'status' => $parent->status,
            'created_at' => $parent->created_at,
            'admins' => $parent->parentAdmins->map(fn ($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'active' => $admin->active,
                'must_change_password' => $admin->must_change_password,
                'last_login_at' => $admin->last_login_at,
            ])->values()->all(),
            'affiliate_count' => $parent->affiliates_count,
            'provider_connection_count' => $parent->provider_connections_count,
            'level_count' => $parent->reseller_levels_count,
        ];
    }
}
