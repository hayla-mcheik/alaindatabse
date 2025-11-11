<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

// Upload form for Excel files
const form = useForm({
    excel_files: [],
});

const uploadSuccess = ref(false);
const filters = ref({
    client: '',
    agency: '',
    budget: '',
    platform: '',
    country: '',
});

const filteredData = ref([]);
const filterLoading = ref(false);

// Submit upload form
const submit = () => {
    uploadSuccess.value = false;
    form.post(route('analytics.importData'), {
        onSuccess: () => {
            uploadSuccess.value = true;
            form.reset('excel_files');
        },
    });
};

// Handle multiple file uploads
const handleFileChange = (event) => {
    form.excel_files = Array.from(event.target.files);
    if (form.errors.excel_files) delete form.errors.excel_files;
};

// Apply filters (fetch filtered data)
const applyFilters = () => {
    filterLoading.value = true;
    router.get(
        route('analytics.filter'),
        filters.value,
        {
            preserveState: true,
            onSuccess: (page) => {
                // response comes from Laravel as JSON (so we use page.props)
                filteredData.value = page.props.data || [];
                filterLoading.value = false;
            },
            onError: () => {
                filterLoading.value = false;
            },
        }
    );
};
</script>

<template>
    <Head title="Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Data Analytics & Import
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg p-8">
                    <!-- ============================ -->
                    <!-- Upload Section -->
                    <!-- ============================ -->
                    <h3 class="text-2xl font-bold mb-6 text-indigo-700">
                        Import Excel Files
                    </h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="excel_files" class="block text-sm font-medium text-gray-700 mb-2">
                                Select 3 Excel Files for Import
                            </label>
                            <input
                                id="excel_files"
                                type="file"
                                multiple
                                @change="handleFileChange"
                                :class="{ 'border-red-500': form.errors.excel_files }"
                                class="w-full border border-gray-300 rounded-lg p-2 focus:border-indigo-500 transition duration-150"
                                accept=".xlsx, .xls, .csv"
                            />

                            <div v-if="form.excel_files.length" class="mt-2 text-sm text-gray-600">
                                Selected:
                                <span v-for="(file, index) in form.excel_files" :key="index" class="mr-3 p-1 bg-indigo-50 rounded-md">
                                    {{ file.name }}
                                </span>
                            </div>

                            <p v-if="form.errors.excel_files" class="mt-2 text-sm text-red-600">
                                {{ form.errors.excel_files }}
                            </p>
                        </div>

                        <div v-if="uploadSuccess" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                            <p class="font-bold">Success!</p>
                            <p>Files uploaded and merged successfully.</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || form.excel_files.length === 0"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Processing...' : 'Upload & Merge Files' }}
                        </button>
                    </form>

                    <!-- ============================ -->
                    <!-- Filter Section -->
                    <!-- ============================ -->
                    <div class="mt-10 pt-6 border-t border-gray-200">
                        <h3 class="text-xl font-bold mb-4 text-gray-800">Filter Merged Data</h3>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <input type="text" v-model="filters.client" class="w-full border rounded-md p-2" placeholder="Client name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Agency</label>
                                <input type="text" v-model="filters.agency" class="w-full border rounded-md p-2" placeholder="Agency name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                                <input type="text" v-model="filters.budget" class="w-full border rounded-md p-2" placeholder="Budget" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                                <input type="text" v-model="filters.platform" class="w-full border rounded-md p-2" placeholder="Platform" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <input type="text" v-model="filters.country" class="w-full border rounded-md p-2" placeholder="e.g. Qatar, Kuwait..." />
                            </div>
                        </div>

                        <button
                            @click="applyFilters"
                            :disabled="filterLoading"
                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition disabled:opacity-50"
                        >
                            {{ filterLoading ? 'Filtering...' : 'Apply Filters' }}
                        </button>

                        <!-- ============================ -->
                        <!-- Filtered Results Table -->
                        <!-- ============================ -->
                        <div v-if="filteredData.length" class="mt-8 overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                <thead class="bg-gray-100 text-gray-700 font-semibold">
                                    <tr>
                                        <th class="px-4 py-2 border">Client</th>
                                        <th class="px-4 py-2 border">Agency</th>
                                        <th class="px-4 py-2 border">Budget</th>
                                        <th class="px-4 py-2 border">Platform</th>
                                        <th class="px-4 py-2 border">Country</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in filteredData" :key="index" class="odd:bg-white even:bg-gray-50">
                                        <td class="px-4 py-2 border">{{ row[0] }}</td>
                                        <td class="px-4 py-2 border">{{ row[1] }}</td>
                                        <td class="px-4 py-2 border">{{ row[2] }}</td>
                                        <td class="px-4 py-2 border">{{ row[3] }}</td>
                                        <td class="px-4 py-2 border">{{ row[4] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p v-else-if="!filterLoading" class="mt-4 text-gray-500">
                            No filtered data to display yet.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
