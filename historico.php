<?php
session_start();
$conn = new mysqli("localhost", "root", "", "farmacia");
if ($conn->connect_error) die("Erro na conexão: " . $conn->connect_error);

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$tipo_relatorio = $_GET['tipo'] ?? 'historico';
$usuario_logado = $_SESSION['nome_usuario'] ?? 'Desconhecido';

// Consulta vendas do dia
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

// Total geral do dia
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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico de Vendas</title>
<!-- Importando icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background-color: #f4f6f9; margin:0; font-family: "Segoe UI", sans-serif; }
header { background: linear-gradient(90deg, #0d6efd, #0b5ed7); color: white; text-align: center; padding: 20px; font-size: 1.8rem; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
nav { background-color: #0d6efd; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; position: relative; }
nav .logo { font-weight: bold; font-size: 20px; }

/* Botão hamburger sempre visível */
.menu-toggle {
    display: block;
    cursor: pointer;
    width: 30px;
    height: 22px;
    position: relative;
    z-index: 1100;
}
.menu-toggle span {
    background: white;
    position: absolute;
    width: 100%;
    height: 3px;
    left: 0;
    transition: 0.3s;
}
.menu-toggle span:nth-child(1){ top:0; }
.menu-toggle span:nth-child(2){ top:9px; }
.menu-toggle span:nth-child(3){ top:18px; }
.menu-toggle.active span:nth-child(1){ transform:rotate(45deg); top:9px; }
.menu-toggle.active span:nth-child(2){ opacity:0; }
.menu-toggle.active span:nth-child(3){ transform:rotate(-45deg); top:9px; }

/* Menu lateral fixo */
nav ul {
    display: none;
    flex-direction: column;
    position: fixed;
    top: 60px;
    left: 0;
    width: 250px;
    height: 100%;
    background: #0d6efd;
    padding: 20px;
    border-right: 2px solid #0b5ed7;
    z-index: 1000;
}
nav ul li { 
    margin: 10px 0; 
  list-style:none;
}
nav ul li a { color: white; text-decoration: none; font-weight: 500; transition: color 0.3s; }
nav ul li a:hover { 
    color: #020202ff;
}
nav ul.active { display: flex; }

.container { background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 25px auto; max-width: 1200px; }
.table th { background-color: #0d6efd; color: white; }
.chart-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
.chart-box { flex: 1; min-width: 280px; max-width: 500px; }
.btn-custom { background-color: #198754; color: white; border-radius: 8px; }
.btn-custom:hover { background-color: #146c43; }
footer { background:#0d6efd; color:white; text-align:center; padding:15px; margin-top:40px; }
</style>
</head>
<body>

<header>📊 Histórico de Medicamentos</header>
<nav>
    <span class="logo">💊 Sistema Farmacêutico</span>
    <div class="menu-toggle" id="menuToggle">
        <span></span><span></span><span></span>
    </div>
    <ul>
      <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
        <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
        <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Medicamentos</a></li>
        <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
        <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
        <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
    </ul>
</nav>

<div class="container">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0">Filtrar por Data:</label>
            <input type="date" name="data" value="<?= $data ?>" class="form-control">
            <label class="form-label mb-0">Tipo de Relatório:</label>
            <select name="tipo" class="form-control">
                <option value="historico" <?= $tipo_relatorio=='historico'?'selected':'' ?>>Histórico de Vendas</option>
                <option value="resumo_usuario" <?= $tipo_relatorio=='resumo_usuario'?'selected':'' ?>>Resumo por Usuário</option>
                <option value="total_dia" <?= $tipo_relatorio=='total_dia'?'selected':'' ?>>Total do Dia</option>
                <option value="medicamento" <?= $tipo_relatorio=='medicamento'?'selected':'' ?>>Vendas por Medicamento</option>
            </select>
            <button type="submit" class="btn btn-custom">Filtrar</button>
        </form>
        <a id="gerarPDF" href="relatorio_vendas.php?tipo=<?= $tipo_relatorio ?>&data=<?= $data ?>" class="btn btn-outline-success">📑 Gerar PDF</a>
    </div>

    <h2 class="mb-4">Histórico de Vendas - <?= date("d/m/Y", strtotime($data)) ?></h2>
    <p><strong>Usuário logado:</strong> <?= htmlspecialchars($usuario_logado) ?> | <strong>Total do Dia:</strong> FCFA <?= number_format($total_dia, 2, ',', '.') ?></p>
    <?php if ($mais_vendido): ?>
        <p><strong>Medicamento mais vendido no mês:</strong> <?= htmlspecialchars($mais_vendido['nome']) ?> (<?= $mais_vendido['total_qtd'] ?> unidades)</p>
    <?php else: ?>
        <p><strong>Medicamento mais vendido no mês:</strong> Nenhum registro.</p>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr><th>ID</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th>Usuário</th><th>Data</th></tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows): while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['medicamento'] ?></td>
                    <td><?= $row['quantidade'] ?></td>
                    <td>FCFA <?= number_format($row['preco_unitario'], 2, ',', '.') ?></td>
                    <td>FCFA <?= number_format($row['total'], 2, ',', '.') ?></td>
                    <td><?= $row['usuario'] ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($row['data_venda'])) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7">Nenhuma venda registrada para essa data.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h4 class="mt-5">Resumo por Usuário</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-secondary"><tr><th>Usuário</th><th>Vendas Realizadas</th><th>Valor Total</th></tr></thead>
            <tbody>
            <?php while ($r = $resumo->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['nome'] ?></td>
                    <td><?= $r['total_vendas'] ?></td>
                    <td>FCFA <?= number_format($r['total_valor'], 2, ',', '.') ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <h2 class="mt-5">Histórico de Abastecimento por Fornecedor</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead><tr><th>Fornecedor</th><th>Medicamento</th><th>Quantidade</th><th>Data</th></tr></thead>
            <tbody>
            <?php 
            if($historico && $historico->num_rows>0): ?>
                <?php while($row = $historico->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Fornecedor"><?= $row['fornecedor'] ?></td>
                        <td data-label="Medicamento"><?= $row['medicamento'] ?></td>
                        <td data-label="Quantidade"><?= $row['quantidade'] ?></td>
                        <td data-label="Data"><?= date('d/m/Y', strtotime($row['data_abastecimento'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">Nenhum abastecimento registrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

    <h4 class="mt-5">📊 Gráficos</h4>
    <div class="chart-container">
        <div class="chart-box"><canvas id="graficoDias"></canvas></div>
        <div class="chart-box"><canvas id="graficoMedicamentos"></canvas></div>
    </div>
</div>

<footer>
&copy; 2025 Sistema de Gestão Farmacêutica
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Menu Hamburger
const menuToggle = document.getElementById('menuToggle');
const navUl = document.querySelector('nav ul');
menuToggle.addEventListener('click', () => {
    navUl.classList.toggle('active');
    menuToggle.classList.toggle('active');
});

// Atualizar link PDF
const dataInput = document.querySelector('input[name="data"]');
const tipoSelect = document.querySelector('select[name="tipo"]');
const gerarPDF = document.getElementById('gerarPDF');
function atualizarLinkPDF() {
    gerarPDF.href = `relatorio_vendas.php?tipo=${tipoSelect.value}&data=${dataInput.value}`;
}
dataInput.addEventListener('change', atualizarLinkPDF);
tipoSelect.addEventListener('change', atualizarLinkPDF);

// Gráfico de vendas por dia
const labelsDias = <?= json_encode($labels_dias) ?>;
const valoresDias = <?= json_encode($valores_dias) ?>;
new Chart(document.getElementById('graficoDias'), {
    type: 'pie',
    data: { labels: labelsDias, datasets:[{ label:'Vendas por Dia', data:valoresDias, backgroundColor: labelsDias.map(()=>'#'+Math.floor(Math.random()*16777215).toString(16)) }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
});

// Gráfico de vendas por medicamento
const labelsMedicamentos = <?= json_encode($labels_medicamentos) ?>;
const valoresMedicamentos = <?= json_encode($valores_medicamentos) ?>;
new Chart(document.getElementById('graficoMedicamentos'), {
    type: 'pie',
    data: { labels: labelsMedicamentos, datasets:[{ label:'Vendas por Medicamento', data:valoresMedicamentos, backgroundColor: labelsMedicamentos.map(()=>'#'+Math.floor(Math.random()*16777215).toString(16)) }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
});
</script>
</body>
</html>
