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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->string('title');
            $table->string('position');
            $table->text('description');
            $table->string('status')->default('1');
            $table->string('users')->nullable(); #nullable means all
            $table->string('allowed_pages')->nullable(); #nullable means all
            $table->string('description', 500)->change();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
