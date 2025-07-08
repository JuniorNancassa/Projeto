<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Recebe filtro por data via GET, padrão para último mês
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Buscar dados das vendas filtrados por data
$sql = "SELECT v.*, m.nome AS medicamento, u.nome AS usuario
        FROM vendas v
        JOIN medicamentos m ON v.id_medicamento = m.id
        JOIN usuarios u ON v.id_usuario= u.id
        WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
        ORDER BY v.data_venda DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resumo por usuário no período
$sqlResumo = "SELECT u.nome, COUNT(v.id) AS total_vendas, SUM(v.quantidade * v.preco_unitario) AS total_valor
              FROM vendas v
              JOIN usuarios u ON v.id_usuario = u.id
              WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
              GROUP BY v.id_usuario";
$stmtResumo = $conn->prepare($sqlResumo);
$stmtResumo->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$resumoUsuarios = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);

// Medicamento mais vendido no período
$sqlMedicamentoTop = "SELECT m.nome, SUM(v.quantidade) AS total_quantidade
                      FROM vendas v
                      JOIN medicamentos m ON v.id_medicamento = m.id
                      WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                      GROUP BY v.id_medicamento
                      ORDER BY total_quantidade DESC
                      LIMIT 1";
$stmtMedicamentoTop = $conn->prepare($sqlMedicamentoTop);
$stmtMedicamentoTop->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$medicamentoTop = $stmtMedicamentoTop->fetch(PDO::FETCH_ASSOC);

// Gráfico - Vendas por Usuário
function gerarGraficoQuickChart($configJson) {
    $url = "https://quickchart.io/chart?c=" . urlencode($configJson);
    $image = file_get_contents($url);
    return 'data:image/png;base64,' . base64_encode($image);
}

$labelsUsuarios = array_map(fn($r) => $r['nome'], $resumoUsuarios);
$valoresUsuarios = array_map(fn($r) => round($r['total_valor'], 2), $resumoUsuarios);
$graficoUsuarios = gerarGraficoQuickChart(json_encode([
    'type' => 'bar',
    'data' => [
        'labels' => $labelsUsuarios,
        'datasets' => [[
            'label' => 'Vendas por Usuário (R$)',
            'data' => $valoresUsuarios,
            'backgroundColor' => 'rgba(54, 162, 235, 0.7)'
        ]]
    ],
    'options' => ['plugins' => ['legend' => ['display' => false]]]
]));

// Gráfico - Vendas por Medicamento
$sqlMedicamentosGraf = "SELECT m.nome, SUM(v.quantidade * v.preco_unitario) AS total_valor
                       FROM vendas v
                       JOIN medicamentos m ON v.id_medicamento = m.id
                       WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                       GROUP BY v.id_medicamento
                       ORDER BY total_valor DESC";
$stmtMedicamentosGraf = $conn->prepare($sqlMedicamentosGraf);
$stmtMedicamentosGraf->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$medicamentosGraf = $stmtMedicamentosGraf->fetchAll(PDO::FETCH_ASSOC);

$labelsMedicamentos = array_map(fn($r) => $r['nome'], $medicamentosGraf);
$valoresMedicamentos = array_map(fn($r) => round($r['total_valor'], 2), $medicamentosGraf);
$graficoMedicamentos = gerarGraficoQuickChart(json_encode([
    'type' => 'pie',
    'data' => [
        'labels' => $labelsMedicamentos,
        'datasets' => [[
            'label' => 'Vendas por Medicamento (R$)',
            'data' => $valoresMedicamentos,
            'backgroundColor' => array_map(fn() => sprintf('#%06X', mt_rand(0, 0xFFFFFF)), $labelsMedicamentos)
        ]]
    ],
    'options' => ['plugins' => ['legend' => ['position' => 'bottom']]]
]));

// Gráfico - Tendência de Vendas por Dia
$sqlTendencia = "SELECT DATE(data_venda) as data, SUM(quantidade * preco_unitario) as total
                 FROM vendas
                 WHERE DATE(data_venda) BETWEEN :inicio AND :fim
                 GROUP BY DATE(data_venda)
                 ORDER BY data";
