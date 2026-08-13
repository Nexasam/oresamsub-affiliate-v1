<?php

namespace App\Services\Onboarding;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateUserPlan;
use App\Models\ParentBusiness;
use App\Models\Transaction;
use App\Models\User;

class OnboardingChecklistService
{
    public function forParent(ParentBusiness $parent): array
    {
        $affiliateIds = $parent->affiliates()->pluck('id');
        $steps = [
            $this->step('Business profile', filled($parent->name) && filled($parent->slug), 'Confirm the business identity used across its workspace.', route('parent-admin.dashboard')),
            $this->step('Six reseller levels', $parent->resellerLevels()->where('status', 'active')->count() === 6, 'Create the six acquisition-price levels affiliates can be assigned to.', route('parent-admin.pricing.index')),
            $this->step('Approved provider connection', $parent->providerConnections()->where('status', 'active')->where('approval_status', 'approved')->exists(), 'Add API credentials and obtain platform approval.', route('parent-admin.provider-connections.index')),
            $this->step('Product plans and routes', $parent->productPlans()->whereHas('providerRoutes', fn ($q) => $q->where('active', true))->exists(), 'Create plans and map each active plan to a provider.', route('parent-admin.product-plans.index')),
            $this->step('Reseller pricing', $parent->productPlans()->whereHas('parentPrices')->exists(), 'Set acquisition prices or service defaults for reseller levels.', route('parent-admin.pricing.index')),
            $this->step('Funding provider', $parent->fundingProviders()->where('active', true)->exists(), 'Enable at least one approved funding provider.', route('parent-admin.funding-providers.index')),
            $this->step('Approved affiliate', $parent->affiliates()->exists(), 'Submit an affiliate and wait for platform approval.', route('parent-admin.affiliates.index')),
            $this->step('Affiliate setup', AffiliateProductPlan::withoutGlobalScope('affiliate')->whereIn('affiliate_id', $affiliateIds)->exists(), 'Generate the affiliate catalogue and confirm its customer-facing plan setup.', route('parent-admin.operations.index')),
            $this->step('Affiliate settlement wallet', $parent->affiliates()->whereHas('settlementWallet')->exists(), 'Prepare a settlement wallet for an affiliate.', route('parent-admin.settlement-wallets.index')),
            $this->step('Successful test transaction', Transaction::withoutGlobalScope('affiliate')->where(function ($query) use ($parent, $affiliateIds) {
                $query->where('parent_business_id', $parent->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('parent_business_id')->whereIn('affiliate_id', $affiliateIds));
            })->where('status', 1)->exists(), 'Complete one low-value end-to-end purchase.', route('parent-admin.transactions.index')),
        ];

        return $this->summary($steps);
    }

    public function forAffiliate(Affiliate $affiliate): array
    {
        $steps = [
            $this->step('Business profile', filled($affiliate->name) && filled($affiliate->domain_url), 'Confirm the affiliate name and domain.', route('affiliate.edit')),
            $this->step('Customer reseller plans', AffiliateUserPlan::query()->where('affiliate_id', $affiliate->id)->count() > 0, 'Generate or configure customer pricing levels.', route('admin.reseller_plans.index')),
            $this->step('Product catalogue', AffiliateProductPlan::query()->where('affiliate_id', $affiliate->id)->exists(), 'Ensure parent plans have been made available to this affiliate.', route('admin.product_plans.index')),
            $this->step('Funding configuration', $affiliate->fundingProviderConfigurations()->where('active', true)->exists(), 'Configure credentials and banks for customer funding.', route('admin.affiliate-funding-providers.index')),
            $this->step('Settlement funding account', $affiliate->settlementVirtualAccounts()->exists(), 'Generate the business virtual account used to fund settlement.', route('admin.settlement-funding.index')),
            $this->step('First customer', User::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->whereHas('role', fn ($q) => $q->where('role_name', 'User'))->exists(), 'Create or register the first customer.', route('admin.users.index')),
            $this->step('Successful transaction', Transaction::query()->where('affiliate_id', $affiliate->id)->where('status', 1)->exists(), 'Complete one low-value purchase.', route('admin.transactions.index')),
        ];

        return $this->summary($steps);
    }

    private function step(string $name, bool $complete, string $description, string $url): array
    {
        return compact('name', 'complete', 'description', 'url');
    }

    private function summary(array $steps): array
    {
        $completed = collect($steps)->where('complete', true)->count();
        return ['steps' => $steps, 'completed' => $completed, 'total' => count($steps), 'percentage' => count($steps) ? (int) round($completed / count($steps) * 100) : 100];
    }
}
