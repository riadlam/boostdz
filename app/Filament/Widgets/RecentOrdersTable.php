<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersTable extends TableWidget
{
    protected static ?string $heading = 'Recent orders';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->with(['user', 'service'])->latest()->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('service.name')->label('Service')->limit(40),
                TextColumn::make('status')->badge(),
                TextColumn::make('charge_dzd')->label('Charge')->numeric(decimalPlaces: 0)->suffix(' DA'),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
