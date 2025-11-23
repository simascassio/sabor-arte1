<?php
session_start();
include '../conexao.php';
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

/**
 * Paginação & filtros
 * - page: página atual (1-based)
 * - filter: today|week|month|year|custom
 * - start / end: datas no formato YYYY-MM-DD (somente para custom)
 */

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$startDate = isset($_GET['start']) ? $_GET['start'] : null;
$endDate = isset($_GET['end']) ? $_GET['end'] : null;

// Build WHERE clause depending on filter
$whereSql = "1=1"; // default (sem filtro)
$bindParamsForMain = []; // used when we need prepared params (custom dates)
$bindTypesForMain = ""; 

if ($filter === 'today') {
    $whereSql = "DATE(p.data_pedido) = CURDATE()";
} elseif ($filter === 'week') {
    // semana ISO: usa YEARWEEK com modo 1 (segunda-feira como primeiro dia)
    $whereSql = "YEARWEEK(p.data_pedido,1) = YEARWEEK(CURDATE(),1)";
} elseif ($filter === 'month') {
    $whereSql = "YEAR(p.data_pedido)=YEAR(CURDATE()) AND MONTH(p.data_pedido)=MONTH(CURDATE())";
} elseif ($filter === 'year') {
    $whereSql = "YEAR(p.data_pedido)=YEAR(CURDATE())";
} elseif ($filter === 'custom' && $startDate && $endDate) {
    // validar formato YYYY-MM-DD simples
    $sValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate);
    $eValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate);
    if ($sValid && $eValid) {
        // incluir horas para pegar o dia inteiro
        $whereSql = "p.data_pedido BETWEEN ? AND ?";
        $bindParamsForMain[] = $startDate . " 00:00:00";
        $bindParamsForMain[] = $endDate . " 23:59:59";
        $bindTypesForMain = "ss";
    } else {
        // se datas inválidas, ignora o filtro custom
        $whereSql = "1=1";
    }
}

// ---------- 1) calcular total de grupos (para paginação) ----------
/*
 We count number of distinct orders (grouping by id_usuario + data_pedido),
 so pagination works on orders, not on individual rows.
*/
$countSql = "
    SELECT COUNT(*) AS total_groups FROM (
        SELECT 1
        FROM pedidos p
        INNER JOIN clientes c ON p.id_usuario = c.id
        WHERE {$whereSql}
        GROUP BY p.id_usuario, p.data_pedido
    ) t
";

$stmtCount = $mysqli->prepare($countSql);
if ($stmtCount === false) {
    die("Erro prepare count: " . $mysqli->error);
}

// bind params if needed (only for custom)
if (!empty($bindParamsForMain)) {
    // bind dynamically
    $stmtCount->bind_param($bindTypesForMain, ...$bindParamsForMain);
}

$stmtCount->execute();
$resCount = $stmtCount->get_result();
$totalGroups = 0;
if ($row = $resCount->fetch_assoc()) {
    $totalGroups = intval($row['total_groups']);
}
$stmtCount->close();

$totalPages = ($totalGroups > 0) ? ceil($totalGroups / $perPage) : 1;
if ($page > $totalPages) $page = $totalPages;

// ---------- 2) buscar os grupos (paginação aplicada) ----------
$mainSql = "
    SELECT 
        p.id_usuario,
        c.nome AS cliente_nome,
        c.login AS cliente_login,
        p.data_pedido,
        MAX(p.total) AS total_final
    FROM pedidos p
    INNER JOIN clientes c ON p.id_usuario = c.id
    WHERE {$whereSql}
    GROUP BY p.id_usuario, p.data_pedido
    ORDER BY p.data_pedido DESC
    LIMIT ?, ?
";

$stmt = $mysqli->prepare($mainSql);
if ($stmt === false) {
    die("Erro prepare main: " . $mysqli->error);
}

// bind: if custom we have two string params first, then offset & perPage (ints)
if (!empty($bindParamsForMain)) {
    // prepare bind list: start, end, offset, perPage
    $bindTypes = $bindTypesForMain . "ii";
    $params = $bindParamsForMain;
    $params[] = $offset;
    $params[] = $perPage;
    $stmt->bind_param($bindTypes, ...$params);
} else {
    // only limit/offset
    $stmt->bind_param("ii", $offset, $perPage);
}

