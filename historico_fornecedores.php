<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='login.php';</script>";
    exit;
}

$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error) die("Erro: ".$conn->connect_error);

$filtroFornecedor = $_GET['fornecedor'] ?? '';
$filtroDataInicio = $_GET['data_inicio'] ?? '';
$filtroDataFim    = $_GET['data_fim'] ?? '';

// Consulta principal
$sql = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_registro
        FROM historico_fornecedores hf
        JOIN fornecedores f ON hf.fornecedor_id=f.id
        JOIN medicamentos m ON hf.medicamento_id=m.id
        WHERE 1=1";
if ($filtroFornecedor) $sql .= " AND f.nome LIKE '%$filtroFornecedor%'";
if ($filtroDataInicio && $filtroDataFim) $sql .= " AND hf.data_registro BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$sql .= " ORDER BY hf.data_registro DESC";
$result = $conn->query($sql);

// Estatísticas
$totalQuery = "SELECT SUM(hf.quantidade) AS total_unidades FROM historico_fornecedores hf WHERE 1=1";
if ($filtroFornecedor) $totalQuery .= " AND hf.fornecedor_id IN (SELECT id FROM fornecedores WHERE nome LIKE '%$filtroFornecedor%')";
if ($filtroDataInicio && $filtroDataFim) $totalQuery .= " AND hf.data_registro BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$totalUnidades = $conn->query($totalQuery)->fetch_assoc()['total_unidades'] ?? 0;

$topFornecedorQuery = "SELECT f.nome, SUM(hf.quantidade) AS total 
                       FROM historico_fornecedores hf
                       JOIN fornecedores f ON hf.fornecedor_id=f.id
                       WHERE 1=1";
if ($filtroDataInicio && $filtroDataFim) $topFornecedorQuery .= " AND hf.data_registro BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$topFornecedorQuery .= " GROUP BY f.nome ORDER BY total DESC LIMIT 1";
$topFornecedor = $conn->query($topFornecedorQuery)->fetch_assoc()['nome'] ?? 'N/A';

$topMesQuery = "SELECT MONTH(hf.data_registro) AS mes, SUM(hf.quantidade) AS total
                FROM historico_fornecedores hf WHERE 1=1";
if ($filtroDataInicio && $filtroDataFim) $topMesQuery .= " AND hf.data_registro BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$topMesQuery .= " GROUP BY MONTH(hf.data_registro) ORDER BY total DESC LIMIT 1";
$topMes = $conn->query($topMesQuery)->fetch_assoc()['mes'] ?? 0;
$meses = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
$topMesNome = $meses[$topMes-1] ?? 'N/A';

// Dados para gráfico
$graficoQuery = "SELECT f.nome AS fornecedor, MONTH(hf.data_registro) AS mes, SUM(hf.quantidade) AS total
                 FROM historico_fornecedores hf
                 JOIN fornecedores f ON hf.fornecedor_id=f.id
                 GROUP BY f.nome, MONTH(hf.data_registro)";
$graficoResult = $conn->query($graficoQuery);
$dadosGrafico = [];
while ($row = $graficoResult->fetch_assoc()) {
    $dadosGrafico[$row['fornecedor']][] = ['mes'=>$row['mes'],'total'=>$row['total']];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Histórico de Fornecedores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
  --cor-primaria: #0d6efd;
  --cor-escura: #343a40;
  --cor-fundo: #f8f9fa;
  --cor-branca: #fff;
}
body {
  font-family: 'Poppins', sans-serif;
  background: var(--cor-fundo);
  margin: 0;
  padding-top: 70px;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: -260px;
  width: 250px;
  height: 100%;
  background: var(--cor-escura);
  color: white;
  transition: left 0.3s ease;
  z-index: 1050;
  padding-top: 70px;
}
.sidebar.active {left: 0;}
.sidebar a {
  display: block;
  color: white;
  padding: 12px 20px;
  text-decoration: none;
  font-weight: 500;
}
.sidebar a:hover {
  background: var(--cor-primaria);
  color: white;
  transition: 0.3s;
}

/* Overlay para fundo escuro */
.overlay {
  position: fixed;
  display: none;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 1040;
}
.overlay.active {display: block;}

/* Navbar */
.navbar {
  background: var(--cor-escura);
}
.navbar-brand {
  font-weight: 600;
}

/* Cartões de resumo */
.card-hover:hover {
  transform: scale(1.03);
  transition: 0.3s;
}

/* Rodapé */
footer {
  background: var(--cor-escura);
  color: white;
  text-align: center;
  padding: 15px;
  position: relative;
  bottom: 0;
  width: 100%;
}

/* Tabela */
.table-responsive {
  max-height: 400px;
  overflow-y: auto;
}