$stmtTendencia = $conn->prepare($sqlTendencia);
$stmtTendencia->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$tendencias = $stmtTendencia->fetchAll(PDO::FETCH_ASSOC);

$labelsTendencia = array_map(fn($t) => date('d/m', strtotime($t['data'])), $tendencias);
$valoresTendencia = array_map(fn($t) => round($t['total'], 2), $tendencias);
$graficoTendencia = gerarGraficoQuickChart(json_encode([
    'type' => 'line',
    'data' => [
        'labels' => $labelsTendencia,
        'datasets' => [[
            'label' => 'Tendência de Vendas (R$)',
            'data' => $valoresTendencia,
            'fill' => false,
            'borderColor' => 'rgba(255, 99, 132, 0.9)',
            'tension' => 0.2
        ]]
    ],
    'options' => ['scales' => ['y' => ['beginAtZero' => true]]]
]));

// Total geral no período
$totalGeral = array_sum(array_map(fn($v) => $v['quantidade'] * $v['preco_unitario'], $vendas));
$totalVendas = count($vendas);

// Total diário (agrupado por dia)
$sqlTotalDiario = "SELECT DATE(data_venda) AS data, SUM(quantidade * preco_unitario) AS total
                   FROM vendas
                   WHERE DATE(data_venda) BETWEEN :inicio AND :fim
                   GROUP BY DATE(data_venda)";
$stmtTotalDiario = $conn->prepare($sqlTotalDiario);
$stmtTotalDiario->execute(['inicio' => $data_inicio, 'fim' => $data_fim]);
$totalDiario = $stmtTotalDiario->fetchAll(PDO::FETCH_ASSOC);

// Início HTML do PDF
ob_start();
?>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1, h2, h3 { color: #00539C; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background-color: #e3f2fd; }
    ul { list-style: none; padding-left: 0; }
    ul li { margin-bottom: 5px; }
    .highlight { background-color: #ffd54f; padding: 6px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; }
</style>

<h1>Relatório de Histórico de Vendas</h1>
<p><strong>Período:</strong> <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
<p><strong>Data do relatório:</strong> <?= date('d/m/Y H:i') ?></p>

<h2>Resumo Geral</h2>
<ul>
    <li><strong>Total de vendas:</strong> <?= $totalVendas ?></li>
    <li><strong>Total arrecadado:</strong> R$ <?= number_format($totalGeral, 2, ',', '.') ?></li>
</ul>

<?php if ($medicamentoTop): ?>
    <div class="highlight">
        Medicamento mais vendido: <?= htmlspecialchars($medicamentoTop['nome']) ?> (<?= $medicamentoTop['total_quantidade'] ?> unidades)
    </div>
<?php endif; ?>

<h2>Resumo por Usuário</h2>
<table>
    <tr><th>Usuário</th><th>Qtd. Vendas</th><th>Total Vendido (R$)</th></tr>
    <?php foreach ($resumoUsuarios as $linha): ?>
        <tr>
            <td><?= htmlspecialchars($linha['nome']) ?></td>
            <td><?= $linha['total_vendas'] ?></td>
            <td><?= number_format($linha['total_valor'], 2, ',', '.') ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Total Diário de Vendas (R$)</h2>
<table>
    <tr><th>Data</th><th>Total</th></tr>
    <?php foreach ($totalDiario as $dia): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($dia['data'])) ?></td>
            <td><?= number_format($dia['total'], 2, ',', '.') ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Histórico Detalhado de Vendas</h2>
<table>
    <tr>
        <th>Data</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th>
    </tr>
    <?php foreach ($vendas as $v): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($v['data_venda'])) ?></td>
            <td><?= htmlspecialchars($v['medicamento']) ?></td>
            <td><?= $v['quantidade'] ?></td>
            <td>R$ <?= number_format($v['preco_unitario'], 2, ',', '.') ?></td>
            <td>R$ <?= number_format($v['quantidade'] * $v['preco_unitario'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($v['usuario']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Gráfico: Vendas por Usuário</h2>
<img src="<?= $graficoUsuarios ?>" width="600" />

<h2>Gráfico: Vendas por Medicamento</h2>
<img src="<?= $graficoMedicamentos ?>" width="600" />

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