$stmt->execute();
$res = $stmt->get_result();

$grupos = [];
while ($r = $res->fetch_assoc()) {
    $grupos[] = $r;
}
$stmt->close();

// ---------- 3) calcular total arrecadado considerando o filtro ----------
/*
 We sum the totals per order (the MAX per group) using a subquery.
*/
$sumSql = "
    SELECT IFNULL(SUM(sub.total_final), 0) AS total_sum FROM (
        SELECT MAX(p.total) AS total_final
        FROM pedidos p
        INNER JOIN clientes c ON p.id_usuario = c.id
        WHERE {$whereSql}
        GROUP BY p.id_usuario, p.data_pedido
    ) sub
";

$stmtSum = $mysqli->prepare($sumSql);
if ($stmtSum === false) {
    die("Erro prepare sum: " . $mysqli->error);
}
if (!empty($bindParamsForMain)) {
    $stmtSum->bind_param($bindTypesForMain, ...$bindParamsForMain);
}
$stmtSum->execute();
$resSum = $stmtSum->get_result();
$totalArrecadado = 0;
if ($row = $resSum->fetch_assoc()) {
    $totalArrecadado = floatval($row['total_sum']);
}
$stmtSum->close();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico de Vendas (ADM)</title>
    <link rel="stylesheet" href="css/historico.css">
    <script src="../js/dark.js" defer></script>
    <script src="../js/login.js" defer></script>
    <script src="../js/navbar.js" defer></script>
  
</head>
<body>
<header class="opcoes">
  <nav class="container-navbar">
    <div class="nav-esquerda">
      <a href="home-adm.php" class="text">Home</a>
      <a href="usuario.php" class="text">Usuários</a>
      <a href="usuariologado.php" class="text">Log</a>
      <a href="historico.php" class="text active">Vendas</a>
    </div>
    <div class="usuario-box">
        <?php if (isset($_SESSION['admin']) && !empty($_SESSION['admin'])): ?>
        <span id="nome-usuario" class="text"><?= htmlspecialchars($_SESSION['nome_admin']) ?></span>
        <img id="usuario-icone" src="https://img.icons8.com/ios-filled/50/FFFFFF/user-male-circle.png" alt="Usuário" onclick="toggleMenu()">
        <div class="submenu-usuario" id="submenu-usuario">
          <a href="editar-produtos.php">Editar produtos</a>
          <a href="../logout.php">Sair</a>
        </div>
        <?php else: ?>
        <a href="Login.php" class="text">Login</a>
       <?php endif; ?>
    </div>
  </nav>
</header>

