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
$fornecedor = $_GET['fornecedor'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

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
img.chart { max-width: 100%; height: auto; margin-top: 20px; }
</style>

<?php
// -------------------------------
// Consulta detalhada do histórico
// -------------------------------
$sql = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_registro
        FROM historico_fornecedores hf
        JOIN fornecedores f ON hf.fornecedor_id=f.id
        JOIN medicamentos m ON hf.medicamento_id=m.id
        WHERE 1=1";
if ($fornecedor) $sql .= " AND f.nome LIKE :fornecedor";
if ($dataInicio && $dataFim) $sql .= " AND hf.data_registro BETWEEN :dataInicio AND :dataFim";
$sql .= " ORDER BY hf.data_registro DESC";

$stmt = $conn->prepare($sql);
if ($fornecedor) $stmt->bindValue(':fornecedor', "%$fornecedor%");
if ($dataInicio && $dataFim) {
    $stmt->bindValue(':dataInicio', $dataInicio);
    $stmt->bindValue(':dataFim', $dataFim);
}
$stmt->execute();
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Estatísticas
// -------------------------------
// Total de unidades
$totalQuery = "SELECT SUM(hf.quantidade) AS total_unidades
               FROM historico_fornecedores hf
               JOIN fornecedores f ON hf.fornecedor_id=f.id
               WHERE 1=1";
if ($fornecedor) $totalQuery .= " AND f.nome LIKE :fornecedor";
if ($dataInicio && $dataFim) $totalQuery .= " AND hf.data_registro BETWEEN :dataInicio AND :dataFim";

$stmtTotal = $conn->prepare($totalQuery);
if ($fornecedor) $stmtTotal->bindValue(':fornecedor', "%$fornecedor%");
if ($dataInicio && $dataFim) {
    $stmtTotal->bindValue(':dataInicio', $dataInicio);
    $stmtTotal->bindValue(':dataFim', $dataFim);
}
$stmtTotal->execute();
$totalUnidades = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_unidades'] ?? 0;

// Fornecedor que mais forneceu
$topFornecedorQuery = "SELECT f.nome, SUM(hf.quantidade) AS total
                       FROM historico_fornecedores hf
                       JOIN fornecedores f ON hf.fornecedor_id=f.id
                       WHERE 1=1";
if ($fornecedor) $topFornecedorQuery .= " AND f.nome LIKE :fornecedor";
if ($dataInicio && $dataFim) $topFornecedorQuery .= " AND hf.data_registro BETWEEN :dataInicio AND :dataFim";
$topFornecedorQuery .= " GROUP BY f.nome ORDER BY total DESC LIMIT 1";

$stmtTop = $conn->prepare($topFornecedorQuery);
if ($fornecedor) $stmtTop->bindValue(':fornecedor', "%$fornecedor%");
if ($dataInicio && $dataFim) {
    $stmtTop->bindValue(':dataInicio', $dataInicio);
    $stmtTop->bindValue(':dataFim', $dataFim);
}
$stmtTop->execute();
$topFornecedor = $stmtTop->fetch(PDO::FETCH_ASSOC)['nome'] ?? 'N/A';

// Mês com maior fornecimento
$topMesQuery = "SELECT MONTH(hf.data_registro) AS mes, SUM(hf.quantidade) AS total
                FROM historico_fornecedores hf
                WHERE 1=1";
if ($fornecedor) $topMesQuery .= " AND hf.fornecedor_id IN (SELECT id FROM fornecedores WHERE nome LIKE :fornecedor)";
if ($dataInicio && $dataFim) $topMesQuery .= " AND hf.data_registro BETWEEN :dataInicio AND :dataFim";
$topMesQuery .= " GROUP BY MONTH(hf.data_registro) ORDER BY total DESC LIMIT 1";

$stmtMes = $conn->prepare($topMesQuery);
if ($fornecedor) $stmtMes->bindValue(':fornecedor', "%$fornecedor%");
if ($dataInicio && $dataFim) {
    $stmtMes->bindValue(':dataInicio', $dataInicio);
    $stmtMes->bindValue(':dataFim', $dataFim);
}
$stmtMes->execute();
$topMesNum = $stmtMes->fetch(PDO::FETCH_ASSOC)['mes'] ?? 0;
$meses = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
$topMesNome = $meses[$topMesNum-1] ?? 'N/A';

// -------------------------------
// Dados do gráfico empilhado
// -------------------------------
$graficoQuery = "SELECT f.nome AS fornecedor, MONTH(hf.data_registro) AS mes, SUM(hf.quantidade) AS total
                 FROM historico_fornecedores hf
                 JOIN fornecedores f ON hf.fornecedor_id=f.id
                 WHERE 1=1";
if ($fornecedor) $graficoQuery .= " AND f.nome LIKE :fornecedor";
if ($dataInicio && $dataFim) $graficoQuery .= " AND hf.data_registro BETWEEN :dataInicio AND :dataFim";
$graficoQuery .= " GROUP BY f.nome, MONTH(hf.data_registro) ORDER BY f.nome, mes";

$stmtGraf = $conn->prepare($graficoQuery);
if ($fornecedor) $stmtGraf->bindValue(':fornecedor', "%$fornecedor%");
if ($dataInicio && $dataFim) {
    $stmtGraf->bindValue(':dataInicio', $dataInicio);
    $stmtGraf->bindValue(':dataFim', $dataFim);
}
$stmtGraf->execute();
$graficoData = $stmtGraf->fetchAll(PDO::FETCH_ASSOC);

// Preparar matriz meses x fornecedores
$mesesRange = range(1,12);
$fornecedores = array_unique(array_column($graficoData,'fornecedor'));
sort($fornecedores);
$dadosMes = [];
foreach ($mesesRange as $m) {
    foreach ($fornecedores as $f) $dadosMes[$m][$f] = 0;
}
foreach ($graficoData as $d) $dadosMes[$d['mes']][$d['fornecedor']] = $d['total'];

// Cores fixas para fornecedores
$coresFornecedores = [];
$coresBase = [
    '#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1','#fd7e14','#20c997','#6610f2','#e83e8c'
];
foreach ($fornecedores as $i => $f) $coresFornecedores[$f] = $coresBase[$i % count($coresBase)];

// -------------------------------
// Criar gráfico empilhado usando GD
// -------------------------------
$width = 800; $height = 400; $im = imagecreatetruecolor($width,$height);
$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
imagefill($im,0,0,$white);

$barWidth = intval($width / (count($mesesRange)*2));
$x = $barWidth/2;
$maxTotal = max(array_map(fn($m) => array_sum($m), $dadosMes)) ?: 1;

foreach ($mesesRange as $m) {
    $y0 = $height - 40;
    foreach ($fornecedores as $f) {
        $val = $dadosMes[$m][$f];
        $barHeight = ($val/$maxTotal)*($height-60);
        list($r,$g,$b) = sscanf($coresFornecedores[$f], "#%02x%02x%02x");
        $color = imagecolorallocate($im,$r,$g,$b);
        imagefilledrectangle($im, $x, $y0-$barHeight, $x+$barWidth, $y0, $color);
        $y0 -= $barHeight;
    }
    imagestring($im, 3, $x+2, $height-35, $meses[$m-1], $black);
    $x += 2*$barWidth;
}

// Legenda
$legendaY = 10;
foreach ($fornecedores as $f) {
    list($r,$g,$b) = sscanf($coresFornecedores[$f], "#%02x%02x%02x");
    $color = imagecolorallocate($im,$r,$g,$b);
    imagefilledrectangle($im,10,$legendaY,30,$legendaY+15,$color);
    imagestring($im,3,35,$legendaY,$f,$black);
    $legendaY += 20;
}

// Salvar gráfico em memória
ob_start();
imagepng($im);
$imgData = ob_get_clean();
imagedestroy($im);
$imgBase64 = 'data:image/png;base64,'.base64_encode($imgData);

// -------------------------------
// Cabeçalho do relatório
// -------------------------------
echo "<h1>Relatório de Histórico de Fornecedores</h1>";
echo "<p><strong>Fornecedor:</strong> " . ($fornecedor ?: "Todos") . "</p>";
echo "<p><strong>Período:</strong> " . (($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos") . "</p>";

echo "<div class='stats'>
        <div><strong>Total de Unidades Fornecidas:</strong> $totalUnidades</div>
        <div><strong>Fornecedor que Mais Forneceu:</strong> $topFornecedor</div>
        <div><strong>Mês com Maior Fornecimento:</strong> $topMesNome</div>
      </div>";

// Tabela
echo "<table>
        <tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr>";
foreach ($historico as $h) {
    echo "<tr>
            <td>{$h['fornecedor']}</td>
            <td>{$h['medicamento']}</td>
            <td>{$h['quantidade']}</td>
            <td>".date('d/m/Y', strtotime($h['data_registro']))."</td>
          </tr>";
}
echo "</table>";

// Inserir gráfico abaixo da tabela
echo "<h2>📊 Tendência Mensal Empilhada por Fornecedor</h2>";
echo "<img class='chart' src='$imgBase64' alt='Gráfico Empilhado'>";

// -------------------------------
// Gerar PDF
// -------------------------------
$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "relatorio_historico_fornecedores.pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>
