<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão!'); window.location.href='dashboard.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['medicamento_id'];
    $quantidade = (int)$_POST['quantidade'];

    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) {
        die("Erro de conexão: " . mysqli_connect_error());
    }

    $sql = "UPDATE medicamentos SET quantidade = quantidade + ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $quantidade, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Estoque atualizado com sucesso!'); window.location.href='estoque.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar estoque.'); window.location.href='estoque.php';</script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
