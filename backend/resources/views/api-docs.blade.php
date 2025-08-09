<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetFlow Bangladesh - API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7f8c8d;
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #667eea;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .card-description {
            color: #7f8c8d;
            line-height: 1.6;
        }

        .features {
            background: #ecf0f1;
            border-radius: 15px;
            padding: 30px;
            margin-top: 40px;
        }

        .features h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            text-align: left;
        }

        .feature-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .feature-item strong {
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .feature-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">🌊 NetFlow Bangladesh</div>
        <div class="subtitle">Learning Management System API Documentation</div>
        <p style="color: #7f8c8d; margin-bottom: 30px;">
            Comprehensive API documentation for the NetFlow Bangladesh LMS platform.
            Choose your preferred documentation interface below.
        </p>

        <div class="cards">
            <a href="/docs" class="card">
                <div class="card-icon">📚</div>
                <div class="card-title">Scribe Documentation</div>
                <div class="card-description">
                    Beautiful, Laravel-native documentation with interactive examples,
                    detailed endpoint descriptions, and easy-to-read formatting.
                </div>
            </a>

            <a href="/api/swagger" class="card">
                <div class="card-icon">⚡</div>
                <div class="card-title">Swagger UI</div>
                <div class="card-description">
                    Industry-standard OpenAPI documentation with interactive API testing,
                    request/response examples, and real-time API exploration.
                </div>
            </a>
        </div>

        <div class="features">
            <h3>🚀 API Features</h3>
            <div class="feature-list">
                <div class="feature-item">
                    <strong>83+ Endpoints</strong><br>
                    Complete CRUD operations for all LMS entities
                </div>
                <div class="feature-item">
                    <strong>Authentication</strong><br>
                    Sanctum-based token authentication with refresh
                </div>
                <div class="feature-item">
                    <strong>15 Controllers</strong><br>
                    Organized into logical groups for easy navigation
                </div>
                <div class="feature-item">
                    <strong>Public API</strong><br>
                    Course catalog and information endpoints
                </div>
                <div class="feature-item">
                    <strong>User Management</strong><br>
                    Profile, enrollment, and progress tracking
                </div>
                <div class="feature-item">
                    <strong>Assessment System</strong><br>
                    Quizzes, assignments, and certification
                </div>
                <div class="feature-item">
                    <strong>Communication</strong><br>
                    Messages, forums, and notifications
                </div>
                <div class="feature-item">
                    <strong>Payments</strong><br>
                    Course payments and transaction management
                </div>
            </div>
        </div>

        <div
            style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1; color: #7f8c8d; font-size: 0.9rem;">
            <p>🔗 <strong>Base URL:</strong> {{ url('/api') }}</p>
            <p>🔐 <strong>Authentication:</strong> Bearer Token (Sanctum)</p>
            <p>📅 <strong>Version:</strong> v1.0 - Laravel {{ app()->version() }}</p>
        </div>
    </div>
</body>

</html>
