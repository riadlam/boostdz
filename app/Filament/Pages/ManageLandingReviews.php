<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Testimonials\Tables\TestimonialsTable;
use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Models\StorefrontReviewsSettings;
use App\Models\Testimonial;
use App\Services\Content\StorefrontReviewsContent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use UnitEnum;

class ManageLandingReviews extends Page implements HasTable
{
    use CanUseDatabaseTransactions;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Landing reviews';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Landing reviews';

    protected static ?string $slug = 'manage-landing-reviews';

    protected string $view = 'filament.pages.manage-landing-reviews';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $settings = StorefrontReviewsSettings::current();

        $this->form->fill($settings->attributesToArray());
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $settings = StorefrontReviewsSettings::current();
            $settings->fill($data);
            $settings->save();

            app(StorefrontReviewsContent::class)->clearCache();

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title('Landing review section updated')
                ->success()
                ->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Visibility')
                    ->columns(3)
                    ->schema([
                        Toggle::make('section_enabled')
                            ->label('Show reviews section'),
                        Toggle::make('show_stats')
                            ->label('Show stat cards'),
                        Toggle::make('show_leave_review_cta')
                            ->label('Show leave-a-review card'),
                    ]),
                Section::make('Stat cards')
                    ->description('Numbers shown beside the review carousel.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('likes_delivered_display')
                            ->label('Likes delivered')
                            ->required()
                            ->maxLength(32)
                            ->placeholder('10M+'),
                        TextInput::make('satisfaction_rate_display')
                            ->label('Satisfaction rate')
                            ->required()
                            ->maxLength(32)
                            ->placeholder('98%'),
                    ]),
                Section::make('Leave a review CTA')
                    ->schema([
                        TextInput::make('leave_review_url')
                            ->label('Review link URL')
                            ->url()
                            ->placeholder('https://...')
                            ->helperText('Opens when visitors click “Leave a Review”.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return TestimonialsTable::configure($table)
            ->query(fn (): Builder => Testimonial::query())
            ->heading('Review cards')
            ->description('Only published reviews appear on the landing page.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add review')
                ->url(TestimonialResource::getUrl('create')),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save section settings')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
