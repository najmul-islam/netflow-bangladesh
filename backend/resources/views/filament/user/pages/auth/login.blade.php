<x-filament-panels::page.simple>
    <div class="fi-simple-main-ctn">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-gradient-to-br from-[#0B2E58] to-[#F76704] rounded-2xl flex items-center justify-center mb-6 shadow-xl">
                <span class="text-2xl font-bold text-white">NF</span>
            </div>
            <h1 class="text-3xl font-bold text-[#0B2E58] mb-2">{{ $this->getHeading() }}</h1>
            <p class="text-gray-600">{{ $this->getSubheading() }}</p>
        </div>

        <!-- Login Form -->
        <div class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-between">
                <a href="#" class="text-sm text-[#F76704] hover:text-[#0B2E58] font-medium transition-colors">
                    Forgot your password?
                </a>
            </div>

            <x-filament::button
                type="submit"
                form="authenticate"
                size="lg"
                class="w-full bg-gradient-to-r from-[#0B2E58] to-[#0B2E58] hover:from-[#0B2E58] hover:to-[#F76704] text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300"
            >
                Sign In
            </x-filament::button>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                Don't have an account? 
                <a href="#" class="text-[#F76704] hover:text-[#0B2E58] font-medium transition-colors">
                    Contact Administrator
                </a>
            </p>
        </div>

        <!-- Features -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div class="p-4">
                <div class="w-12 h-12 bg-blue-50 rounded-lg mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#0B2E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-[#0B2E58] mb-1">Expert Courses</h3>
                <p class="text-xs text-gray-500">Learn from industry professionals</p>
            </div>
            
            <div class="p-4">
                <div class="w-12 h-12 bg-orange-50 rounded-lg mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#F76704]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-[#0B2E58] mb-1">Certifications</h3>
                <p class="text-xs text-gray-500">Earn recognized certificates</p>
            </div>
            
            <div class="p-4">
                <div class="w-12 h-12 bg-green-50 rounded-lg mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-[#0B2E58] mb-1">Fast Learning</h3>
                <p class="text-xs text-gray-500">Accelerated learning paths</p>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
