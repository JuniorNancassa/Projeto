<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='login.php';</script>";
    exit;
}

// Conexão
$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error) die("Erro: ".$conn->connect_error);

// Filtros
$filtroFornecedor = $_GET['fornecedor'] ?? '';
$filtroDataInicio = $_GET['data_inicio'] ?? '';
$filtroDataFim = $_GET['data_fim'] ?? '';

// PDF
if(isset($_GET['exportar_pdf'])){
    require 'vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();

    $sqlPdf = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_fornecimento
               FROM historico_fornecimento hf
               JOIN fornecedores f ON hf.fornecedor_id=f.id
               JOIN medicamentos m ON hf.medicamento_id=m.id
               WHERE 1=1";
    if($filtroFornecedor) $sqlPdf .= " AND f.nome LIKE '%$filtroFornecedor%'";
    if($filtroDataInicio && $filtroDataFim) $sqlPdf .= " AND hf.data_fornecimento BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
    $sqlPdf .= " ORDER BY hf.data_fornecimento DESC";

    $resPdf = $conn->query($sqlPdf);

    $html = "<h2>Relatório de Histórico de Fornecedores</h2>
             <table border='1' cellspacing='0' cellpadding='5' width='100%'>
             <tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr>";
    while($row = $resPdf->fetch_assoc()){
        $html .= "<tr>
                    <td>{$row['fornecedor']}</td>
                    <td>{$row['medicamento']}</td>
                    <td>{$row['quantidade']}</td>
                    <td>{$row['data_fornecimento']}</td>
                  </tr>";
    }
    $html .= "</table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','landscape');
    $dompdf->render();
    $dompdf->stream("relatorio_fornecedores.pdf",["Attachment"=>true]);
    exit;
}

// Consulta detalhada
$sql = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_fornecimento
        FROM historico_fornecimento hf
        JOIN fornecedores f ON hf.fornecedor_id=f.id
        JOIN medicamentos m ON hf.medicamento_id=m.id
        WHERE 1=1";
if ($filtroFornecedor) $sql .= " AND f.nome LIKE '%$filtroFornecedor%'";
if ($filtroDataInicio && $filtroDataFim) $sql .= " AND hf.data_fornecimento BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$sql .= " ORDER BY hf.data_fornecimento DESC";
$result = $conn->query($sql);

// Resumo estatístico
$totalQuery = "SELECT SUM(hf.quantidade) AS total_unidades FROM historico_fornecimento hf WHERE 1=1";
if ($filtroFornecedor) $totalQuery .= " AND hf.fornecedor_id IN (SELECT id FROM fornecedores WHERE nome LIKE '%$filtroFornecedor%')";
if ($filtroDataInicio && $filtroDataFim) $totalQuery .= " AND hf.data_fornecimento BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$totalUnidades = $conn->query($totalQuery)->fetch_assoc()['total_unidades'] ?? 0;

$topFornecedorQuery = "SELECT f.nome, SUM(hf.quantidade) AS total FROM historico_fornecimento hf
                       JOIN fornecedores f ON hf.fornecedor_id=f.id WHERE 1=1";
if ($filtroDataInicio && $filtroDataFim) $topFornecedorQuery .= " AND hf.data_fornecimento BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$topFornecedorQuery .= " GROUP BY f.nome ORDER BY total DESC LIMIT 1";
$topFornecedor = $conn->query($topFornecedorQuery)->fetch_assoc()['nome'] ?? 'N/A';

$topMesQuery = "SELECT MONTH(hf.data_fornecimento) AS mes, SUM(hf.quantidade) AS total
                FROM historico_fornecimento hf WHERE 1=1";
if ($filtroDataInicio && $filtroDataFim) $topMesQuery .= " AND hf.data_fornecimento BETWEEN '$filtroDataInicio' AND '$filtroDataFim'";
$topMesQuery .= " GROUP BY MONTH(hf.data_fornecimento) ORDER BY total DESC LIMIT 1";
$topMes = $conn->query($topMesQuery)->fetch_assoc()['mes'] ?? 0;
$meses = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
$topMesNome = $meses[$topMes-1] ?? 'N/A';

