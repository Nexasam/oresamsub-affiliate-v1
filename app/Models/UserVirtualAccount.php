<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function affiliateFundingProviderConfig(): BelongsTo
    {
        return $this->belongsTo(AffiliateFundingProviderConfig::class);
    }

    /**
     * Resolve the charge shown beside an account across both funding schemas.
     */
    public function fundingChargeDetails(): ?array
    {
        $legacyBank = $this->funding_option?->bank_codes
            ?->first(fn ($bank) => ! $this->bank_code || strcasecmp((string) $bank->bank_code, (string) $this->bank_code) === 0)
            ?? $this->funding_option?->bank_codes?->first();

        if ($legacyBank) {
            $type = strtolower((string) $legacyBank->rate_category) === 'percent' ? 'percentage' : 'flat';
            $value = number_format((float) $legacyBank->bank_charges, 2, '.', '');

            return [
                'type' => $type,
                'value' => $value,
                'cap' => null,
                'display' => $type === 'percentage' ? "{$value}%" : "₦{$value}",
                'description' => $legacyBank->short_description,
            ];
        }

        $normalizedBank = $this->affiliateFundingProviderConfig?->banks
            ?->first(fn ($bank) => ! $this->bank_code || strcasecmp((string) $bank->parentBank?->bank_code, (string) $this->bank_code) === 0);

        if (! $normalizedBank) {
            return null;
        }

        $type = $normalizedBank->rate_type === 'percentage' ? 'percentage' : 'flat';
        $value = number_format((float) $normalizedBank->rate_value, 2, '.', '');
        $cap = $normalizedBank->percentage_cap !== null
            ? number_format((float) $normalizedBank->percentage_cap, 2, '.', '')
            : null;
        $display = $type === 'percentage' ? "{$value}%" : "₦{$value}";
        if ($type === 'percentage' && $cap !== null) {
            $display .= " (capped at ₦{$cap})";
        }

        return [
            'type' => $type,
            'value' => $value,
            'cap' => $cap,
            'display' => $display,
            'description' => $normalizedBank->parentBank?->name,
        ];
    }

    public function dashboardPayload(): array
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'bank_code' => $this->bank_code,
            'charge' => $this->fundingChargeDetails(),
        ];
    }

    
}