/* Responsividade */
@media (max-width: 992px) {
  .row.mb-4.text-center .col-md-4 {margin-bottom: 15px;}
}
@media (max-width: 768px) {
  .sidebar {width: 220px;}
  .container {padding: 0 10px;}
  footer {font-size: 0.9rem;}
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <a href="menu_admin.php"><i class="bi bi-house"></i> Dashboard</a>
  <a href="historico_fornecedores.php"><i class="bi bi-archive"></i> Histórico</a>
  <a href="fluxo_caixa.php"><i class="bi bi-cash-stack"></i> Fluxo de Caixa</a>
  <a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
</div>
<div class="overlay" id="overlay"></div>

<!-- Navbar -->
<nav class="navbar navbar-dark fixed-top">
  <div class="container-fluid">
    <button class="btn btn-dark me-2" id="menuToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand" href="#">👨 Administrador</a>
  </div>
</nav>

<!-- Conteúdo -->
<div class="container mt-4">
  <h2 class="text-center mb-4 text-primary">📦 Histórico de Fornecedores</h2>

  <!-- Filtros -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-4 col-12">
      <input type="text" name="fornecedor" class="form-control" placeholder="Fornecedor" value="<?= $filtroFornecedor ?>">
    </div>
    <div class="col-md-3 col-6">
      <input type="date" name="data_inicio" class="form-control" value="<?= $filtroDataInicio ?>">
    </div>
    <div class="col-md-3 col-6">
      <input type="date" name="data_fim" class="form-control" value="<?= $filtroDataFim ?>">
    </div>
    <div class="col-md-2 col-12 d-grid">
      <button type="submit" class="btn btn-primary">🔍 Pesquisar</button>
    </div>
  </form>

  <a href="relatorio_historico_fornecedores.php" class="btn btn-danger mb-3">📄 Exportar PDF</a>

  <!-- Resumo -->
  <div class="row mb-4 text-center">
    <div class="col-md-4 col-12 mb-3">
      <div class="p-3 bg-primary text-white rounded shadow card-hover">
        <h5>Total de Unidades</h5>
        <p class="fs-4"><?= $totalUnidades ?></p>
      </div>
    </div>
    <div class="col-md-4 col-12 mb-3">
      <div class="p-3 bg-success text-white rounded shadow card-hover">
        <h5>Fornecedor que Mais Forneceu</h5>
        <p class="fs-5"><?= $topFornecedor ?></p>
      </div>
    </div>
    <div class="col-md-4 col-12 mb-3">
      <div class="p-3 bg-warning text-dark rounded shadow card-hover">
        <h5>Mês com Maior Fornecimento</h5>
        <p class="fs-5"><?= $topMesNome ?></p>
      </div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="table-responsive bg-white p-3 rounded shadow mb-5">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>Fornecedor</th>
          <th>Medicamento</th>
          <th>Quantidade</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['fornecedor'] ?></td>
          <td><?= $row['medicamento'] ?></td>
          <td><?= $row['quantidade'] ?></td>
          <td><?= $row['data_registro'] ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Gráfico -->
  <h3 class="text-center mb-3">📊 Tendência Mensal por Fornecedor</h3>
  <canvas id="graficoFornecedores" style="max-width:100%;"></canvas>
</div>

<footer>&copy; <?= date("Y") ?> Sistema de Gestão Farmacêutica</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Menu hambúrguer responsivo
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
document.getElementById('menuToggle').addEventListener('click', () => {
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
  sidebar.classList.remove('active');
  overlay.classList.remove('active');
});

// Função gerar cor aleatória
function gerarCor(alpha = 0.6) {
  const r = Math.floor(Math.random() * 200) + 30;
  const g = Math.floor(Math.random() * 200) + 30;
  const b = Math.floor(Math.random() * 200) + 30;
  return `rgba(${r},${g},${b},${alpha})`;
}

// Dados do gráfico
const datasets = [
<?php
foreach($dadosGrafico as $fornecedor=>$dados){
  $totais = array_fill(1,12,0);
  foreach($dados as $d) $totais[$d['mes']] = $d['total'];
  echo "{label:'$fornecedor', data:[".implode(",", $totais)."], backgroundColor:gerarCor()},"; 
}
?>
];

// Gráfico de barras
new Chart(document.getElementById('graficoFornecedores'), {
  type: 'bar',
  data: {
    labels: ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],
    datasets: datasets
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.raw + ' unidades' } }
    },
    scales: {
      y: { beginAtZero: true, title: { display: true, text: 'Quantidade Fornecida' } },
      x: { title: { display: true, text: 'Meses' } }
    }
  }
});
</script>
</body>
</html>
