<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-banknotes class="h-8 w-8 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Available for Withdrawal
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    GHS {{ number_format($totalAvailable, 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="h-8 w-8 text-yellow-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Transactions
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $pendingTransactions }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-calendar-days class="h-8 w-8 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Last Updated
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ now()->format('M d, Y H:i') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    Process Fee Withdrawal
                </h3>
                
                @if($totalAvailable > 0)
                    {{ $this->form }}
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-inbox class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No fees available</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            There are currently no fees available for withdrawal.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Transactions (Optional) -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    Recent Withdrawals
                </h3>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>Last withdrawal: {{ \App\Models\WithdrawalFee::where('status', 'applied')->latest()->first()?->updated_at?->diffForHumans() ?? 'No withdrawals yet' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
