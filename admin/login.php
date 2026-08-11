<?php
require_once __DIR__ . '/../config/auth.php';

// Si ya está autenticado, redirigir al Dashboard directamente
if (isAdminAuthenticated()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso a la Barra - Octava Café</title>
  <link rel="stylesheet" href="../public/css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
</head>
<body>

  <div class="login-container">
    <div class="login-header">
      <div class="logo-icon" style="margin: 0 auto; width: 52px; height: 52px;">☕</div>
      <h1 class="login-title">Barra - Octava Café</h1>
      <p style="font-size: 0.85rem; color: var(--text-muted);">Ingresá tus credenciales para ver las comandas y gestionar la carta</p>
    </div>

    <div id="login-error" class="alert-error"></div>

    <form id="login-form">
      <div class="form-group">
        <label class="form-label" for="username">Usuario Admin</label>
        <input type="text" id="username" class="form-input" placeholder="admin" required value="admin">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" class="form-input" placeholder="••••••••" required value="admin123">
      </div>

      <button type="submit" id="btn-submit" class="btn-login">
        Iniciar Sesión
      </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center;">
      <a href="../index.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">← Volver a la Carta del Kiosco Virtual</a>
    </div>
  </div>

  <script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const errorDiv = document.getElementById('login-error');
      const btn = document.getElementById('btn-submit');
      
      errorDiv.style.display = 'none';
      btn.disabled = true;
      btn.textContent = 'Verificando...';

      try {
        const res = await fetch('../api/admin/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            username: document.getElementById('username').value,
            password: document.getElementById('password').value
          })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          window.location.href = data.redirect || 'index.php';
        } else {
          errorDiv.textContent = data.message || 'Credenciales inválidas.';
          errorDiv.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Iniciar Sesión';
        }
      } catch (err) {
        errorDiv.textContent = 'Error de conexión con el servidor.';
        errorDiv.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Iniciar Sesión';
      }
    });
  </script>
</body>
</html>
