<?php 
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão!'); window.location.href='dashboard.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medicamento = (int)$_POST['medicamento_id'];
    $id_fornecedor  = (int)$_POST['fornecedor_id']; // 👈 precisa vir do formulário
    $quantidade     = (int)$_POST['quantidade'];
    $data_abastecimento = $_POST['data_abastecimento'] ?? date("Y-m-d");

    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) {
        die("Erro de conexão: " . mysqli_connect_error());
    }

    // 1. Buscar informações do medicamento
    $sql_med = "SELECT nome, preco FROM medicamentos WHERE id = ?";
    $stmt_med = mysqli_prepare($conn, $sql_med);
    mysqli_stmt_bind_param($stmt_med, "i", $id_medicamento);
    mysqli_stmt_execute($stmt_med);
    $result_med = mysqli_stmt_get_result($stmt_med);
    $med = mysqli_fetch_assoc($result_med);

    if (!$med) {
        echo "<script>alert('Medicamento não encontrado!'); window.location.href='estoque.php';</script>";
        exit;
    }

    $nome_medicamento = $med['nome'];
    $preco_unit = (float)$med['preco'];

    // 2. Atualizar quantidade do estoque
    $sql_up = "UPDATE medicamentos SET quantidade = quantidade + ? WHERE id = ?";
    $stmt_up = mysqli_prepare($conn, $sql_up);
    mysqli_stmt_bind_param($stmt_up, "ii", $quantidade, $id_medicamento);
    mysqli_stmt_execute($stmt_up);

    // 3. Registrar histórico de fornecedores
    $sql_hist = "INSERT INTO historico_fornecedores (id_fornecedor, medicamento, quantidade, preco, data_registro)
                 VALUES (?, ?, ?, ?, ?)";
    $stmt_hist = mysqli_prepare($conn, $sql_hist);
    mysqli_stmt_bind_param($stmt_hist, "isids", $id_fornecedor, $nome_medicamento, $quantidade, $preco_unit, $data_abastecimento);
    mysqli_stmt_execute($stmt_hist);

    // 4. Registrar no fluxo de caixa (saída)
    $valor_total = $quantidade * $preco_unit;
    $descricao = "Abastecimento: $nome_medicamento (Fornecedor ID: $id_fornecedor)";
    $sql_fc = "INSERT INTO fluxo_caixa (tipo, descricao, valor, data_movimento)
               VALUES ('saida', ?, ?, ?)";
    $stmt_fc = mysqli_prepare($conn, $sql_fc);
    mysqli_stmt_bind_param($stmt_fc, "sds", $descricao, $valor_total, $data_abastecimento);
    mysqli_stmt_execute($stmt_fc);

    // Finalizar
    mysqli_stmt_close($stmt_med);
    mysqli_stmt_close($stmt_up);
    mysqli_stmt_close($stmt_hist);
    mysqli_stmt_close($stmt_fc);
    mysqli_close($conn);

    echo "<script>alert('Estoque abastecido e registrado no histórico com sucesso!'); window.location.href='estoque.php';</script>";
}
?>
