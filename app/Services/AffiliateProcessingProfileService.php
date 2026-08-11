<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateProcessingChangeRequest;
use App\Models\AffiliateProcessingProfile;
use App\Models\ParentAdmin;
use App\Models\ParentProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliateProcessingProfileService
{
    public function ensure(Affiliate $affiliate): AffiliateProcessingProfile
    {
        if (! $affiliate->parent_business_id) {
            $this->fail('affiliate', 'The affiliate must belong to a parent business.');
        }

        $affiliate->loadMissing('parentBusiness:id,slug');
        $legacy = $affiliate->parentBusiness?->slug === 'oresamsub';

        return AffiliateProcessingProfile::query()->firstOrCreate(
            ['affiliate_id' => $affiliate->id],
            [
                'parent_business_id' => $affiliate->parent_business_id,
                'management_mode' => $legacy ? 'affiliate_managed' : 'parent_managed',
                'processing_engine' => $legacy ? 'legacy_oresamsub' : 'multi_parent',
                'status' => 'active',
            ],
        );
    }

    public function requestChange(Affiliate $affiliate, ParentAdmin $actor, array $data): AffiliateProcessingChangeRequest
    {
        if ((int) $affiliate->parent_business_id !== (int) $actor->parent_business_id) {
            $this->fail('affiliate', 'You cannot change another parent business affiliate.');
        }

        $profile = $this->ensure($affiliate);
        $mode = $data['management_mode'] ?? null;
        $engine = $data['processing_engine'] ?? null;
        $connectionId = $data['parent_provider_connection_id'] ?? null;
        $credentials = array_filter($data['credentials'] ?? [], fn ($value) => filled($value));

        if (! in_array($mode, ['parent_managed', 'affiliate_managed'], true)) {
            $this->fail('management_mode', 'Select a supported processing management mode.');
        }
        if (! in_array($engine, ['multi_parent', 'legacy_oresamsub'], true)) {
            $this->fail('processing_engine', 'Select a supported processing engine.');
        }
        if ($engine === 'legacy_oresamsub' && ($mode !== 'affiliate_managed' || $affiliate->parentBusiness?->slug !== 'oresamsub')) {
            $this->fail('processing_engine', 'The legacy engine is available only to OresamSub affiliate-managed profiles.');
        }

        if ($mode === 'affiliate_managed' && $engine === 'multi_parent') {
            $connection = ParentProviderConnection::query()
                ->whereKey($connectionId)
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('status', 'active')->where('approval_status', 'approved')->first();
            if (! $connection) {
                $this->fail('parent_provider_connection_id', 'Select an active approved connection owned by this affiliate parent.');
            }
            if ($credentials === []) {
                $this->fail('credentials', 'Affiliate-managed processing requires parent-issued credentials.');
            }
        } else {
            $connectionId = null;
            $credentials = [];
        }

        if ($profile->changeRequests()->where('status', 'pending')->exists()) {
            $this->fail('management_mode', 'This affiliate already has a pending processing change.');
        }

        return $profile->changeRequests()->create([
            'parent_business_id' => $affiliate->parent_business_id,
            'affiliate_id' => $affiliate->id,
            'requested_by_parent_admin_id' => $actor->id,
            'requested_management_mode' => $mode,
            'requested_processing_engine' => $engine,
            'requested_parent_provider_connection_id' => $connectionId,
            'credentials' => $credentials ?: null,
            'status' => 'pending',
        ]);
    }

    public function approve(AffiliateProcessingChangeRequest $request, Admin $reviewer): AffiliateProcessingProfile
    {
        return DB::transaction(function () use ($request, $reviewer) {
            $request = AffiliateProcessingChangeRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                $this->fail('status', 'This processing change has already been reviewed.');
            }

            $profile = AffiliateProcessingProfile::query()->lockForUpdate()->findOrFail($request->affiliate_processing_profile_id);
            $profile->update([
                'management_mode' => $request->requested_management_mode,
                'processing_engine' => $request->requested_processing_engine,
                'parent_provider_connection_id' => $request->requested_parent_provider_connection_id,
                'credentials' => $request->credentials,
                'status' => 'active',
            ]);
            $request->update(['status' => 'approved', 'reviewed_by_admin_id' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => null]);

            return $profile->fresh();
        });
    }

    public function reject(AffiliateProcessingChangeRequest $request, Admin $reviewer, string $reason): AffiliateProcessingChangeRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reason) {
            $request = AffiliateProcessingChangeRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                $this->fail('status', 'This processing change has already been reviewed.');
            }
            $request->update(['status' => 'rejected', 'reviewed_by_admin_id' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => trim($reason)]);

            return $request->fresh();
        });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
