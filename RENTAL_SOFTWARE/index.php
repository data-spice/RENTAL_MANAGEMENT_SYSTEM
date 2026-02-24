<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PangaRms - Login/Register</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="logo-text">
            <span class="panga">Panga</span><span class="rms">Rms</span>
        </div>
    </div>

    <div class="container">
        <div class="login-card">
            <div class="welcome">
                <h1><span class="wave">👋</span> <span id="formTitle">Welcome to RMS</span></h1>
            </div>

            <div id="toast-notification" class="toast-notification"></div>

            <div class="role-selector">
                <p>Please select a role</p>
                <div class="role-buttons">
                    <button class="role-btn active" data-role="tenant">tenant</button>
                    <button class="role-btn" data-role="caretaker">caretaker</button>
                </div>
            </div>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="login.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="loginRole" value="tenant">
                
                <div class="form-group">
                    <input type="text" name="username" class="form-input" placeholder="Email or username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>

            <!-- Register Form (Hidden by default) -->
            <form id="registerForm" method="POST" action="login.php" class="hidden">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="role" id="registerRole" value="tenant">
                
                <div class="form-group">
                    <input type="text" name="fullname" class="form-input" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-input" placeholder="Email" required>
                </div>
                <div class="form-group" id="phone-number-group">
                    <input type="text" name="phone_number" class="form-input" placeholder="Phone Number" required>
                </div>
                <div class="form-group">
                    <input type="text" name="username" class="form-input" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="login-btn">Register</button>
            </form>

            <div class="forgot-password" id="forgotPassword">
                <a href="#">Forgot Password?</a>
            </div>

            <div class="register-section">
                <span id="toggleText">Don't have an account?</span> <a href="#" id="toggleForm">Register</a>
            </div>
        </div>

        <div class="image-container">
            <div class="image-frame">
                <div class="image-inner">
                    <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="skyGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#1e3a3f;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#2d4d52;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <rect width="600" height="600" fill="url(#skyGrad)"/>
                        <ellipse cx="300" cy="520" rx="250" ry="60" fill="#1a2d32" opacity="0.6"/>
                        <polygon points="200,320 200,480 320,480 400,400 400,240" fill="#0f1a1d"/>
                        <polygon points="400,240 400,400 480,320 480,160" fill="#1a2d32"/>
                        <rect x="220" y="340" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="360" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="380" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="400" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="420" width="80" height="3" fill="#fb923c"/>
                        <rect x="220" y="440" width="80" height="3" fill="#fb923c"/>
                        <rect x="410" y="260" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="280" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="300" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="320" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="340" width="60" height="3" fill="#fb923c"/>
                        <rect x="410" y="360" width="60" height="3" fill="#fb923c"/>
                        <rect x="0" y="480" width="600" height="120" fill="#0a1214"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <script src="js/login.js"></script>
