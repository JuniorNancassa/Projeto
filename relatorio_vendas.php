<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = new PDO("mysql:host=localhost;dbname=farmacia", "root", "");

// Recebe filtros via GET
$tipo = $_GET['tipo'] ?? 'historico';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Função para gerar gráficos via QuickChart
function gerarGraficoQuickChart($configJson) {
    $url = "https://quickchart.io/chart?c=" . urlencode($configJson);
    $image = @file_get_contents($url);
    return $image ? 'data:image/png;base64,' . base64_encode($image) : '';
}

// Inicializa variáveis
$vendas = [];
$resumoUsuarios = [];
$medicamentoTop = null;
$graficoUsuarios = '';
$graficoMedicamentos = '';
$graficoTendencia = '';
$totalGeral = 0;
$totalVendas = 0;
$totalDiario = [];

// Dados conforme tipo de relatório
switch($tipo){
    case 'historico':
    default:
        // Vendas detalhadas
        $sql = "SELECT v.*, m.nome AS medicamento, u.nome AS usuario
                FROM vendas v
                JOIN medicamentos m ON v.id_medicamento = m.id
                JOIN usuarios u ON v.id_usuario = u.id
                WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                ORDER BY v.data_venda DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Resumo por usuário
        $sqlResumo = "SELECT u.nome, COUNT(v.id) AS total_vendas, SUM(v.quantidade * v.preco_unitario) AS total_valor
                      FROM vendas v
                      JOIN usuarios u ON v.id_usuario = u.id
                      WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                      GROUP BY v.id_usuario";
        $stmtResumo = $conn->prepare($sqlResumo);
        $stmtResumo->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $resumoUsuarios = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);

        // Medicamento mais vendido
        $sqlTop = "SELECT m.nome, SUM(v.quantidade) AS total_quantidade
                   FROM vendas v
                   JOIN medicamentos m ON v.id_medicamento = m.id
                   WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                   GROUP BY v.id_medicamento
                   ORDER BY total_quantidade DESC
                   LIMIT 1";
        $stmtTop = $conn->prepare($sqlTop);
        $stmtTop->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $medicamentoTop = $stmtTop->fetch(PDO::FETCH_ASSOC);

        // Gráficos
        if(count($resumoUsuarios)){
            $graficoUsuarios = gerarGraficoQuickChart(json_encode([
                'type'=>'bar',
                'data'=>[
                    'labels'=>array_map(fn($r) => $r['nome'],$resumoUsuarios),
                    'datasets'=>[['label'=>'Vendas por Usuário (R$)','data'=>array_map(fn($r)=>round($r['total_valor'],2),$resumoUsuarios),'backgroundColor'=>'rgba(54, 162, 235, 0.7)']]
                ],
                'options'=>['plugins'=>['legend'=>['display'=>false]]]
            ]));
        }

        $sqlMedicamentos = "SELECT m.nome, SUM(v.quantidade * v.preco_unitario) AS total_valor
                            FROM vendas v
                            JOIN medicamentos m ON v.id_medicamento = m.id
                            WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                            GROUP BY v.id_medicamento
                            ORDER BY total_valor DESC";
        $stmtMedicamentos = $conn->prepare($sqlMedicamentos);
        $stmtMedicamentos->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $medicamentosGraf = $stmtMedicamentos->fetchAll(PDO::FETCH_ASSOC);
        if(count($medicamentosGraf)){
            $graficoMedicamentos = gerarGraficoQuickChart(json_encode([
                'type'=>'pie',
                'data'=>[
                    'labels'=>array_map(fn($r)=>$r['nome'],$medicamentosGraf),
                    'datasets'=>[['label'=>'Vendas por Medicamento (R$)','data'=>array_map(fn($r)=>round($r['total_valor'],2),$medicamentosGraf),'backgroundColor'=>array_map(fn()=>sprintf('#%06X',mt_rand(0,0xFFFFFF)),$medicamentosGraf)]]
                ],
                'options'=>['plugins'=>['legend'=>['position'=>'bottom']]]
            ]));
        }

        // Tendência de vendas
        $sqlTend = "SELECT DATE(data_venda) AS data, SUM(quantidade*preco_unitario) AS total
                    FROM vendas
                    WHERE DATE(data_venda) BETWEEN :inicio AND :fim
                    GROUP BY DATE(data_venda)
                    ORDER BY data";
        $stmtTend = $conn->prepare($sqlTend);
        $stmtTend->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $tendencias = $stmtTend->fetchAll(PDO::FETCH_ASSOC);
        if(count($tendencias)){
            $graficoTendencia = gerarGraficoQuickChart(json_encode([
                'type'=>'line',
                'data'=>[
                    'labels'=>array_map(fn($t)=>date('d/m',strtotime($t['data'])),$tendencias),
                    'datasets'=>[['label'=>'Tendência de Vendas (R$)','data'=>array_map(fn($t)=>round($t['total'],2),$tendencias),'fill'=>false,'borderColor'=>'rgba(255,99,132,0.9)','tension'=>0.2]]
                ],
                'options'=>['scales'=>['y'=>['beginAtZero'=>true]]]
            ]));
        }

        // Totais
        $totalGeral = array_sum(array_map(fn($v)=>($v['quantidade']??$v['total_qtd'])*($v['preco_unitario']??($v['total_valor']??0)/max($v['quantidade']??$v['total_qtd'],1)),$vendas));
        $totalVendas = count($vendas);

        // Total diário
        $sqlTotalDiario = "SELECT DATE(data_venda) AS data, SUM(quantidade*preco_unitario) AS total
                           FROM vendas
                           WHERE DATE(data_venda) BETWEEN :inicio AND :fim
                           GROUP BY DATE(data_venda)";
        $stmtTotalDiario = $conn->prepare($sqlTotalDiario);
        $stmtTotalDiario->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $totalDiario = $stmtTotalDiario->fetchAll(PDO::FETCH_ASSOC);
    break;

    case 'resumo_usuario':
        $sqlResumo = "SELECT u.nome, COUNT(v.id) AS total_vendas, SUM(v.quantidade*v.preco_unitario) AS total_valor
                      FROM vendas v
                      JOIN usuarios u ON v.id_usuario=u.id
                      WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                      GROUP BY v.id_usuario";
        $stmtResumo = $conn->prepare($sqlResumo);
        $stmtResumo->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $resumoUsuarios = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);
        $totalGeral = array_sum(array_map(fn($r)=>$r['total_valor'], $resumoUsuarios));
        $totalVendas = array_sum(array_map(fn($r)=>$r['total_vendas'], $resumoUsuarios));
    break;

    case 'total_dia':
        $sqlTotal = "SELECT SUM(quantidade*preco_unitario) AS total_dia FROM vendas
                     WHERE DATE(data_venda) BETWEEN :inicio AND :fim";
        $stmtTotal = $conn->prepare($sqlTotal);
        $stmtTotal->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $totalGeral = $stmtTotal->fetchColumn();
        $totalVendas = null;
    break;

    case 'medicamento':
        $sqlMed = "SELECT m.nome, SUM(v.quantidade) AS total_qtd, SUM(v.quantidade*v.preco_unitario) AS total_valor
                   FROM vendas v
                   JOIN medicamentos m ON v.id_medicamento=m.id
                   WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
                   GROUP BY v.id_medicamento
                   ORDER BY total_qtd DESC";
        $stmtMed = $conn->prepare($sqlMed);
        $stmtMed->execute(['inicio'=>$data_inicio,'fim'=>$data_fim]);
        $vendas = $stmtMed->fetchAll(PDO::FETCH_ASSOC);
        $totalGeral = array_sum(array_map(fn($v)=>$v['total_valor'],$vendas));
        $totalVendas = array_sum(array_map(fn($v)=>$v['total_qtd'],$vendas));
    break;
}

