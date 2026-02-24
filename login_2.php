<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal de Acceso</title>

<style>
:root{
  --primary:#1e4fd6;
  --primary-dark:#153fb3;
  --bg-dark:#0b1220;
  --bg-light:#f8fafc;
  --text:#0f172a;
  --muted:#64748b;
}

/* Reset */
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family: 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg,#0a0f1b,#0b1220);
  display:flex;
  justify-content:center;
  align-items:center;
  height:100vh;
  padding:20px;
}

/* Container principal */
.shell{
  width:1100px;
  max-width:100%;
  height:650px;
  display:grid;
  grid-template-columns:1fr 1.05fr;
  border-radius:24px;
  overflow:hidden;
  box-shadow:0 40px 80px rgba(0,0,0,.45);
}

/* Lado izquierdo */
.left{
  padding:60px;
  color:#fff;
  background: linear-gradient(135deg,#0d1a38,#09142a);
  display:flex;
  flex-direction:column;
  justify-content:center;
}

.left h1{
  font-size:40px;
  margin-bottom:20px;
  font-weight:700;
}

.left p{
  font-size:16px;
  opacity:.85;
  margin-bottom:25px;
}

.badges{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

.badge{
  padding:8px 14px;
  border-radius:30px;
  background:rgba(255,255,255,.08);
  font-size:13px;
  border:1px solid rgba(255,255,255,.1);
}

/* Lado derecho */
.right{
  background: linear-gradient(180deg,#f8fafc,#eef2f7);
  display:flex;
  justify-content:center;
  align-items:center;
  padding:40px;
}

/* Card */
.card{
  background:#fff;
  width:420px;
  max-width:100%;
  padding:40px;
  border-radius:24px;
  box-shadow:0 20px 60px rgba(0,0,0,.15);
  animation:fadeUp .5s ease;
}

/* Animación */
@keyframes fadeUp{
  from{opacity:0;transform:translateY(20px);}
  to{opacity:1;transform:translateY(0);}
}

/* Logo */
.logo-top{
  text-align:center;
  margin-bottom:20px;
}

.logo-top .seal{
  width:100px;
  height:100px;
  margin:auto;
  border-radius:24px;
  background:#fff;
  box-shadow:0 15px 35px rgba(0,0,0,.12);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:15px;
}

.logo-top img{
  max-width:100%;
  max-height:100%;
  object-fit:contain;
}

/* Títulos */
.card h2{
  text-align:center;
  margin-bottom:6px;
  font-size:28px;
  color:var(--text);
}

.card p{
  text-align:center;
  margin-bottom:25px;
  font-size:14px;
  color:var(--muted);
}

/* Inputs */
.field{
  margin-bottom:15px;
  position:relative;
}

.field input{
  width:100%;
  padding:14px 45px;
  border-radius:14px;
  border:1px solid #e2e8f0;
  background:#f8fafc;
  font-size:14px;
  transition:.2s;
}

.field input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 4px rgba(30,79,214,.15);
}

.field .icon{
  position:absolute;
  left:15px;
  top:50%;
  transform:translateY(-50%);
  opacity:.5;
}

.toggle-pass{
  position:absolute;
  right:15px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  opacity:.6;
}

/* Row */
.row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:14px;
  margin-bottom:20px;
}

.row a{
  color:var(--primary-dark);
  text-decoration:none;
  font-weight:600;
}

.row a:hover{
  text-decoration:underline;
}

/* Botón */
.btn{
  width:100%;
  padding:16px;
  border:none;
  border-radius:16px;
  background:linear-gradient(180deg,var(--primary),var(--primary-dark));
  color:#fff;
  font-size:15px;
  font-weight:600;
  cursor:pointer;
  box-shadow:0 18px 40px rgba(30,79,214,.35);
  transition:.2s;
}

.btn:hover{
  transform:translateY(-2px);
}

/* Footer */
.footer{
  text-align:center;
  margin-top:18px;
  font-size:13px;
  color:var(--muted);
}

/* Responsive */
@media(max-width:900px){
  .shell{
    grid-template-columns:1fr;
    height:auto;
  }
  .left{
    padding:40px;
  }
}
</style>
</head>

<body>

<div class="shell">

  <!-- IZQUIERDA -->
  <div class="left">
    <h1>Accede a tu plataforma</h1>
    <p>Una experiencia simple, segura y moderna. Gestiona tus módulos desde un solo lugar.</p>
    <div class="badges">
      <div class="badge">🔒 Acceso Seguro</div>
      <div class="badge">⚡ Rápido</div>
      <div class="badge">📊 Dashboard</div>
    </div>
  </div>

  <!-- DERECHA -->
  <div class="right">
    <div class="card">

      <div class="logo-top">
        <div class="seal">
          <img src="imagenes/logo_seduc_02.png" alt="Logo Institución">
        </div>
      </div>

      <h2>Bienvenido</h2>
      <p>Inicia sesión para continuar</p>

      <form action="validacion.php" method="POST">

        <div class="field">
          <span class="icon">👤</span>
          <input type="text" name="usuario" placeholder="Usuario" required>
        </div>

        <div class="field">
          <span class="icon">🔒</span>
          <input type="password" id="password" name="password" placeholder="Contraseña" required>
          <span class="toggle-pass" onclick="togglePassword()">👁</span>
        </div>

        <div class="row">
          <label><input type="checkbox" name="recordarme"> Recordarme</label>
          <a href="recuperar.php">Olvidé mi contraseña</a>
        </div>

        <button type="submit" class="btn">Iniciar Sesión</button>

        <div class="footer">
          © 2026 Nombre Institución
        </div>

      </form>

    </div>
  </div>

</div>

<script>
function togglePassword(){
  const pass = document.getElementById('password');
  pass.type = pass.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>