<?php

use Livewire\Volt\Component;
use App\Models\Setting;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $hotel_name;
    public $hotel_address;
    public $hotel_phone;
    public $hotel_email;
    public $hotel_bank_name;
    public $hotel_bank_account_name;
    public $hotel_bank_account_number;
    public $hotel_logo;
    public $existing_logo;

    public function mount()
    {
        $this->hotel_name = Setting::get('hotel_name', 'WARM RESORT');
        $this->hotel_address = Setting::get('hotel_address', '');
        $this->hotel_phone = Setting::get('hotel_phone', '');
        $this->hotel_email = Setting::get('hotel_email', '');
        $this->hotel_bank_name = Setting::get('hotel_bank_name', '');
        $this->hotel_bank_account_name = Setting::get('hotel_bank_account_name', '');
        $this->hotel_bank_account_number = Setting::get('hotel_bank_account_number', '');
        $this->existing_logo = Setting::get('hotel_logo', '');
    }

    public function save()
    {
        $this->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_address' => 'nullable|string',
            'hotel_phone' => 'nullable|string',
            'hotel_email' => 'nullable|email',
            'hotel_logo' => 'nullable|image|max:1024', // 1MB Max
        ]);

        Setting::set('hotel_name', $this->hotel_name);
        Setting::set('hotel_address', $this->hotel_address);
        Setting::set('hotel_phone', $this->hotel_phone);
        Setting::set('hotel_email', $this->hotel_email);
        Setting::set('hotel_bank_name', $this->hotel_bank_name);
        Setting::set('hotel_bank_account_name', $this->hotel_bank_account_name);
        Setting::set('hotel_bank_account_number', $this->hotel_bank_account_number);

        if ($this->hotel_logo) {
            $path = $this->hotel_logo->store('public/hotel');
            $url = Storage::url($path);
            Setting::set('hotel_logo', $url);
            $this->existing_logo = $url;
        }

        $this->dispatch('flux-toast', variant: 'success', message: 'Hotel profile updated successfully.');
    }
}; ?>

<div class="p-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Hotel Profile Settings') }}</flux:heading>
        <flux:subheading>{{ __('Manage your property details, contact info, and payment records.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Info -->
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('Basic Information') }}</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input wire:model="hotel_name" label="{{ __('Hotel Name') }}" />
                <flux:input wire:model="hotel_email" label="{{ __('Contact Email') }}" type="email" />
                <flux:input wire:model="hotel_phone" label="{{ __('Contact Phone') }}" />

                <div class="md:col-span-2">
                    <flux:textarea wire:model="hotel_address" label="{{ __('Hotel Address') }}" rows="3" />
                </div>
            </div>
        </flux:card>

        <!-- Logo & Branding -->
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('Branding') }}</flux:heading>

            <div class="flex items-start gap-8">
                <div
                    class="size-32 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-center overflow-hidden bg-zinc-50 dark:bg-zinc-900">
                    @if ($hotel_logo)
                        <img src="{{ $hotel_logo->temporaryUrl() }}" class="object-contain size-full">
                    @elseif ($existing_logo)
                        <img src="{{ $existing_logo }}" class="object-contain size-full">
                    @else
                        <flux:icon.photo class="size-8 text-zinc-400" />
                    @endif
                </div>

                <div class="flex-1 space-y-2">
                    <flux:label>{{ __('Hotel Logo') }}</flux:label>
                    <input type="file" wire:model="hotel_logo"
                        class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="text-xs text-zinc-500">
                        {{ __('Recommended: SVG or PNG with transparent background. Max 1MB.') }}</p>
                </div>
            </div>
        </flux:card>

        <!-- Payment Info -->
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('Bank Transfer Details') }}</flux:heading>
            <p class="text-sm text-zinc-500">
                {{ __('These details will be shown to guests during the checkout process.') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:input wire:model="hotel_bank_name" label="{{ __('Bank Name') }}"
                    placeholder="e.g. SCB, K-Bank" />
                <flux:input wire:model="hotel_bank_account_name" label="{{ __('Account Name') }}" />
                <flux:input wire:model="hotel_bank_account_number" label="{{ __('Account Number') }}" />
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</div>
