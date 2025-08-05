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

// Dados para gráfico circular de vendas por dia (participação percentual)
$grafico_dias = $conn->query("SELECT DATE_FORMAT(data_venda, '%d/%m') as dia, SUM(quantidade * preco_unitario) as total 
                             FROM vendas 
                             WHERE MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE())
                             GROUP BY dia ORDER BY data_venda");
$labels_dias = [];
$valores_dias = [];
while ($g = $grafico_dias->fetch_assoc()) {
    $labels_dias[] = $g['dia'];
    $valores_dias[] = (float)$g['total'];
}

// Dados para gráfico circular de vendas por medicamento (participação percentual)
$grafico_medicamentos = $conn->query("SELECT m.nome AS medicamento, SUM(v.quantidade * v.preco_unitario) AS total
                                     FROM vendas v
                                     JOIN medicamentos m ON v.id_medicamento = m.id
                                     WHERE MONTH(v.data_venda) = MONTH(CURDATE()) AND YEAR(v.data_venda) = YEAR(CURDATE())
                                     GROUP BY v.id_medicamento
                                     ORDER BY total DESC");
$labels_medicamentos = [];
$valores_medicamentos = [];
while ($g = $grafico_medicamentos->fetch_assoc()) {
    $labels_medicamentos[] = $g['medicamento'];
    $valores_medicamentos[] = (float)$g['total'];
}

// Medicamento mais vendido do mês
$mais_vendido = $conn->query("SELECT m.nome, SUM(v.quantidade) AS total_qtd
                              FROM vendas v
                              JOIN medicamentos m ON v.id_medicamento = m.id
                              WHERE MONTH(v.data_venda) = MONTH(CURDATE()) AND YEAR(v.data_venda) = YEAR(CURDATE())
                              GROUP BY v.id_medicamento
                              ORDER BY total_qtd DESC
                              LIMIT 1")->fetch_assoc();

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
            padding: 30px ;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 30px auto;
        }
        header {
        background-color: #0d6efd;
        color: white;
        text-align: center;
        padding: 20px;
        font-size: 1.8rem;
         font-weight: bold;
        }
        nav {
            background-color: #0d6efd;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            font-weight: bold;
            font-size: 18px;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        nav ul li a:hover {
            color:rgb(13, 14, 13);
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

        footer {
            margin-top: 60px;
            background-color: #0d6efd;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>Histórico Das Dos Medicamentos</header>
<nav>
    <ul>
      <li><a href="dashboard.php">🏠 Início</a></li>
      <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
      <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
      <li><a href="venda.php">🛒 Venda</a></li>
      <li><a href="historico.php">📈 Histórico</a></li>
      <li><a href="estoque.php">📦 Estoque</a></li>
      <li><a href="pagina_inicial.php">🚪 Sair</a></li>
    </ul>
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

    <?php if ($mais_vendido): ?>
        <p><strong>Medicamento mais vendido no mês:</strong> <?= htmlspecialchars($mais_vendido['nome']) ?> (<?= $mais_vendido['total_qtd'] ?> unidades)</p>
    <?php else: ?>
        <p><strong>Medicamento mais vendido no mês:</strong> Nenhum registro.</p>
    <?php endif; ?>

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
            <canvas id="graficoDias"></canvas>
        </div>
        <div class="chart-box">
            <canvas id="graficoMedicamentos"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Dados do gráfico de vendas por dia (gráfico circular)
    const labelsDias = <?= json_encode($labels_dias) ?>;
    const valoresDias = <?= json_encode($valores_dias) ?>;

    new Chart(document.getElementById('graficoDias'), {
        type: 'pie',
        data: {
            labels: labelsDias,
            datasets: [{
                label: 'Vendas por Dia (FCFA)',
                data: valoresDias,
                backgroundColor: labelsDias.map(() => '#' + Math.floor(Math.random()*16777215).toString(16)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Dados do gráfico de vendas por medicamento (gráfico circular)
    const labelsMedicamentos = <?= json_encode($labels_medicamentos) ?>;
    const valoresMedicamentos = <?= json_encode($valores_medicamentos) ?>;

    new Chart(document.getElementById('graficoMedicamentos'), {
        type: 'pie',
        data: {
            labels: labelsMedicamentos,
            datasets: [{
                label: 'Vendas por Medicamento (FCFA)',
                data: valoresMedicamentos,
                backgroundColor: labelsMedicamentos.map(() => '#' + Math.floor(Math.random()*16777215).toString(16)),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
</body>
<footer>
    &copy; 2025 Sistema de Gestão Farmacêutica
  </footer>
</html>

