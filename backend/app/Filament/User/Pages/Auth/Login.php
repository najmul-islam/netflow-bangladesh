<?php

namespace App\Filament\User\Pages\Auth;

use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Use the custom Blade view for the login page.
     */
    protected static string $view = 'filament.user.pages.auth.login';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()
                    ->label('Email Address')
                    ->placeholder('Enter your email address'),
                $this->getPasswordFormComponent()
                    ->label('Password')
                    ->placeholder('Enter your password'),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email Address')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->placeholder('Enter your email address')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->placeholder('Enter your password')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    // Fix method signatures to match parent class and hide all default content
    public function getTitle(): Htmlable|string
    {
        return '';
    }

    public function getHeading(): Htmlable|string
    {
        return '';
    }

    public function getSubheading(): Htmlable|string|null
    {
        return null;
    }

    // Hide brand logo and name
    public function getBrandName(): ?string
    {
        return null;
    }

    public function getBrandLogo(): ?string
    {
        return null;
    }
   
}
