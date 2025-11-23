<?php
session_start();
include(__DIR__ . '/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if ($usuario === '' || $senha === '') {
        header("Location: Login.php?erro=campos_vazios");
        exit;
    }

    // LOGIN ADMIN
    if (strtolower($usuario) === 'gestor' && $senha === 'controle') {
        $_SESSION['usuario'] = 'GESTOR';
        $_SESSION['role'] = 'gestor';
        $_SESSION['id'] = 0;
        $_SESSION['admin'] = true;
        $_SESSION['nome_admin'] = "Administrador";

        $ip = $_SERVER['REMOTE_ADDR'];
        $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
        $data = date('Y-m-d H:i:s');

        $insert = $mysqli->prepare("INSERT INTO acessos (usuario, ip, navegador, data_acesso)
                                   VALUES (?, ?, ?, ?)");
        $u = "gestor";
        $insert->bind_param("ssss", $u, $ip, $navegador, $data);
        $insert->execute();
        $insert->close();

        header('Location: ./adm/home-adm.php');
        exit;
    }

    // LOGIN CLIENTE
    $sql = "SELECT id, login, Senha FROM clientes WHERE login = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        header("Location: Login.php?erro=erro_interno");
        exit;
    }

    if (strlen($usuario) !== 6) {
        header("Location: Login.php?erro=login_tamanho");
        exit;
    }

    if (strlen($senha) !== 8) {
        header("Location: Login.php?erro=senha_tamanho");
        exit;
    }

    if (preg_match('/[0-9]/', $senha)) {
        header("Location: Login.php?erro=senha_numeros");
        exit;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: Login.php?erro=usuario_nao_encontrado");
        exit;
    }

    $row = $result->fetch_assoc();

    if (!password_verify($senha, $row['Senha'])) {
        header("Location: Login.php?erro=senha_incorreta");
        exit;
    }

    // LOGIN ACEITO
    $_SESSION['usuario']     = $row['login'];
    $_SESSION['role']        = 'user';
    $_SESSION['id']          = $row['id'];
    $_SESSION['id_usuario']  = $row['id'];

    // Registro de acesso (mantém igual)
    $ip = $_SERVER['REMOTE_ADDR'];
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
    $agora = date('Y-m-d H:i:s');

    $check = $mysqli->prepare("
        SELECT data_acesso
        FROM acessos
        WHERE usuario = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $check->bind_param("s", $usuario);
    $check->execute();
    $res = $check->get_result();

    $registrar = true;

    if ($res->num_rows > 0) {
        $ultimo = $res->fetch_assoc()['data_acesso'];
        $diferenca = strtotime($agora) - strtotime($ultimo);

        if ($diferenca < 60) {
            $registrar = false;
        }
    }

    if ($registrar) {
        $insert = $mysqli->prepare("
            INSERT INTO acessos (usuario, ip, navegador, data_acesso)
            VALUES (?, ?, ?, ?)
        ");
        $insert->bind_param("ssss", $usuario, $ip, $navegador, $agora);
        $insert->execute();
        $insert->close();
    }

    header('Location: home.php');
    exit;
}
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tela de Login</title>
  <link rel="stylesheet" href="css/login.css">
  <script src="js/dark.js" defer></script>
  <script src="js/login.js" defer></script>
  <script src="js/navbar.js" defer></script>
</head>
<body>
  <!-- Navbar -->
  <header class="opcoes">
  <nav class="container-navbar">
    <div class="nav-esquerda">
      <a href="Home.php" class="text">Home</a>
      <a href="Home.php#redes" class="text">Contato</a>
      <a href="finalizar.php" class="text">Pedido</a>
      <div class="dropdown">
        <a href="Cardapio.php" class="text">Cardápio</a>
        <div class="dropdown-content">
          <a href="Cardapio.php">Pizzas</a>
          <a href="Cardapio.php#sobremesas">Sobremesas</a>
          <a href="Cardapio.php#bebidas">Bebidas</a>
        </div>
      </div>
    </div>

    <!-- USUÁRIO: substitua por este bloco -->
    <div class="usuario-box" id="usuario-box">
      <?php if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])): ?>
        <!-- Quando está logado: mostra nome + ícone + submenu -->
        <span class="text nome-usuario" id="nome-usuario">
          <?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?>
        </span>

        <img id="usuario" src="https://img.icons8.com/ios-filled/50/FFFFFF/user-male-circle.png" alt="Usuário" aria-haspopup="true" aria-expanded="false">

        <!-- Reaproveita a classe dropdown-content para manter o estilo atual -->
        <div class="dropdown-content usuario-menu" id="usuario-menu" style="display: none;">
          <a href="editar.php">Meu perfil</a>
          <a href="meus-pedidos.php">Meus pedidos</a>
          <a href="logout.php" id="logout-link">Sair</a>
        </div>

      <?php else: ?>
        <!-- Quando NÃO está logado: mostra apenas o link de Login -->
        <a href="Login.php" class="text" id="login-link">Login</a>
      <?php endif; ?>
    </div>

  </nav>
