<?php
session_start();
/*if (!isset($_SESSION['usuario_logado'])) {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='login.php';</script>";
    exit;
}*/

$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error) die("Erro: ".$conn->connect_error);

// Filtros
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

// Exportar PDF
if(isset($_GET['exportar_pdf'])){
    require 'vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();

    $sqlPdf = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
    if($dataInicio && $dataFim) $sqlPdf .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sqlPdf .= " ORDER BY data_movimento DESC";

    $resPdf = $conn->query($sqlPdf);

    $html = "<h2>Relatório de Fluxo de Caixa</h2>
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

// Consulta detalhada
$sql = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
if($dataInicio && $dataFim) $sql .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
$sql .= " ORDER BY data_movimento DESC";
$result = $conn->query($sql);

// Resumo
$totalEntradas = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada'".($dataInicio && $dataFim?" AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'":""))->fetch_assoc()['total'] ?? 0;
$totalSaidas = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='saida'".($dataInicio && $dataFim?" AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'":""))->fetch_assoc()['total'] ?? 0;
$saldo = $totalEntradas - $totalSaidas;

// Resumo de vendas
$vendasDiarias = $conn->query("SELECT SUM(valor) AS total 
                               FROM fluxo_caixa 
                               WHERE tipo='entrada' 
                               AND DATE(data_movimento) = CURDATE()")->fetch_assoc()['total'] ?? 0;

$vendasSemanais = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada' AND WEEK(data_movimento)=WEEK(CURDATE())")->fetch_assoc()['total'] ?? 0;
$vendasMensais = $conn->query("SELECT SUM(valor) AS total FROM fluxo_caixa WHERE tipo='entrada' AND MONTH(data_movimento)=MONTH(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Dados gráfico
$graficoQuery = "SELECT DATE(data_movimento) AS dia, 
                        SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) AS entradas,
                        SUM(CASE WHEN tipo='saida' THEN valor ELSE 0 END) AS saidas
                 FROM fluxo_caixa
                 WHERE 1=1";
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
<title>Fluxo de Caixa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{padding-top:70px;}
footer{background:#0d6efd;color:white;padding:15px 0;text-align:center;margin-top:40px;}
.navbar-brand{font-weight:bold;}
.card-summary{border-left:5px solid #0d6efd;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="menu_admin.php">Administrador</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="fluxo_caixa.php">Fluxo de Caixa</a></li>
        <li class="nav-item"><a class="nav-link" href="historico_fornecedores.php">Histórico Fornecedores</a></li>
        <li class="nav-item"><a class="nav-link" href="pagina_inicial.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">

<h2 class="mb-4">💰 Fluxo de Caixa</h2>

<!-- FILTROS -->
<form method="get" class="row g-3 mb-4">
  <div class="col-md-4"><input type="date" name="data_inicio" class="form-control" value="<?= $dataInicio ?>"></div>
  <div class="col-md-4"><input type="date" name="data_fim" class="form-control" value="<?= $dataFim ?>"></div>
  <div class="col-md-4 d-grid"><button type="submit" class="btn btn-light text-primary">Filtrar</button></div>
</form>

<a href="relatorio_fluxo_caixa.php" class="btn btn-danger mb-3">📄 Exportar PDF</a>

<!-- CARDS RESUMO -->
<div class="row mb-4 text-center">
  <div class="col-md-3"><div class="card p-3 card-summary"><h5>Entradas</h5><p class="fs-4">CFA <?= number_format($totalEntradas,2,",",".") ?></p></div></div>
  <div class="col-md-3"><div class="card p-3 card-summary"><h5>Saídas</h5><p class="fs-4">CFA <?= number_format($totalSaidas,2,",",".") ?></p></div></div>
  <div class="col-md-3"><div class="card p-3 card-summary"><h5>Saldo</h5><p class="fs-4">CFA <?= number_format($saldo,2,",",".") ?></p></div></div>
  <div class="col-md-3"><div class="card p-3 card-summary"><h5>Vendas Hoje</h5><p class="fs-4">CFA <?= number_format($vendasDiarias,2,",",".") ?></p></div></div>
</div>

<div class="row mb-4 text-center">
  <div class="col-md-6"><div class="card p-3 card-summary"><h5>Vendas Semana</h5><p class="fs-4">CFA <?= number_format($vendasSemanais,2,",",".") ?></p></div></div>
  <div class="col-md-6"><div class="card p-3 card-summary"><h5>Vendas Mês</h5><p class="fs-4">CFA <?= number_format($vendasMensais,2,",",".") ?></p></div></div>
</div>

<!-- TABELA -->
<div class="table-responsive bg-white p-3 rounded shadow mb-5">
<table class="table table-striped">
<thead><tr><th>Tipo</th><th>Descrição</th><th>Valor</th><th>Data</th></tr></thead>
<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= ucfirst($row['tipo']) ?></td>
<td><?= $row['descricao'] ?></td>
<td>R$ <?= number_format($row['valor'],2,",",".") ?></td>
<td><?= $row['data_movimento'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- GRÁFICO -->
<h3 class="mb-3">📊 Fluxo de Caixa - Entradas vs Saídas</h3>
<canvas id="graficoFluxo" style="max-width:100%;"></canvas>

</div>

<!-- FOOTER -->
<footer>
  &copy; <?= date("Y") ?> Farmácia Dashboard. Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('graficoFluxo').getContext('2d');
new Chart(ctx,{
type:'bar',
data:{
labels: <?= json_encode($labelsGrafico) ?>,
datasets:[
{label:'Entradas',data: <?= json_encode($entradasGrafico) ?>, backgroundColor:'rgba(13,110,253,0.6)'},
{label:'Saídas', data: <?= json_encode($saidasGrafico) ?>, backgroundColor:'rgba(220,53,69,0.6)'}
]
},
options:{
responsive:true,
plugins:{legend:{position:'top'}, tooltip:{callbacks:{label: ctx=>ctx.dataset.label+': CFA '+ctx.raw.toFixed(2)}}},
scales:{y:{beginAtZero:true,title:{display:true,text:'Valor (CFA)'}},x:{title:{display:true,text:'Data'}}}
}
});
</script>

</body>
</html>
