<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Today's Habits</h3>

                    @if($habits->isEmpty())
                        <p class="text-gray-500">You don't have any habits yet. <a href="{{ route('habits.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now.</a></p>
                    @else
                        <ul role="list" class="divide-y divide-gray-200">
                            @foreach($habits as $habit)
                                <li class="py-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        @if($habit->category)
                                            <span class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $habit->category->color }}"></span>
                                        @else
                                            <span class="w-3 h-3 rounded-full bg-gray-300 mr-3"></span>
                                        @endif
                                        <div class="ml-2">
                                            <p class="text-sm font-medium text-gray-900">{{ $habit->name }}</p>
                                            @if($habit->category)
                                                <p class="text-xs text-gray-500">{{ $habit->category->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <form action="{{ route('habits.toggle', $habit) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border {{ $habit->is_completed_today ? 'border-green-500 text-green-500' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }} rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                                {{ $habit->is_completed_today ? 'Completed' : 'Complete' }}
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