</header>

  <!-- Main login -->
  <main>
    <canvas id="particles"></canvas>

    <section class="container-login">
      <form action="Login.php" method="POST">
        <div class="input-group">
          <label for="input-nome-login">Login</label>
          <input type="text" name="usuario" id="login" class="input" placeholder="Digite seu login" required>
          <div class="erro-msg" id="erro-login"></div>
          <span class="input-icon">&#128100;</span>
        </div>

        <div class="input-group">
          <label for="input-senha-login">Senha</label>
          <input type="password" name="senha" id="senha" class="input" placeholder="Digite sua senha" required>
          <div class="erro-msg" id="erro-senha"></div>

          <span class="input-icon">&#128274;</span>
        </div>

        <button type="submit" class="btn">Entrar</button>
      </form>

      <div class="cadastro">
        <p>Não tem uma conta?</p>
        <a href="cadastro.php">Cadastre-se</a>
      </div>

      <div class="social">
        <img src="imagens/google.svg" alt="Google Login">
        <img src="imagens/fcb.svg" alt="Facebook Login">
      </div>

      <script>
const params = new URLSearchParams(window.location.search);
const erro = params.get('erro');

if (erro) {
    const elLogin = document.getElementById('erro-login');
    const elSenha = document.getElementById('erro-senha');

    switch (erro) {
        case 'campos_vazios':
            elLogin.textContent = "Preencha login e senha.";
            break;

        case 'login_tamanho':
            elLogin.textContent = "O login deve ter exatamente 6 caracteres.";
            break;

        case 'senha_tamanho':
            elSenha.textContent = "A senha deve ter exatamente 8 caracteres.";
            break;

        case 'senha_numeros':
            elSenha.textContent = "A senha não pode conter números.";
            break;

        case 'usuario_nao_encontrado':
            elLogin.textContent = "Usuário não encontrado.";
            break;

        case 'senha_incorreta':
            elSenha.textContent = "Senha incorreta.";
            break;

        case 'erro_interno':
            elLogin.textContent = "Erro interno. Tente novamente.";
            break;
    }
}
</script>

    </section>

    <!-- Botões Dark Mode -->
    <img id="dark-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077517.png" alt="Ativar tema escuro">
    <img id="light-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077095.png" alt="Ativar tema claro" style="display:none;">
  </main>

  <script>
    // Efeito de entrada
    const loginContainer = document.querySelector('.container-login');
    loginContainer.style.opacity = 0;
    setTimeout(() => loginContainer.style.opacity = 1, 100);

    // Dark Mode
    const darkBtn = document.getElementById('dark-btn');
    const lightBtn = document.getElementById('light-btn');
    function toggleDarkMode() {
      document.body.classList.toggle('dark-mode');
      const dark = document.body.classList.contains('dark-mode');
      darkBtn.style.display = dark ? 'none' : 'block';
      lightBtn.style.display = dark ? 'block' : 'none';
      initParticles();
    }
    darkBtn.addEventListener('click', toggleDarkMode);
    lightBtn.addEventListener('click', toggleDarkMode);
  </script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  // Efeito de entrada suave
  const loginContainer = document.querySelector('.container-login');
  if (loginContainer) {
    loginContainer.style.opacity = 0;
    setTimeout(() => loginContainer.style.opacity = 1, 100);
  }

  // Botões de tema
  const darkBtn = document.getElementById('dark-btn');
  const lightBtn = document.getElementById('light-btn');

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const dark = document.body.classList.contains('dark-mode');
    if (darkBtn && lightBtn) {
      darkBtn.style.display = dark ? 'none' : 'block';
      lightBtn.style.display = dark ? 'block' : 'none';
    }
    initParticles();
  }

  if (darkBtn && lightBtn) {
    darkBtn.addEventListener('click', toggleDarkMode);
    lightBtn.addEventListener('click', toggleDarkMode);
  }

  // === Efeito de partículas ===
  const canvas = document.createElement('canvas');
  document.body.appendChild(canvas);
  canvas.style.position = 'fixed';
  canvas.style.top = 0;
  canvas.style.left = 0;
  canvas.style.width = '100%';
  canvas.style.height = '100%';
  canvas.style.zIndex = '-1';
  canvas.style.pointerEvents = 'none';

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  let particlesArray = [];
  let mouse = { x: null, y: null };

  window.addEventListener('mousemove', e => {
    mouse.x = e.x;
    mouse.y = e.y;
  });

  class Particle {
    constructor(x, y, size, color, speedX, speedY) {
      this.x = x;
      this.y = y;
      this.size = size;
      this.baseSize = size;
      this.color = color;
      this.speedX = speedX;
      this.speedY = speedY;
    }

    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
    }

    update() {
      this.x += this.speedX;
      this.y += this.speedY;
      if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
      if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;

      let dx = mouse.x - this.x, dy = mouse.y - this.y;
      let dist = Math.sqrt(dx * dx + dy * dy);

      if (dist < 100) {
        this.size = this.baseSize + 2;
        this.color = 'rgba(255,255,255,0.8)';
      } else {
        this.size = this.baseSize;
        this.color = document.body.classList.contains('dark-mode')
          ? 'rgba(150,150,150,0.6)'
          : 'rgba(100,0,0,0.4)';
      }
    }
  }

  function initParticles() {
    particlesArray = [];
    const dark = document.body.classList.contains('dark-mode');
    const color = dark ? 'rgba(150,150,150,0.6)' : 'rgba(100,0,0,0.4)';

    for (let i = 0; i < 80; i++) {
      let size = Math.random() * 3 + 1;
      let x = Math.random() * canvas.width;
      let y = Math.random() * canvas.height;
      let speedX = (Math.random() - 0.5) * 0.7;
      let speedY = (Math.random() - 0.5) * 0.7;
      particlesArray.push(new Particle(x, y, size, color, speedX, speedY));
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particlesArray.forEach(p => {
      p.update();
      p.draw();
    });
    requestAnimationFrame(animate);
  }

  window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    initParticles();
  });

  initParticles();
  animate();
});
</script>




</body>
</html>
