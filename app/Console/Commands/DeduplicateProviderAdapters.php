<?php

namespace App\Console\Commands;

use App\Models\ProviderAdapter;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeduplicateProviderAdapters extends Command
{
    protected $signature = 'provider-adapters:deduplicate {--execute : Relink references and deactivate duplicate adapters}';

    protected $description = 'Preview or consolidate technically equivalent provider adapters without deleting audit history.';

    public function handle(): int
    {
        $groups = ProviderAdapter::query()->orderBy('id')->get()->groupBy(fn (ProviderAdapter $adapter) => $this->fingerprint($adapter))
            ->filter(fn ($adapters) => $adapters->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No duplicate provider adapters were found.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($groups as $adapters) {
            $canonical = $adapters->first();
            foreach ($adapters->skip(1) as $duplicate) {
                $rows[] = [$canonical->id, $canonical->name, $duplicate->id, $duplicate->name];
                if ($this->option('execute')) {
                    $this->consolidate($canonical, $duplicate);
                }
            }
        }

        $this->table(['Canonical ID', 'Canonical adapter', 'Duplicate ID', 'Duplicate adapter'], $rows);
        $this->line($this->option('execute')
            ? 'Duplicate references were relinked and duplicate adapters were deactivated.'
            : 'DRY RUN ONLY. Re-run with --execute after reviewing these groups.');

        return self::SUCCESS;
    }

    private function consolidate(ProviderAdapter $canonical, ProviderAdapter $duplicate): void
    {
        DB::transaction(function () use ($canonical, $duplicate): void {
            DB::table('provider_connections')->where('provider_adapter_id', $duplicate->id)->update([
                'provider_adapter_id' => $canonical->id,
                'adapter' => $canonical->adapter_key,
                'adapter_version' => $canonical->version,
                'updated_at' => now(),
            ]);
            DB::table('parent_provider_connections')->where('provider_adapter_id', $duplicate->id)->update([
                'provider_adapter_id' => $canonical->id,
                'updated_at' => now(),
            ]);
            DB::table('provider_configuration_promotions')->where('provider_adapter_id', $duplicate->id)->update([
                'provider_adapter_id' => $canonical->id,
                'updated_at' => now(),
            ]);
            $duplicate->update(['status' => 'inactive']);
        });
    }

    private function fingerprint(ProviderAdapter $adapter): string
    {
        $name = Str::of($adapter->name)->lower()->replaceMatches('/\s+adapter(?:[_\s-]*\d+)?$/', '')->trim()->toString();

        return hash('sha256', json_encode([
            'name' => $name,
            'capabilities' => $this->sortRecursive($adapter->capabilities ?? []),
            'settings' => $this->sortRecursive($this->genericSettings($adapter->settings ?? [])),
        ]));
    }

    private function genericSettings(array $settings): array
    {
        return collect($settings)->reject(function ($value, $key): bool {
            $key = strtolower((string) $key);

            return $key === 'endpoints' || str_ends_with($key, '_url') || in_array($key, ['base_url', 'endpoint_url'], true);
        })->map(fn ($value) => is_array($value) ? $this->genericSettings($value) : $value)->all();
    }

    private function sortRecursive(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->sortRecursive($item);
            }
        }

        return $value;
    }
}
