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
$usuario_logado = $_SESSION['usuario']['nome'] ?? 'Desconhecido';

// Consultar vendas
$sql = "SELECT v.*, u.nome AS usuario, m.nome AS medicamento, 
        (v.quantidade * v.preco_unitario) AS total
        FROM vendas v
        LEFT JOIN usuarios u ON v.id_usuario = u.id
        LEFT JOIN medicamentos m ON v.id_medicamento = m.id
        WHERE DATE(v.data_venda) 
        ORDER BY v.data_venda DESC";

$result = $conn->query($sql);

// Total geral do dia
$sql_total = "SELECT SUM(quantidade * preco_unitario) AS total_dia FROM vendas WHERE DATE(data_venda) = '$data'";
$res_total = $conn->query($sql_total);
$total_dia = $res_total->fetch_assoc()['total_dia'] ?? 0;

// Resumo por usuário
$sql_resumo = "SELECT u.nome, COUNT(*) as total_vendas, SUM(v.quantidade * preco_unitario) as total_valor
               FROM vendas v
               JOIN usuarios u ON v.id_usuario = u.id
               WHERE DATE(v.data_venda) = '$data'
               GROUP BY v.id_usuario";
$resumo = $conn->query($sql_resumo);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Vendas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .navbar {
            margin-bottom: 30px;
        }
        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        .btn-custom {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .btn-custom:hover {
            background-color: #218838;
        }
        .filter-container input {
            width: 200px;
        }
        .filter-container label {
            font-weight: bold;
        }
        .chart-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .chart-container canvas {
            flex: 1;
            height: 300px;
        }
    </style>
</head>
<body>

<!-- Navbar (Agora, com o mesmo estilo da tela de estoque) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Gestão Farmacêutica</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastro_medicamento.php">💊 Medicamentos</a></li>
                <li class="nav-item"><a class="nav-link active" href="historico.php">🧾 Histórico</a></li>
                <li class="nav-item"><a class="nav-link" href="estoque.php">📦 Estoque</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">🚪 Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Conteúdo -->
<div class="container">
    <div class="filter-container mb-4">
        <form method="get" class="d-flex justify-content-between">
            <label for="data">Filtrar por Data:</label>
            <input type="date" name="data" value="<?= $data ?>" class="form-control">
            <button type="submit" class="btn btn-custom">Filtrar</button>
        </form>
    </div>

    <h2>Histórico de Vendas</h2>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($data)) ?></p>
        <p><strong>Usuário logado:</strong> <?= htmlspecialchars($usuario_logado) ?></p>
        <p><strong>Total do Dia:</strong> FCFA <?= number_format($total_dia, 2, ',', '.') ?></p>
        <a href="relatorio_vendas.php" class="btn btn-success">📄 Gerar PDF</a>
    </div>

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
            <tr><td colspan="7">Nenhuma venda registrada.</td></tr>
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

    <div class="chart-container">
        <div>
            <canvas id="grafico1"></canvas>
        </div>
        <div>
            <canvas id="grafico2"></canvas>
        </div>
    </div>
</div>

<!-- Bootstrap + Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const labels = [
        <?php
        $res = $conn->query("SELECT DATE(data_venda) as dia, SUM(quantidade * preco_unitario) as total FROM vendas WHERE MONTH(data_venda) = MONTH(CURDATE()) GROUP BY dia");
        while ($row = $res->fetch_assoc()) {
            echo '"' . $row['dia'] . '",';
        }
        ?>
    ];
    const valores = [
        <?php
        $res = $conn->query("SELECT DATE(data_venda) as dia, SUM(quantidade * preco_unitario) as total FROM vendas WHERE MONTH(data_venda) = MONTH(CURDATE()) GROUP BY dia");
        while ($row = $res->fetch_assoc()) {
            echo $row['total'] . ',';
        }
        ?>
    ];

    new Chart(document.getElementById('grafico1'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vendas por Dia (FCFA)',
                data: valores,
                backgroundColor: '#007bff'
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
                fill: false,
                borderColor: 'green',
                tension: 0.3
            }]
        }
    });
</script>
</body>
</html>
