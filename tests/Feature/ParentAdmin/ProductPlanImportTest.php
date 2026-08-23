<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function parentImportFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Import Parent', 'slug' => 'import-parent']);
    $admin = ParentAdmin::create([
        'parent_business_id' => $parent->id,
        'name' => 'Import Owner',
        'email' => 'import@example.test',
        'password' => 'password',
        'active' => true,
    ]);
    foreach (['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'] as $index => $name) {
        ParentResellerLevel::create([
            'parent_business_id' => $parent->id,
            'name' => $name,
            'position' => $index + 1,
            'status' => 'active',
        ]);
    }
    $product = Product::create(['api_id' => 'import-data', 'product_name' => 'DATA', 'slug' => 'data']);
    $category = ProductPlanCategory::create([
        'api_id' => 'import-mtn',
        'product_id' => $product->id,
        'product_plan_category_name' => 'MTN SME',
    ]);
    $adapter = ProviderConnection::create([
        'name' => 'HTTP Provider',
        'slug' => 'http-provider',
        'adapter' => 'configurable_http',
        'capabilities' => ['services' => ['data'], 'methods' => ['POST'], 'credential_fields' => []],
        'status' => 'active',
    ]);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $adapter->id,
        'name' => 'Primary OresamSub',
        'status' => 'active',
        'approval_status' => 'approved',
    ]);

    return compact('parent', 'admin', 'category', 'connection');
}

function importWorkbook(array $row): UploadedFile
{
    $headers = [
        'Plan Name', 'Category', 'Provider Connection', 'Provider Plan ID', 'Internal Reference',
        'Provider Cost', 'Active', 'Affiliate Visible', 'Public Visible', 'Pricing Mode',
        'Data Size MB', 'Validity Days', 'Basic Price', 'Basic Max Profit', 'Bronze Price',
        'Bronze Max Profit', 'Silver Price', 'Silver Max Profit', 'Gold Price', 'Gold Max Profit',
        'Diamond Price', 'Diamond Max Profit', 'Platinum Price', 'Platinum Max Profit',
    ];
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Product Plans');
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray(array_map(fn ($header) => $row[$header] ?? null, $headers), null, 'A2');
    $path = tempnam(sys_get_temp_dir(), 'plan-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile($path, 'plans.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('downloads a parent-specific xlsx template with lookup dropdowns', function () {
    $fixture = parentImportFixture();

    $response = $this->actingAs($fixture['admin'], 'parent_admin')
        ->get('/parent-admin/product-plans/import/template')
        ->assertOk()
        ->assertDownload('import-parent-product-plans.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'downloaded-template-').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $workbook = IOFactory::load($path);

    expect($workbook->getSheetNames())->toContain('Instructions', 'Product Plans', 'Lookups')
        ->and($workbook->getSheetByName('Lookups')->getSheetState())->toBe('hidden')
        ->and($workbook->getSheetByName('Lookups')->getCell('A2')->getValue())->toContain('DATA')
        ->and($workbook->getSheetByName('Lookups')->getCell('C2')->getValue())->toContain('Primary OresamSub')
        ->and($workbook->getSheetByName('Product Plans')->getCell('B2')->getDataValidation()->getType())->toBe('list')
        ->and($workbook->getSheetByName('Product Plans')->getCell('C2')->getDataValidation()->getType())->toBe('list');
});

it('previews new and existing xlsx rows then updates without creating a duplicate', function () {
    $fixture = parentImportFixture();
    $categoryLabel = 'DATA · MTN SME';
    $connectionLabel = 'Primary OresamSub · HTTP Provider';
    $existing = ProductPlan::create([
        'parent_business_id' => $fixture['parent']->id,
        'product_plan_category_id' => $fixture['category']->id,
        'product_plan_name' => 'Old 1GB',
        'cost_price' => 500,
    ]);
    $existing->providerRoutes()->create([
        'parent_business_id' => $fixture['parent']->id,
        'parent_provider_connection_id' => $fixture['connection']->id,
        'provider_plan_id' => 'MTN-1GB',
        'priority' => 1,
        'active' => true,
    ]);
    $file = importWorkbook([
        'Plan Name' => 'MTN SME 1GB',
        'Category' => $categoryLabel,
        'Provider Connection' => $connectionLabel,
        'Provider Plan ID' => 'MTN-1GB',
        'Internal Reference' => 'MTN-SME-1GB',
        'Provider Cost' => 535,
        'Active' => 'Yes',
        'Affiliate Visible' => 'Yes',
        'Public Visible' => 'No',
        'Pricing Mode' => 'Flat',
        'Data Size MB' => 1000,
        'Validity Days' => 30,
        'Basic Price' => 565,
        'Bronze Price' => 560,
        'Silver Price' => 555,
        'Gold Price' => 550,
        'Diamond Price' => 545,
        'Platinum Price' => 540,
    ]);

    $preview = $this->actingAs($fixture['admin'], 'parent_admin')
        ->post('/parent-admin/product-plans/import/preview', ['plans_file' => $file])
        ->assertOk()
        ->assertViewHas('rows', fn ($rows) => count($rows) === 1
            && $rows[0]['classification'] === 'update'
            && $rows[0]['existing_plan_id'] === $existing->id);

    $token = $preview->viewData('token');
    $this->actingAs($fixture['admin'], 'parent_admin')
        ->post('/parent-admin/product-plans/import/confirm', ['token' => $token])
        ->assertRedirect('/parent-admin/product-plans');

    expect(ProductPlan::where('parent_business_id', $fixture['parent']->id)->count())->toBe(1)
        ->and($existing->fresh()->product_plan_name)->toBe('MTN SME 1GB')
        ->and((float) $existing->fresh()->cost_price)->toBe(535.0)
        ->and($existing->parentPrices()->count())->toBe(6);
});

it('rejects workbook labels that are not available to the signed-in parent', function () {
    $fixture = parentImportFixture();
    $file = importWorkbook([
        'Plan Name' => 'Unsafe Plan',
        'Category' => 'DATA · MTN SME',
        'Provider Connection' => 'Another Parent Connection',
        'Provider Plan ID' => 'UNSAFE-1',
        'Provider Cost' => 100,
        'Basic Price' => 110,
        'Bronze Price' => 110,
        'Silver Price' => 110,
        'Gold Price' => 110,
        'Diamond Price' => 110,
        'Platinum Price' => 110,
    ]);

    $this->actingAs($fixture['admin'], 'parent_admin')
        ->post('/parent-admin/product-plans/import/preview', ['plans_file' => $file])
        ->assertOk()
        ->assertViewHas('errors', fn ($errors) => isset($errors[2])
            && str_contains($errors[2], 'approved connection owned by this parent'))
        ->assertViewHas('token', null);
});
