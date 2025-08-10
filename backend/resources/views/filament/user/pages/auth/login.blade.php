<x-filament-panels::page.simple>
    <div
        style="min-height: 100vh; background: linear-gradient(135deg, #0B2E58 0%, #1e40af 25%, #F76704 75%, #ea580c 100%); display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <style>
            /* Hide all Filament defaults completely */
            .fi-simple-header,
            .fi-simple-layout .fi-header,
            .fi-layout-header,
            .fi-simple-main>.fi-section-header,
            .fi-simple-main>.fi-header,
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
            .fi-input input,
            .fi-input-wrapper input,
            input[type="email"],
            input[type="password"],
            input[type="text"] {
                width: 100% !important;
                padding: 0.875rem 1rem !important;
                border: 2px solid #e2e8f0 !important;
                border-radius: 0.75rem !important;
                background-color: #ffffff !important;
                font-size: 1rem !important;
                line-height: 1.5 !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
            }

            .fi-input input:focus,
            .fi-input-wrapper input:focus,
            input[type="email"]:focus,
            input[type="password"]:focus,
            input[type="text"]:focus {
                outline: none !important;
                border-color: #F76704 !important;
                box-shadow: 0 0 0 3px rgba(247, 103, 4, 0.1), 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
                transform: translateY(-1px) !important;
            }

            .fi-input input:hover,
            .fi-input-wrapper input:hover,
            input[type="email"]:hover,
            input[type="password"]:hover,
            input[type="text"]:hover {
                border-color: #cbd5e1 !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            }

            /* Style form labels */
            .fi-field-label,
            .fi-fo-field-wrp-label,
            label {
                color: #0B2E58 !important;
                font-weight: 600 !important;
                font-size: 0.875rem !important;
                margin-bottom: 0.5rem !important;
                display: block !important;
            }

            /* Style checkbox for remember me */
            .fi-checkbox input,
            input[type="checkbox"] {
                width: 1.125rem !important;
                height: 1.125rem !important;
                color: #F76704 !important;
                border-radius: 0.375rem !important;
                border: 2px solid #d1d5db !important;
                background-color: #ffffff !important;
            }

            .fi-checkbox input:focus,
            input[type="checkbox"]:focus {
                box-shadow: 0 0 0 3px rgba(247, 103, 4, 0.1) !important;
                border-color: #F76704 !important;
            }

            .fi-checkbox input:checked,
            input[type="checkbox"]:checked {
                background-color: #F76704 !important;
                border-color: #F76704 !important;
            }

            /* Style form fields container */
            .fi-fo-field-wrp,
            .fi-field,
            .fi-input {
                margin-bottom: 1.25rem !important;
            }

            /* Style form validation errors */
            .fi-fo-field-wrp-error-message,
            .fi-field-error {
                color: #dc2626 !important;
                font-size: 0.875rem !important;
                margin-top: 0.5rem !important;
            }

            /* Style password field toggle */
            .fi-input-suffix {
                padding-right: 0.75rem !important;
            }

            .fi-input-suffix button {
                color: #6b7280 !important;
                padding: 0.25rem !important;
                border-radius: 0.375rem !important;
                transition: color 0.2s !important;
            }

            .fi-input-suffix button:hover {
                color: #F76704 !important;
            }

            /* Additional form styling */
            .fi-form {
                display: flex !important;
                flex-direction: column !important;
                gap: 1.25rem !important;
            }

            .fi-form .fi-fieldset {
                display: flex !important;
                flex-direction: column !important;
                gap: 1.25rem !important;
            }

            /* Style remember me label */
            .fi-checkbox .fi-field-label {
                display: flex !important;
                align-items: center !important;
                gap: 0.5rem !important;
                margin-bottom: 0 !important;
                font-size: 0.875rem !important;
                color: #374151 !important;
            }

            /* Responsive adjustments */
            @media (max-width: 640px) {

                .fi-input input,
                .fi-input-wrapper input,
                input[type="email"],
                input[type="password"],
                input[type="text"] {
                    padding: 0.75rem !important;
                    font-size: 0.875rem !important;
                }
            }
        </style>

        <div style="width: 100%; max-width: 28rem;">
            <!-- Main login card -->
            <div
                style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                <!-- Brand section -->
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div
                        style="display: inline-flex; align-items: center; justify-content: center; width: 4rem; height: 4rem; background: linear-gradient(135deg, #0B2E58 0%, #F76704 100%); border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-bottom: 1rem;">
                        <span style="font-size: 1.5rem; font-weight: bold; color: white;">NF</span>
                    </div>
                    <h1 style="font-size: 1.5rem; font-weight: bold; color: #0B2E58; margin-bottom: 0.25rem;">NetFlow
                        Bangladesh</h1>
                    <p style="color: #6b7280; font-size: 0.875rem;">Sign in to continue your learning journey</p>
                </div>

                <!-- Login form -->
                <div style="margin-bottom: 1.5rem;">
                    <form wire:submit="authenticate" style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            {{ $this->form }}
                        </div>

                        <!-- Submit button -->
                        <button type="submit"
                            style="width: 100%; background: linear-gradient(135deg, #0B2E58 0%, #1e40af 100%); color: white; font-weight: 600; padding: 0.875rem 1rem; border-radius: 0.75rem; transition: all 0.3s; transform: scale(1); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: none; cursor: pointer; margin-top: 0.5rem;"
                            onmouseover="this.style.background='linear-gradient(135deg, #0B2E58 0%, #F76704 100%)'; this.style.transform='scale(1.02)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)';"
                            onmouseout="this.style.background='linear-gradient(135deg, #0B2E58 0%, #1e40af 100%)'; this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)';">
                            <span style="display: inline-flex; align-items: center; justify-content: center;">
                                <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Sign In
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Footer links -->
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                        <a href="#" style="color: #F76704; text-decoration: none; transition: color 0.2s;"
                            onmouseover="this.style.color='#0B2E58';" onmouseout="this.style.color='#F76704';">
                            Forgot password?
                        </a>
                        <a href="#" style="color: #6b7280; text-decoration: none; transition: color 0.2s;"
                            onmouseover="this.style.color='#0B2E58';" onmouseout="this.style.color='#6b7280';">
                            Need help?
                        </a>
                    </div>
                </div>
            </div>

            <!-- Features grid -->
            <div
                style="margin-top: 2rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
                <div
                    style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); border-radius: 0.5rem; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div
                        style="width: 2.5rem; height: 2.5rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 style="color: white; font-weight: 500; font-size: 0.75rem;">Expert Courses</h3>
                    <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.75rem; margin-top: 0.25rem;">Professional
                        training</p>
                </div>

                <div
                    style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); border-radius: 0.5rem; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div
                        style="width: 2.5rem; height: 2.5rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <h3 style="color: white; font-weight: 500; font-size: 0.75rem;">Certifications</h3>
                    <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.75rem; margin-top: 0.25rem;">Recognized
                        credentials</p>
                </div>

                <div
                    style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); border-radius: 0.5rem; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div
                        style="width: 2.5rem; height: 2.5rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 style="color: white; font-weight: 500; font-size: 0.75rem;">Fast Learning</h3>
                    <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.75rem; margin-top: 0.25rem;">Accelerated
                        paths</p>
                </div>
            </div>

            <!-- Copyright -->
            <p style="text-align: center; color: rgba(255, 255, 255, 0.6); font-size: 0.75rem; margin-top: 1.5rem;">
                © 2025 NetFlow Bangladesh. All rights reserved.
            </p>
        </div>
    </div>
</x-filament-panels::page.simple>
