<?php
require_once __DIR__ . '/../includes/supabase.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Authenticate via Supabase Auth REST API
        $authResult = sb_login($email, $password);

        if (isset($authResult['access_token'])) {
            $token = $authResult['access_token'];
            $userId = $authResult['user']['id'];

            setcookie('supabase_token', $token, [
                'expires' => time() + 86400,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $profileResponse = sb_request('GET', 'profiles', 'id=eq.' . urlencode($userId) . '&select=*', null, $token);

            // Default to a normal user if the profile row can't be read
            $role = 'user';
            if (is_array($profileResponse) && isset($profileResponse[0]['role'])) {
                $role = $profileResponse[0]['role'];
            }

            // Routes match vercel.json: /admin/ and /user/
            if ($role === 'admin') {
                header('Location: /admin/');
            } else {
                header('Location: /user/');
            }
            exit;
        } else {
            // Show the real reason instead of always saying "Invalid email or password."
            $code = $authResult['response_body']['error_code'] ?? '';
            if ($code === 'email_not_confirmed') {
                $error = 'Please confirm your email first. Check your inbox for the confirmation link.';
            } elseif ($code === 'invalid_credentials') {
                $error = 'Invalid email or password.';
            } else {
                $error = 'Login failed. Please try again.';
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Deployed</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h2>Basta Ma deploy muna Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
            <a href="register.php" class="btn-secondary">Register</a>
        </form>
    </div>
</body>
</html>