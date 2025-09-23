<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['usuario_logado'])) {
    echo "<script>alert('Você não tem permissão'); window.location.href='login.php';</script>";
    exit;
}

// Conexão PDO
$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Filtros via GET
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

// -------------------------------
// Consulta de fluxo de caixa
// -------------------------------
$sql = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
if ($dataInicio && $dataFim) {
    $sql .= " AND data_movimento BETWEEN :dataInicio AND :dataFim";
}
$sql .= " ORDER BY data_movimento DESC";

$stmt = $conn->prepare($sql);
if ($dataInicio && $dataFim) {
    $stmt->bindValue(':dataInicio', $dataInicio);
    $stmt->bindValue(':dataFim', $dataFim);
}
$stmt->execute();
$movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Estatísticas
// -------------------------------
$totalEntrada = 0;
$totalSaida = 0;
$fluxoMensal = [];

foreach ($movimentos as $m) {
    $mes = date('m', strtotime($m['data_movimento']));
    if (!isset($fluxoMensal[$mes])) $fluxoMensal[$mes] = ['entrada'=>0,'saida'=>0];

    if (strtolower($m['tipo']) === 'entrada') {
        $totalEntrada += $m['valor'];
        $fluxoMensal[$mes]['entrada'] += $m['valor'];
    } else {
        $totalSaida += $m['valor'];
        $fluxoMensal[$mes]['saida'] += $m['valor'];
    }
}

$saldoFinal = $totalEntrada - $totalSaida;

// -------------------------------
// Montar dados para o gráfico
// -------------------------------
$meses = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
$entradasGrafico = [];
$saidasGrafico = [];

for ($i=1; $i<=12; $i++) {
    $key = str_pad($i,2,'0',STR_PAD_LEFT);
    $entradasGrafico[] = $fluxoMensal[$key]['entrada'] ?? 0;
    $saidasGrafico[] = $fluxoMensal[$key]['saida'] ?? 0;
}

// Gerar URL do QuickChart
$chartConfig = [
    "type"=>"bar",
    "data"=>[
        "labels"=>$meses,
        "datasets"=>[
            ["label"=>"Entradas","data"=>$entradasGrafico,"backgroundColor"=>"rgba(40,167,69,0.6)"],
            ["label"=>"Saídas","data"=>$saidasGrafico,"backgroundColor"=>"rgba(220,53,69,0.6)"]
        ]
    ],
    "options"=>[
        "plugins"=>["legend"=>["position"=>"top"]],
        "scales"=>[
            "y"=>["beginAtZero"=>true,"title"=>["display"=>true,"text"=>"Valor (CFA)"]],
            "x"=>["title"=>["display"=>true,"text"=>"Meses"]]
        ]
    ]
];

$chartUrl = "https://quickchart.io/chart?c=".urlencode(json_encode($chartConfig));

// -------------------------------
// Gerar HTML para o PDF
// -------------------------------
ob_start();
?>

<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
h1, h2 { color: #0d6efd; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background-color: #f0f0f0; }
.stats { margin-bottom: 20px; }
.stats div { margin-bottom: 5px; }
.chart-container { width: 100%; height: 300px; margin-top: 20px; text-align:center; }
.chart-container img { width:100%; height:auto; }
</style>

<h1>Relatório de Fluxo de Caixa</h1>
<p><strong>Período:</strong> <?= ($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos" ?></p>

<div class="stats">
    <div><strong>Total de Entradas:</strong> <?= number_format($totalEntrada,2,",",".") ?> CFA</div>
    <div><strong>Total de Saídas:</strong> <?= number_format($totalSaida,2,",",".") ?> CFA</div>
    <div><strong>Saldo Final:</strong> <?= number_format($saldoFinal,2,",",".") ?> CFA</div>
</div>

<table>
    <tr>
        <th>Tipo</th>
        <th>Descrição</th>
        <th>Valor (CFA)</th>
        <th>Data</th>
    </tr>
    <?php foreach ($movimentos as $m): ?>
    <tr>
        <td><?= ucfirst($m['tipo']) ?></td>
        <td><?= $m['descricao'] ?></td>
        <td><?= number_format($m['valor'],2,",",".") ?></td>
        <td><?= date('d/m/Y', strtotime($m['data_movimento'])) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="chart-container">
    <h3>Fluxo Mensal - Entradas vs Saídas</h3>
    <img src="<?= $chartUrl ?>" alt="Gráfico Fluxo de Caixa">
</div>

<?php
$html = ob_get_clean();

// -------------------------------
// Gerar PDF com Dompdf
// -------------------------------
$options = new Options();
$options->set('isRemoteEnabled', true); // necessário para imagens externas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("relatorio_fluxo_caixa.pdf", ["Attachment" => false]);
exit;
?>