// Geração do HTML do PDF
ob_start();
?>
<style>
body { font-family: Arial,sans-serif; font-size:12px; color:#333; }
h1,h2,h3{ color:#00539C; }
table{ width:100%; border-collapse:collapse; margin-bottom:20px; }
th,td{ border:1px solid #ddd; padding:6px; text-align:left; }
th{ background-color:#e3f2fd; }
.highlight{ background-color:#ffd54f; padding:6px; border-radius:4px; font-weight:bold; margin-bottom:15px; }
</style>

<h1>Relatório: <?= ucfirst($tipo) ?></h1>
<p><strong>Período:</strong> <?= date('d/m/Y',strtotime($data_inicio)) ?> a <?= date('d/m/Y',strtotime($data_fim)) ?></p>
<p><strong>Data do relatório:</strong> <?= date('d/m/Y H:i') ?></p>

<h2>Resumo Geral</h2>
<ul>
<li><strong>Total de vendas:</strong> <?= $totalVendas ?? 0 ?></li>
<li><strong>Total arrecadado:</strong> R$ <?= number_format($totalGeral,2,',','.') ?></li>
</ul>

<?php if($medicamentoTop): ?>
<div class="highlight">
Medicamento mais vendido: <?= htmlspecialchars($medicamentoTop['nome']) ?> (<?= $medicamentoTop['total_quantidade'] ?> unidades)
</div>
<?php endif; ?>

<?php if(count($resumoUsuarios)): ?>
<h2>Resumo por Usuário</h2>
<table>
<tr><th>Usuário</th><th>Qtd. Vendas</th><th>Total Vendido (R$)</th></tr>
<?php foreach($resumoUsuarios as $r): ?>
<tr>
<td><?= htmlspecialchars($r['nome']) ?></td>
<td><?= $r['total_vendas'] ?></td>
<td><?= number_format($r['total_valor'],2,',','.') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($totalDiario)): ?>
<h2>Total Diário de Vendas</h2>
<table>
<tr><th>Data</th><th>Total</th></tr>
<?php foreach($totalDiario as $d): ?>
<tr>
<td><?= date('d/m/Y',strtotime($d['data'])) ?></td>
<td><?= number_format($d['total'],2,',','.') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($vendas)): ?>
<h2>Histórico Detalhado</h2>
<table>
<tr><th>Data</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th></tr>
<?php foreach($vendas as $v): 
    $dataVenda = isset($v['data_venda']) ? date('d/m/Y',strtotime($v['data_venda'])) : '-';
    $med = htmlspecialchars($v['medicamento'] ?? '-');
    $qtd = $v['quantidade'] ?? $v['total_qtd'] ?? 0;
    $preco = $v['preco_unitario'] ?? (($v['total_valor'] ?? 0)/max($qtd,1));
    $total = $qtd * $preco;
    $usuario = htmlspecialchars($v['usuario'] ?? '-');
?>
<tr>
<td><?= $dataVenda ?></td>
<td><?= $med ?></td>
<td><?= $qtd ?></td>
<td>R$ <?= number_format($preco,2,',','.') ?></td>
<td>R$ <?= number_format($total,2,',','.') ?></td>
<td><?= $usuario ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if($graficoUsuarios): ?><h2>Gráfico: Vendas por Usuário</h2><img src="<?= $graficoUsuarios ?>" width="600"><?php endif; ?>
<?php if($graficoMedicamentos): ?><h2>Gráfico: Vendas por Medicamento</h2><img src="<?= $graficoMedicamentos ?>" width="600"><?php endif; ?>
<?php if($graficoTendencia): ?><h2>Gráfico: Tendência de Vendas</h2><img src="<?= $graficoTendencia ?>" width="600"><?php endif; ?>

<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled',true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("relatorio_vendas.pdf",["Attachment"=>false]);
?>
