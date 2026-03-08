<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('categories.store') }}" class="mt-6 space-y-6 max-w-xl">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="color" :value="__('Color (Hex, e.g., #ff0000)')" />
                            <div class="flex items-center gap-2">
                                <input type="color" id="color-picker" class="h-10 w-10 border-gray-300 rounded" value="{{ old('color', '#3b82f6') }}" onchange="document.getElementById('color').value = this.value">
                                <x-text-input id="color" name="color" type="text" class="mt-1 block w-full uppercase" :value="old('color', '#3b82f6')" pattern="^#[0-9A-Fa-f]{6}$" required onchange="document.getElementById('color-picker').value = this.value" />
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('color')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
