<?php
// Inclui o Dompdf manualmente
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Conexão com o banco de dados
$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Buscar dados das vendas
$sql = "SELECT v.*, m.nome AS medicamento, u.nome AS usuario
        FROM vendas v
        JOIN medicamentos m ON v.id_medicamento = m.id
        JOIN usuarios u ON v.id_usuario= u.id
        ORDER BY v.data_venda DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar resumo por usuário
$sqlResumo = "SELECT u.nome, COUNT(v.id) AS total_vendas, SUM(v.quantidade * v.preco_unitario) AS total_valor
              FROM vendas v
              JOIN usuarios u ON v.id_usuario = u.id
              GROUP BY v.id_usuario";
$stmtResumo = $conn->prepare($sqlResumo);
$stmtResumo->execute();
$resumoUsuarios = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);

// Gerar gráfico via QuickChart
function gerarGraficoQuickChart($configJson) {
    $url = "https://quickchart.io/chart?c=" . urlencode($configJson);
    $image = file_get_contents($url);
    return 'data:image/png;base64,' . base64_encode($image);
}

$labels = array_map(fn($r) => $r['nome'], $resumoUsuarios);
$valores = array_map(fn($r) => $r['total_valor'], $resumoUsuarios);
$graficoUsuarios = gerarGraficoQuickChart(json_encode([
    'type' => 'bar',
    'data' => [
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Vendas por Usuário',
            'data' => $valores,
            'backgroundColor' => 'rgba(75, 192, 192, 0.6)'
        ]]
    ]
]));

// Buscar dados de vendas por dia
$sqlTendencia = "SELECT DATE(data_venda) as data, SUM(quantidade * preco_unitario) as total
                 FROM vendas
                 GROUP BY DATE(data_venda)
                 ORDER BY data";
$stmtTendencia = $conn->prepare($sqlTendencia);
$stmtTendencia->execute();
$tendencias = $stmtTendencia->fetchAll(PDO::FETCH_ASSOC);

// Preparar dados para o gráfico de linha (tendência)
$labelsTendencia = array_map(fn($t) => date('d/m', strtotime($t['data'])), $tendencias);
$valoresTendencia = array_map(fn($t) => $t['total'], $tendencias);
$graficoTendencia = gerarGraficoQuickChart(json_encode([
    'type' => 'line',
    'data' => [
        'labels' => $labelsTendencia,
        'datasets' => [[
            'label' => 'Tendência de Vendas',
            'data' => $valoresTendencia,
            'fill' => false,
            'borderColor' => 'rgba(255, 99, 132, 1)',
            'tension' => 0.1
        ]]
    ],
    'options' => [
        'scales' => [
            'y' => ['beginAtZero' => true]
        ]
    ]
]));


// Início do HTML
ob_start();
?>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h1, h2 { color: #333; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
    th { background-color: #f0f0f0; }
</style>

<h1>Sistema de Gestão Farmacêutica</h1>
<p><strong>Data:</strong> <?= date('d/m/Y H:i') ?></p>

<h2>Resumo Geral</h2>
<ul>
    <li><strong>Total de vendas:</strong> <?= count($vendas) ?></li>
    <li><strong>Total arrecadado:</strong> R$ 
        <?= number_format(array_sum(array_map(fn($v) => $v['quantidade'] * $v['preco_unitario'], $vendas)), 2, ',', '.') ?>
    </li>
</ul>

<h2>Resumo por Usuário</h2>
<table>
    <tr><th>Usuário</th><th>Qtd. Vendas</th><th>Total Vendido</th></tr>
    <?php foreach ($resumoUsuarios as $linha): ?>
        <tr>
            <td><?= $linha['nome'] ?></td>
            <td><?= $linha['total_vendas'] ?></td>
            <td>R$ <?= number_format($linha['total_valor'], 2, ',', '.') ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Histórico de Vendas</h2>
<table>
    <tr><th>Data</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th></tr>
    <?php foreach ($vendas as $v): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($v['data_venda'])) ?></td>
            <td><?= $v['medicamento'] ?></td>
            <td><?= $v['quantidade'] ?></td>
            <td>R$ <?= number_format($v['preco_unitario'], 2, ',', '.') ?></td>
            <td>R$ <?= number_format($v['quantidade'] * $v['preco_unitario'], 2, ',', '.') ?></td>
            <td><?= $v['usuario'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Gráfico: Vendas por Usuário</h2>
<img src="<?= $graficoUsuarios ?>" width="600" />

<h2>Gráfico: Tendência de Vendas por Dia</h2>
<img src="<?= $graficoTendencia ?>" width="600" />


<?php
$html = ob_get_clean();

// Geração do PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("relatorio_vendas.pdf", ["Attachment" => false]);
?>
