<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductPlan;
use App\Models\AffiliateProductPlan;

class SyncAffiliateProductPlanApiIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:affiliate-product-plan-api-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync api_id from product_plans into affiliate_product_plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updated = 0;

        AffiliateProductPlan::chunk(500, function ($plans) use (&$updated) {

            foreach ($plans as $affiliatePlan) {

                $productPlan = ProductPlan::find(
                    $affiliatePlan->product_plan_id
                );

                if (!$productPlan) {
                    $this->warn(
                        "ProductPlan not found for AffiliateProductPlan ID: {$affiliatePlan->id}"
                    );

                    continue;
                }

                $affiliatePlan->update([
                    'api_id' => $productPlan->api_id
                ]);

                $updated++;

                $this->info(
                    "Updated AffiliateProductPlan ID {$affiliatePlan->id}"
                );
            }
        });

        $this->info("Done. Total updated: {$updated}");

        return Command::SUCCESS;
    }
}