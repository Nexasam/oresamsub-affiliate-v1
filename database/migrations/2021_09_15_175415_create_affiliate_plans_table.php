<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_plans', function (Blueprint $table) {
            $table->id();
            $table->string('affiliate_plan_name')->unique();
            $table->string('plan_level')->comment('1 - 10');
            $table->string('visibility')->default(1);
            $table->timestamps();
        });

        // Define the 10 plan names
        $plans = [
            'Bronze',
            'Silver',
            'Gold',
            'Platinum',
            'Diamond',
            'Emerald',
            'Sapphire',
            'Ruby',
            'Elite',
            'Legendary',
        ];

        foreach ($plans as $index => $name) {
            DB::table('affiliate_plans')->insert([
                'affiliate_plan_name' => $name . ' Plan',
                'plan_level' => $index + 1,
                'visibility' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_plans');
    }
};
