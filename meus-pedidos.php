<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: Login.php");
    exit;
}

$idUsuario = (int) $_SESSION['id'];

$sql = "SELECT id, sabor, tamanho, valor, total, data_pedido 
        FROM pedidos 
        WHERE id_usuario = ?
        ORDER BY data_pedido DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

// AGRUPAR pedidos usando data + total
$pedidos = [];

while ($row = $result->fetch_assoc()) {

    // chave única por data + total
    $chave = $row['data_pedido'] . '_' . $row['total'];

    if (!isset($pedidos[$chave])) {
        $pedidos[$chave] = [
            "data" => $row['data_pedido'],
            "total" => $row['total'],
            "itens" => []
        ];
    }

    $pedidos[$chave]["itens"][] = [
        "sabor" => $row["sabor"],
        "tamanho" => $row["tamanho"],
        "valor" => $row["valor"],
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/meus-pedidos.css">
    <script src="js/dark.js" defer></script>
    <script src="js/login.js" defer></script>
    <script src="js/navbar.js" defer></script>
     <title>Meus Pedidos</title>
</head>
<body>
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

    <div class="usuario-box">
      <?php if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])): ?>
        
        <!-- Nome -->
        <span id="nome-usuario" class="text">
          <?= htmlspecialchars($_SESSION['usuario']) ?>
        </span>

        <!-- Ícone -->
        <img 
          id="usuario-icone" 
          src="https://img.icons8.com/ios-filled/50/FFFFFF/user-male-circle.png"
          alt="Usuário"
          onclick="toggleMenu()"
        >

        <!-- Menu do usuário -->
        <div class="submenu-usuario" id="submenu-usuario">
          <a href="editar.php">Editar perfil</a>
          <a href="meus-pedidos.php">Meus pedidos</a>
          <a href="logout.php">Sair</a>
        </div>

      <?php else: ?>
        <a href="Login.php" class="text">Login</a>
      <?php endif; ?>
    </div>

  </nav>
</header>

<main class="container-principal">

    <h1 class="titulo-pedidos">🧾 Meus Pedidos</h1>

    <?php if (empty($pedidos)): ?>

        <p style="text-align:center; margin-top:40px; font-size:20px;">
            Você ainda não fez nenhum pedido 😕
        </p>

    <?php else: ?>

        <?php foreach ($pedidos as $pedido): ?>

            <div class="bloco-pedido">
                <h2>
                    Pedido feito em <?= date("d/m/Y H:i", strtotime($pedido["data"])) ?>
                </h2>

                <ul class="lista-itens">
                    <?php foreach ($pedido["itens"] as $item): ?>
                        <li>
                            <?= htmlspecialchars($item["sabor"]) ?>
                            <?= $item["tamanho"] ? "({$item["tamanho"]})" : "" ?> —
                            R$ <?= number_format($item["valor"], 2, ',', '.') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="total-pedido">
                    <strong>Total:</strong>  
                    R$ <?= number_format($pedido["total"], 2, ',', '.') ?>
                </p>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    
         <section class="dark-mode">
  <!-- Botão para ativar Dark Mode -->
              <img id="dark-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077517.png" alt="Ativar tema escuro">

  <!-- Botão para voltar ao tema claro (escondido inicialmente) -->
              <img id="light-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077095.png" alt="Ativar tema claro" style="display: none;">
        </section>


</main>

</body>
</html>
