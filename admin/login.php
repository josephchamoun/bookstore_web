<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — BookStore</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0d0d0d;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #2a2a2a;
        }

        .logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: #2d6ef5;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }

        h1 { font-size: 22px; text-align: center; }
        p  { color: #9e9e9e; text-align: center; font-size: 13px; margin-top: 6px; }

        label {
            display: block;
            font-size: 11px;
            color: #9e9e9e;
            letter-spacing: 1px;
            margin-bottom: 6px;
            margin-top: 20px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            background: #242424;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
        }

        input:focus { border-color: #2d6ef5; }

        button {
            width: 100%;
            padding: 14px;
            background: #2d6ef5;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 28px;
            transition: background 0.2s;
        }

        button:hover { background: #1a4fbf; }

        .error {
            background: #3a1a1a;
            border: 1px solid #f44336;
            color: #f44336;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 16px;
            display: none;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">📚</div>
        <h1>Admin Panel</h1>
        <p>BookStore Management System</p>
    </div>

    <label>USERNAME</label>
    <input type="text" id="username" placeholder="admin" />

    <label>PASSWORD</label>
    <input type="password" id="password" placeholder="••••••••" />

    <div class="error" id="error">Invalid username or password</div>

    <button onclick="login()">Login →</button>
</div>

<script>
async function login() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const error    = document.getElementById('error');

    error.style.display = 'none';

    const res  = await fetch('/bookstore_api/api/admin/admin_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    });
    const data = await res.json();

    if (res.ok) {
        window.location.href = '/bookstore_api/admin/dashboard.php';
    } else {
        error.style.display = 'block';
    }
}

// Allow Enter key
document.addEventListener('keydown', e => {
    if (e.key === 'Enter') login();
});
</script>
</body>
</html>