<main>
    <h1 style="text-align:center; margin-top:20px;">📜 Histórico de Vendas</h1>

    <!-- FILTROS -->
    <div class="filtros">
      <div class="left">
        <form id="form-filtro" method="get" action="historico.php" style="display:flex; gap:8px; align-items:center;">
          <label for="filter">Período:</label>
          <select name="filter" id="filter" onchange="onFilterChange()">
            <option value="all" <?= $filter==='all' ? 'selected' : '' ?>>Todos</option>
            <option value="today" <?= $filter==='today' ? 'selected' : '' ?>>Hoje</option>
            <option value="week" <?= $filter==='week' ? 'selected' : '' ?>>Semana</option>
            <option value="month" <?= $filter==='month' ? 'selected' : '' ?>>Mês</option>
            <option value="year" <?= $filter==='year' ? 'selected' : '' ?>>Ano</option>
            <option value="custom" <?= $filter==='custom' ? 'selected' : '' ?>>Personalizado</option>
          </select>

          <div id="custom-dates" style="display: <?= ($filter==='custom') ? 'flex' : 'none' ?>; gap:6px; align-items:center;">
            <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>">
            <span>até</span>
            <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>">
          </div>

          <button type="submit">Aplicar</button>
        </form>
      </div>

      <div style="text-align:right;">
        <small>Página <?= $page ?> / <?= $totalPages ?> (<?= $totalGroups ?> pedidos)</small>
      </div>
    </div>

    <?php if (empty($grupos)): ?>
        <p style="text-align:center; margin-top:40px;">Nenhum pedido encontrado para esse período.</p>
    <?php endif; ?>

    <!-- Exibir cada grupo (pedido) -->
    <?php foreach ($grupos as $grupo): ?>
        <div class="pedido-box">
            <div class="titulo">
                Cliente: <?= htmlspecialchars($grupo["cliente_nome"]) ?> — Login: <?= htmlspecialchars($grupo["cliente_login"]) ?><br>
                Data: <?= date("d/m/Y H:i", strtotime($grupo["data_pedido"])) ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sabor</th>
                        <th>Tamanho</th>
                        <th>Preço Unitário</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // buscar itens desse pedido: por id_usuario + data_pedido
                    $stmtItems = $mysqli->prepare("SELECT sabor, tamanho, valor FROM pedidos WHERE id_usuario = ? AND data_pedido = ? ORDER BY id ASC");
                    if ($stmtItems) {
                        $stmtItems->bind_param("is", $grupo['id_usuario'], $grupo['data_pedido']);
                        $stmtItems->execute();
                        $resItems = $stmtItems->get_result();
                        while ($it = $resItems->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($it['sabor']) . "</td>";
                            echo "<td>" . (!empty($it['tamanho']) ? htmlspecialchars($it['tamanho']) : '-') . "</td>";
                            echo "<td>R$ " . number_format($it['valor'], 2, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        $stmtItems->close();
                    } else {
                        echo "<tr><td colspan='3'>Erro ao buscar itens: " . $mysqli->error . "</td></tr>";
                    }
                ?>
                </tbody>
            </table>

            <div class="total-final">
                Total do Pedido:
                <span style="color:#ff6600;">
                    R$ <?= number_format($grupo["total_final"], 2, ',', '.') ?>
                </span>
            </div>

            
        </div>
    <?php endforeach; ?>
    
    <img id="dark-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077517.png" alt="Tema escuro">
    <img id="light-btn" src="https://cdn-icons-png.flaticon.com/128/6077/6077095.png" alt="Tema claro" style="display:none;">

    <!-- PAGINAÇÃO -->
    <div class="pagination" aria-label="Paginação">
      <?php
        // build base url preserving filter params
        $baseParams = $_GET;
        unset($baseParams['page']);
        $baseUrl = 'historico.php';
        if (!empty($baseParams)) {
            $baseUrl .= '?' . http_build_query($baseParams) . '&';
        } else {
            $baseUrl .= '?';
        }

        // previous
        if ($page > 1) {
            echo '<a href="'.$baseUrl.'page='.($page-1).'">&laquo; Anterior</a>';
        } else {
            echo '<span style="opacity:0.5;">&laquo; Anterior</span>';
        }

        // page numbers (mostre até 7 links)
        $startPage = max(1, $page - 3);
        $endPage = min($totalPages, $startPage + 6);
        for ($p = $startPage; $p <= $endPage; $p++) {
            if ($p == $page) {
                echo '<span class="active">'.$p.'</span>';
            } else {
                echo '<a href="'.$baseUrl.'page='.$p.'">'.$p.'</a>';
            }
        }

        // next
        if ($page < $totalPages) {
            echo '<a href="'.$baseUrl.'page='.($page+1).'">Próxima &raquo;</a>';
        } else {
            echo '<span style="opacity:0.5;">Próxima &raquo;</span>';
        }
      ?>
    </div>

    <!-- TOTAL ARRECADADO (fixo rodapé) -->
    <div class="total-fixed" role="contentinfo" aria-live="polite">
        <div>
            <strong>Total arrecadado (período):</strong>
            <span>
                R$ <?= number_format($totalArrecadado, 2, ',', '.') ?>
            </span>
        </div>
    </div>

</main>

<script>
function onFilterChange() {
  const sel = document.getElementById('filter');
  const cd = document.getElementById('custom-dates');
  if (sel.value === 'custom') {
    cd.style.display = 'flex';
  } else {
    cd.style.display = 'none';
  }
}
</script>

</body>
</html>
