<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('affiliate_plan_id')->default(1);
            $table->string('address')->nullable();
            $table->string('ip_address')->unique();
            $table->string('domain_url')->nullable();
            $table->string('contact_phone')->unique();
            $table->string('contact_email');
            $table->string('parent_plan_level')->default(1); //lowest plan
            $table->string('parent_key')->unique(); //this is the parent key to connect to parent website.
            $table->string('parent_email')->unique(); //this is what connects to the parent website.
            $table->string('activation_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
