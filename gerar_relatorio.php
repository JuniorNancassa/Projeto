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

// Tipo de relatório: fluxo ou fornecedor
$tipo = $_GET['tipo'] ?? 'fluxo'; 

ob_start();
?>

<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
h1, h2 { color: #0d6efd; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background-color: #f0f0f0; }
</style>

<?php
if($tipo == 'fluxo'){
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    $sql = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
    if($dataInicio && $dataFim) $sql .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sql .= " ORDER BY data_movimento DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Relatório de Fluxo de Caixa</h1>";
    echo "<p><strong>Período:</strong> " . (($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos") . "</p>";
    echo "<table>
            <tr><th>Tipo</th><th>Descrição</th><th>Valor (CFA)</th><th>Data</th></tr>";
    foreach($movimentos as $m){
        echo "<tr>
                <td>".ucfirst($m['tipo'])."</td>
                <td>{$m['descricao']}</td>
                <td>".number_format($m['valor'],2,",",".")."</td>
                <td>".date('d/m/Y', strtotime($m['data_movimento']))."</td>
              </tr>";
    }
    echo "</table>";

} elseif($tipo == 'fornecedor') {
    $fornecedor = $_GET['fornecedor'] ?? '';
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    $sql = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_fornecimento
            FROM historico_fornecimento hf
            JOIN fornecedores f ON hf.fornecedor_id=f.id
            JOIN medicamentos m ON hf.medicamento_id=m.id
            WHERE 1=1";
    if($fornecedor) $sql .= " AND f.nome LIKE '%$fornecedor%'";
    if($dataInicio && $dataFim) $sql .= " AND hf.data_fornecimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sql .= " ORDER BY hf.data_fornecimento DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Relatório de Histórico de Fornecedores</h1>";
    echo "<p><strong>Fornecedor:</strong> " . ($fornecedor ?: "Todos") . "</p>";
    echo "<p><strong>Período:</strong> " . (($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos") . "</p>";

    echo "<table>
            <tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr>";
    foreach($historico as $h){
        echo "<tr>
                <td>{$h['fornecedor']}</td>
                <td>{$h['medicamento']}</td>
                <td>{$h['quantidade']}</td>
                <td>".date('d/m/Y', strtotime($h['data_fornecimento']))."</td>
              </tr>";
    }
    echo "</table>";
} else {
    die("Tipo de relatório inválido.");
}

$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Nome do arquivo
$filename = ($tipo=='fluxo') ? "relatorio_fluxo_caixa.pdf" : "relatorio_historico_fornecedores.pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>
<?php

session_start();
if (!isset($_SESSION['usuario_logado'])) {
    echo "<script>alert('Você não tem permissão'); window.location.href='login.php';</script>";
    exit;
}

// Conexão PDO
$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Tipo de relatório: fluxo ou fornecedor
$tipo = $_GET['tipo'] ?? 'fluxo'; 

ob_start();
?>

<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
h1, h2 { color: #0d6efd; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background-color: #f0f0f0; }
</style>

<?php
if($tipo == 'fluxo'){
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    $sql = "SELECT tipo, descricao, valor, data_movimento FROM fluxo_caixa WHERE 1=1";
    if($dataInicio && $dataFim) $sql .= " AND data_movimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sql .= " ORDER BY data_movimento DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Relatório de Fluxo de Caixa</h1>";
    echo "<p><strong>Período:</strong> " . (($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos") . "</p>";
    echo "<table>
            <tr><th>Tipo</th><th>Descrição</th><th>Valor (CFA)</th><th>Data</th></tr>";
    foreach($movimentos as $m){
        echo "<tr>
                <td>".ucfirst($m['tipo'])."</td>
                <td>{$m['descricao']}</td>
                <td>".number_format($m['valor'],2,",",".")."</td>
                <td>".date('d/m/Y', strtotime($m['data_movimento']))."</td>
              </tr>";
    }
    echo "</table>";

} elseif($tipo == 'fornecedor') {
    $fornecedor = $_GET['fornecedor'] ?? '';
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    $sql = "SELECT hf.id, f.nome AS fornecedor, m.nome AS medicamento, hf.quantidade, hf.data_fornecimento
            FROM historico_fornecimento hf
            JOIN fornecedores f ON hf.fornecedor_id=f.id
            JOIN medicamentos m ON hf.medicamento_id=m.id
            WHERE 1=1";
    if($fornecedor) $sql .= " AND f.nome LIKE '%$fornecedor%'";
    if($dataInicio && $dataFim) $sql .= " AND hf.data_fornecimento BETWEEN '$dataInicio' AND '$dataFim'";
    $sql .= " ORDER BY hf.data_fornecimento DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Relatório de Histórico de Fornecedores</h1>";
    echo "<p><strong>Fornecedor:</strong> " . ($fornecedor ?: "Todos") . "</p>";
    echo "<p><strong>Período:</strong> " . (($dataInicio && $dataFim) ? "$dataInicio a $dataFim" : "Todos") . "</p>";

    echo "<table>
            <tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr>";
    foreach($historico as $h){
        echo "<tr>
                <td>{$h['fornecedor']}</td>
                <td>{$h['medicamento']}</td>
                <td>{$h['quantidade']}</td>
                <td>".date('d/m/Y', strtotime($h['data_fornecimento']))."</td>
              </tr>";
    }
    echo "</table>";
} else {
    die("Tipo de relatório inválido.");
}

$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Nome do arquivo
$filename = ($tipo=='fluxo') ? "relatorio_fluxo_caixa.pdf" : "relatorio_historico_fornecedores.pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>
