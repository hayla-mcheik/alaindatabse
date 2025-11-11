<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Analytics Statistics</h1>
            <Link 
                href="/analytics"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                Back to Records
            </Link>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Records</h3>
                <p class="text-3xl font-bold text-blue-600">{{ stats.total_records || 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Budget</h3>
                <p class="text-3xl font-bold text-green-600">${{ formatBudget(stats.total_budget) }}</p>
            </div>
        </div>

        <!-- Platform Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Platform Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Budget</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Budget</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="platform in stats.platform_stats || []" :key="platform.platform">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getPlatformBadgeClass(platform.platform)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ platform.platform?.charAt(0).toUpperCase() + platform.platform?.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ platform.count || 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${{ formatBudget(platform.total_budget) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${{ formatBudget((platform.total_budget || 0) / (platform.count || 1)) }}</td>
                        </tr>
                        <tr v-if="!stats.platform_stats || stats.platform_stats.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No platform data available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Clients -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Top Clients by Budget</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Budget</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="client in stats.top_clients || []" :key="client.client">
                            <td class="px-6 py-4 whitespace-nowrap">{{ client.client || 'Unknown' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ client.count || 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${{ formatBudget(client.total_budget) }}</td>
                        </tr>
                        <tr v-if="!stats.top_clients || stats.top_clients.length === 0">
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                No client data available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    }
});

const formatBudget = (budget) => {
    if (!budget && budget !== 0) return '0.00';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(budget);
};

const getPlatformBadgeClass = (platform) => {
    if (!platform) return 'bg-gray-100 text-gray-800';
    
    const classes = {
        google: 'bg-red-100 text-red-800',
        snap: 'bg-yellow-100 text-yellow-800',
        tiktok: 'bg-blue-100 text-blue-800',
        meta: 'bg-indigo-100 text-indigo-800',
        twitter: 'bg-sky-100 text-sky-800',
    };
    return classes[platform] || 'bg-gray-100 text-gray-800';
};
</script>