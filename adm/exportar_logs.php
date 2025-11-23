<?php
session_start();
include(__DIR__ . '/../conexao.php');

if (!isset($_SESSION['admin'])) {
    exit("Acesso negado");
}

header("Content-Type: text/html; charset=UTF-8");

$sql = "SELECT * FROM acessos ORDER BY id DESC";
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Todos os Logs</title>

<style>
body { font-family: Arial, sans-serif; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #000; padding: 6px; font-size: 14px; }
thead { background: #eee; }
button { padding: 10px 15px; margin: 20px 0; cursor: pointer; }
</style>

</head>
<body>

<h2>Relatório Completo de Logs</h2>

<button onclick="window.print()" >Gerar PDF</button>

<a href="usuariologado.php" style="
    display: inline-block;
    background-color: #555;
    color: #fff;
    text-decoration: none;
    padding: 10px 15px;
    margin-left: 10px;
    border-radius: 5px;
">Voltar</a>




<table>
<thead>
    <tr>
        <th>ID</th>
        <th>Usuário</th>
        <th>IP</th>
        <th>Navegador</th>
        <th>Data de Acesso</th>
    </tr>
</thead>
<tbody>
<?php while ($l = $result->fetch_assoc()): ?>
<tr>
    <td><?= $l['id'] ?></td>
    <td><?= htmlspecialchars($l['usuario']) ?></td>
    <td><?= htmlspecialchars($l['ip']) ?></td>
    <td><?= htmlspecialchars($l['navegador']) ?></td>
    <td><?= date("d/m/Y H:i:s", strtotime($l['data_acesso'])) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
    // Quando abrir essa página, já pode mandar imprimir automaticamente
    // Descomente se quiser:
    // window.print();
</script>

</body>
</html>
