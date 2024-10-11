<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'User';
    protected static ?string $modelLabel = 'User';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $slug = 'users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
    
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique('users', 'email')
                    ->maxLength(255),
    
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'admin' => 'Admin',
                        'signatory' => 'Signatory',
                        'organization' => 'Organization',
                    ])
                    ->required()
                    ->default('student')
                    ->reactive(),
    
                Forms\Components\TextInput::make('id_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->hidden(fn(Forms\Get $get) => $get('role') === 'organization')
                    ->hidden(fn(Forms\Get $get) => $get('role') === 'signatory')
                    ->hidden(fn(Forms\Get $get) => $get('role') === 'admin'),
    
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required()
                    ->label('Department')
                    ->options([
                        'College of Engineering Architecture and Computing' => 'College of Engineering Architecture and Computing',
                        'College of Arts and Science' => 'College of Arts and Science',
                        'College of Education' => 'College of Education',
                        'College of Business Administration' => 'College of Business Administration',
                    ])
                    ->visible(fn (Forms\Get $get) => !in_array($get('role'), ['signatory', 'organization', 'admin']))
                    ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="w-5 h-5" wire:loading wire:target="data.department_id" />')))
                    ->live(),

                Forms\Components\Select::make('position')
                    ->label('Position')
                    ->options([
                        'school_president' => 'School President',
                        'school_director' => 'School Director',
                    ])
                    ->visible(fn(Forms\Get $get) => $get('role') === 'signatory')
                    ->required(fn(Forms\Get $get) => $get('role') === 'signatory')
                    ->hidden(fn(Forms\Get $get) => $get('role') === 'organization'),
    
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->hidden(fn(Forms\Get $get) => $get('role') !== 'organization'),
    
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->revealable()
                    ->rule(Password::default()),
    
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->revealable()
                    ->same('password')
                    ->rule(Password::default()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('id_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('department.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('position')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'organization' => 'Organization',
                        'admin' => 'Admin',
                        'signatory' => 'Signatory',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
