<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\PaymentConfirmation;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate key metrics
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalMerchants = Merchant::where('status', 'active')->count();

        // Merchant with active subscriptions
        $merchantsWithActiveSubscriptions = Merchant::whereHas('subscriptions', function ($query) {
            $query->where('status', 'active');
        })->count();

        // Revenue calculations
        $totalRevenue = Invoice::where('status', 'paid')->sum('amount');
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $lastMonthRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('amount');

        // Calculate revenue growth
        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        // Pending payments
        $pendingPayments = Invoice::where('status', 'pending')->count();
        $awaitingConfirmation = Invoice::where('status', 'awaiting_confirmation')->count();

        // Payment confirmations needing review
        $pendingReviews = PaymentConfirmation::where('status', 'submitted')->count();

        // Average revenue per merchant
        $avgRevenuePerMerchant = $merchantsWithActiveSubscriptions > 0 ? $totalRevenue / $merchantsWithActiveSubscriptions : 0;

        return [
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total pendapatan keseluruhan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'))
                ->description(($revenueGrowth >= 0 ? '+' : '') . number_format($revenueGrowth, 1) . '% dari bulan lalu')
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Total Subscription Aktif', number_format($activeSubscriptions))
                ->description($totalMerchants . ' total merchant terdaftar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Perlu Review', number_format($pendingReviews))
                ->description('Bukti pembayaran perlu direview')
                ->descriptionIcon('heroicon-m-eye')
                ->color($pendingReviews > 0 ? 'danger' : 'success'),
        ];
    }
}
