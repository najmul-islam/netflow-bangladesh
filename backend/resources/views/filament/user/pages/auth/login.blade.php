<x-filament-panels::page.simple>
    <div class="min-h-screen bg-gradient-to-br from-[#0B2E58] via-blue-800 to-[#F76704] flex items-center justify-center p-4">
        <style>
            /* Hide all Filament defaults completely */
            .fi-simple-header,
            .fi-simple-layout .fi-header,
            .fi-layout-header,
            .fi-simple-main > .fi-section-header,
            .fi-simple-main > .fi-header,
            .fi-page-header,
            .fi-breadcrumbs,
            .fi-simple-layout .fi-logo,
            .fi-simple-layout .fi-brand {
                display: none !important;
            }
            
            /* Ensure full viewport coverage */
            .fi-simple-layout,
            .fi-simple-main {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: 100vh !important;
            }
            
            /* Style form inputs to match design */
            .fi-input input {
                @apply w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F76704] focus:border-transparent transition-all duration-200 bg-white;
            }
            
            /* Style form labels */
            .fi-field-label {
                @apply text-[#0B2E58] font-medium mb-2;
            }
            
            /* Style checkbox for remember me */
            .fi-checkbox input {
                @apply text-[#F76704] focus:ring-[#F76704];
            }
        </style>
        
        <div class="w-full max-w-md">
            <!-- Main login card -->
            <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
                <!-- Brand section -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#0B2E58] to-[#F76704] rounded-xl shadow-lg mb-4">
                        <span class="text-2xl font-bold text-white">NF</span>
                    </div>
                    <h1 class="text-2xl font-bold text-[#0B2E58] mb-1">NetFlow Bangladesh</h1>
                    <p class="text-gray-600 text-sm">Sign in to continue your learning journey</p>
                </div>

                <!-- Login form -->
                <form wire:submit="authenticate" class="space-y-5">
                    {{ $this->form }}
                    
                    <!-- Submit button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-[#0B2E58] to-blue-700 hover:from-[#0B2E58] hover:to-[#F76704] text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[#F76704] focus:ring-offset-2"
                    >
                        <span class="inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Sign In
                        </span>
                    </button>
                </form>

                <!-- Footer links -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center text-sm">
                        <a href="#" class="text-[#F76704] hover:text-[#0B2E58] transition-colors">
                            Forgot password?
                        </a>
                        <a href="#" class="text-gray-600 hover:text-[#0B2E58] transition-colors">
                            Need help?
                        </a>
                    </div>
                </div>
            </div>

            <!-- Features grid -->
            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-white font-medium text-xs">Expert Courses</h3>
                    <p class="text-white/80 text-xs mt-1">Professional training</p>
                </div>
                
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-white font-medium text-xs">Certifications</h3>
                    <p class="text-white/80 text-xs mt-1">Recognized credentials</p>
                </div>
                
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-white font-medium text-xs">Fast Learning</h3>
                    <p class="text-white/80 text-xs mt-1">Accelerated paths</p>
                </div>
            </div>

            <!-- Copyright -->
            <p class="text-center text-white/60 text-xs mt-6">
                © 2025 NetFlow Bangladesh. All rights reserved.
            </p>
        </div>
    </div>
</x-filament-panels::page.simple>
