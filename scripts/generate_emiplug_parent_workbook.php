<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require dirname(__DIR__).'/vendor/autoload.php';

$templatePath = '/Users/mac/Downloads/emiplug-parent-product-plans.xlsx';
$sqlPath = '/Users/mac/Downloads/product_plans (14).sql';
$outputPath = dirname(__DIR__).'/emiplug-parent-product-plans-ready.xlsx';

$sql = file_get_contents($sqlPath);
if ($sql === false || ! preg_match('/INSERT INTO `product_plans` \((.*?)\) VALUES\s*(.*?);\s*\n\s*--\s*\n-- Indexes/s', $sql, $match)) {
    throw new RuntimeException('The product plan INSERT statement could not be read.');
}

$columns = array_map(static fn (string $column): string => trim($column, " `\t\n\r"), explode(',', $match[1]));
$records = [];
foreach (parseSqlTuples($match[2]) as $values) {
    if (count($values) !== count($columns)) {
        throw new RuntimeException('A product plan row has an unexpected column count.');
    }
    $records[] = array_combine($columns, $values);
}

$active = array_values(array_filter($records, static fn (array $row): bool => (string) $row['active_status'] === '1' && filledValue($row['automation_product_plan_id'])));
usort($active, static fn (array $a, array $b): int => strcmp((string) $b['updated_at'], (string) $a['updated_at']));

$selected = [];
$duplicates = [];
foreach ($active as $row) {
    $providerPlanId = trim((string) $row['automation_product_plan_id']);
    if (isset($selected[strtolower($providerPlanId)])) {
        $duplicates[] = $row;
        continue;
    }
    $selected[strtolower($providerPlanId)] = $row;
}
$selected = array_values($selected);
usort($selected, static fn (array $a, array $b): int => serviceOrder($a) <=> serviceOrder($b) ?: strcasecmp((string) $a['product_plan_name'], (string) $b['product_plan_name']));

$workbook = IOFactory::load($templatePath);
$plans = $workbook->getSheetByName('Product Plans') ?? throw new RuntimeException('Product Plans sheet is missing.');
$lookups = $workbook->getSheetByName('Lookups') ?? throw new RuntimeException('Lookups sheet is missing.');
$headers = array_values($plans->rangeToArray('A1:X1', null, true, true, false)[0]);
$categoryLabels = array_values(array_filter($lookups->rangeToArray('A2:A'.$lookups->getHighestRow(), null, true, true, false), static fn (array $row): bool => filledValue($row[0])));
$categoryLabels = array_column($categoryLabels, 0);
$connection = (string) $lookups->getCell('C2')->getValue();

if ($connection === '') {
    throw new RuntimeException('The template does not contain a default provider connection.');
}

$plans->getStyle('A2:X'.$plans->getHighestRow());
$plans->getParent()?->getActiveSheet();
for ($row = 2; $row <= $plans->getHighestRow(); $row++) {
    foreach (range('A', 'X') as $column) {
        $plans->setCellValue($column.$row, null);
    }
}
$outputRows = [];
foreach ($selected as $source) {
    $outputRows[] = workbookRow($source, $headers, $categoryLabels, $connection);
}
$plans->fromArray($outputRows, null, 'A2', true);
copyTemplateFormatting($plans, count($outputRows));

$audit = $workbook->getSheetByName('Import Audit');
if ($audit === null) {
    $audit = new Worksheet($workbook, 'Import Audit');
    $workbook->addSheet($audit);
}
$audit->fromArray([
    ['Summary', 'Count'],
    ['Active SQL rows with provider IDs', count($active)],
    ['Import-ready unique plans', count($outputRows)],
    ['Skipped duplicate provider IDs', count($duplicates)],
    [],
    ['Skipped Plan Name', 'Provider Plan ID', 'Internal/API ID', 'Updated At', 'Reason'],
], null, 'A1');
$auditRow = 7;
foreach ($duplicates as $duplicate) {
    $audit->fromArray([[
        $duplicate['product_plan_name'],
        $duplicate['automation_product_plan_id'],
        $duplicate['api_id'],
        $duplicate['updated_at'],
        'A newer active SQL row uses the same provider ID on the default connection.',
    ]], null, 'A'.$auditRow++);
}
$audit->getStyle('A1:B1')->getFont()->setBold(true);
$audit->getStyle('A6:E6')->getFont()->setBold(true);
$audit->freezePane('A7');
foreach (range('A', 'E') as $column) {
    $audit->getColumnDimension($column)->setAutoSize(true);
}

