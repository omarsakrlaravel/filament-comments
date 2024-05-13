<?php

namespace Parallax\FilamentComments\Livewire;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Parallax\FilamentComments\Models\FilamentComment;
use Filament\Facades\Filament;

class CommentsComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Model $record;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        if (!auth()->user()->can('create', FilamentComment::class)) {
            return $form;
        }
    
        $tenant = Filament::getTenant();
    
        return $form
            ->schema([
                Forms\Components\RichEditor::make('comment')
                    ->hiddenLabel()
                    ->required()
                    ->placeholder(__('filament-comments::filament-comments.comments.placeholder'))
                    ->extraInputAttributes(['style' => 'min-height: 6rem'])
                    ->toolbarButtons(config('filament-comments.toolbar_buttons')),
    
                Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->relationship('organization', 'name', modifyQueryUsing: fn( Builder $query ) => 
                        $tenant ? $query->where('parent_id', $tenant->id) : $query
                    )
                    ->hidden( fn() => $tenant ? !empty($tenant->parent_id) : true ),
            ])
            ->statePath('data');
    }
    

    public function create(): void
    {
        if (!auth()->user()->can('create', FilamentComment::class)) {
            return;
        }

        $this->form->validate();

        $data = $this->form->getState();

        $this->record->filamentComments()->create([
            'subject_type' => $this->record->getMorphClass(),
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
            'organization_id' => Filament::getTenant()->id,
        ]);

        Notification::make()
            ->title(__('filament-comments::filament-comments.notifications.created'))
            ->success()
            ->send();

        $this->form->fill();
    }

    public function delete(int $id): void
    {
        $comment = FilamentComment::find($id);

        if (!$comment) {
            return;
        }

        if (!auth()->user()->can('delete', $comment)) {
            return;
        }

        $comment->delete();

        Notification::make()
            ->title(__('filament-comments::filament-comments.notifications.deleted'))
            ->success()
            ->send();
    }

    public function render(): View
    {
        $comments = $this->record->filamentComments()->latest()->get();

        return view('filament-comments::comments', ['comments' => $comments]);
    }
}
