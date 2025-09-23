<?php

namespace App\Filament\Resources\PaymentConfirmations\Tables;

use App\Models\PaymentConfirmation;
use App\Services\PaymentProcessingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentConfirmationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.id')
                    ->label('Invoice ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.merchant.business_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submitted_by')
                    ->label('Submitted By')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable(),
                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->searchable(),
                ImageColumn::make('evidence_path')
                    ->label('Evidence')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(url('/images/no-image.png'))
                    ->tooltip('Click to view full image'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => PaymentConfirmation::STATUS_SUBMITTED,
                        'success' => PaymentConfirmation::STATUS_APPROVED,
                        'danger' => PaymentConfirmation::STATUS_REJECTED,
                    ]),
                TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        PaymentConfirmation::STATUS_SUBMITTED => 'Submitted',
                        PaymentConfirmation::STATUS_APPROVED => 'Approved',
                        PaymentConfirmation::STATUS_REJECTED => 'Rejected',
                    ]),
                SelectFilter::make('invoice_id')
                    ->label('Invoice ID')
                    ->searchable()
                    ->relationship('invoice', 'id'),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('view_evidence')
                    ->label('View Evidence')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->modalContent(
                        fn(PaymentConfirmation $record) =>
                        view('filament.components.evidence-viewer', [
                            'evidencePath' => $record->evidence_path,
                            'paymentConfirmation' => $record
                        ])
                    )
                    ->modalHeading('Payment Evidence')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn(PaymentConfirmation $record) => !empty($record->evidence_path)),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Admin Note')
                            ->placeholder('Optional note about the approval')
                            ->rows(3),
                    ])
                    ->action(function (PaymentConfirmation $record, array $data) {
                        try {
                            $paymentService = app(PaymentProcessingService::class);
                            $paymentService->approvePaymentConfirmation(
                                $record,
                                Filament::auth()->user()->id,
                                $data['admin_note'] ?? null
                            );

                            Notification::make()
                                ->title('Payment confirmation approved successfully!')
                                ->body('Invoice has been marked as paid and subscription activated.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error approving payment confirmation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(PaymentConfirmation $record) =>
                        $record->status === PaymentConfirmation::STATUS_SUBMITTED
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment Confirmation')
                    ->modalDescription('This will automatically mark the invoice as paid and activate the subscription.')
                    ->modalSubmitActionLabel('Approve Payment'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide reason for rejection')
                            ->rows(3),
                    ])
                    ->action(function (PaymentConfirmation $record, array $data) {
                        try {
                            $paymentService = app(PaymentProcessingService::class);
                            $paymentService->rejectPaymentConfirmation(
                                $record,
                                Filament::auth()->user()->id,
                                $data['admin_note']
                            );

                            Notification::make()
                                ->title('Payment confirmation rejected')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error rejecting payment confirmation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(PaymentConfirmation $record) =>
                        $record->status === PaymentConfirmation::STATUS_SUBMITTED
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reject Payment Confirmation')
                    ->modalSubmitActionLabel('Reject Payment'),

                Action::make('view_invoice')
                    ->label('View Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(
                        fn(PaymentConfirmation $record) =>
                        route('filament.admin.resources.invoices.view', $record->invoice_id)
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
