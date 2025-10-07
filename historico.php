<?php
session_start();
$conn = new mysqli("localhost", "root", "", "farmacia");
if ($conn->connect_error) die("Erro na conexão: " . $conn->connect_error);

$data = $_GET['data'] ?? date('Y-m-d');
$tipo_relatorio = $_GET['tipo'] ?? 'historico';
$usuario_logado = $_SESSION['nome_usuario'] ?? 'Desconhecido';

// Histórico de vendas
$sql = "SELECT v.*, u.nome AS usuario, m.nome AS medicamento, (v.quantidade * v.preco_unitario) AS total
        FROM vendas v
        LEFT JOIN usuarios u ON v.id_usuario = u.id
        LEFT JOIN medicamentos m ON v.id_medicamento = m.id
        WHERE DATE(v.data_venda) = ?
        ORDER BY v.data_venda DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data);
$stmt->execute();
$result = $stmt->get_result();

// Total do dia
$sql_total = "SELECT SUM(quantidade * preco_unitario) AS total_dia FROM vendas WHERE DATE(data_venda) = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("s", $data);
$stmt_total->execute();
$res_total = $stmt_total->get_result();
$total_dia = $res_total->fetch_assoc()['total_dia'] ?? 0;

// Resumo por usuário
$sql_resumo = "SELECT u.nome, COUNT(*) as total_vendas, SUM(v.quantidade * preco_unitario) as total_valor
               FROM vendas v
               JOIN usuarios u ON v.id_usuario = u.id
               WHERE DATE(v.data_venda) = ?
               GROUP BY v.id_usuario";
$stmt_resumo = $conn->prepare($sql_resumo);
$stmt_resumo->bind_param("s", $data);
$stmt_resumo->execute();
$resumo = $stmt_resumo->get_result();

// Gráficos
$grafico_dias = $conn->query("SELECT DATE_FORMAT(data_venda, '%d/%m') as dia, SUM(quantidade * preco_unitario) as total 
                             FROM vendas 
                             WHERE MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE())
                             GROUP BY dia ORDER BY data_venda");
$labels_dias = []; $valores_dias = [];
while ($g = $grafico_dias->fetch_assoc()) { $labels_dias[] = $g['dia']; $valores_dias[] = (float)$g['total']; }

