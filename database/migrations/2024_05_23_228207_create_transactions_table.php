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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->string('api_id');
            $table->string('txn_reference')->unique()->nullable(); 
            $table->string('affiliate_product_plan_id')->constrained(table: 'affiliate_product_plans');
            $table->foreignId('user_id')->constrained(table: 'users');
            $table->string('failure_notification')->default('0');
            $table->string('coupon_code_id')->nullable();
            $table->string('validation_address')->nullable();
            $table->string('refund_reason')->nullable();
            $table->string('reprocess_automation_id')->nullable();    
            $table->string('automation_plan_amount')->nullable()->comment('some vendors do others dont');    
            $table->string('first_automation_balance_before')->nullable()->comment('some vendors do others dont');    
            $table->string('first_automation_balance_after')->nullable()->comment('some vendors do others dont');    
            $table->string('transaction_category')->nullable()->comment('Options: data, airtime, bills, cable subscription etc');
            $table->longText('extra_info')->nullable();    
            $table->boolean('is_marketer')->default(false);
            $table->string('status')->default(0)->nullable()->comment('status of transaction: 1:success, 0:pending(default), -1:failed, 2:refunded, 3:processing');
            $table->string('locked_for_manual_processing')->nullable();
            $table->string('set_for_manual')->default(0);
            $table->integer('retry_count')->default(0);
            $table->string('manually_processed_by')->nullable();
            $table->string('wallet_category')->comment('data_wallet/main_wallet');
            $table->string('phone_number')->comment('phone number that benefits')->nullable();
            $table->string('smart_card_number')->comment('iuc number that benefits that benefits')->nullable();
            $table->string('metre_number')->comment('metre number that benefits')->nullable();
            $table->string('cable_tv_slots')->default('1')->comment('no of slots bought')->nullable();
            $table->string('utility_slots')->default('1')->comment('no of slots bought')->nullable();
            $table->string('amount')->comment('amount that was bought');
            $table->string('balance_before');
            $table->string('balance_after');
            $table->string('description');
            $table->string('discounted_amount')->nullable();
            $table->string('referral_commission_value')->nullable();
            $table->longText('user_screen_message')->nullable();
            $table->longText('admin_screen_message')->nullable();
            $table->longText('referral_commission_status')->nullable()->default(0)->comment('1 - given 0 - not given');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
