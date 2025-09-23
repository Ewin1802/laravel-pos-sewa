<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Services\PaymentProcessingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscription.id')
                    ->label('Subscription ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => Invoice::STATUS_PENDING,
                        'primary' => Invoice::STATUS_AWAITING_CONFIRMATION,
                        'success' => Invoice::STATUS_PAID,
                        'danger' => Invoice::STATUS_EXPIRED,
                        'secondary' => Invoice::STATUS_CANCELLED,
                    ]),
                TextColumn::make('payment_method')
                    ->label('Payment Method'),
                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Paid Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Invoice::STATUS_PENDING => 'Pending',
                        Invoice::STATUS_AWAITING_CONFIRMATION => 'Awaiting Confirmation',
                        Invoice::STATUS_PAID => 'Paid',
                        Invoice::STATUS_EXPIRED => 'Expired',
                        Invoice::STATUS_CANCELLED => 'Cancelled',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        Invoice::PAYMENT_METHOD_MANUAL_BANK => 'Manual Bank Transfer',
                        Invoice::PAYMENT_METHOD_MANUAL_QRIS => 'Manual QRIS',
                        Invoice::PAYMENT_METHOD_OTHER => 'Other',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('reference_no')
                            ->label('Reference Number')
                            ->placeholder('Enter payment reference number'),
                        Textarea::make('admin_note')
                            ->label('Admin Note')
                            ->placeholder('Optional note about the payment')
                            ->rows(3),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $paymentService = app(PaymentProcessingService::class);
                            $paymentService->markInvoiceAsPaid(
                                $record,
                                'manual_admin',
                                $data['reference_no'] ?? null
                            );

                            Notification::make()
                                ->title('Invoice marked as paid successfully!')
                                ->body('Subscription has been activated and payment confirmations approved.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error marking invoice as paid')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(Invoice $record) =>
                        in_array($record->status, [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Invoice as Paid')
                    ->modalDescription('This will automatically activate the subscription and approve any pending payment confirmations.')
                    ->modalSubmitActionLabel('Mark as Paid'),

                Action::make('cancel_invoice')
                    ->label('Cancel Invoice')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->placeholder('Please provide reason for cancellation')
                            ->rows(3),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $record->update([
                            'status' => Invoice::STATUS_CANCELLED,
                            'note' => $data['cancellation_reason'],
                        ]);

                        // Cancel related subscription
                        if ($record->subscription) {
                            $record->subscription->update([
                                'status' => 'cancelled'
                            ]);
                        }

                        Notification::make()
                            ->title('Invoice cancelled successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(Invoice $record) =>
                        in_array($record->status, [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Invoice')
                    ->modalDescription('This will cancel the invoice and related subscription.')
                    ->modalSubmitActionLabel('Cancel Invoice'),

                Action::make('view_payment_confirmations')
                    ->label('View Payment Confirmations')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(
                        fn(Invoice $record) =>
                        route('filament.admin.resources.payment-confirmations.index', [
                            'tableFilters[invoice_id][value]' => $record->id
                        ])
                    )
                    ->visible(
                        fn(Invoice $record) =>
                        $record->paymentConfirmations()->exists()
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
