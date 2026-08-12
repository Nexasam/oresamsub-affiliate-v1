<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Services\MultiParent\ResetAffiliateTenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ResetAffiliateForParentTest extends Command
{
    protected $signature = 'multi-parent:reset-test-affiliate
        {--domain= : Exact existing affiliate domain}
        {--parent-name= : New parent business name}
        {--parent-slug= : New parent business slug}
        {--parent-admin-name= : New parent administrator name}
        {--parent-admin-email= : New parent administrator email}
        {--affiliate-name= : Fresh affiliate name}
        {--affiliate-slug= : Fresh affiliate slug}
        {--execute : Permanently purge and recreate the tenant}
        {--confirm-domain= : Must exactly match --domain when executing}';

    protected $description = 'Preview or reset one legacy affiliate as a fresh new-parent production test tenant';

    public function handle(ResetAffiliateTenantService $service): int
    {
        $domain = $this->normalizeDomain((string) $this->option('domain'));
        $matches = Affiliate::whereRaw('LOWER(domain_url) = ?', [$domain])->get();
        if ($domain === '' || $matches->count() !== 1) {
            $this->error('The normalized domain must resolve exactly one affiliate.');
            return self::FAILURE;
        }
        $affiliate = $matches->first()->load('parentBusiness');
        if ($affiliate->parentBusiness?->slug !== 'oresamsub') {
            $this->error('Safety refusal: the target is not an OresamSub legacy affiliate.');
            return self::FAILURE;
        }

        $this->table(['Table', 'Rows'], collect($service->inventory($affiliate))->map(fn ($count, $table) => [$table, $count])->values()->all());
        if (! $this->option('execute')) {
            $this->warn('DRY RUN ONLY. No records were changed.');
            return self::SUCCESS;
        }
        if ($this->normalizeDomain((string) $this->option('confirm-domain')) !== $domain) {
            $this->error('--confirm-domain must exactly match the normalized --domain.');
            return self::FAILURE;
        }

        $password = (string) config('parent_businesses.reset_test_affiliate.parent_admin_password');
        if ($password === '' && $this->input->isInteractive()) {
            $password = (string) $this->secret('New parent administrator password');
        }
        $input = [
            'domain' => $domain, 'parent_name' => trim((string) $this->option('parent-name')),
            'parent_slug' => strtolower(trim((string) $this->option('parent-slug'))),
            'parent_admin_name' => trim((string) $this->option('parent-admin-name')),
            'parent_admin_email' => strtolower(trim((string) $this->option('parent-admin-email'))),
            'affiliate_name' => trim((string) $this->option('affiliate-name')),
            'affiliate_slug' => strtolower(trim((string) $this->option('affiliate-slug'))), 'password' => $password,
        ];
        $validator = Validator::make($input, [
            'domain' => ['required'], 'parent_name' => ['required', 'max:255'],
            'parent_slug' => ['required', 'alpha_dash', 'max:100'],
            'parent_admin_name' => ['required', 'max:255'], 'parent_admin_email' => ['required', 'email:rfc'],
            'affiliate_name' => ['required', 'max:255'], 'affiliate_slug' => ['required', 'alpha_dash', 'max:100'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);
        if ($validator->fails() || ParentBusiness::where('slug', $input['parent_slug'])->exists() || ParentAdmin::where('email', $input['parent_admin_email'])->exists()) {
            $this->error($validator->errors()->first() ?: 'The new parent slug or administrator email already exists.');
            return self::FAILURE;
        }

        try {
            $fresh = $service->reset($affiliate, $input);
            $this->info("Fresh affiliate {$fresh->id} created for parent {$fresh->parentBusiness->name}.");
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#i', '', trim($domain));
        return strtolower(rtrim(preg_replace('/^www\./i', '', $domain), '/'));
    }
}
