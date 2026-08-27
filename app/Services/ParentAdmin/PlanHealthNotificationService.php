<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Notifications\ParentPlanHealthNotification;
use Illuminate\Support\Collection;

class PlanHealthNotificationService
{
    public function sync(ParentBusiness $parent, Collection $alerts): void
    {
        $admins = $parent->parentAdmins()->where('active', true)->get();
        $activeIncidentKeys = $alerts->map(fn ($alert) => $this->incidentKey($parent, $alert))->all();

        foreach ($admins as $admin) {
            $admin->unreadNotifications()
                ->where('type', ParentPlanHealthNotification::class)
                ->get()
                ->reject(fn ($notification) => in_array(data_get($notification->data, 'incident_key'), $activeIncidentKeys, true))
                ->each->markAsRead();
        }

        foreach ($alerts as $alert) {
            $incidentKey = $this->incidentKey($parent, $alert);
            $payload = [
                'incident_key' => $incidentKey,
                'title' => 'Provider failures need attention',
                'plan_id' => $alert->plan->id,
                'plan_name' => $alert->plan->product_plan_name,
                'connection_id' => $alert->connection?->id,
                'connection_name' => $alert->connection?->name ?: 'Connection unavailable',
                'failure_count' => $alert->failure_count,
                'latest_reason' => $alert->failure_reason,
                'latest_reference' => $alert->transaction->txn_reference,
                'url' => route('parent-admin.transactions.index', ['reference' => $alert->transaction->txn_reference]),
            ];

            foreach ($admins as $admin) {
                $existing = $admin->unreadNotifications()
                    ->where('type', ParentPlanHealthNotification::class)
                    ->get()
                    ->first(fn ($notification) => data_get($notification->data, 'incident_key') === $incidentKey);

                if ($existing) {
                    $existing->forceFill(['data' => $payload, 'updated_at' => now()])->save();
                } else {
                    $admin->notify(new ParentPlanHealthNotification($payload));
                }
            }
        }
    }

    private function incidentKey(ParentBusiness $parent, object $alert): string
    {
        return implode(':', [
            $parent->id,
            $alert->plan->id,
            $alert->connection?->id ?: 'unavailable',
        ]);
    }
}
