<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityResource\Pages;
use App\Filament\Resources\FacilityResource\RelationManagers;
use App\Models\Facility;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Facility';
    protected static ?string $modelLabel = 'Facility';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $slug = 'Facilities';


    public static function form(Form $form): Form{
        return $form
        ->schema([
            TextInput::make('facility_name')
                ->label('Facility Name')
                ->required(),

            TextInput::make('facility_type')
                ->label('Facility Type')
                ->required(),

            TextInput::make('capacity')
                ->label('Capacity')
                ->numeric()
                ->required(),

            TextInput::make('building_name')
                ->label('Building Name')
                ->required(),

            TextInput::make('floor_level')
                ->label('Floor Level')
                ->required(),

            TextInput::make('room_number')
                ->label('Room Number')
                ->required(),

            Textarea::make('description')
                ->label('Description')
                ->required(),

            FileUpload::make('facility_image')
                ->label('Facility Image')
                ->image()
                ->directory('facility-images'),

            Toggle::make('status')
                ->label('Active Status')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([

            ImageColumn::make('facility_image')
                ->label('Facility Image')
                ->sortable(),

            TextColumn::make('facility_name')
                ->label('Facility Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('facility_type')
                ->label('Facility Type')
                ->sortable()
                ->searchable(),

            TextColumn::make('capacity')
                ->label('Capacity')
                ->sortable(),

            TextColumn::make('building_name')
                ->label('Building Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('floor_level')
                ->label('Floor Level')
                ->sortable()
                ->searchable(),

            TextColumn::make('room_number')
                ->label('Room Number')
                ->sortable()
                ->searchable(),

            TextColumn::make('description')
                ->label('Description')
                ->limit(50), // Limiting the number of characters for the description

            BooleanColumn::make('status')
                ->label('Active')
                ->sortable(),
        ])
        ->filters([
            //
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
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
