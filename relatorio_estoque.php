<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Conexão com o banco
$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Consulta sem a coluna 'categoria'
$sql = "SELECT nome, quantidade, preco, validade FROM medicamentos ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_medicamentos = count($medicamentos);
$total_unidades = array_sum(array_column($medicamentos, 'quantidade'));

ob_start();
?>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h1, h2 { color: #333; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    th { background-color: #f0f0f0; }
</style>

<h1>Relatório de Estoque</h1>
<p><strong>Data:</strong> <?= date('d/m/Y H:i') ?></p>

<h2>Resumo</h2>
<ul>
    <li><strong>Total de medicamentos:</strong> <?= $total_medicamentos ?></li>
    <li><strong>Total de unidades:</strong> <?= $total_unidades ?></li>
</ul>

<h2>Lista de Medicamentos</h2>
<table>
    <tr>
        <th>Nome</th>
        <th>Quantidade</th>
        <th>Preço (R$)</th>
        <th>Validade</th>
    </tr>
    <?php foreach ($medicamentos as $m): ?>
    <tr>
        <td><?= $m['nome'] ?></td>
        <td><?= $m['quantidade'] ?></td>
        <td><?= number_format($m['preco'], 2, ',', '.') ?></td>
        <td><?= date('d/m/Y', strtotime($m['validade'])) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("relatorio_estoque.pdf", ["Attachment" => false]);
?>
