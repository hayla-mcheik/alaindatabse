<template>
  <Head title="Excel Data Software" />

  <div class="relative min-h-screen bg-gray-50 dark:bg-black flex flex-col items-center justify-center px-6 text-center">
    <!-- Background Grid -->
    <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/squared-metal.png')]"></div>

    <!-- Hero Section -->
    <div class="relative z-10 max-w-3xl mx-auto py-20">
      <img
        src="https://cdn-icons-png.flaticon.com/512/2965/2965335.png"
        alt="Excel Data Icon"
        class="mx-auto w-24 h-24 mb-6"
      />

      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        Smart Software for Excel Data Management
      </h1>

      <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
        Import, merge, filter, and analyze your Excel files effortlessly.
        Designed for data-driven teams who value automation and clarity.
      </p>

      <div class="flex flex-wrap justify-center gap-4">
        <Link
          :href="route('login')"
          class="px-6 py-3 bg-[#22C55E] text-white rounded-xl font-semibold hover:bg-[#16A34A] transition"
        >
          Get Started
        </Link>
        <Link
          :href="route('register')"
          class="px-6 py-3 bg-white text-gray-800 border border-gray-300 rounded-xl font-semibold hover:bg-gray-100 transition dark:bg-zinc-900 dark:text-white dark:border-zinc-700"
        >
          Learn More
        </Link>
      </div>
    </div>

    <!-- Feature Cards -->
    <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-8 mt-10 max-w-6xl mx-auto">
      <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-md">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-12 mx-auto mb-4" />
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Import Excel</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">Upload Excel or CSV files easily and preview your data instantly.</p>
      </div>

      <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-md">
        <img src="https://cdn-icons-png.flaticon.com/512/3039/3039387.png" class="w-12 mx-auto mb-4" />
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Merge Sheets</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">Combine multiple Excel sheets into one unified dataset.</p>
      </div>

      <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-md">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" class="w-12 mx-auto mb-4" />
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Filter & Analyze</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">Filter by columns, remove duplicates, and export reports.</p>
      </div>
    </div>
  </div>
</template>
