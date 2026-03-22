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
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Last 7 Days</h3>

                    @if($habits->isEmpty())
                        <p class="text-gray-500">You don't have any habits yet. <a href="{{ route('habits.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now.</a></p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Habit</th>
                                    @foreach($days as $day)
                                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs font-normal text-gray-500">{{ $day['name'] }}</span>
                                                <span>{{ $day['day'] }}</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($habits as $habit)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                                            <div class="flex items-center">
                                                @if($habit->category)
                                                    <span class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $habit->category->color }}"></span>
                                                @else
                                                    <span class="w-3 h-3 rounded-full bg-gray-300 mr-3"></span>
                                                @endif
                                                <div class="ml-2">
                                                    <p class="font-medium text-gray-900">{{ $habit->name }}</p>
                                                    @if($habit->category)
                                                        <p class="text-xs text-gray-500">{{ $habit->category->name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        @foreach($days as $day)
                                            @php
                                                $isCompleted = $habit->completions_by_day[$day['date']];
                                                $isToday = $day['date'] === now()->timezone(auth()->user()->timezone ?? 'UTC')->format('Y-m-d');
                                            @endphp
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm {{ $isToday ? 'bg-gray-50' : '' }}">
                                                <form action="{{ route('habits.toggle', $habit) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="date" value="{{ $day['date'] }}">
                                                    <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center border {{ $isCompleted ? 'bg-green-100 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-400 hover:bg-gray-100' }} transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" title="{{ $isCompleted ? 'Completed on ' . $day['date'] : 'Mark complete for ' . $day['date'] }}">
                                                        @if($isCompleted)
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        @else
                                                            <span class="sr-only">Incomplete</span>
                                                            <div class="w-2 h-2 rounded-full {{ $isToday ? 'bg-gray-300' : 'bg-gray-200' }}"></div>
                                                        @endif
                                                    </button>
                                                </form>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