// Dados para gráfico
$graficoQuery = "SELECT f.nome AS fornecedor, MONTH(hf.data_fornecimento) AS mes, SUM(hf.quantidade) AS total
                 FROM historico_fornecimento hf
                 JOIN fornecedores f ON hf.fornecedor_id=f.id
                 GROUP BY f.nome, MONTH(hf.data_fornecimento)";
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
<title>Dashboard de Fornecedores</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{padding-top:70px;}
footer{background:#343a40;color:white;padding:15px 0;text-align:center;margin-top:40px;}
.navbar-brand{font-weight:bold;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="menu_admin.php">👨 Administrador</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="historico_fornecedores.php">Histórico Fornecedores</a></li>
        <li class="nav-item"><a class="nav-link" href="fluxo_caixa.php">Fluxo De Caixa</a></li>
        <li class="nav-item"><a class="nav-link" href="pagina_inicial.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">

<h2 class="mb-4">📦 Histórico de Fornecedores</h2>

<!-- FILTROS -->
<form method="get" class="row g-3 mb-4">
  <div class="col-md-4"><input type="text" name="fornecedor" class="form-control" placeholder="Fornecedor" value="<?= $filtroFornecedor ?>"></div>
  <div class="col-md-3"><input type="date" name="data_inicio" class="form-control" value="<?= $filtroDataInicio ?>"></div>
  <div class="col-md-3"><input type="date" name="data_fim" class="form-control" value="<?= $filtroDataFim ?>"></div>
  <div class="col-md-2 d-grid"><button type="submit" class="btn btn-primary">Pesquisar</button></div>
</form>

<a href="gerar_relatorio.php?tipo=fornecedor&fornecedor=Junior&data_inicio=2025-09-01&data_fim=2025-09-15" class="btn btn-danger mb-3">📄 Exportar PDF</a>

<!-- RESUMO -->
<div class="row mb-4 text-center">
  <div class="col-md-4"><div class="p-3 bg-primary text-white rounded shadow"><h5>Total de Unidades</h5><p class="fs-4"><?= $totalUnidades ?></p></div></div>
  <div class="col-md-4"><div class="p-3 bg-success text-white rounded shadow"><h5>Fornecedor que Mais Forneceu</h5><p class="fs-5"><?= $topFornecedor ?></p></div></div>
  <div class="col-md-4"><div class="p-3 bg-warning text-dark rounded shadow"><h5>Mês com Maior Fornecimento</h5><p class="fs-5"><?= $topMesNome ?></p></div></div>
</div>

<!-- TABELA -->
<div class="table-responsive bg-white p-3 rounded shadow mb-5">
<table class="table table-striped">
<thead><tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr></thead>
<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= $row['fornecedor'] ?></td>
<td><?= $row['medicamento'] ?></td>
<td><?= $row['quantidade'] ?></td>
<td><?= $row['data_fornecimento'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- GRÁFICO -->
<h3 class="mb-3">📊 Tendência Mensal Empilhada por Fornecedor</h3>
<canvas id="graficoFornecedores" style="max-width:100%;"></canvas>

</div>

<!-- FOOTER -->
<footer>
  &copy; <?= date("Y") ?> Farmácia Dashboard. Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function gerarCor(alpha=0.6){
    const r=Math.floor(Math.random()*200)+30;
    const g=Math.floor(Math.random()*200)+30;
    const b=Math.floor(Math.random()*200)+30;
    return `rgba(${r},${g},${b},${alpha})`;
}

const datasets = [
<?php
foreach($dadosGrafico as $fornecedor=>$dados){
    $totais=array_fill(1,12,0);
    foreach($dados as $d) $totais[$d['mes']]=$d['total'];
    echo "{label:'$fornecedor', data:[".implode(",", $totais)."], backgroundColor:gerarCor()},";
}
?>
];

new Chart(document.getElementById('graficoFornecedores'),{
type:'bar',
data:{labels:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],datasets:datasets},
options:{
responsive:true,
plugins:{legend:{position:'top'}, tooltip:{callbacks:{label: ctx=>ctx.dataset.label+': '+ctx.raw+' unidades'}}},
scales:{y:{beginAtZero:true,title:{display:true,text:'Quantidade Fornecida'}},x:{title:{display:true,text:'Meses'}}}
}
});
</script>

</body>
</html>
