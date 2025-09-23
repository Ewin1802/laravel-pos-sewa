<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopPlansChart extends ChartWidget
{
    protected ?string $heading = 'Plan Terpopuler';

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getData(): array
    {
        // Get top plans by subscription count
        $planStats = Subscription::select('plan_id', DB::raw('count(*) as subscription_count'))
            ->with('plan')
            ->groupBy('plan_id')
            ->orderBy('subscription_count', 'desc')
            ->limit(5)
            ->get();

        $labels = [];
        $data = [];
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

        foreach ($planStats as $index => $stat) {
            $labels[] = $stat->plan->name ?? 'Unknown Plan';
            $data[] = $stat->subscription_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Subscription',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " subscriptions"; }',
                    ],
                ],
            ],
        ];
    }
}