$grafico_medicamentos = $conn->query("SELECT m.nome AS medicamento, SUM(v.quantidade * v.preco_unitario) AS total
                                     FROM vendas v
                                     JOIN medicamentos m ON v.id_medicamento = m.id
                                     WHERE MONTH(v.data_venda) = MONTH(CURDATE()) AND YEAR(v.data_venda) = YEAR(CURDATE())
                                     GROUP BY v.id_medicamento
                                     ORDER BY total DESC");
$labels_medicamentos = []; $valores_medicamentos = [];
while ($g = $grafico_medicamentos->fetch_assoc()) { $labels_medicamentos[] = $g['medicamento']; $valores_medicamentos[] = (float)$g['total']; }

$mais_vendido = $conn->query("SELECT m.nome, SUM(v.quantidade) AS total_qtd
                              FROM vendas v
                              JOIN medicamentos m ON v.id_medicamento = m.id
                              WHERE MONTH(v.data_venda) = MONTH(CURDATE()) AND YEAR(v.data_venda) = YEAR(CURDATE())
                              GROUP BY v.id_medicamento
                              ORDER BY total_qtd DESC
                              LIMIT 1")->fetch_assoc();

// Histórico de abastecimento
$sql_abastecimento = "SELECT f.nome AS fornecedor, m.nome AS medicamento, a.quantidade, a.data_registro
                      FROM historico_fornecedores a
                      JOIN fornecedores f ON a.fornecedor_id = f.id
                      JOIN medicamentos m ON a.medicamento_id = m.id
                      ORDER BY a.data_registro DESC LIMIT 50";
$historico_abastecimento = $conn->query($sql_abastecimento);

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico de Vendas e Abastecimento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { margin:0; font-family:Arial,sans-serif; background:#f4f6f9; }
header { background: linear-gradient(90deg,#0d6efd,#0b5ed7); color:white; text-align:center; padding:20px; font-size:1.8rem; font-weight:bold; }
nav { background:#0d6efd; color:white; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; padding:10px 15px; position:relative; }
nav .logo { font-weight:bold; font-size:20px; }
nav ul { display:flex; flex-direction:row; gap:15px; list-style:none; margin:0; padding:0; }
nav ul li a { color:white; text-decoration:none; font-weight:500; }
nav ul li a:hover { color:#e0e0e0; }
.menu-toggle { display:none; cursor:pointer; width:30px; height:22px; position:relative; z-index:1100; }
.menu-toggle span { background:white; position:absolute; width:100%; height:3px; left:0; transition:0.3s; }
.menu-toggle span:nth-child(1){ top:0; }
.menu-toggle span:nth-child(2){ top:9px; }
.menu-toggle span:nth-child(3){ top:18px; }
.menu-toggle.active span:nth-child(1){ transform:rotate(45deg); top:9px; }
.menu-toggle.active span:nth-child(2){ opacity:0; }
.menu-toggle.active span:nth-child(3){ transform:rotate(-45deg); top:9px; }
/* Mobile menu */
@media(max-width:991px){
  nav ul { display:none; flex-direction:column; position:fixed; top:60px; left:0; width:250px; height:100%; background:#0d6efd; padding:20px; border-right:2px solid #0b5ed7; z-index:1000; gap:10px; }
  nav ul.active{ display:flex; }
  .menu-toggle{ display:block; }
}
.container{ background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin:25px auto; max-width:1200px; }
.table-responsive { overflow-x:auto; margin-bottom:30px; }
.chart-container { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; margin-top:25px; }
.chart-box { flex:1; min-width:280px; max-width:500px; }
.grid-abastecimento { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:20px; margin-top:15px; }
.card-abastecimento { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition:0.3s; display:flex; flex-direction:column; gap:8px; }
.card-abastecimento:hover { transform:translateY(-4px); box-shadow:0 6px 18px rgba(0,0,0,0.15); }
.card-abastecimento p { margin:0; font-size:0.95rem; }
.card-abastecimento strong { color:#0d6efd; }
.btn-custom { background:#198754; color:white; border-radius:8px; }
.btn-custom:hover{ background:#146c43; }
footer { background:#0d6efd; color:white; text-align:center; padding:15px; margin-top:40px; }
@media(max-width:768px){
  .chart-container { flex-direction:column; }
  form.d-flex { flex-direction:column; align-items:flex-start; gap:10px; }
  form.d-flex input, form.d-flex select, form.d-flex button, .btn-outline-success { width:100%; }
}
</style>
</head>
<body>
<header>📊 Histórico de Vendas e Abastecimento</header>
<nav>
  <span class="logo">💊 Sistema Farmacêutico</span>
  <div class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></div>
  <ul>
    <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
    <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
    <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Medicamentos</a></li>
    <li><a href="venda.php">🛒 Venda</a></li>
    <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
    <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
    <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
    <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
  </ul>
</nav>

<div class="container">
  <form method="get" class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <label class="form-label mb-0">Data:</label>
    <input type="date" name="data" value="<?= $data ?>" class="form-control">
    <label class="form-label mb-0">Tipo:</label>
    <select name="tipo" class="form-control">
      <option value="historico" <?= $tipo_relatorio=='historico'?'selected':'' ?>>Histórico de Vendas</option>
      <option value="resumo_usuario" <?= $tipo_relatorio=='resumo_usuario'?'selected':'' ?>>Resumo por Usuário</option>
      <option value="total_dia" <?= $tipo_relatorio=='total_dia'?'selected':'' ?>>Total do Dia</option>
      <option value="medicamento" <?= $tipo_relatorio=='medicamento'?'selected':'' ?>>Vendas por Medicamento</option>
    </select>
    <button type="submit" class="btn btn-custom">Filtrar</button>
    <a id="gerarPDF" href="relatorio_vendas.php?tipo=<?= $tipo_relatorio ?>&data=<?= $data ?>" class="btn btn-outline-success">📑 Gerar PDF</a>
  </form>

  <h2>Histórico de Vendas - <?= date("d/m/Y", strtotime($data)) ?></h2>
  <p><strong>Usuário logado:</strong> <?= htmlspecialchars($usuario_logado) ?> | <strong>Total do Dia:</strong> FCFA <?= number_format($total_dia,2,',','.') ?></p>
  <p><strong>Mais vendido no mês:</strong> <?= $mais_vendido ? htmlspecialchars($mais_vendido['nome'])." ({$mais_vendido['total_qtd']} unidades)" : "Nenhum registro" ?></p>

  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead class="table-primary"><tr><th>ID</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th><th>Data</th></tr></thead>
      <tbody>
        <?php if($result->num_rows): while($row=$result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= $row['medicamento'] ?></td>
          <td><?= $row['quantidade'] ?></td>
          <td>FCFA <?= number_format($row['preco_unitario'],2,',','.') ?></td>
          <td>FCFA <?= number_format($row['total'],2,',','.') ?></td>
          <td><?= $row['usuario'] ?></td>
          <td><?= date("d/m/Y H:i", strtotime($row['data_venda'])) ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7">Nenhuma venda registrada para essa data.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h4 class="mt-4">Resumo por Usuário</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-secondary"><tr><th>Usuário</th><th>Vendas</th><th>Valor Total</th></tr></thead>
      <tbody>
      <?php while($r=$resumo->fetch_assoc()): ?>
        <tr>
          <td><?= $r['nome'] ?></td>
          <td><?= $r['total_vendas'] ?></td>
          <td>FCFA <?= number_format($r['total_valor'],2,',','.') ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <h4 class="mt-4">Histórico de Abastecimento</h4>
  <div class="grid-abastecimento">
    <?php if($historico_abastecimento->num_rows):
        while($ab=$historico_abastecimento->fetch_assoc()): ?>
        <div class="card-abastecimento">
          <p><strong>Fornecedor:</strong> <?= $ab['fornecedor'] ?></p>
          <p><strong>Medicamento:</strong> <?= $ab['medicamento'] ?></p>
          <p><strong>Quantidade:</strong> <?= $ab['quantidade'] ?></p>
          <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($ab['data_registro'])) ?></p>
        </div>
    <?php endwhile; else: ?>
      <p>Nenhum abastecimento registrado.</p>
    <?php endif; ?>
  </div>

  <h4 class="mt-4">📊 Gráficos</h4>
  <div class="chart-container">
    <div class="chart-box"><canvas id="graficoDias"></canvas></div>
    <div class="chart-box"><canvas id="graficoMedicamentos"></canvas></div>
  </div>
</div>

<footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const menuToggle = document.getElementById('menuToggle');
const navUl = document.querySelector('nav ul');
menuToggle.addEventListener('click',()=>{navUl.classList.toggle('active');menuToggle.classList.toggle('active');});

const dataInput=document.querySelector('input[name="data"]');
const tipoSelect=document.querySelector('select[name="tipo"]');
const gerarPDF=document.getElementById('gerarPDF');
function atualizarLinkPDF(){gerarPDF.href=`relatorio_vendas.php?tipo=${tipoSelect.value}&data=${dataInput.value}`;}
dataInput.addEventListener('change',atualizarLinkPDF);
tipoSelect.addEventListener('change',atualizarLinkPDF);

const labelsDias=<?= json_encode($labels_dias) ?>;
const valoresDias=<?= json_encode($valores_dias) ?>;
new Chart(document.getElementById('graficoDias'),{type:'pie',data:{labels:labelsDias,datasets:[{label:'Vendas por Dia',data:valoresDias,backgroundColor:labelsDias.map(()=> '#'+Math.floor(Math.random()*16777215).toString(16))}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});

const labelsMedicamentos=<?= json_encode($labels_medicamentos) ?>;
const valoresMedicamentos=<?= json_encode($valores_medicamentos) ?>;
new Chart(document.getElementById('graficoMedicamentos'),{type:'pie',data:{labels:labelsMedicamentos,datasets:[{label:'Vendas por Medicamento',data:valoresMedicamentos,backgroundColor:labelsMedicamentos.map(()=> '#'+Math.floor(Math.random()*16777215).toString(16))}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
</script>
</body>
</html>
