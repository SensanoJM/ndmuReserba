<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class UserRegister extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getIdNumberComponent(),
                        $this->getRoleFormComponent(), 
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getIdNumberComponent(): Component
    {
        return TextInput::make('id_number')
            ->label('ID Number')
            ->required()
            ->unique('users', 'id_number')
            ->maxLength(255);
    }
 
    protected function getDepartmentFormComponent(): Component
    {
        return Select::make('department_id')
            ->label('Department')
            ->options([
                'College of Engineering Architecture and Computing' => 'College of Engineering Architecture and Computing',
                'College of Arts and Science' => 'College of Arts and Science',
                'College of Education' => 'College of Education',
                'College of Business Administration' => 'College of Business Administration',
            ])
            ->required();
    }
}