(new Xlsx($workbook))->save($outputPath);
echo json_encode([
    'output' => $outputPath,
    'source_rows' => count($records),
    'active_rows' => count($active),
    'import_rows' => count($outputRows),
    'skipped_duplicates' => count($duplicates),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

/** @return list<list<mixed>> */
function parseSqlTuples(string $valuesSql): array
{
    $rows = [];
    $row = [];
    $token = '';
    $inTuple = false;
    $inString = false;
    $escaped = false;
    $length = strlen($valuesSql);

    for ($i = 0; $i < $length; $i++) {
        $character = $valuesSql[$i];
        if (! $inTuple) {
            if ($character === '(') {
                $inTuple = true;
                $row = [];
                $token = '';
            }
            continue;
        }
        if ($inString) {
            if ($escaped) {
                $token .= match ($character) {
                    'n' => "\n", 'r' => "\r", 't' => "\t", default => $character,
                };
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === "'") {
                if ($i + 1 < $length && $valuesSql[$i + 1] === "'") {
                    $token .= "'";
                    $i++;
                } else {
                    $inString = false;
                }
            } else {
                $token .= $character;
            }
            continue;
        }
        if ($character === "'") {
            $inString = true;
        } elseif ($character === ',') {
            $row[] = sqlValue($token);
            $token = '';
        } elseif ($character === ')') {
            $row[] = sqlValue($token);
            $rows[] = $row;
            $inTuple = false;
            $token = '';
        } else {
            $token .= $character;
        }
    }

    return $rows;
}

function sqlValue(string $token): mixed
{
    $value = trim($token);
    return strtoupper($value) === 'NULL' ? null : $value;
}

function workbookRow(array $source, array $headers, array $categoryLabels, string $connection): array
{
    $service = service($source);
    $providerCost = normalizedProviderCost($source, $service);
    $prices = normalizedPrices($source, $service, $providerCost);
    $values = [
        'Plan Name' => trim((string) $source['product_plan_name']),
        'Category' => categoryFor($source, $categoryLabels, $service),
        'Provider Connection' => $connection,
        'Provider Plan ID' => trim((string) $source['automation_product_plan_id']),
        'Internal Reference' => 'emiplug-'.trim((string) $source['api_id']),
        'Provider Cost' => $providerCost,
        'Active' => 'Yes',
        'Affiliate Visible' => (string) $source['visibility'] === '1' ? 'Yes' : 'No',
        'Public Visible' => (string) $source['public_visibility'] === '1' ? 'Yes' : 'No',
        'Pricing Mode' => in_array($service, ['airtime', 'electricity'], true) ? 'Percentage' : 'Flat',
        'Data Size MB' => $service === 'data' && is_numeric($source['data_size_in_mb']) ? (float) $source['data_size_in_mb'] : null,
        'Validity Days' => in_array($service, ['data', 'cable'], true) && is_numeric($source['validity_in_days']) ? (float) $source['validity_in_days'] : null,
    ];
    foreach (['Basic11', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'] as $index => $level) {
        $values[$level.' Price'] = $prices[$index];
        $values[$level.' Max Profit'] = null;
    }

    return array_map(static fn (string $header): mixed => $values[$header] ?? null, $headers);
}

function service(array $row): string
{
    $name = strtoupper((string) $row['product_plan_name']);
    if (str_contains($name, 'PREPAID') || str_contains($name, 'POSTPAID') || str_contains($name, 'ELECTRIC')) {
        return 'electricity';
    }
    if (str_contains($name, 'GOTV') || str_contains($name, 'DSTV') || str_contains($name, 'STARTIMES')) {
        return 'cable';
    }
    if (str_contains($name, 'TOP UP') || str_contains($name, 'AIRTIME')) {
        return 'airtime';
    }
    return 'data';
}

function serviceOrder(array $row): int
{
    return array_search(service($row), ['data', 'airtime', 'cable', 'electricity'], true) ?: 0;
}

function categoryFor(array $row, array $labels, string $service): string
{
    $name = strtoupper((string) $row['product_plan_name']);
    $network = strtoupper(trim((string) $row['network']));
    $needles = match ($service) {
        'electricity' => ['UTILITY BILLS', 'PREPAID'],
        'cable' => ['CABLE SUBSCRIPTION', str_contains($name, 'GOTV') ? 'GOTV' : (str_contains($name, 'DSTV') ? 'DSTV' : 'STARTIMES')],
        'airtime' => ['AIRTIME', $network ?: (str_contains($name, '9MOBILE') ? '9MOBILE' : 'MTN')],
        default => ['DATA', $network ?: detectNetwork($name)],
    };

    $candidates = array_values(array_filter($labels, static function (string $label) use ($needles): bool {
        $upper = strtoupper($label);
        return array_reduce($needles, static fn (bool $carry, string $needle): bool => $carry && str_contains($upper, $needle), true);
    }));
    if ($service === 'data' && $candidates !== []) {
        foreach ([
            'AWOOF' => 'AWOOF', 'HOT' => 'AWOOF', 'GIFT' => 'GIFTING', 'SHARE' => 'SHARE', 'CG' => 'CG', 'DASH' => 'DASH', 'SME' => 'SME',
        ] as $sourceNeedle => $categoryNeedle) {
            if (str_contains($name, $sourceNeedle)) {
                $matched = array_values(array_filter($candidates, static fn (string $label): bool => str_contains(strtoupper($label), $categoryNeedle)));
                if ($matched !== []) {
                    return $matched[0];
                }
            }
        }
    }
    if ($service === 'airtime' && $candidates !== []) {
        $vtu = array_values(array_filter($candidates, static fn (string $label): bool => str_contains(strtoupper($label), 'VTU')));
        return $vtu[0] ?? $candidates[0];
    }
    if ($candidates !== []) {
        return $candidates[0];
    }

    throw new RuntimeException('No template category matches '.$row['product_plan_name'].'.');
}

function normalizedProviderCost(array $row, string $service): float
{
    if ($service === 'electricity') {
        return 1000.00;
    }
    $raw = is_numeric($row['cost_price']) ? (float) $row['cost_price'] : 0.0;
    if ($service !== 'airtime') {
        return round(max(0, $raw), 2);
    }
    if ($raw >= 80 && $raw <= 100) {
        return round($raw * 10, 2);
    }
    if ($raw >= 0 && $raw <= 20) {
        return round(1000 * (1 - ($raw / 100)), 2);
    }
    return round($raw, 2);
}

/** @return list<float> */
function normalizedPrices(array $row, string $service, float $providerCost): array
{
    if ($service === 'electricity') {
        return array_fill(0, 6, 1010.00);
    }
    $prices = [];
    $last = is_numeric($row['default_selling_price']) ? (float) $row['default_selling_price'] : $providerCost + 1;
    for ($level = 1; $level <= 6; $level++) {
        $raw = $row['user_level_'.$level.'_selling_price'] ?? null;
        if (is_numeric($raw)) {
            $last = (float) $raw;
        }
        $price = $service === 'airtime' ? 1000 * (1 - ($last / 100)) : $last;
        $prices[] = round(max($providerCost + 1, $price), 2);
    }
    return $prices;
}

function detectNetwork(string $name): string
{
    foreach (['9MOBILE', 'AIRTEL', 'GLO', 'MTN'] as $network) {
        if (str_contains($name, $network)) {
            return $network;
        }
    }
    return 'MTN';
}

function filledValue(mixed $value): bool
{
    return $value !== null && trim((string) $value) !== '';
}

function copyTemplateFormatting(Worksheet $sheet, int $rows): void
{
    if ($rows === 0) {
        return;
    }
    $templateStyle = $sheet->getStyle('A2:X2');
    for ($row = 2; $row <= $rows + 1; $row++) {
        $sheet->duplicateStyle($templateStyle, 'A'.$row.':X'.$row);
        foreach (range('A', 'X') as $column) {
            $sourceValidation = $sheet->getCell($column.'2')->getDataValidation();
            if ($sourceValidation->getType() !== 'none') {
                $sheet->getCell($column.$row)->setDataValidation(clone $sourceValidation);
            }
        }
    }
    $sheet->setAutoFilter('A1:X'.($rows + 1));
    $sheet->freezePane('A2');
}
