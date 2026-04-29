<!DOCTYPE html>
<html lang="en" x-data="onboardingForm()" x-init="init()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Outfit', sans-serif; }
        /* .drag-drop {
            @apply mt-1 block w-full border-2 border-dashed rounded p-4 text-center cursor-pointer bg-white dark:bg-gray-700 transition-colors;
        } */

        .wallet-bg {
            background: radial-gradient(circle at top left, rgba(99, 102, 241, 0.2), transparent 70%),
                        radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.2), transparent 70%);
        }
        .wallet-svg-bg {
            position: relative;
            overflow: hidden;
        }
        .wallet-svg-bg::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 600'%3E%3Cdefs%3E%3CradialGradient id='grad' cx='50%25' cy='50%25' r='50%25'%3E%3Cstop offset='0%25' stop-color='%23bfdbfe'/%3E%3Cstop offset='100%25' stop-color='%230e7490'/%3E%3C/radialGradient%3E%3C/defs%3E%3Ccircle cx='300' cy='300' r='300' fill='url(%23grad)' fill-opacity='0.15'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.3;
            z-index: 0;
        }
        .wallet-svg-bg > * {
            position: relative;
            z-index: 1;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
<div class="h-screen flex overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Off-canvas menu for mobile -->
    <div class="md:hidden">
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex">
            <div @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50"></div>
            <div class="relative flex-1 flex flex-col max-w-[12rem] w-full bg-blue-700 dark:bg-gray-800 text-white transform transition duration-200 ease-in-out border-r" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="flex items-center justify-between h-16 px-4 border-b border-blue-800 dark:border-gray-700">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a7/React-icon.svg" alt="Logo" class="h-8">
                    <button @click="sidebarOpen = false" class="text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <nav class="mt-10 space-y-4 px-4 text-base font-semibold">
                    <div>
                        <h4 class="text-lg text-white font-extrabold mb-2">Collections</h4>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Dashboard</a>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Transactions</a>
                    </div>
                    <div>
                        <h4 class="text-lg text-white font-extrabold mt-6 mb-2">Disbursements</h4>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Payouts</a>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Schedules</a>
                    </div>
                    <div>
                        <h4 class="text-lg text-white font-extrabold mt-6 mb-2">Merchants</h4>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Merchant List</a>
                        <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Add Merchant</a>
                    </div>
                </nav>
                <div class="mt-auto px-4 py-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar for desktop -->
    <aside class="hidden md:flex md:flex-shrink-0 bg-blue-700 dark:bg-gray-800 text-white w-64 flex-col border-r border-blue-800 dark:border-gray-700">
        <div class="flex items-center justify-center h-16 shadow-md">
            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a7/React-icon.svg" alt="Logo" class="h-8">
        </div>
        <nav class="mt-10 flex-1 space-y-4 px-4 text-base font-semibold">
            <div>
                <h4 class="text-lg text-white font-extrabold mb-2">Collections</h4>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Dashboard</a>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Transactions</a>
            </div>
            <div>
                <h4 class="text-lg text-white font-extrabold mt-6 mb-2">Disbursements</h4>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Payouts</a>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Schedules</a>
            </div>
            <div>
                <h4 class="text-lg text-white font-extrabold mt-6 mb-2">Merchants</h4>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Merchant List</a>
                <a href="#" class="block py-2.5 px-4 rounded hover:bg-blue-600 dark:hover:bg-gray-700 transition">Add Merchant</a>
            </div>
        </nav>
        <div class="px-4 py-4">
            {{-- <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">Logout</button>
            </form> --}}

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <img class="h-8 w-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Admin&background=random" alt="Admin">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 bottom-full mb-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg py-1 z-50">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">Logout</button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden ">
        <!-- Top Bar -->
        <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 border-b shadow-sm">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-200 focus:outline-none md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-100 ml-4">Dashboard</h1>
            </div>
           
            <div class="flex items-center gap-4">
                <span class="text-gray-600 dark:text-gray-300">Welcome, Admin</span>
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 px-3 py-1 rounded">
                    <span x-text="darkMode ? '☀️ Light' : '🌙 Dark'"></span>
                </button>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <img class="h-8 w-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Admin&background=random" alt="Admin">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg py-1 z-50">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Dashboard Cards -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-10">

            <div class="relative mb-8 rounded-lg overflow-hidden shadow-md border border-blue-100 dark:border-gray-700">
                <img src="https://images.unsplash.com/photo-1508780709619-79562169bc64?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80" alt="API Illustration" class="absolute inset-0 w-full h-full object-cover opacity-15 dark:opacity-10">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 dark:from-gray-800 dark:via-gray-900 dark:to-black opacity-30"></div>
                <div class="relative z-10 p-6 md:p-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">Profile</h1>
                    <nav class="text-sm text-gray-700 dark:text-gray-300" aria-label="Breadcrumb">
                        <ol class="list-reset flex space-x-2">
                            <li><a href="#" class="hover:underline">Home</a></li>
                            <li><span class="mx-2">/</span></li>
                            <li class="text-gray-500 dark:text-gray-400">Profile</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="mb-10 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Business Onboarding</h2>

                <template x-for="s in steps" :key="s">
                    <div x-show="step === s" class="space-y-4">
                        <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400" x-text="'Step ' + s + ': ' + stepTitles[s-1]"></h3>
                        <div class="grid grid-cols-1 gap-4">
                            <template x-for="(field, index) in fields[s-1]" :key="index">
                                <div>
                                    <template x-if="field.type === 'file'">
                                        <label class="block w-full">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="field.label"></span>
                                            <div class="mt-1 block w-full border-2 border-dashed rounded p-4 text-center cursor-pointer bg-white dark:bg-gray-700 transition-colors relative"
                                                 @dragover.prevent="dragOver = true"
                                                 @dragleave.prevent="dragOver = false"
                                                 @drop.prevent="handleDragDrop($event, field.key); dragOver = false"
                                                 :class="{ 'border-blue-500': dragOver }">
                                                <input type="file" class="absolute inset-0 opacity-0 cursor-pointer"
                                                       @change="handleFileUpload($event, field.key)">
                                                <div class="text-sm text-gray-500 dark:text-gray-300">
                                                    <template x-if="!formData[field.key]">
                                                        <p>Drag & drop or click to upload</p>
                                                    </template>
                                                    <template x-if="formData[field.key]">
                                                        <p x-text="formData[field.key]?.name"></p>
                                                    </template>
                                                </div>
                                            </div>
                                            <p x-show="errors[field.key]" class="text-red-500 text-sm mt-1">This field is required</p>
                                        </label>
                                    </template>

                                    <template x-if="field.type !== 'file'">
                                        <div>
                                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200" x-text="field.label"></label>
                                            <input :type="field.type"
                                                   x-model="formData[field.key]"
                                                   @input="errors[field.key] = ''"
                                                   :class="[
                                                       'px-4 py-2 border rounded w-full',
                                                       'text-gray-900 dark:text-white',
                                                       errors[field.key] ? 'border-red-500' : 'border-gray-300 dark:border-gray-600',
                                                       'bg-white dark:bg-gray-700'
                                                   ]">
                                            <p x-show="errors[field.key]" class="text-red-500 text-sm mt-1">This field is required</p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-between mt-6">
                            <button x-show="s > 1" @click="prevStep" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Previous</button>
                            <button x-show="s < steps.length" @click="nextStep" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Next</button>
                            <button x-show="s === steps.length" @click="submitForm" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Submit</button>
                        </div>
                    </div>
                </template>
            </div>



            

          <!-- API Settings Forms -->
       
            <!-- Profile Tabs Section -->
            <div x-data="{ tab: 'profile' }" class=" mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md mt-8">
                    <div class="flex flex-col md:flex-row border-b border-gray-200 dark:border-gray-700">
                    <button @click="tab = 'profile'" :class="tab === 'profile' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'" class="w-full text-left px-6 py-4 font-medium hover:text-blue-600 focus:outline-none">Profile Update</button>
                    <button @click="tab = 'security'" :class="tab === 'security' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'" class="w-full text-left px-6 py-4 font-medium hover:text-blue-600 focus:outline-none">Security</button>
                    <button @click="tab = 'api'" :class="tab === 'api' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'" class="w-full text-left px-6 py-4 font-medium hover:text-blue-600 focus:outline-none">API Settings</button>
                    <button @click="tab = 'docs'" :class="tab === 'docs' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'" class="w-full text-left px-6 py-4 font-medium hover:text-blue-600 focus:outline-none">Documentation</button>
                    </div>
                
                    <div class="p-6">
                    <!-- Profile Update Tab -->
                    <div x-show="tab === 'profile'">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Update Profile</h2>
                        <form class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium">Full Name</label>
                            <input type="text" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium">Email Address</label>
                            <input type="email" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                        </form>
                    </div>
                
                    <!-- Security Tab -->
                    <div x-show="tab === 'security'">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Security Settings</h2>
                        <form class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium">Current Password</label>
                            <input type="password" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium">New Password</label>
                            <input type="password" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Change Password</button>
                        </form>
                    </div>
                
                    <!-- API Settings Tab -->
                    <div x-show="tab === 'api'">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">API Settings</h2>
                        <form class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium">API Key</label>
                            <input type="text" placeholder="Your API Key" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium">Secret Key</label>
                            <input type="text" placeholder="Your Secret Key" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Update</button>
                        </form>
                    </div>
                
                    <!-- Documentation Tab -->
                    <div x-show="tab === 'docs'">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Developer Documentation</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Find all the information you need to integrate with our platform.</p>
                        <a href="#" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">View Docs</a>
                    </div>
                    </div>
                </div>
  

          




            <!-- Copyright Footer -->
            <div class="mt-10 text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; 2025 Nexasam Technologies. All rights reserved.
            </div>
            
        </main>
    </div>
