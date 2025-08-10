<x-filament-panels::page.simple>
    <style>
        /* Inline CSS to ensure styling works */
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #0B2E58 0%, #1e40af 25%, #F76704 75%, #ea580c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 440px;
            width: 100%;
            box-shadow:
                0 32px 64px -12px rgba(11, 46, 88, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0B2E58 0%, #F76704 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 32px rgba(11, 46, 88, 0.3);
        }

        .brand-text {
            color: white;
            font-size: 28px;
            font-weight: bold;
        }

        .welcome-title {
            color: #0B2E58;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            color: #6B7280;
            text-align: center;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            border-color: #0B2E58;
            outline: none;
            box-shadow: 0 0 0 3px rgba(11, 46, 88, 0.1);
        }

        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #0B2E58 0%, #0B2E58 100%);
            color: white;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 24px;
        }

        .login-button:hover {
            background: linear-gradient(135deg, #0B2E58 0%, #F76704 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(11, 46, 88, 0.35);
        }

        .forgot-link {
            color: #F76704;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #0B2E58;
        }

        .footer-text {
            text-align: center;
            margin-top: 32px;
            color: #6B7280;
            font-size: 14px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
            text-align: center;
        }

        .feature-item {
            padding: 16px;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: #F8FAFC;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }

        .feature-title {
            color: #0B2E58;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feature-desc {
            color: #6B7280;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .login-card {
                margin: 16px;
                padding: 24px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>

    <div class="login-container">
        <div class="login-card">
            <!-- Brand Header -->
            <div class="brand-logo">
                <span class="brand-text">NF</span>
            </div>

            <h1 class="welcome-title">Welcome Back</h1>
            <p class="welcome-subtitle">Sign in to your NetFlow Bangladesh account</p>

            <!-- Login Form -->
            {{ $this->form }}

            <div style="display: flex; justify-content: space-between; align-items: center; margin: 16px 0;">
                <a href="#" class="forgot-link">Forgot your password?</a>
            </div>

            <x-filament::button type="submit" form="authenticate" size="lg" class="login-button"
                style="width: 100%; background: linear-gradient(135deg, #0B2E58 0%, #0B2E58 100%); color: white; padding: 16px 24px; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer;">
                Sign In
            </x-filament::button>

            <!-- Footer -->
            <div class="footer-text">
                Don't have an account?
                <a href="#" class="forgot-link">Contact Administrator</a>
            </div>

            <!-- Features -->
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="#0B2E58" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Expert Courses</h3>
                    <p class="feature-desc">Learn from industry professionals</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="#F76704" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Certifications</h3>
                    <p class="feature-desc">Earn recognized certificates</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="#10B981" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Fast Learning</h3>
                    <p class="feature-desc">Accelerated learning paths</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>

<div class="p-4">
    <div class="w-12 h-12 bg-orange-50 rounded-lg mx-auto mb-3 flex items-center justify-center">
        <svg class="w-6 h-6 text-[#F76704]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
        </svg>
    </div>
    <h3 class="font-semibold text-[#0B2E58] mb-1">Certifications</h3>
    <p class="text-xs text-gray-500">Earn recognized certificates</p>
</div>

<div class="p-4">
    <div class="w-12 h-12 bg-green-50 rounded-lg mx-auto mb-3 flex items-center justify-center">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
    </div>
    <h3 class="font-semibold text-[#0B2E58] mb-1">Fast Learning</h3>
    <p class="text-xs text-gray-500">Accelerated learning paths</p>
</div>
</div>
</div>
</x-filament-panels::page.simple>
