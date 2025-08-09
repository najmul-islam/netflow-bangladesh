<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Welcome Card -->
        <div class="col-span-full">
            <div class="bg-gradient-to-r from-[#0B2E58] to-[#F76704] rounded-xl p-6 text-white shadow-lg">
                <h2 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->first_name }}!</h2>
                <p class="text-blue-100">Ready to continue your learning journey?</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Courses</p>
                    <p class="text-2xl font-bold text-[#0B2E58]">
                        {{ auth()->user()->enrollments()->where('status', 'active')->count() }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-[#0B2E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-2xl font-bold text-[#0B2E58]">
                        {{ auth()->user()->enrollments()->where('status', 'completed')->count() }}</p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Certificates</p>
                    <p class="text-2xl font-bold text-[#0B2E58]">{{ auth()->user()->certificates()->count() }}</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <svg class="w-6 h-6 text-[#F76704]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-span-full lg:col-span-2">
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-lg font-semibold text-[#0B2E58] mb-4">Recent Activity</h3>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg class="w-4 h-4 text-[#0B2E58]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Course Progress Updated</p>
                            <p class="text-xs text-gray-500">2 hours ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
            <h3 class="text-lg font-semibold text-[#0B2E58] mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="#"
                    class="block w-full text-left p-3 bg-gradient-to-r from-[#0B2E58] to-[#0B2E58] text-white rounded-lg hover:from-[#0B2E58] hover:to-[#F76704] transition-all duration-300 transform hover:scale-105">
                    Browse Courses
                </a>
                <a href="#"
                    class="block w-full text-left p-3 bg-blue-50 text-[#0B2E58] rounded-lg hover:bg-blue-100 transition-colors">
                    View Progress
                </a>
                <a href="#"
                    class="block w-full text-left p-3 bg-orange-50 text-[#F76704] rounded-lg hover:bg-orange-100 transition-colors">
                    My Certificates
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
