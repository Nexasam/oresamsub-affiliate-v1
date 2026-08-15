<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkspaceUiComponentsTest extends TestCase
{
    public function test_workspace_components_render_accessible_consistent_markup(): void
    {
        $header = Blade::render('<x-workspace.page-header title="Transactions" description="Review activity"><button>Export</button></x-workspace.page-header>');
        $alert = Blade::render('<x-workspace.alert type="success">Saved</x-workspace.alert>');
        $status = Blade::render('<x-workspace.status type="pending">Pending</x-workspace.status>');

        $this->assertStringContainsString('Transactions', $header);
        $this->assertStringContainsString('Review activity', $header);
        $this->assertStringContainsString('Export', $header);
        $this->assertStringContainsString('role="status"', $alert);
        $this->assertStringContainsString('Saved', $alert);
        $this->assertStringContainsString('Pending', $status);
    }

    public function test_workspace_date_accepts_database_strings_and_date_objects(): void
    {
        $stringDate = Blade::render('<x-workspace.date :value="$value" />', ['value' => '2026-08-15 02:59:31']);
        $objectDate = Blade::render('<x-workspace.date :value="$value" />', ['value' => Carbon::parse('2026-08-15 02:59:31')]);

        $this->assertSame('15 Aug 2026, 02:59', trim($stringDate));
        $this->assertSame('15 Aug 2026, 02:59', trim($objectDate));
    }
}