</div>

<script>
function onboardingForm() {
    return {
        step: 1,
        stepTitles: ['Basic Details', 'Directors', 'Verifications', 'Document Uploads'],
        steps: [1, 2, 3, 4],
        fields: [
            [
                { label: 'Full Name', key: 'fullName', type: 'text' },
                { label: 'Business Name', key: 'businessName', type: 'text' },
                { label: 'Email Address', key: 'email', type: 'email' },
                { label: 'Phone Number', key: 'phone', type: 'text' },
                { label: 'RC Number', key: 'rcNumber', type: 'text' },
                { label: 'Business Category', key: 'category', type: 'text' },
            ],
            [
                { label: 'Director 1 Name', key: 'director1Name', type: 'text' },
                { label: 'Director 1 Phone', key: 'director1Phone', type: 'text' },
                { label: 'Director 1 Email', key: 'director1Email', type: 'email' },
                { label: 'Director 2 Name', key: 'director2Name', type: 'text' },
                { label: 'Director 2 Phone', key: 'director2Phone', type: 'text' },
                { label: 'Director 2 Email', key: 'director2Email', type: 'email' },
            ],
            [
                { label: 'Business Address', key: 'address', type: 'text' },
                { label: 'Tax ID', key: 'taxId', type: 'text' },
                { label: 'TIN', key: 'tin', type: 'text' },
                { label: 'NIN', key: 'nin', type: 'text' },
                { label: 'Bank Name', key: 'bankName', type: 'text' },
                { label: 'Account Number', key: 'accountNumber', type: 'text' },
            ],
            [
                { label: 'CAC Document', key: 'cacDoc', type: 'file' },
                { label: 'Utility Bill', key: 'utilityBill', type: 'file' },
                { label: 'Valid ID', key: 'validId', type: 'file' },
                { label: 'Passport Photo', key: 'passport', type: 'file' },
                { label: 'Signature', key: 'signature', type: 'file' },
                { label: 'Business Logo', key: 'logo', type: 'file' },
            ]
        ],
        formData: {},
        errors: {},
        init() {
            this.darkMode = localStorage.getItem('darkMode') === 'true';
            this.formData = JSON.parse(localStorage.getItem('onboardingFormData') || '{}');
        },
        validateStep() {
            this.errors = {};
            for (const field of this.fields[this.step - 1]) {
                const value = this.formData[field.key];
                if (!value || (field.type === 'file' && value instanceof File === false)) {
                    this.errors[field.key] = true;
                }
            }
            return Object.keys(this.errors).length === 0;
        },
        nextStep() {
            if (this.validateStep()) {
                this.saveData();
                if (this.step < this.steps.length) this.step++;
            }
        },
        prevStep() {
            if (this.step > 1) this.step--;
        },
        saveData() {
            localStorage.setItem('onboardingFormData', JSON.stringify(this.formData));
        },
        submitForm() {
            if (this.validateStep()) {
                this.saveData();
                alert('Form Submitted! Check your console for data.');
                console.log(this.formData);
                localStorage.removeItem('onboardingFormData');
            }
        }
    }
}
</script>
</body>
</html>
