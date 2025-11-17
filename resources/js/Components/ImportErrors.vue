<template>
    <div v-if="errors && errors.length > 0" class="mb-6">
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-medium text-red-800">
                    Import Errors & Skipped Records ({{ errors.length }})
                </h3>
                <div class="space-x-2">
                    <button 
                        @click="exportErrors"
                        class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
                    >
                        Export Errors
                    </button>
                    <button 
                        @click="$emit('clear')"
                        class="text-red-600 hover:text-red-800 text-sm font-medium"
                    >
                        Dismiss
                    </button>
                </div>
            </div>
            
            <!-- Error Summary -->
            <div class="mb-4 p-3 bg-white rounded border">
                <h4 class="font-medium text-red-800 mb-2">Summary:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-xl font-bold text-green-600">{{ stats?.imported || 0 }}</div>
                        <div class="text-green-700">Successfully Imported</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-orange-600">{{ stats?.failed || 0 }}</div>
                        <div class="text-orange-700">Skipped Records</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-blue-600">{{ stats?.total_processed || 0 }}</div>
                        <div class="text-blue-700">Total Processed</div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <div 
                    v-for="(error, index) in errors" 
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

                    <!-- Additional Info -->
                    <div class="mt-2 text-xs text-orange-600">
                        <p>This record was not imported into the database due to validation issues.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <div class="flex items-start">
                    <span class="text-yellow-600 mr-2 mt-0.5">💡</span>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium">How to fix:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Check that all records have valid budget amounts (greater than 0)</li>
                            <li>Ensure client names are provided and not empty</li>
                            <li>Verify that dates are in a recognizable format</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    errors: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['clear']);

// Helper methods
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

const exportErrors = () => {
    const errorData = props.errors.map(error => ({
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
</script>