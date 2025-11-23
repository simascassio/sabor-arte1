<?php
session_start();
include 'conexao.php'; // contém $mysqli

// -------------------------------
// VERIFICA LOGIN
// -------------------------------
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não logado"]);
    exit;
}

$id_usuario = $_SESSION['id'];
$login_usuario = $_SESSION['usuario']; // ← LOGIN DO USUÁRIO

// -------------------------------
// RECEBE O JSON DOS ITENS
// -------------------------------
$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados || empty($dados["itens"]) || !isset($dados["total"])) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos"]);
    exit;
}

$itens = $dados["itens"];
$totalPedido = $dados["total"];

// -------------------------------
// GERA ID DO PEDIDO (ÚNICO)
// -------------------------------
$id_pedido = time(); // único — timestamp

// -------------------------------
// SALVA CADA ITEM
// -------------------------------
$sql = "INSERT INTO pedidos 
        (id_pedido, id_usuario, login, sabor, tamanho, valor, total, data_pedido)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro no prepare: " . $mysqli->error]);
    exit;
}

foreach ($itens as $item) {

    $sabor = $item["nome"];
    $tamanho = $item["tamanho"] ?? null;
    $valor = $item["preco"];

    $stmt->bind_param(
        "iisssdd",
        $id_pedido,
        $id_usuario,
        $login_usuario,
        $sabor,
        $tamanho,
        $valor,
        $totalPedido
    );

    $stmt->execute();
}

// -------------------------------
// RETORNO
// -------------------------------
echo json_encode([
    "status" => "sucesso",
    "mensagem" => "Pedido salvo!",
    "id_pedido" => $id_pedido
]);
?>
