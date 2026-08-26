<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_adapters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter_key')->unique();
            $table->json('capabilities')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->foreignId('provider_adapter_id')->nullable()->after('id')->constrained('provider_adapters')->restrictOnDelete();
            $table->text('base_url')->nullable()->after('capabilities');
            $table->text('website_url')->nullable()->after('base_url');
            $table->text('documentation_url')->nullable()->after('website_url');
            $table->json('settings')->nullable()->after('documentation_url');
            $table->unsignedInteger('adapter_version')->nullable()->after('settings');
        });

        $now = now();
        DB::table('provider_connections')->orderBy('id')->get()->each(function ($connection) use ($now): void {
            $adapterId = DB::table('provider_adapters')->insertGetId([
                'name' => $connection->name,
                'slug' => $connection->slug,
                'adapter_key' => $connection->adapter,
                'capabilities' => $connection->capabilities,
                'settings' => null,
                'version' => 1,
                'status' => $connection->status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('provider_connections')->where('id', $connection->id)->update([
                'provider_adapter_id' => $adapterId,
                'adapter_version' => 1,
            ]);
        });

        Schema::table('parent_provider_connections', function (Blueprint $table): void {
            $table->foreignId('provider_adapter_id')->nullable()->after('parent_business_id')->constrained('provider_adapters')->restrictOnDelete();
            $table->string('request_type')->default('existing')->after('provider_connection_id')->index();
            $table->string('proposed_provider_name')->nullable()->after('name');
            $table->text('proposed_base_url')->nullable()->after('proposed_provider_name');
            $table->text('proposed_documentation_url')->nullable()->after('proposed_base_url');
            $table->text('discovery_notes')->nullable()->after('proposed_documentation_url');
        });

        DB::table('provider_connections')->whereNotNull('provider_adapter_id')->get(['id', 'provider_adapter_id'])
            ->each(fn ($provider) => DB::table('parent_provider_connections')
                ->where('provider_connection_id', $provider->id)
                ->update(['provider_adapter_id' => $provider->provider_adapter_id]));

        Schema::table('parent_provider_connections', function (Blueprint $table): void {
            $table->unsignedBigInteger('provider_connection_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('parent_provider_connections', function (Blueprint $table): void {
            $table->unsignedBigInteger('provider_connection_id')->nullable(false)->change();
            $table->dropForeign(['provider_adapter_id']);
            $table->dropColumn(['provider_adapter_id', 'request_type', 'proposed_provider_name', 'proposed_base_url', 'proposed_documentation_url', 'discovery_notes']);
        });
        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->dropForeign(['provider_adapter_id']);
            $table->dropColumn(['provider_adapter_id', 'base_url', 'website_url', 'documentation_url', 'settings', 'adapter_version']);
        });
        Schema::dropIfExists('provider_adapters');
    }
};
