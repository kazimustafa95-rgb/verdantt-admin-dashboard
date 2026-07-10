<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button wire:click="saveTerms" wire:loading.attr="disabled">
                Save Terms of Use
            </x-filament::button>

            <x-filament::button wire:click="savePrivacy" color="gray" wire:loading.attr="disabled">
                Save Privacy Policy
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
