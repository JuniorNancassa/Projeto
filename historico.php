<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

$usuario_logado = isset($_SESSION['nome']) ? $_SESSION['nome'] : "Desconhecido";

if (isset($_GET['data']) && !empty($_GET['data'])) {
    $data_filter = $_GET['data'];
} else {
    $data_filter = date("Y-m-d", strtotime("yesterday"));
}

function conectar_banco() {
    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) {
        die("Erro na conexão: " . mysqli_connect_error());
    }
    return $conn;
}

$conn = conectar_banco();

// Histórico do dia
$sql = "SELECT v.id, v.quantidade, v.preco_unitario, v.data_venda, 
               u.nome AS usuario, m.nome AS medicamento,
               (v.quantidade * v.preco_unitario) AS total
        FROM vendas v
        LEFT JOIN usuarios u ON v.id_usuario = u.id
        LEFT JOIN medicamentos m ON v.id_medicamento = m.id
        WHERE DATE(v.data_venda) 
        ORDER BY v.data_venda DESC";

$result = mysqli_query($conn, $sql);

// Total do dia
$sql_total = "SELECT SUM(v.quantidade * v.preco_unitario) AS total_dia 
              FROM vendas v
              WHERE DATE(v.data_venda)";
$result_total = mysqli_query($conn, $sql_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_dia = $row_total['total_dia'] ?? 0;

// Gráfico - vendas do mês
$sql_grafico = "SELECT DATE(data_venda) as dia, SUM(quantidade * preco_unitario) as total
                FROM vendas
                WHERE MONTH(data_venda) = MONTH(CURRENT_DATE())
                GROUP BY dia
                ORDER BY dia";
$res_grafico = mysqli_query($conn, $sql_grafico);

$datas = [];
$valores = [];
while ($row = mysqli_fetch_assoc($res_grafico)) {
    $datas[] = $row['dia'];
    $valores[] = $row['total'];
}

// Resumo por usuário
$sql_usuarios = "SELECT u.nome, SUM(v.quantidade * v.preco_unitario) as total_usuario
                 FROM vendas v
                 JOIN usuarios u ON v.id_usuario = u.id
                 GROUP BY u.nome
                 ORDER BY total_usuario DESC";
$res_usuarios = mysqli_query($conn, $sql_usuarios);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Histórico de Vendas</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; }
    header, footer { background: #2c3e50; color: #fff; padding: 20px; text-align: center; }
    main { max-width: 1200px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; }
    nav ul { display: flex; gap: 20px; justify-content: center; background: #34495e; padding: 10px; list-style: none; }
    nav ul li a { color: white; text-decoration: none; padding: 10px; }
    nav ul li a:hover { background: #4CAF50; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
    th { background: #4CAF50; color: white; }
    .actions, .filter { text-align: center; margin-top: 20px; }
    .actions a, .filter button { padding: 10px 15px; background: #4CAF50; color: #fff; text-decoration: none; border: none; border-radius: 4px; margin: 5px; }
    .filter input[type="date"] { padding: 8px; }
    .grafico-container { margin-top: 40px; }
  </style>
</head>
<body>
  <header>Histórico de Vendas</header>
  <nav>
    <ul>
      <li><a href="dashboard.php">Início</a></li>
      <li><a href="cadastro_usuarios.php">Cadastrar Usuário</a></li>
      <li><a href="cadastro_medicamento.php">Cadastrar Medicamento</a></li>
      <li><a href="venda.php">Venda</a></li>
      <li><a href="historico.php">Histórico</a></li>
      <li><a href="estoque.php">Estoque</a></li>
      <li><a href="logout.php">Sair</a></li>
    </ul>
  </nav>
  <main>
    <div class="info" style="text-align:center;">
      <p><strong>Usuário Logado:</strong> <?= $usuario_logado ?></p>
      <p><strong>Data Selecionada:</strong> <?= date("d/m/Y", strtotime($data_filter)) ?></p>
      <p><strong>Total do Dia:</strong> FCFA <?= number_format($total_dia, 2, ',', '.') ?></p>
    </div>

    <div class="actions">
      <a href="exportar_pdf.php?data=<?= $data_filter ?>" target="_blank">Exportar PDF</a>
    </div>

    <div class="filter">
      <form action="historico.php" method="get">
        <label for="data">Filtrar por Data:</label>
        <input type="date" name="data" id="data" value="<?= $data_filter ?>">
        <button type="submit">Filtrar</button>
      </form>
    </div>

    <table>
      <thead>
        <tr><th>ID</th><th>Medicamento</th><th>Quant.</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th><th>Data/Hora</th></tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($result) > 0):
          while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= $row["id"] ?></td>
              <td><?= $row["medicamento"] ?></td>
              <td><?= $row["quantidade"] ?></td>
              <td>FCFA <?= number_format($row["preco_unitario"], 2, ',', '.') ?></td>
              <td>FCFA <?= number_format($row["total"], 2, ',', '.') ?></td>
              <td><?= $row["usuario"] ?></td>
              <td><?= date("d/m/Y H:i:s", strtotime($row["data_venda"])) ?></td>
            </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7">Nenhuma venda encontrada neste dia.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="grafico-container">
      <h2 style="text-align:center;">Gráfico de Vendas do Mês</h2>
      <canvas id="graficoVendasMes" height="100"></canvas>
    </div>

    <script>
      const ctx = document.getElementById('graficoVendasMes').getContext('2d');
      const grafico = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($datas) ?>,
          datasets: [{
            label: 'Total por Dia (FCFA)',
            data: <?= json_encode($valores) ?>,
            backgroundColor: '#4CAF50'
          }]
        },
        options: {
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    </script>

    <div style="margin-top: 50px;">
      <h2 style="text-align:center;">Resumo Geral por Usuário</h2>
      <table style="width:60%; margin:auto;">
        <thead><tr><th>Usuário</th><th>Total Vendido (FCFA)</th></tr></thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($res_usuarios)): ?>
            <tr>
              <td><?= $row['nome'] ?></td>
              <td><?= number_format($row['total_usuario'], 2, ',', '.') ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </main>
  <footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>
</body>
</html>
