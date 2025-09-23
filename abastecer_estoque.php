<?php 
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão!'); window.location.href='dashboard.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medicamento = (int)$_POST['medicamento_id'];
    $id_fornecedor  = (int)$_POST['fornecedor_id'];
    $quantidade     = (int)$_POST['quantidade'];
    $data_abastecimento = $_POST['data_abastecimento'] ?? date("Y-m-d H:i:s");

    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) {
        die("Erro de conexão: " . mysqli_connect_error());
    }

    // 1️⃣ Buscar informações do medicamento
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

    // 2️⃣ Atualizar quantidade do estoque
    $sql_up = "UPDATE medicamentos SET quantidade = quantidade + ? WHERE id = ?";
    $stmt_up = mysqli_prepare($conn, $sql_up);
    mysqli_stmt_bind_param($stmt_up, "ii", $quantidade, $id_medicamento);
    mysqli_stmt_execute($stmt_up);

    // 3️⃣ Registrar histórico de fornecedores
    $sql_hist = "INSERT INTO historico_fornecedores (fornecedor_id, medicamento_id, quantidade, preco, data_registro)
                 VALUES (?, ?, ?, ?, ?)";
    $stmt_hist = mysqli_prepare($conn, $sql_hist);
    // ✅ Corrigido: passar medicamento_id (INT) em vez de nome
    mysqli_stmt_bind_param($stmt_hist, "iiids", $id_fornecedor, $id_medicamento, $quantidade, $preco_unit, $data_abastecimento);
    mysqli_stmt_execute($stmt_hist);

    // 4️⃣ Registrar no fluxo de caixa (saída)
    $valor_total = $quantidade * $preco_unit;
    $descricao = "Abastecimento: $nome_medicamento (Fornecedor ID: $id_fornecedor)";
    $sql_fc = "INSERT INTO fluxo_caixa (tipo, descricao, valor, data_movimento)
               VALUES ('saida', ?, ?, ?)";
    $stmt_fc = mysqli_prepare($conn, $sql_fc);
    mysqli_stmt_bind_param($stmt_fc, "sds", $descricao, $valor_total, $data_abastecimento);
    mysqli_stmt_execute($stmt_fc);

    // Fechar statements e conexão
    mysqli_stmt_close($stmt_med);
    mysqli_stmt_close($stmt_up);
    mysqli_stmt_close($stmt_hist);
    mysqli_stmt_close($stmt_fc);
    mysqli_close($conn);

    echo "<script>alert('Estoque abastecido e registrado no histórico com sucesso!'); window.location.href='estoque.php';</script>";
}
?>
