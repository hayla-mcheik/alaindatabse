<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
              <div class="p-8">
                        <div class="bg-white p-6 rounded-lg shadow-md h-96">
                            <h2 class="text-xl font-semibold text-gray-800">Main Content Area</h2>
                            <p class="text-gray-600">This area is responsive and sits next to the sidebar on large screens.</p>
                        </div>
                        <div class="mt-8 bg-white p-6 rounded-lg shadow-md h-96">
                             <h2 class="text-xl font-semibold text-gray-800">Scrollable Section 1</h2>
                        </div>
                        <div class="mt-8 bg-white p-6 rounded-lg shadow-md h-96">
                             <h2 class="text-xl font-semibold text-gray-800">Scrollable Section 2</h2>
                        </div>
                    </div>
    </AuthenticatedLayout>
</template>
