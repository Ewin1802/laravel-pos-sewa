<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubscriptionTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Tren Subscription (6 Bulan Terakhir)';

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getData(): array
    {
        // Get subscription data for last 6 months
        $months = [];
        $activeSubscriptions = [];
        $newSubscriptions = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            // Count active subscriptions at the end of each month
            $activeCount = Subscription::where('status', 'active')
                ->where('start_at', '<=', $date->endOfMonth())
                ->where(function ($query) use ($date) {
                    $query->whereNull('end_at')
                        ->orWhere('end_at', '>=', $date->endOfMonth());
                })
                ->count();

            // Count new subscriptions in each month
            $newCount = Subscription::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $months[] = $monthName;
            $activeSubscriptions[] = $activeCount;
            $newSubscriptions[] = $newCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Subscription Aktif',
                    'data' => $activeSubscriptions,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Subscription Baru',
                    'data' => $newSubscriptions,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => false,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
