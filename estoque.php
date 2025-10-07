<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

/**
 * Conecta ao banco e retorna a conexão (mysqli)
 */
function conectar_banco() {
    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) die("Conexão falhou: " . mysqli_connect_error());
    return $conn;
}

$conn = conectar_banco();

// Carrega medicamentos
$sql = "SELECT id, nome, quantidade, validade FROM medicamentos ORDER BY nome ASC";
$res_medicamentos = mysqli_query($conn, $sql);
$medicamentos = [];
if ($res_medicamentos && mysqli_num_rows($res_medicamentos) > 0) {
    while ($row = mysqli_fetch_assoc($res_medicamentos)) {
        $medicamentos[] = $row;
    }
}

// Carrega fornecedores (para o select do formulário)
$sql_fornecedores = "SELECT id, nome FROM fornecedores ORDER BY nome ASC";
$res_fornecedores = mysqli_query($conn, $sql_fornecedores);

// NÃO FECHAR A CONEXÃO AINDA — será usada se o formulário for enviado por AJAX no futuro
// mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Estoque de Medicamentos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Ícones e Bootstrap (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ---------------- Global ---------------- */
    :root{
      --primary:#0d6efd;
      --muted:#6c757d;
      --bg:#f8f9fa;
      --card-bg:#ffffff;
      --success:#198754;
      --warning:#ffc107;
      --danger:#dc3545;
      --max-bar:200; /* valor usado apenas para calcular porcentagem visual */
    }
    html,body{height:100%;}
    body{
      font-family: "Segoe UI", Roboto, Arial, sans-serif;
      background:var(--bg);
      margin:0;
      padding-top:70px; /* espaço para navbar fixa */
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* ---------------- Navbar ---------------- */
    .navbar-brand{font-weight:600;}
    .btn-menu { width:44px; height:44px; display:flex; align-items:center; justify-content:center; }

    /* ---------------- Sidebar (mobile slide) ---------------- */
    .sidebar {
      position: fixed;
      top: 0;
      left: -260px;
      width: 260px;
      height: 100%;
      background: var(--primary);
      color: #fff;
      padding-top: 70px;
      transition: left .28s ease;
      z-index: 1100;
    }
    .sidebar.active { left: 0; }
    .sidebar a { display:block; padding:12px 18px; color: #fff; text-decoration:none; font-weight:500; }
    .sidebar a:hover { background: rgba(255,255,255,0.08); }

    /* overlay quando sidebar aberta */
    .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1050; }
    .overlay.active { display:block; }

    /* ---------------- Main card ---------------- */
    main.container {
      max-width:1100px;
      margin: 18px auto 90px;
    }
    .panel {
      background: var(--card-bg);
      padding:18px;
      border-radius:12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }

    /* ---------------- Form abastecimento ---------------- */
    form.abastecimento { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    form.abastecimento select,
    form.abastecimento input { padding:8px 10px; border-radius:8px; border:1px solid #ddd; min-width:0; flex:1 1 160px; }
    form.abastecimento button { background:var(--primary); color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
    form.abastecimento button:hover { filter:brightness(.95); }

    /* ---------------- Search ---------------- */
    .search-container { text-align:center; margin-bottom:14px; }
    .search-container input { width:100%; max-width:380px; padding:10px 12px; border-radius:8px; border:1px solid #ddd; }

    /* ---------------- Tabela ---------------- */
    .table-responsive { border-radius:10px; overflow:auto; }
    table { width:100%; border-collapse:collapse; min-width:720px; }
    th, td { padding:12px 10px; text-align:center; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    thead th { background:var(--primary); color:#fff; position:sticky; top:0; z-index:2; }
    tr:hover td { background: rgba(13,110,253,0.03); }

    /* ---------------- Status visual ----------------
       - Barra de progresso simples indicando "nível" relativo
       - Cores por faixa: vermelho (baixo), amarelo (médio), verde (cheio)
    */
    .stock-cell { display:flex; flex-direction:column; align-items:center; gap:6px; }
    .stock-bar {
      width:100%;
      background:#e9ecef;
      border-radius:8px;
      overflow:hidden;
      height:12px;
    }
    .stock-bar > i {
      display:block;
      height:100%;
      width:0%;
      transition: width .6s ease;
    }
    .stock-label { font-size:13px; color:var(--muted); }

    /* badges compactos para validade etc */
    .badge-valid { font-size:12px; padding:6px 8px; border-radius:8px; }

    /* ---------------- Footer ---------------- */
    footer {
      position:fixed;
      bottom:0;
      left:0;
      width:100%;
      background:var(--primary);
      color:#fff;
      text-align:center;
      padding:12px 10px;
      z-index:100;
    }

    /* ---------------- Responsividade ---------------- */
    @media (max-width: 992px) {
      main.container { padding: 0 14px; }
      form.abastecimento { gap:8px; }
      table { min-width:640px; }
    }

    @media (max-width: 768px) {
      /* Sidebar largura reduzida */
      .sidebar { width:220px; left:-220px; }
      .sidebar.active { left:0; }
      /* Form empilha verticalmente em mobile */
      form.abastecimento { flex-direction:column; align-items:stretch; }
      form.abastecimento select, form.abastecimento input { flex: 1 1 auto; width:100%; }
      form.abastecimento button { width:100%; }
      /* Tabela: permite rolagem horizontal com boa aparência */
      table { min-width:600px; }
      footer { position:relative; }
    }

    @media (max-width: 420px) {
      table { min-width:520px; font-size:13px; }
      th, td { padding:10px 8px; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR (mobile) -->
  <div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a>
    <a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a>
    <a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Cadastrar Medicamentos</a>
    <a href="venda.php"><i class="bi bi-cart4"></i> Venda</a>
    <a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a>
    <a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a>
    <a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a>
    <a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
  </div>

  <!-- overlay -->
  <div class="overlay" id="overlay"></div>

  <!-- NAVBAR -->
  <nav class="navbar navbar-dark bg-primary fixed-top d-flex align-items-center">
    <div class="container-fluid">
      <button class="btn btn-menu btn-outline-light me-2" id="menuToggle" aria-label="Abrir menu">☰</button>
      <a class="navbar-brand text-white" href="#">Estoque de Medicamentos</a>
      <!-- utilidade: espaço à direita para futuros botões -->
      <div></div>
    </div>
  </nav>

  <!-- CONTEÚDO PRINCIPAL -->
  <main class="container">
    <section class="panel">

      <!-- Cabeçalho -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">📦 Controle de Estoque</h4>
        <div class="text-muted small">Painel administrativo</div>
      </div>

      <!-- Formulário de abastecimento (mantido no mesmo arquivo) -->
      <form class="abastecimento" action="abastecer_estoque.php" method="post" aria-label="Formulário de abastecimento">
        <select name="medicamento_id" required aria-label="Selecionar medicamento">
          <option value="">Selecione o medicamento</option>
          <?php foreach ($medicamentos as $m): ?>
            <option value="<?= htmlspecialchars($m['id']) ?>"><?= htmlspecialchars($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="fornecedor_id" required aria-label="Selecionar fornecedor">
          <option value="">Selecione o fornecedor</option>
          <?php if ($res_fornecedores && mysqli_num_rows($res_fornecedores) > 0):
                mysqli_data_seek($res_fornecedores, 0); // garantir cursor no início ?>
              <?php while ($f = mysqli_fetch_assoc($res_fornecedores)): ?>
                <option value="<?= htmlspecialchars($f['id']) ?>"><?= htmlspecialchars($f['nome']) ?></option>
              <?php endwhile; ?>
          <?php endif; ?>
        </select>

        <input type="number" name="quantidade" min="1" placeholder="Quantidade" required aria-label="Quantidade">
        <input type="date" name="data_abastecimento" required value="<?= date('Y-m-d') ?>" aria-label="Data de abastecimento">
        <button type="submit" aria-label="Abastecer">➕ Abastecer</button>
      </form>

      <!-- Campo de pesquisa -->
      <div class="search-container mb-3">
        <input type="text" id="searchInput" onkeyup="filtrarMedicamentos()" placeholder="Buscar medicamento (nome / id / qtd)..." aria-label="Pesquisar medicamentos">
      </div>

      <!-- Tabela responsiva com rolagem -->
      <div class="table-responsive">
        <table id="medicamentosTable" class="table table-borderless align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Quantidade</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($medicamentos)): ?>
              <?php foreach ($medicamentos as $row):
                $qtd = (int)$row['quantidade'];
                $hoje = strtotime(date('Y-m-d'));
                $validade_ts = strtotime($row['validade']);
                $dias_para_vencimento = is_numeric($validade_ts) ? floor(($validade_ts - $hoje) / (60*60*24)) : null;

                // Determina faixa de estoque (e cor)
                if ($qtd <= 50) { $faixa = 'baixo'; $color = 'var(--danger)'; }
                elseif ($qtd <= 100) { $faixa = 'medio'; $color = 'var(--warning)'; }
                else { $faixa = 'cheio'; $color = 'var(--success)'; }

                // Calcula % para barra visual (cap em 100% usando --max-bar)
                $pct = ($qtd / intval(getenv('MAX_BAR') ?: 200)) * 100;
                if ($pct > 100) $pct = 100;
                $pct = round($pct, 1);

                // Validade: se faltando dias ou já vencido
                if ($dias_para_vencimento === null) {
                  $valid_label = "<span class='badge badge-valid bg-secondary text-white'>Sem data</span>";
                } elseif ($dias_para_vencimento < 0) {
                  $valid_label = "<span class='badge badge-valid bg-danger text-white'>Vencido</span>";
                } elseif ($dias_para_vencimento <= 30) {
                  $valid_label = "<span class='badge badge-valid bg-warning text-dark'>Vence em {$dias_para_vencimento}d</span>";
                } else {
                  $valid_label = "<span class='badge badge-valid bg-success text-white'>Ok ({$dias_para_vencimento}d)</span>";
                }

                // Status textual
                $status_text = ($faixa === 'baixo') ? 'Baixo' : (($faixa === 'medio') ? 'Médio' : 'Cheio');
              ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['nome']) ?></td>
                  <td><?= $qtd ?></td>
                  <td>
                    <div class="stock-cell" style="min-width:160px;">
                      <!-- Barra visual -->
                      <div class="stock-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" title="<?= $pct ?>%">
                        <i style="width:<?= $pct ?>%; background:<?= $color ?>;"></i>
                      </div>
                      <!-- Rótulo pequeno com status e validade -->
                      <div style="display:flex; gap:8px; align-items:center; justify-content:center;">
                        <small class="stock-label" style="font-weight:600; color:<?= $color ?>;"><?= $status_text ?> (<?= $pct ?>%)</small>
                        <span><?= $valid_label ?></span>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4">Nenhum medicamento encontrado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </section>
  </main>

  <!-- FOOTER -->
  <footer>
    &copy; <?= date("Y") ?> Sistema de Gestão Farmacêutica
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ===== Menu hambúrguer & overlay =====
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuToggle = document.getElementById('menuToggle');

    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
    });

    // ===== Filtro local (busca) =====
    function filtrarMedicamentos() {
      const filtro = document.getElementById('searchInput').value.trim().toUpperCase();
      const linhas = document.querySelectorAll('#medicamentosTable tbody tr');
      linhas.forEach(linha => {
        const texto = linha.textContent.toUpperCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
      });
    }

    // ===== Acessibilidade: fechar sidebar com ESC =====
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }
    });

    // ===== Ajuste das barras (caso queira animar após carregamento) =====
    window.addEventListener('load', () => {
      // anima a largura das barras (já definido inline, mas isto força repaint suave)
      document.querySelectorAll('.stock-bar > i').forEach(el => {
        const w = el.style.width;
        el.style.width = '0%';
        setTimeout(()=> el.style.width = w, 50);
      });
    });
  </script>
</body>
</html>
