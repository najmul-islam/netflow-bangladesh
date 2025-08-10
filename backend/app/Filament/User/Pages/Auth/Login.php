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
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->extraAttributes([
                'class' => 'bg-white border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->password()
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->extraAttributes([
                'class' => 'bg-white border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm',
            ]);
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
