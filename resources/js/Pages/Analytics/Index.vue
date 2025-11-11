<template>
    <Head title="Analytics" />

    <AuthenticatedLayout>
        <div class="container mx-auto p-6">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.flash && $page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ $page.props.flash.error }}
            </div>

            <!-- Import Errors Section -->
            <div v-if="$page.props.flash && $page.props.flash.import_errors && $page.props.flash.import_errors.length > 0" class="mb-6">
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-medium text-red-800">
                            Import Errors ({{ $page.props.flash.import_errors.length }})
                        </h3>
                        <button 
                            @click="clearErrors"
                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                        >
                            Dismiss
                        </button>
                    </div>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <div 
                            v-for="(error, index) in $page.props.flash.import_errors" 
                            :key="index"
                            class="bg-white border border-red-200 rounded p-3"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-red-700">
                                    File: {{ error.file }}
                                </span>
                                <span class="text-sm text-red-600 bg-red-100 px-2 py-1 rounded">
                                    Row {{ error.row }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="font-medium">Field:</span> 
                                    <span class="text-red-600 ml-1">{{ formatFieldName(error.attribute) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Value:</span> 
                                    <span class="text-gray-600 ml-1">{{ error.values[error.attribute] || 'N/A' }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <span class="font-medium text-red-700">Error:</span>
                                <ul class="list-disc list-inside mt-1">
                                    <li v-for="(err, errIndex) in error.errors" :key="errIndex" class="text-red-600 text-sm">
                                        {{ err }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-sm text-red-600">
                        <p>Please fix these errors in your Excel file and try importing again.</p>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Analytics Records</h1>
                <div class="space-x-4">
                    <Link 
                        href="/analytics/stats"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-black rounded-md hover:bg-green-700"
                    >
                        View Statistics
                    </Link>
                </div>
            </div>

      

            <!-- File Upload Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">Import & Export Excel Files</h2>
    <form @submit.prevent="submit" class="space-y-4">
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
            <button 
                type="submit" 
                :disabled="form.processing"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
            >
                <span v-if="form.processing">Importing...</span>
                <span v-else>Import Files</span>
            </button>

            <button 
                type="button"
                @click="exportData"
                :disabled="exportForm.processing"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
            >
                <span v-if="exportForm.processing">Exporting...</span>
                <span v-else>Export Data</span>
            </button>

            <button 
                type="button"
                @click="clearAll"
                :disabled="clearAllForm.processing"
                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
            >
                Clear All Data
            </button>
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
                <option value="">All Countries</option>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Budget
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Platform
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Country
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
                        
                            </tr>
                            <tr v-if="!records?.data || records.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
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
                    :href="link.url + getFilterQueryString()"
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
import { ref, watch } from 'vue';
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
    }
});

// Add method to clear errors
const clearErrors = () => {
    router.reload();
};

// Add method to format field names
const formatFieldName = (field) => {
    const fieldMap = {
        'client': 'Client',
        'agency': 'Agency',
        'budget': 'Budget',
        'platform': 'Platform',
        'country': 'Country',
        'General': 'General Error'
    };
    return fieldMap[field] || field;
};

// Format agency display
const formatAgency = (agency) => {
    if (!agency || agency === 'direct' || agency === 'Unknown Agency') {
        return 'Direct';
    }
    return agency;
};

// Agency badge styling
const getAgencyBadgeClass = (agency) => {
    if (!agency || agency === 'direct' || agency === 'Unknown Agency') {
        return 'bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium';
    }
    return 'text-gray-900';
};

// Method to remove a country from selection
const removeCountry = (countryToRemove) => {
    filters.value.countries = filters.value.countries.filter(country => country !== countryToRemove);
};

// Method to build filter query string for pagination
const getFilterQueryString = () => {
    const params = new URLSearchParams();
    
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            if (Array.isArray(filters.value[key])) {
                // Handle array values (countries)
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

const form = useForm({
    files: null,
});

const exportForm = useForm({});
const clearAllForm = useForm({});

const filters = ref({
    search: props.filters?.search || '',
    platform: props.filters?.platform || '',
    countries: props.filters?.countries || (props.filters?.country ? [props.filters.country] : []), // Handle both single and multiple countries
    client: props.filters?.client || '',
    agency: props.filters?.agency || '',
    budget_tier: props.filters?.budget_tier || '',
    min_budget: props.filters?.min_budget || '',
    max_budget: props.filters?.max_budget || '',
});

// Watch for filter changes and update URL
watch(filters, (newFilters) => {
    // Create a clean filters object for the URL
    const urlFilters = { ...newFilters };
    
    // Remove empty arrays and strings
    Object.keys(urlFilters).forEach(key => {
        if (Array.isArray(urlFilters[key]) && urlFilters[key].length === 0) {
            delete urlFilters[key];
        } else if (urlFilters[key] === '' || urlFilters[key] === null || urlFilters[key] === undefined) {
            delete urlFilters[key];
        }
    });

    // Remove the old single country filter if we're using multiple countries
    if (urlFilters.countries && urlFilters.countries.length > 0) {
        delete urlFilters.country;
    }

    router.get('/analytics', urlFilters, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, { deep: true, immediate: false });

const submit = () => {
    if (!form.files || form.files.length === 0) {
        alert('Please select at least one file');
        return;
    }

    form.post('/analytics/import', {
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
    // Build export URL with current filters
    const params = new URLSearchParams();
    
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            if (Array.isArray(filters.value[key])) {
                // Handle array values (countries)
                filters.value[key].forEach(value => {
                    params.append(`${key}[]`, value);
                });
            } else {
                params.append(key, filters.value[key]);
            }
        }
    });

    const url = `/analytics/export?${params.toString()}`;
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'true');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const deleteRecord = (record) => {
    if (confirm('Are you sure you want to delete this record?')) {
        router.delete(`/analytics/${record.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show success message or refresh data
            }
        });
    }
};

const clearAll = () => {
    if (confirm('Are you sure you want to delete ALL records? This action cannot be undone.')) {
        clearAllForm.post('/analytics/clear-all', {
            preserveScroll: true,
            onSuccess: () => {
                console.log('All data cleared successfully');
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error clearing data:', errors);
                alert('Error clearing data. Please try again.');
            },
        });
    }
};

const resetFilters = () => {
    filters.value = {
        search: '',
        platform: '',
        countries: [], // Reset to empty array
        client: '',
        agency: '',
        budget_tier: '',
        min_budget: '',
        max_budget: '',
    };
};

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