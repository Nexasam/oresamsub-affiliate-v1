<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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
}
