<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Login Type</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f4f4f4;
            font-family: 'Inter', sans-serif;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card-container {
            display: flex;
            gap: 20px;
        }
        .card {
            width: 260px;
            height: 220px;
            background: white;
            border-radius: 12px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
        }
        .card svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        .user-icon {
            fill: #007bff;
        }
        .student-icon {
            fill: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-container">
            <!-- User Login Card -->
            <a href="login_user.php" class="text-decoration-none">
                <div class="card">
                    <svg class="user-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2a5 5 0 11-5 5 5 5 0 015-5zm-7 17a7 7 0 0114 0v1H5v-1z"/>
                    </svg>
                    <h5 class="card-title">Login</h5>
                </div>
            </a>

            <!-- Student Login Card -->
            <a href="login_student.php" class="text-decoration-none">
                <div class="card">
                    <svg class="student-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2L1 7l11 5 9-4.09V13h2V7L12 2zm0 9.65L3.24 7 12 3.35 20.76 7 12 11.65zm-2 3.35v1.79c-.73.21-1.41.58-2 1.08v-2.87h2zm4 0h2v2.87a4.95 4.95 0 00-2-1.08V15zm-2 1.14c1.9 0 3.45 1.55 3.45 3.45H6.55c0-1.9 1.55-3.45 3.45-3.45z"/>
                    </svg>
                    <h5 class="card-title">Student Login</h5>
                </div>
            </a>
        </div>
    </div>
</body>
</html>
