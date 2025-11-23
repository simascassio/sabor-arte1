<?php
session_start();
include __DIR__ . "/../conexao.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? null;
    $oculto = $_POST["oculto"] ?? null;

    if ($id !== null && $oculto !== null) {
        $sql = $mysqli->prepare("UPDATE produtos_visibilidade SET oculto = ? WHERE id = ?");
        $sql->bind_param("is", $oculto, $id);
        $sql->execute();
        $sql->close();
    }

    exit; // para o fetch JS
}

// ---------------- LISTA COMPLETA ---------------- //
$produtos = [
    "margherita" => "Margherita",
    "calabresa" => "Calabresa",
    "portuguesa" => "Portuguesa",
    "quatro_queijos" => "Quatro Queijos",
    "frango_catupiry" => "Frango com Catupiry",
    "peperoni" => "Peperoni",

    "refri_lata" => "Refrigerante 350 ml",
    "refri_2l" => "Refrigerante 2 Litros",
    "guaracamp" => "Guaracamp",

    "torta_limao" => "Torta de Limão",
    "brownie" => "Brownie"
];

// pega tudo do banco
$vis = [];
$res = $mysqli->query("SELECT * FROM produtos_visibilidade");
while ($d = $res->fetch_assoc()) {
    $vis[$d['id']] = $d['oculto'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Produtos</title>
<link rel="stylesheet" href="css/editar-cardapio.css">
  <script src="../js/dark.js" defer></script>
  <script src="../js/login.js" defer></script>
  <script src="../js/navbar.js" defer></script>
  <script>
 
    function alterarStatus(id, oculto) {
    let formData = new FormData();
    formData.append("id", id);
    formData.append("oculto", oculto);

    fetch("", {
        method: "POST",
        body: formData
    }).then(() => {
        // recarrega só o botão sem recarregar a página toda
        location.reload();
    });
}
    </script>

</head>
<body>

<header class="opcoes">
    <nav class="container-navbar">
        <div class="nav-esquerda">
            <a href="home-adm.php" class="text">Home</a>
            <a href="usuario.php" class="text">Usuários</a>
            <a href="usuariologado.php" class="text">Log</a>
            <a href="historico.php" class="text">Vendas</a>
        </div>
             <div class="usuario-box">
           <?php if (isset($_SESSION['admin']) && !empty($_SESSION['admin'])): ?>
        
        <!-- Nome -->
        <span id="nome-usuario" class="text">
          <?= htmlspecialchars($_SESSION['nome_admin']) ?>
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
          <a href="editar-produtos.php">Editar produtos</a>
          <a href="../logout.php">Sair</a>
        </div>

        <?php else: ?>
            <a href="../login.php" class="text">Login</a>
        <?php endif; ?>
        </div>
    </nav>
</header>

<body>

<h1>Gerenciar Produtos</h1>
<h2>Pizzas</h2>
<?php foreach ($produtos as $id => $nome): ?>
    <?php if (!in_array($id, ["refri_lata","refri_2l","guaracamp","torta_limao","brownie"])): ?>
        <div class="produto">

            <span><?= $nome ?></span>

            <?php if ($vis[$id] == 0): ?>
               
                <button class="btn disponivel"
                    onclick="alterarStatus('<?= $id ?>', 1)">
                     ✔ Disponível
                </button>
            <?php else: ?>
                
                <button class="btn indisponivell"
                    onclick="alterarStatus('<?= $id ?>', 0)">
                    ✔ Disponível
                </button>
            <?php endif; ?>

        </div>
    <?php endif; ?>
<?php endforeach; ?>


<h2>Bebidas</h2>
<?php foreach ($produtos as $id => $nome): ?>
    <?php if (in_array($id, ["refri_lata","refri_2l","guaracamp"])): ?>
        <div class="produto">

            <span><?= $nome ?></span>

         <?php if ($vis[$id] == 0): ?>
               
                <button class="btn disponivel"
                    onclick="alterarStatus('<?= $id ?>', 1)">
                     ✔ Disponível
                </button>
            <?php else: ?>
                
                <button class="btn indisponivell"
                    onclick="alterarStatus('<?= $id ?>', 0)">
                    ✔ Disponível
                </button>
            <?php endif; ?>


        </div>
    <?php endif; ?>
<?php endforeach; ?>


<h2>Sobremesas</h2>
<?php foreach ($produtos as $id => $nome): ?>
    <?php if (in_array($id, ["torta_limao","brownie"])): ?>
        <div class="produto">

            <span><?= $nome ?></span>

           <?php if ($vis[$id] == 0): ?>
               
                <button class="btn disponivel"
                    onclick="alterarStatus('<?= $id ?>', 1)">
                     ✔ Disponível
                </button>
            <?php else: ?>
                
                <button class="btn indisponivell"
                    onclick="alterarStatus('<?= $id ?>', 0)">
                    ✔ Disponível
                </button>
            <?php endif; ?>


        </div>
    <?php endif; ?>
<?php endforeach; ?>


<h2>Sobremesas</h2>
<?php foreach ($produtos as $id => $nome): ?>
    <?php if (in_array($id, ["torta_limao","brownie"])): ?>
        <div class="produto">

            <span><?= $nome ?></span>

       <?php if ($vis[$id] == 0): ?>
               
                <button class="btn disponivel"
                    onclick="alterarStatus('<?= $id ?>', 1)">
                     ✔ Disponível
                </button>
            <?php else: ?>
                
                <button class="btn indisponivell"
                    onclick="alterarStatus('<?= $id ?>', 0)">
                    ✔ Disponível
                </button>
            <?php endif; ?>



        </div>
    <?php endif; ?>
<?php endforeach; ?>
<img id="dark-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077517.png" alt="Tema escuro">
<img id="light-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077095.png" alt="Tema claro" style="display:none;">


</body>
</html>
