<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Enums\PaymentSubmissionStatus;
use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use App\Models\Deposit;
use App\Models\PaymentSubmission;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PendingReviewsTable extends TableWidget
{
    protected static ?string $heading = 'Pending deposit reviews';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $depositIds = Deposit::query()
                    ->where('status', DepositStatus::Pending)
                    ->latest()
                    ->limit(5)
                    ->pluck('id');

                $paymentIds = PaymentSubmission::query()
                    ->where('status', PaymentSubmissionStatus::Pending)
                    ->latest()
                    ->limit(5)
                    ->pluck('id');

                return Deposit::query()
                    ->whereIn('id', $depositIds)
                    ->with('user');
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->state(fn (): string => 'Deposit'),
                TextColumn::make('user.email')->label('User'),
                TextColumn::make('amount_dzd')->label('Amount')->numeric(decimalPlaces: 0)->suffix(' DA'),
                TextColumn::make('method')->badge(),
                TextColumn::make('created_at')->label('Submitted')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Deposit $record): string => DepositResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No pending deposit reviews')
            ->description('Payment submissions pending review are listed under Fulfillment → Payment Submissions.');
    }
}
