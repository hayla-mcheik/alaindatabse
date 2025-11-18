<template>
    <Head title="Analytics" />

    <AuthenticatedLayout>
        <div class="container mx-auto p-6">

            <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <button 
                    @click="clearFlash('success')"
                    class="absolute top-2 right-2 text-green-600 hover:text-green-800"
                >
                    ×
                </button>
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <button 
                    @click="clearFlash('error')"
                    class="absolute top-2 right-2 text-red-600 hover:text-red-800"
                >
                    ×
                </button>
                {{ $page.props.flash.error }}
            </div>

            <!-- Import Statistics -->
            <div v-if="$page.props.flash.import_stats" class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                <h3 class="text-lg font-medium text-blue-800 mb-3">Import Statistics</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $page.props.flash.import_stats.imported || 0 }}</div>
                        <div class="text-blue-700">New Records</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $page.props.flash.import_stats.updated || 0 }}</div>
                        <div class="text-blue-700">Updated Records</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $page.props.flash.import_stats.failed || 0 }}</div>
                        <div class="text-blue-700">Failed Records</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $page.props.flash.import_stats.total_processed || 0 }}</div>
                        <div class="text-blue-700">Total Processed</div>
                    </div>
                </div>
                
                <!-- Summary Message -->
                <div class="mt-3 p-3 bg-white rounded border">
                    <h4 class="font-medium text-blue-800 mb-2">Summary:</h4>
                    <div class="space-y-1 text-sm">
                        <div v-if="$page.props.flash.import_stats.imported > 0" class="flex items-center text-green-700">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            {{ $page.props.flash.import_stats.imported }} new records added to database
                        </div>
                        <div v-if="$page.props.flash.import_stats.updated > 0" class="flex items-center text-yellow-700">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                            {{ $page.props.flash.import_stats.updated }} existing records updated
                        </div>
                        <div v-if="$page.props.flash.import_stats.failed > 0" class="flex items-center text-red-700">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            {{ $page.props.flash.import_stats.failed }} records failed to import (see errors below)
                        </div>
                        <div class="flex items-center text-blue-700">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                            Processed {{ $page.props.flash.import_stats.files_count }} file(s)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Updated Import Errors Section -->
            <div v-if="$page.props.flash && $page.props.flash.import_errors && $page.props.flash.import_errors.length > 0" class="mb-6">
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-medium text-red-800">
                            Import Errors & Skipped Records ({{ $page.props.flash.import_errors.length }})
                        </h3>
                        <div class="space-x-2">
                            <button 
                                @click="exportErrors"
                                class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
                            >
                                Export Errors
                            </button>
                            <button 
                                @click="clearErrors"
                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                    
                    <!-- Affected Clients Summary -->
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <span class="text-yellow-600 mr-2 mt-0.5">⚠️</span>
                            <div>
                                <h4 class="font-medium text-yellow-800 mb-2">Affected Clients with Zero/Negative Budgets:</h4>
                                <div class="text-sm text-yellow-700">
                                    <p class="mb-2">The following clients were skipped due to invalid budget values (zero or negative):</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-40 overflow-y-auto">
                                        <div v-for="client in getAffectedClients()" :key="client" class="flex items-center">
                                            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                            <span class="text-yellow-800">{{ client }}</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs">Total affected clients: {{ getAffectedClients().length }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div 
                            v-for="(error, index) in $page.props.flash.import_errors" 
                            :key="index"
                            class="bg-orange-50 border border-orange-200 rounded-lg p-4"
                        >
                            <!-- Header with File and Row Info -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <span class="text-orange-600 mr-2">⏭️</span>
                                    <span class="font-medium text-orange-800">Skipped Record</span>
                                    <span class="text-sm text-orange-600 ml-3">
                                        File: <strong>{{ error.file }}</strong>
                                    </span>
                                </div>
                                <span class="text-sm bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                    Row {{ error.row_index || error.row || 'N/A' }}
                                </span>
                            </div>

                            <!-- Record Details -->
                            <div class="bg-white rounded-lg p-3 border border-orange-100 mb-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                                    <!-- Client -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">CLIENT</span>
                                        <span class="font-semibold text-gray-800">
                                            {{ getClientFromError(error) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Agency -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">AGENCY</span>
                                        <span class="text-gray-700">
                                            {{ getAgencyFromError(error) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Budget -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">BUDGET</span>
                                        <span class="font-mono text-red-600">
                                            ${{ formatBudget(getBudgetFromError(error)) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Platform -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">PLATFORM</span>
                                        <span class="text-gray-700">
                                            {{ getPlatformFromError(error) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Country -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">COUNTRY</span>
                                        <span class="text-gray-700">
                                            {{ getCountryFromError(error) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Year -->
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-medium mb-1">YEAR</span>
                                        <span class="text-gray-700">
                                            {{ getYearFromError(error) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Reason -->
                            <div class="bg-red-50 rounded-lg p-3 border border-red-200">
                                <div class="flex items-start">
                                    <span class="text-red-500 mr-2 mt-0.5">❌</span>
                                    <div>
                                        <span class="font-medium text-red-800 block mb-1">Reason for Skipping:</span>
                                        <p class="text-red-700 text-sm">
                                            {{ error.errors?.[0] || 'Unknown error' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Analytics Records</h1>
                <div class="space-x-4">
                    <button 
                        @click="clearAll"
                        :disabled="clearAllForm.processing"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                    >
                        <span v-if="clearAllForm.processing">Clearing...</span>
                        <span v-else>Clear All Data</span>
                    </button>
                    
                    <!-- FIXED: Use route helper instead of direct href -->
                    <Link 
                        :href="route('analytics.stats')"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-black rounded-md hover:bg-green-700"
                    >
                        View Statistics
                    </Link>
                </div>
            </div>

            <!-- File Upload Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Import & Export Excel Files</h2>
                <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Excel Files (Multiple files supported)
                        </label>
                        <input 
                            type="file" 
                            @input="form.files = $event.target.files"
                            multiple
                            accept=".xlsx,.xls,.csv"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <p class="text-sm text-gray-500 mt-1">
                            Supported platforms: Google, Snap, TikTok, Meta, Twitter
                        </p>
                        <div v-if="form.errors.files" class="text-red-600 text-sm mt-1">
                            {{ form.errors.files }}
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <!-- FIXED: Use route helper for form action -->
                        <button 
                            type="submit" 
                            :disabled="form.processing || !form.files || form.files.length === 0"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Importing...</span>
                            <span v-else>Import Files</span>
                        </button>

                        <!-- FIXED: Use route helper for export -->
                        <Link 
                            :href="route('analytics.export') + getFilterQueryString()"
                            method="get"
                            as="button"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            Export Data
                        </Link>
                    </div>
                </form>
            </div>

            <!-- Updated Filters Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Filters</h2>
                
                <!-- First Row of Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <input 
                            type="text" 
                            v-model="filters.search"
                            placeholder="Search client, agency, country..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Platform</label>
                        <select 
                            v-model="filters.platform"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Platforms</option>
                            <option v-for="platform in platforms" :key="platform" :value="platform">
                                {{ platform?.charAt(0)?.toUpperCase() + platform?.slice(1) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Country</label>
                        <select 
                            v-model="filters.countries"
                            multiple    
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 h-32"
                        >
                            <option v-for="country in countries" :key="country" :value="country">
                                {{ country }}
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple countries</p>
                        <div v-if="filters.countries && filters.countries.length > 0" class="mt-2">
                            <div class="flex flex-wrap gap-1">
                                <span 
                                    v-for="(selectedCountry, index) in filters.countries" 
                                    :key="index"
                                    class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
                                >
                                    {{ selectedCountry }}
                                    <button 
                                        @click="removeCountry(selectedCountry)"
                                        class="ml-1 text-blue-600 hover:text-blue-800"
                                    >
                                        ×
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Client</label>
                        <select 
                            v-model="filters.client"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Clients</option>
                            <option v-for="client in clients" :key="client" :value="client">
                                {{ client }}
                            </option>
                        </select>
                    </div>
                </div>
                
                <!-- Second Row of Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Agency</label>
                        <select 
                            v-model="filters.agency"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Agencies</option>
                            <option value="direct">Direct</option>
                            <option v-for="agency in agencies" :key="agency" :value="agency">
                                {{ agency }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Budget Tier</label>
                        <select 
                            v-model="filters.budget_tier"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Budgets</option>
                            <option value="top">Top Tier (Above Average)</option>
                            <option value="mid">Mid Tier (Average)</option>
                            <option value="bottom">Bottom Tier (Below Average)</option>
                            <option value="high">High ($10,000+)</option>
                            <option value="medium">Medium ($1,000 - $10,000)</option>
                            <option value="low">Low (Below $1,000)</option>
                        </select>
                    </div>

                    <!-- Empty column to maintain layout -->
                    <div></div>
                </div>
                
                <!-- Reset Button -->
                <div class="flex justify-end">
                    <button 
                        @click="resetFilters"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Records Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Client
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Agency
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortByBudget">
                                    <div class="flex items-center">
                                        Budget
                                        <span v-if="sortField === 'budget'" class="ml-1">
                                            {{ sortDirection === 'desc' ? '↓' : '↑' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Platform
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Country
                                </th>
                                <th class="px-6-py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortByDate">
                                    <div class="flex items-center">
                                        Year
                                        <span v-if="sortField === 'date'" class="ml-1">
                                            {{ sortDirection === 'desc' ? '↓' : '↑' }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="record in records?.data || []" :key="record.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ record.client }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span :class="getAgencyBadgeClass(record.agency)">
                                        {{ formatAgency(record.agency) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ formatBudget(record.budget) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getPlatformBadgeClass(record.platform)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ record.platform?.charAt(0)?.toUpperCase() + record.platform?.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ record.country }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ formatYear(record.date) }}
                                </td>
                            </tr>
                            <tr v-if="!records?.data || records.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No records found. Import some Excel files to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records?.links && records.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Showing {{ records.from || 0 }} to {{ records.to || 0 }} of {{ records.total || 0 }} results
                        </div>
                        <div class="space-x-2">
                            <template v-for="link in records.links" :key="link.label">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url + getFilterQueryString() + getSortQueryString()"
                                    :class="{
                                        'bg-blue-600 text-white': link.active,
                                        'text-gray-500 hover:text-gray-700': !link.active
                                    }"
                                    class="px-3 py-1 rounded-md text-sm font-medium"
                                    v-html="link.label"
                                    preserve-state
                                />
                                <span 
                                    v-else
                                    :class="{
                                        'bg-gray-200 text-gray-500': link.active,
                                        'text-gray-400': !link.active
                                    }"
                                    class="px-3 py-1 rounded-md text-sm font-medium cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    records: {
        type: Object,
        default: () => ({})
    },
    platforms: {
        type: Array,
        default: () => []
    },
    countries: {
        type: Array,
        default: () => []
    },
    clients: {
        type: Array,
        default: () => []
    },
    agencies: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    sort: {
        type: String,
        default: 'budget'
    },
    direction: {
        type: String,
        default: 'desc'
    }
});

// Initialize sorting state from props
const sortField = ref(props.sort || 'budget');
const sortDirection = ref(props.direction || 'desc');

// Forms
const form = useForm({
    files: null,
});

const exportForm = useForm({});
const clearAllForm = useForm({});

// Filters
const filters = ref({
    search: props.filters?.search || '',
    platform: props.filters?.platform || '',
    countries: props.filters?.countries || (props.filters?.country ? [props.filters.country] : []),
    client: props.filters?.client || '',
    agency: props.filters?.agency || '',
    budget_tier: props.filters?.budget_tier || '',
    min_budget: props.filters?.min_budget || '',
    max_budget: props.filters?.max_budget || '',
    year: props.filters?.year || '',
});

// ========== ERROR DISPLAY METHODS ==========
const getClientFromError = (error) => {
    if (error.values?.client_name) return error.values.client_name;
    if (error.values?.client) return error.values.client;
    return 'Unknown Client';
};

const getAgencyFromError = (error) => {
    const agency = error.values?.agency_name || error.values?.agency;
    return formatAgency(agency) || 'Direct';
};

const getBudgetFromError = (error) => {
    return error.values?.budget || error.values?.budget_amount || 0;
};

const getPlatformFromError = (error) => {
    return error.values?.platform || 'Unknown Platform';
};

const getCountryFromError = (error) => {
    return error.values?.country || 'Unknown Country';
};

const getYearFromError = (error) => {
    return error.values?.date || 'Unknown Year';
};

const formatAgency = (agency) => {
    if (!agency || agency === 'direct' || agency === 'Unknown Agency') {
        return 'Direct';
    }
    return agency;
};

const formatBudget = (budget) => {
    if (!budget && budget !== 0) return '0.00';
    
    const numericBudget = typeof budget === 'string' ? parseFloat(budget) : budget;
    
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numericBudget);
};

const clearErrors = () => {
    router.reload();
};

// ========== AFFECTED CLIENTS METHODS ==========
const getAffectedClients = () => {
    const errors = usePage().props.flash.import_errors;
    if (!errors || errors.length === 0) return [];
    
    const clients = new Set();
    errors.forEach(error => {
        const client = getClientFromError(error);
        if (client && client !== 'Unknown Client') {
            clients.add(client);
        }
    });
    
    return Array.from(clients).sort();
};

const getAffectedClientsSummary = () => {
    const affectedClients = getAffectedClients();
    if (affectedClients.length === 0) return '';
    
    if (affectedClients.length <= 5) {
        return affectedClients.join(', ');
    } else {
        return `${affectedClients.slice(0, 5).join(', ')} and ${affectedClients.length - 5} more...`;
    }
};

// Enhanced export function to include all affected clients
const exportErrors = () => {
    const errors = usePage().props.flash.import_errors;
    if (!errors || errors.length === 0) return;
    
    const errorData = errors.map(error => ({
        'File': error.file,
        'Row': error.row_index || error.row || 'N/A',
        'Client': getClientFromError(error),
        'Agency': getAgencyFromError(error),
        'Budget': `$${formatBudget(getBudgetFromError(error))}`,
        'Platform': getPlatformFromError(error),
        'Country': getCountryFromError(error),
        'Year': getYearFromError(error),
        'Error Reason': error.errors?.[0] || 'Unknown error'
    }));
    
    // Create CSV content for errors
    const headers = ['File', 'Row', 'Client', 'Agency', 'Budget', 'Platform', 'Country', 'Year', 'Error Reason'];
    const csvContent = "data:text/csv;charset=utf-8," 
        + headers.join(',') + "\n"
        + errorData.map(e => 
            `"${e.File}","${e.Row}","${e.Client}","${e.Agency}","${e.Budget}","${e.Platform}","${e.Country}","${e.Year}","${e['Error Reason']}"`
          ).join("\n");
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `import_errors_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
// ========== END ERROR DISPLAY METHODS ==========

// Watch for filter changes
watch(filters, (newFilters) => {
    updateURLWithSorting();
}, { deep: true, immediate: false });

// Clear All Data Method
const clearAll = () => {
    if (confirm('Are you sure you want to delete ALL records? This action cannot be undone.')) {
        clearAllForm.get(route('analytics.clear-all'), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('All data cleared successfully');
                router.reload();
            },
            onError: (errors) => {
                console.error('Error clearing data:', errors);
                alert('Error clearing data. Please try again.');
            },
        });
    }
};

// Year formatting - only show the year
const formatYear = (dateString) => {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.getFullYear().toString();
    } catch (error) {
        return 'N/A';
    }
};

// Sort by date (year)
const sortByDate = () => {
    if (sortField.value === 'date') {
        sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortField.value = 'date';
        sortDirection.value = 'desc';
    }
    updateURLWithSorting();
};

// Sort by budget
const sortByBudget = () => {
    if (sortField.value === 'budget') {
        sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortField.value = 'budget';
        sortDirection.value = 'desc';
    }
    updateURLWithSorting();
};

// Update URL with sorting and filters
const updateURLWithSorting = () => {
    const urlFilters = { ...filters.value };
    
    // Remove empty arrays and strings
    Object.keys(urlFilters).forEach(key => {
        if (Array.isArray(urlFilters[key]) && urlFilters[key].length === 0) {
            delete urlFilters[key];
        } else if (urlFilters[key] === '' || urlFilters[key] === null || urlFilters[key] === undefined) {
            delete urlFilters[key];
        }
    });

    // Add sort parameters
    urlFilters.sort = sortField.value;
    urlFilters.direction = sortDirection.value;

    // Remove the old single country filter if we're using multiple countries
    if (urlFilters.countries && urlFilters.countries.length > 0) {
        delete urlFilters.country;
    }

    router.get(route('analytics.index'), urlFilters, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

// Get sort query string
const getSortQueryString = () => {
    return `&sort=${sortField.value}&direction=${sortDirection.value}`;
};

// Get filter query string
const getFilterQueryString = () => {
    const params = new URLSearchParams();
    
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            if (Array.isArray(filters.value[key])) {
                filters.value[key].forEach(value => {
                    params.append(`${key}[]`, value);
                });
            } else {
                params.append(key, filters.value[key]);
            }
        }
    });
    
    const queryString = params.toString();
    return queryString ? `&${queryString}` : '';
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        search: '',
        platform: '',
        countries: [],
        client: '',
        agency: '',
        budget_tier: '',
        min_budget: '',
        max_budget: '',
        year: '',
    };
};

// Flash message handling
const clearFlash = (type) => {
    router.reload({ only: ['flash'] });
};

const getAgencyBadgeClass = (agency) => {
    if (!agency || agency === 'direct' || agency === 'Unknown Agency') {
        return 'bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium';
    }
    return 'text-gray-900';
};

const removeCountry = (countryToRemove) => {
    filters.value.countries = filters.value.countries.filter(country => country !== countryToRemove);
};

const submit = () => {
    if (!form.files || form.files.length === 0) {
        alert('Please select at least one file');
        return;
    }

    form.post(route('analytics.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error('Import errors:', errors);
        },
    });
};

const exportData = () => {
    const params = new URLSearchParams();
    
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            if (Array.isArray(filters.value[key])) {
                filters.value[key].forEach(value => {
                    params.append(`${key}[]`, value);
                });
            } else {
                params.append(key, filters.value[key]);
            }
        }
    });

    const url = route('analytics.export') + '?' + params.toString();
    
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'true');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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

// Ensure initial load has correct sorting
onMounted(() => {
    if (!props.sort && !props.direction) {
        sortField.value = 'budget';
        sortDirection.value = 'desc';
    }
});
</script>