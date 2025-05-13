<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "farmacia");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$usuario_logado = $_SESSION['nome_usuario'] ?? 'Desconhecido';


// Consultar vendas do dia
$sql = "SELECT v.*, u.nome AS usuario, m.nome AS medicamento, (v.quantidade * v.preco_unitario) AS total
        FROM vendas v
        LEFT JOIN usuarios u ON v.id_usuario = u.id
        LEFT JOIN medicamentos m ON v.id_medicamento = m.id
        WHERE DATE(v.data_venda) = ?
        ORDER BY v.data_venda DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data);
$stmt->execute();
$result = $stmt->get_result();

// Total geral do dia
$sql_total = "SELECT SUM(quantidade * preco_unitario) AS total_dia FROM vendas WHERE DATE(data_venda) = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("s", $data);
$stmt_total->execute();
$res_total = $stmt_total->get_result();
$total_dia = $res_total->fetch_assoc()['total_dia'] ?? 0;

// Resumo por usuário
$sql_resumo = "SELECT u.nome, COUNT(*) as total_vendas, SUM(v.quantidade * preco_unitario) as total_valor
               FROM vendas v
               JOIN usuarios u ON v.id_usuario = u.id
               WHERE DATE(v.data_venda) = ?
               GROUP BY v.id_usuario";
$stmt_resumo = $conn->prepare($sql_resumo);
$stmt_resumo->bind_param("s", $data);
$stmt_resumo->execute();
$resumo = $stmt_resumo->get_result();

// Dados para gráficos (vendas do mês)
$labels = [];
$valores = [];
$grafico = $conn->query("SELECT DATE_FORMAT(data_venda, '%d/%m') as dia, SUM(quantidade * preco_unitario) as total 
                         FROM vendas 
                         WHERE MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE())
                         GROUP BY dia ORDER BY data_venda");
while ($g = $grafico->fetch_assoc()) {
    $labels[] = $g['dia'];
    $valores[] = (float)$g['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Vendas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #eef2f7;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar {
            margin-bottom: 30px;
        }
        .table th {
            background-color: #0d6efd;
            color: white;
        }
        .btn-custom {
            background-color: #198754;
            color: white;
        }
        .btn-custom:hover {
            background-color: #146c43;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .chart-box {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Gestão Farmacêutica <img src="" alt=""></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastro_medicamento.php">Medicamentos</a></li>
                <li class="nav-item"><a class="nav-link active" href="historico.php">Histórico</a></li>
                <li class="nav-item"><a class="nav-link" href="estoque.php">Estoque</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <form method="get" class="d-flex align-items-center gap-2">
            <label for="data" class="form-label mb-0">Filtrar por Data:</label>
            <input type="date" name="data" value="<?= $data ?>" class="form-control">
            <button type="submit" class="btn btn-custom">Filtrar</button>
        </form>
        <a href="relatorio_vendas.php" class="btn btn-outline-success">Gerar PDF</a>
    </div>

    <h2 class="mb-4">Histórico de Vendas - <?= date("d/m/Y", strtotime($data)) ?></h2>
    <p><strong>Usuário logado:</strong> <?= htmlspecialchars($usuario_logado) ?> | <strong>Total do Dia:</strong> FCFA <?= number_format($total_dia, 2, ',', '.') ?></p>

    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>ID</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th><th>Data</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows): while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['medicamento'] ?></td>
                <td><?= $row['quantidade'] ?></td>
                <td>FCFA <?= number_format($row['preco_unitario'], 2, ',', '.') ?></td>
                <td>FCFA <?= number_format($row['total'], 2, ',', '.') ?></td>
                <td><?= $row['usuario'] ?></td>
                <td><?= date("d/m/Y H:i", strtotime($row['data_venda'])) ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="7">Nenhuma venda registrada para essa data.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h4 class="mt-5">Resumo por Usuário</h4>
    <table class="table table-bordered">
        <thead class="table-secondary">
        <tr>
            <th>Usuário</th><th>Vendas Realizadas</th><th>Valor Total</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($r = $resumo->fetch_assoc()): ?>
            <tr>
                <td><?= $r['nome'] ?></td>
                <td><?= $r['total_vendas'] ?></td>
                <td>FCFA <?= number_format($r['total_valor'], 2, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h4 class="mt-5">Gráficos</h4>
    <div class="chart-container">
        <div class="chart-box">
            <canvas id="grafico1"></canvas>
        </div>
        <div class="chart-box">
            <canvas id="grafico2"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const labels = <?= json_encode($labels) ?>;
    const valores = <?= json_encode($valores) ?>;

    new Chart(document.getElementById('grafico1'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vendas por Dia (FCFA)',
                data: valores,
                backgroundColor: '#0d6efd'
            }]
        }
    });

    new Chart(document.getElementById('grafico2'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tendência de Vendas (FCFA)',
                data: valores,
                borderColor: 'green',
                fill: false,
                tension: 0.3
            }]
        }
    });
</script>
</body>
</html>
