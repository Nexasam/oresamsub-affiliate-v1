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
        Schema::create('funding_options', function (Blueprint $table) {
            $table->id();
            $table->string('is_current_option')->default(0);
            $table->string('funding_option_name');
            $table->string('slug')->unique();
            $table->text('biz_bvn')->nullable();
            $table->string('api_public_key')->nullable();
            $table->string('api_secret_key')->nullable();
            $table->string('activation_status');
            $table->string('bank_name')->nullable();
            $table->string('bank_charges')->nullable();
            $table->string('contract_code')->nullable()->comment('This is for flutterwave');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_options');
    }
};
