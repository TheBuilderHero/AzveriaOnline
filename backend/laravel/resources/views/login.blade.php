<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Azveria Login</title>
  <style>
    body { font-family: Georgia, serif; background: linear-gradient(135deg, #d8e3f4, #f4f0e0); margin: 0; min-height: 100vh; display: grid; place-items: center; }
    .card { width: min(420px, 92vw); background: #ffffffde; border-radius: 12px; padding: 24px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
    h1 { margin: 0 0 12px; }
    p { color: #444; }
    input, button { width: 100%; margin-top: 10px; padding: 10px; border-radius: 8px; border: 1px solid #b5bcc6; }
    button { background: #1a4f8a; color: #fff; border: none; cursor: pointer; }
    button:hover { background: #153f6c; }
    .btn-secondary { background: transparent; color: #1a4f8a; border: 1px solid #1a4f8a; margin-top: 8px; }
    .btn-secondary:hover { background: #eef3fb; color: #1a4f8a; }
    .helper { margin-top: 10px; font-size: 13px; color: #555; }
    .err { color: #8a1a1a; min-height: 20px; }
    .divider { text-align: center; margin: 14px 0 4px; font-size: 13px; color: #888; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Azveria Online</h1>

    <!-- LOGIN FORM -->
    <div id="loginSection">
      <p>Sign in to your nation dashboard.</p>
      <form id="loginForm">
        <input id="email" type="email" placeholder="Email" required>
        <input id="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
      </form>
      <div class="divider">— or —</div>
      <button class="btn-secondary" id="showRegister">Create an Account</button>
      <div class="helper">Demo admin: admin@azveria.local / password123</div>
      <div class="helper">Demo player: player@azveria.local / password123</div>
      <div class="err" id="loginError"></div>
    </div>

    <!-- REGISTER FORM -->
    <div id="registerSection" style="display:none;">
      <p>Create your Azveria account.</p>
      <form id="registerForm">
        <input id="regName" type="text" placeholder="Display Name" required maxlength="120">
        <input id="regEmail" type="email" placeholder="Email" required>
        <input id="regPassword" type="password" placeholder="Password (min 8 characters)" required minlength="8">
        <input id="regPasswordConfirm" type="password" placeholder="Confirm Password" required minlength="8">
        <button type="submit">Create Account</button>
      </form>
      <div class="divider">— or —</div>
      <button class="btn-secondary" id="showLogin">Back to Login</button>
      <div class="err" id="registerError"></div>
    </div>
  </div>

  <script>
    // Toggle between login and register
    document.getElementById('showRegister').addEventListener('click', () => {
      document.getElementById('loginSection').style.display = 'none';
      document.getElementById('registerSection').style.display = 'block';
    });
    document.getElementById('showLogin').addEventListener('click', () => {
      document.getElementById('registerSection').style.display = 'none';
      document.getElementById('loginSection').style.display = 'block';
    });

    // Login
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errorBox = document.getElementById('loginError');
      errorBox.textContent = '';

      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: document.getElementById('email').value,
          password: document.getElementById('password').value,
        }),
      });

      if (!res.ok) {
        errorBox.textContent = 'Login failed. Check credentials.';
        return;
      }

      const data = await res.json();
      localStorage.setItem('azveria_token', data.token);
      localStorage.setItem('azveria_user', JSON.stringify(data.user));
      window.location.href = '/app';
    });

    // Register
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errorBox = document.getElementById('registerError');
      errorBox.textContent = '';

      const name = document.getElementById('regName').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const password = document.getElementById('regPassword').value;
      const confirm = document.getElementById('regPasswordConfirm').value;

      if (password !== confirm) {
        errorBox.textContent = 'Passwords do not match.';
        return;
      }

      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password }),
      });

      const data = await res.json();

      if (!res.ok) {
        const firstError = data.errors
          ? Object.values(data.errors)[0][0]
          : (data.message || 'Registration failed.');
        errorBox.textContent = firstError;
        return;
      }

      localStorage.setItem('azveria_token', data.token);
      localStorage.setItem('azveria_user', JSON.stringify(data.user));
      window.location.href = '/app';
    });
  </script>
</body>
</html>
