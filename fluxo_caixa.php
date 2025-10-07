<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Conexão com o banco de dados
$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error) die("Erro: ".$conn->connect_error);

// ===== FILTROS =====
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

// ===== EXPORTAR PDF =====
if(isset($_GET['exportar_pdf'])){
    require 'vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();

    $sqlPdf = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
    if($dataInicio && $dataFim) $sqlPdf .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sqlPdf .= " ORDER BY data_movimento DESC";
    $resPdf = $conn->query($sqlPdf);

    $html = "<h2 style='text-align:center;'>Relatório de Fluxo de Caixa</h2>
             <table border='1' cellspacing='0' cellpadding='5' width='100%'>
             <tr><th>Tipo</th><th>Descrição</th><th>Valor</th><th>Data</th></tr>";
    while($row = $resPdf->fetch_assoc()){
        $html .= "<tr>
                    <td>{$row['tipo']}</td>
                    <td>{$row['descricao']}</td>
                    <td>{$row['valor']}</td>
                    <td>{$row['data_movimento']}</td>
                  </tr>";
    }
    $html .= "</table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','landscape');
    $dompdf->render();
    $dompdf->stream("relatorio_fluxo_caixa.pdf",["Attachment"=>true]);
    exit;
}

// ===== CONSULTAS =====
$sql = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
if($dataInicio && $dataFim) $sql .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
$sql .= " ORDER BY data_movimento DESC";
$result = $conn->query($sql);

// Totais
$totalEntradas = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada'".($dataInicio && $dataFim?" AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'":""))->fetch_assoc()['total'] ?? 0;
$totalSaidas = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='saida'".($dataInicio && $dataFim?" AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'":""))->fetch_assoc()['total'] ?? 0;
$saldo = $totalEntradas - $totalSaidas;

// Vendas
$vendasDiarias = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada' AND DATE(data_movimento)=CURDATE()")->fetch_assoc()['total'] ?? 0;
$vendasSemanais = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada' AND WEEK(data_movimento)=WEEK(CURDATE())")->fetch_assoc()['total'] ?? 0;
$vendasMensais = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada' AND MONTH(data_movimento)=MONTH(CURDATE())")->fetch_assoc()['total'] ?? 0;

// ===== DADOS DO GRÁFICO =====
$graficoQuery = "SELECT DATE(data_movimento) AS dia,
                        SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) AS entradas,
                        SUM(CASE WHEN tipo='saida' THEN valor ELSE 0 END) AS saidas
                 FROM fluxo_caixa WHERE 1=1";
if($dataInicio && $dataFim) $graficoQuery .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
$graficoQuery .= " GROUP BY DATE(data_movimento) ORDER BY DATE(data_movimento)";
$graficoResult = $conn->query($graficoQuery);

