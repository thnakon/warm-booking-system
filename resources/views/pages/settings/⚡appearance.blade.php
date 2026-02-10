<?php

use Livewire\Component;

new class extends Component {
    public string $language;

    public function mount()
    {
        $this->language = session('locale', config('app.locale'));
    }

    public function updatedLanguage($value)
    {
        session(['locale' => $value]);
        app()->setLocale($value);
        $this->redirect(route('profile.edit'), navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Theme') }}</flux:label>
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Language') }}</flux:label>

                <flux:radio.group wire:model.live="language" variant="segmented">
                    <flux:radio value="en">{{ __('English') }}</flux:radio>
                    <flux:radio value="th">{{ __('Thai') }}</flux:radio>
                </flux:radio.group>
            </flux:field>
        </div>
    </x-pages::settings.layout>
</section>
