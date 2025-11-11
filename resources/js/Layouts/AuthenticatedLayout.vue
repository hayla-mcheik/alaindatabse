<script setup>
import { ref } from 'vue';
import { getCurrentInstance } from 'vue'; // Used to potentially mock globals

const showingNavigationDropdown = ref(false);

// --- MOCK ENVIRONMENT FOR STANDALONE TESTING ---
// NOTE: In your production Inertia/Laravel environment, you should remove this mock section
// and uncomment your original component imports and keep the original usage.

// Mock data for the user (replaces $page.props.auth.user)
const mockUser = {
    name: 'Test User',
    email: 'test@example.com',
};

// Mock function for routing (replaces the global route() function)
const route = (name) => {
    // In a real application, this would generate a URL. Here, it just uses a hash for navigation.
    return `#${name}`;
};

// Helper to check the "current" route for active styling
const currentRoute = ref('dashboard'); // Change this to 'analytics' to see the other link active
const isRouteActive = (name) => currentRoute.value === name;
// --- END MOCK ENVIRONMENT ---


// Placeholder component functionality for when imports are removed
// The original <Link> components are replaced by <a> tags.
// The original <NavLink> components are replaced by <a> tags with dynamic classes.
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans antialiased">
        
        <!-- 1. TOP NAVBAR (Fixed) -->
        <nav
            class="fixed w-full z-30 bg-white border-b border-gray-100 shadow-md"
            style="height: 4rem;"
        >
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo (Replaced ApplicationLogo with a simple text link) -->
                        <div class="flex shrink-0 items-center">
                            <a href="dashboard">
                                <span class="text-xl font-bold text-indigo-600">ExcelFilter</span>
                            </a>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Settings Dropdown (Replaced Dropdown with standard HTML) -->
                        <div class="relative ms-3">
                            <div class="inline-flex rounded-md">
                                <button
                                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                >
                                    {{ mockUser.name }}

                                    <svg
                                        class="-me-0.5 ms-2 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <!-- Mock Dropdown Content - will show up on mobile toggle now for simplicity -->
                            <div
                                v-show="showingNavigationDropdown"
                                class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                            >
                                <div class="py-1">
                                    <a :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Profile
                                    </a>
                                    <a :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Log Out
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu (Sidebar on Mobile) -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden absolute top-16 left-0 w-full bg-white shadow-lg z-20"
            >
                <!-- Sidebar Links for Mobile -->
                <div class="space-y-1 pb-3 pt-2 border-b border-gray-200">
                    <!-- Responsive NavLink Mock -->
                    <a href="dashboard"
                       :class="[isRouteActive('dashboard') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300']"
                       class="block w-full ps-3 pe-4 py-2 border-l-4 text-base font-medium transition duration-150 ease-in-out"
                    >
                        Dashboard
                    </a>
                    <a href="analytics"
                       :class="[isRouteActive('analytics') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300']"
                       class="block w-full ps-3 pe-4 py-2 border-l-4 text-base font-medium transition duration-150 ease-in-out"
                    >
                        Analytics Report
                    </a>
                </div>

                <!-- Responsive Settings Options -->
                <div
                    class="border-t border-gray-200 pb-1 pt-4"
                >
                    <div class="px-4">
                        <div
                            class="text-base font-medium text-gray-800"
                        >
                            {{ mockUser.name }}
                        </div>
                        <div class="text-sm font-medium text-gray-500">
                            {{ mockUser.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <!-- Responsive Dropdown Link Mocks -->
                        <a :href="route('profile.edit')" class="block w-full ps-3 pe-4 py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                            Profile
                        </a>
                        <button :href="route('logout')" class="block w-full text-left ps-3 pe-4 py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                            Log Out
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 2. MAIN LAYOUT: Sidebar + Content -->
        <!-- pt-16 ensures content starts below the fixed 4rem (h-16) navbar -->
        <div class="flex pt-16">
            <!-- SIDEBAR (Desktop) -->
            <aside 
                class="w-64 flex-shrink-0 bg-white shadow-xl p-4 sticky top-16 hidden lg:block overflow-y-auto"
                style="height: calc(100vh - 4rem);"
            >
                <div class="space-y-2">
                    <!-- Dashboard Link (NavLink Mock) -->
                    <a
                        href="dashboard"
                        :class="[isRouteActive('dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50']"
                     
                        class="block w-full text-left py-2 px-3 rounded-lg transition duration-150 ease-in-out"
                    >
                        <!-- Icon Placeholder -->
                        <svg class="inline-block w-5 h-5 me-3" :class="[isRouteActive('dashboard') ? 'text-indigo-500' : 'text-gray-500']" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>
                    
                    <!-- Analytics Report Link (NavLink Mock) -->
                    <a
                        href="analytics"
                        :class="[isRouteActive('analytics') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50']"
                     
                        class="block w-full text-left py-2 px-3 rounded-lg transition duration-150 ease-in-out"
                    >
                        <!-- Icon Placeholder -->
                        <svg class="inline-block w-5 h-5 me-3" :class="[isRouteActive('analytics') ? 'text-indigo-500' : 'text-gray-500']" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-8v8m-4-8v8M4 16h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Analytics Report
                    </a>
                </div>
            </aside>

            <!-- CONTENT WRAPPER -->
            <div class="flex-1 min-w-0">
                <!-- Page Heading (Header) -->
                <header
                    class="bg-white shadow"
                    v-if="$slots.header"
                >
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                    <!-- Placeholder content to push the page down and show sidebar stickiness -->
              
                </main>
            </div>
        </div>
    </div>
</template>