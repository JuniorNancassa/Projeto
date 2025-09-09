<?php
header('Content-Type: application/json');

if(!isset($_GET['codigo'])){
    echo json_encode(['success'=>false, 'msg'=>'Código não fornecido']);
    exit;
}

$codigo = $_GET['codigo'];

// Conexão com o banco
$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error){
    echo json_encode(['success'=>false, 'msg'=>'Erro de conexão']);
    exit;
}

// Buscar medicamento pelo código de barras
$sql = "SELECT id, nome, descricao, categoria, preco FROM medicamentos WHERE codigo_barras = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $med = $result->fetch_assoc();
    echo json_encode([
        'success'   => true,
        'id'        => $med['id'],
        'nome'      => $med['nome'],
        'descricao' => $med['descricao'],
        'categoria' => $med['categoria'],
        'preco'     => $med['preco']
    ]);
} else {
    echo json_encode(['success'=>false, 'msg'=>'Medicamento não encontrado']);
}

$stmt->close();
$conn->close();
?>
