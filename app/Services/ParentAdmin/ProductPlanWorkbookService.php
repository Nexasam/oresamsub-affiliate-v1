<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProductPlanCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductPlanWorkbookService
{
    public const BASE_HEADERS = [
        'Plan Name', 'Category', 'Provider Connection', 'Provider Plan ID', 'Internal Reference',
        'Provider Cost', 'Active', 'Affiliate Visible', 'Public Visible', 'Pricing Mode',
        'Data Size MB', 'Validity Days',
    ];

    public function workbook(ParentBusiness $parent): Spreadsheet
    {
        [$categories, $connections] = $this->lookups($parent);
        $headers = $this->headers($parent);
        $book = new Spreadsheet;
        $instructions = $book->getActiveSheet()->setTitle('Instructions');
        $plans = $book->createSheet()->setTitle('Product Plans');
        $lookups = $book->createSheet()->setTitle('Lookups');

        $instructions->fromArray([
            ['Product Plan Import — '.$parent->name],
            ['1. Open the Product Plans sheet and complete one plan per row.'],
            ['2. Select Category, Provider Connection and Yes/No values from the dropdowns.'],
            ['3. Provider Plan ID must match the external ID expected by that provider.'],
            ['4. Every reseller price must be greater than Provider Cost. Maximum profit is optional.'],
            ['5. Upload the workbook and review New / Will update classifications before confirming.'],
            [],
            ['Example', 'MTN SME 1GB', 'DATA · MTN · SME', 'Approved provider', 'MTN-1GB', '535', '565…540'],
            ['Airtime note', 'Use percentage pricing and a reference face value consistently with the configured plan.'],
            ['Electricity note', 'Use the provider distribution/meter category and external validation plan ID.'],
        ], null, 'A1');
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $instructions->getColumnDimension('A')->setWidth(90);

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $plans->fromArray($headers, null, 'A1');
        $plans->freezePane('A2')->setAutoFilter("A1:{$lastColumn}1");
        $plans->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $plans->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
        foreach (range(1, count($headers)) as $column) {
            $plans->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth($column <= 5 ? 28 : 18);
        }

        $lookups->fromArray(['Category', 'Category ID', 'Provider Connection', 'Connection ID', 'Boolean', 'Pricing Mode'], null, 'A1');
        foreach ($categories->values() as $index => $category) {
            $lookups->setCellValue('A'.($index + 2), $this->categoryLabel($category));
            $lookups->setCellValue('B'.($index + 2), $category->id);
        }
        foreach ($connections->values() as $index => $connection) {
            $lookups->setCellValue('C'.($index + 2), $this->connectionLabel($connection));
            $lookups->setCellValue('D'.($index + 2), $connection->id);
        }
        $lookups->fromArray([['Yes', 'Flat'], ['No', 'Percentage']], null, 'E2');
        $lookups->setSheetState('hidden');

        $categoryEnd = max(2, $categories->count() + 1);
        $connectionEnd = max(2, $connections->count() + 1);
        foreach (range(2, 501) as $row) {
            $this->dropdown($plans->getCell("B{$row}"), "'Lookups'!\$A\$2:\$A\${$categoryEnd}");
            $this->dropdown($plans->getCell("C{$row}"), "'Lookups'!\$C\$2:\$C\${$connectionEnd}");
            foreach (['G', 'H', 'I'] as $column) {
                $this->dropdown($plans->getCell("{$column}{$row}"), "'Lookups'!\$E\$2:\$E\$3");
            }
            $this->dropdown($plans->getCell("J{$row}"), "'Lookups'!\$F\$2:\$F\$3");
        }

        $book->setActiveSheetIndexByName('Product Plans');

        return $book;
    }

    public function write(Spreadsheet $workbook, string $target): void
    {
        (new Xlsx($workbook))->save($target);
        $workbook->disconnectWorksheets();
    }

    public function rows(UploadedFile $file, ParentBusiness $parent): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv' || $extension === 'txt') {
            return $this->csvRows($file, $parent);
        }

        $book = IOFactory::load($file->getRealPath());
        $sheet = $book->getSheetByName('Product Plans');
        if (! $sheet) {
            throw ValidationException::withMessages(['plans_file' => 'The workbook must contain a Product Plans sheet.']);
        }
        $values = $sheet->toArray(null, true, true, false);
        $book->disconnectWorksheets();

        return $this->tabularRows($values, $this->headers($parent));
    }

    public function headers(ParentBusiness $parent): array
    {
        $headers = self::BASE_HEADERS;
        foreach ($parent->resellerLevels()->where('status', 'active')->orderBy('position')->get() as $level) {
            $headers[] = $level->name.' Price';
            $headers[] = $level->name.' Max Profit';
        }

        return $headers;
    }

    public function categoryLabel(ProductPlanCategory $category): string
    {
        return collect([$category->product?->product_name, $category->network?->network_name, $category->product_plan_category_name])
            ->filter()->unique(fn ($value) => strtolower((string) $value))->implode(' · ');
    }

    public function connectionLabel(ParentProviderConnection $connection): string
    {
        return collect([$connection->name, $connection->providerConnection?->name])
            ->filter()->unique(fn ($value) => strtolower((string) $value))->implode(' · ');
    }

    public function lookupMaps(ParentBusiness $parent): array
    {
        [$categories, $connections] = $this->lookups($parent);

        return [
            $categories->mapWithKeys(fn ($category) => [$this->categoryLabel($category) => $category]),
            $connections->mapWithKeys(fn ($connection) => [$this->connectionLabel($connection) => $connection]),
        ];
    }

    private function lookups(ParentBusiness $parent): array
    {
        $categories = ProductPlanCategory::with(['product:id,product_name', 'network:id,network_name'])->orderBy('id')->get();
        $connections = $parent->providerConnections()->where('status', 'active')->where('approval_status', 'approved')
            ->with('providerConnection:id,name')->orderBy('name')->get();

        return [$categories, $connections];
    }

    private function dropdown($cell, string $formula): void
    {
        $validation = $cell->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)->setShowDropDown(true)->setShowErrorMessage(true)
            ->setErrorTitle('Invalid selection')->setError('Choose a value from the dropdown list.')
            ->setFormula1($formula);
    }

    private function csvRows(UploadedFile $file, ParentBusiness $parent): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            $values[] = $row;
        }
        fclose($handle);

        return $this->tabularRows($values, $this->headers($parent));
    }

    private function tabularRows(array $values, array $expectedHeaders): array
    {
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($values) ?? []);
        if ($headers !== $expectedHeaders) {
            throw ValidationException::withMessages(['plans_file' => 'Download and use the current Excel template. The uploaded columns do not match.']);
        }

        $rows = [];
        foreach ($values as $index => $valuesRow) {
            $row = array_combine($headers, array_slice(array_pad($valuesRow, count($headers), null), 0, count($headers)));
            if (filled($row['Plan Name'] ?? null)) {
                $rows[] = ['line' => $index + 2, 'values' => $row];
            }
        }

        return $rows;
    }
}