$labelsGrafico = [];
$entradasGrafico = [];
$saidasGrafico = [];
while($row = $graficoResult->fetch_assoc()){
    $labelsGrafico[] = $row['dia'];
    $entradasGrafico[] = $row['entradas'];
    $saidasGrafico[] = $row['saidas'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>💰 Fluxo de Caixa</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    padding-top: 70px;
    font-family: 'Segoe UI', sans-serif;
    background-color: #f8f9fa;
}

/* ===== Sidebar ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: -250px;
    width: 250px;
    height: 100%;
    background-color: #0d6efd;
    color: white;
    transition: all 0.3s;
    z-index: 1000;
    padding-top: 70px;
}
.sidebar.active { left: 0; }
.sidebar a {
    display: block;
    color: white;
    padding: 15px 20px;
    text-decoration: none;
}
.sidebar a:hover { background-color: rgba(255,255,255,0.2); }

/* Overlay para escurecer fundo */
.overlay {
    position: fixed;
    display: none;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 900;
}
.overlay.active { display: block; }

/* ===== Navbar ===== */
.navbar-brand { font-weight: bold; }
.card-hover:hover { transform: scale(1.05); transition: 0.3s; box-shadow: 0 6px 15px rgba(0,0,0,0.15); }

/* ===== Footer ===== */
footer {
    background: #0d6efd;
    color: white;
    text-align: center;
    padding: 15px 0;
    position: fixed;
    bottom: 0;
    width: 100%;
}

/* ===== Responsividade ===== */
@media (max-width: 768px) {
    .sidebar { width: 200px; }
    .card-summary { margin-bottom: 15px; }
    footer { position: relative; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="menu_admin.php">🏠 Dashboard</a>
    <a href="fluxo_caixa.php">💰 Fluxo de Caixa</a>
    <a href="historico_fornecedores.php">📦 Histórico de Fornecedores</a>
    <a href="pagina_inicial.php">🚪 Sair</a>
</div>
<div class="overlay" id="overlay"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary fixed-top">
  <div class="container-fluid">
    <button class="btn btn-primary" id="menuToggle">☰</button>
    <a class="navbar-brand" href="#">Administrador</a>
  </div>
</nav>

<div class="container mt-4 mb-5">

<h2 class="mb-4 text-center">💰 Fluxo de Caixa</h2>

<!-- ===== FILTROS ===== -->
<form method="get" class="row g-3 mb-4">
  <div class="col-md-4 col-12"><input type="date" name="data_inicio" class="form-control" value="<?= $dataInicio ?>"></div>
  <div class="col-md-4 col-12"><input type="date" name="data_fim" class="form-control" value="<?= $dataFim ?>"></div>
  <div class="col-md-4 col-12 d-grid"><button type="submit" class="btn btn-primary">🔍 Filtrar</button></div>
</form>

<a href="?exportar_pdf=1<?= $dataInicio && $dataFim ? "&data_inicio=$dataInicio&data_fim=$dataFim" : '' ?>" class="btn btn-danger mb-3">📄 Exportar PDF</a>

<!-- ===== CARDS RESUMO ===== -->
<div class="row mb-4 text-center">
  <div class="col-md-3 col-12 mb-3"><div class="p-3 bg-success text-white rounded shadow card-hover"><h5>Entradas</h5><p class="fs-4">CFA <?= number_format($totalEntradas,2,",",".") ?></p></div></div>
  <div class="col-md-3 col-12 mb-3"><div class="p-3 bg-danger text-white rounded shadow card-hover"><h5>Saídas</h5><p class="fs-4">CFA <?= number_format($totalSaidas,2,",",".") ?></p></div></div>
  <div class="col-md-3 col-12 mb-3"><div class="p-3 bg-warning text-dark rounded shadow card-hover"><h5>Saldo</h5><p class="fs-4">CFA <?= number_format($saldo,2,",",".") ?></p></div></div>
  <div class="col-md-3 col-12 mb-3"><div class="p-3 bg-info text-white rounded shadow card-hover"><h5>Vendas Hoje</h5><p class="fs-4">CFA <?= number_format($vendasDiarias,2,",",".") ?></p></div></div>
</div>

<div class="row mb-4 text-center">
  <div class="col-md-6 col-12 mb-3"><div class="p-3 bg-secondary text-white rounded shadow card-hover"><h5>Vendas Semana</h5><p class="fs-4">CFA <?= number_format($vendasSemanais,2,",",".") ?></p></div></div>
  <div class="col-md-6 col-12 mb-3"><div class="p-3 bg-primary text-white rounded shadow card-hover"><h5>Vendas Mês</h5><p class="fs-4">CFA <?= number_format($vendasMensais,2,",",".") ?></p></div></div>
</div>

<!-- ===== TABELA ===== -->
<div class="table-responsive bg-white p-3 rounded shadow mb-5">
<table class="table table-striped table-hover">
<thead class="table-dark"><tr><th>Tipo</th><th>Descrição</th><th>Valor</th><th>Data</th></tr></thead>
<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= ucfirst($row['tipo']) ?></td>
<td><?= $row['descricao'] ?></td>
<td>CFA <?= number_format($row['valor'],2,",",".") ?></td>
<td><?= $row['data_movimento'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- ===== GRÁFICO ===== -->
<h3 class="mb-3 text-center">📊 Fluxo de Caixa - Entradas vs Saídas</h3>
<canvas id="graficoFluxo" style="max-width:100%;"></canvas>

</div>

<!-- ===== FOOTER ===== -->
<footer>
  &copy; <?= date("Y") ?> Farmácia Dashboard. Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== MENU HAMBÚRGUER =====
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
document.getElementById('menuToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});
overlay.addEventListener('click', ()=>{
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// ===== GRÁFICO =====
const ctx = document.getElementById('graficoFluxo').getContext('2d');
new Chart(ctx,{
type:'bar',
data:{
labels: <?= json_encode($labelsGrafico) ?>,
datasets:[
{label:'Entradas',data: <?= json_encode($entradasGrafico) ?>, backgroundColor:'rgba(13,110,253,0.7)'},
{label:'Saídas', data: <?= json_encode($saidasGrafico) ?>, backgroundColor:'rgba(220,53,69,0.7)'}
]
},
options:{
responsive:true,
plugins:{
legend:{position:'top'},
tooltip:{callbacks:{label: ctx=>ctx.dataset.label+': CFA ' + ctx.raw.toFixed(2)}}
},
scales:{
y:{beginAtZero:true,title:{display:true,text:'Valor (CFA)'}},
x:{title:{display:true,text:'Data'}}
}
}
});
</script>
</body>
</html>
