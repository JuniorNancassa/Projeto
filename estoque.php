<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

function conectar_banco() {
    $conn = mysqli_connect("localhost", "root", "", "farmacia");
    if (!$conn) {
        die("Conexão falhou: " . mysqli_connect_error());
    }
    return $conn;
}

$conn = conectar_banco();

// Lista medicamentos
$sql = "SELECT id, nome, quantidade, validade FROM medicamentos";
$result = mysqli_query($conn, $sql);

$medicamentos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $medicamentos[] = $row;
    }
}

// Lista fornecedores
$sql_fornecedores = "SELECT id, nome FROM fornecedores";
$fornecedores = mysqli_query($conn, $sql_fornecedores);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque de Medicamentos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background-color: #f2f2f2; margin: 0; }
    header { background-color: #0d6efd; color: white; text-align: center; padding: 20px; font-size: 1.8rem; font-weight: bold; }
    nav { background-color: #0d6efd; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; position: relative; }
    nav .logo { font-weight: bold; font-size: 18px; }
    nav ul { list-style: none; display: flex; gap: 20px; }
    nav ul li a { color: white; text-decoration: none; font-weight: 500; }
    nav ul li a:hover { color:rgb(13, 14, 13); }
    .menu-toggle { display: none; flex-direction: column; cursor: pointer; }
    .menu-toggle div { width: 25px; height: 3px; background: white; margin: 4px; border-radius: 2px; }
    @media(max-width: 768px) {
      nav ul { display: none; flex-direction: column; background: #0d6efd; position: absolute; top: 55px; right: 0; width: 200px; border-radius: 8px; padding: 10px; }
      nav ul.show { display: flex; }
      .menu-toggle { display: flex; }
    }
    main { max-width: 1000px; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    form.abastecimento { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
    form.abastecimento select, form.abastecimento input { padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    form.abastecimento button { background-color:#0d6efd; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; }
    form.abastecimento button:hover { background-color:#0b5ed7; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; text-align: center; padding: 10px; font-size: 14px; }
    th { background-color: #0d6efd; color: white; }
    .status-icon { width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 6px; }
    .red { background-color: red; } .yellow { background-color: gold; } .green { background-color: green; }
    .search-container { text-align: center; margin-bottom: 20px; }
    .search-container input { padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px; }
    footer { margin-top: 60px; background-color: #0d6efd; color: white; text-align: center; padding: 20px; font-size: 14px; }
  </style>
</head>
<body>
  <header>📦 Estoque de Medicamentos</header>
  <nav>
    <div class="logo">Farmácia</div>
    <div class="menu-toggle" onclick="toggleMenu()"><div></div><div></div><div></div></div>
    <ul>
     <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
     <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
     <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Cadastrar Medicamentos</a></li>
     <li><a href="venda.php">🛒 Venda</a></li>
     <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
     <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
     <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
     <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
    </ul>
  </nav>

  <main>
    <!-- Formulário de Abastecimento -->
    <form class="abastecimento" action="abastecer_estoque.php" method="post">
      <select name="medicamento_id" required>
        <option value="">Selecione o medicamento</option>
        <?php foreach ($medicamentos as $row): ?>
          <option value="<?= $row['id'] ?>"><?= $row['nome'] ?></option>
        <?php endforeach; ?>
      </select>

      <select name="fornecedor_id" required>
        <option value="">Selecione o fornecedor</option>
        <?php
          $conn = conectar_banco();
          $res_fornecedores = mysqli_query($conn, "SELECT id, nome FROM fornecedores ORDER BY nome ASC");
          while ($f = mysqli_fetch_assoc($res_fornecedores)) {
              echo "<option value='{$f['id']}'>{$f['nome']}</option>";
          }
          mysqli_close($conn);
        ?>
      </select>

      <input type="number" name="quantidade" min="1" placeholder="Quantidade" required>
      <input type="date" name="data_abastecimento" required value="<?= date('Y-m-d') ?>">

      <button type="submit">➕ Abastecer</button>
    </form>

    <!-- Campo de pesquisa -->
    <div class="search-container">
      <input type="text" id="searchInput" onkeyup="filtrarMedicamentos()" placeholder="Buscar medicamento...">
    </div>

    <!-- Tabela Estoque -->
    <table id="medicamentosTable">
      <thead>
        <tr><th>ID</th><th>Nome</th><th>Quantidade</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (!empty($medicamentos)): ?>
          <?php foreach ($medicamentos as $row): 
              $qtd = (int)$row['quantidade'];
              $hoje = strtotime(date('Y-m-d'));
              $validade = strtotime($row['validade']);
              $dias_para_vencimento = ($validade - $hoje) / (60*60*24);

              // Status por quantidade
              if ($qtd <= 50) $status_qtd = "<span class='status-icon red'></span><span style='color:red;'>Baixo</span>";
              elseif ($qtd <= 100) $status_qtd = "<span class='status-icon yellow'></span><span style='color:gold;'>Médio</span>";
              else $status_qtd = "<span class='status-icon green'></span><span style='color:green;'>Cheio</span>";

              // Status de validade
              if ($dias_para_vencimento <= 30) $status_val = "<span class='status-icon red'></span><span style='color:red;'>Vence em breve</span>";
              else $status_val = "<span class='status-icon green'></span><span style='color:green;'>Ok</span>";

              $status = $status_qtd . " / " . $status_val;
          ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= $row['nome'] ?></td>
              <td><?= $qtd ?></td>
              <td><?= $status ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">Nenhum medicamento encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>

  <script>
    function filtrarMedicamentos() {
      const input = document.getElementById("searchInput").value.toUpperCase();
      const table = document.getElementById("medicamentosTable");
      const linhas = table.getElementsByTagName("tr");
      for (let i = 1; i < linhas.length; i++) {
        const colId = linhas[i].getElementsByTagName("td")[0];
        const colNome = linhas[i].getElementsByTagName("td")[1];
        const colQtd = linhas[i].getElementsByTagName("td")[2];
        if (colId && colNome && colQtd) {
          const id = colId.textContent || colId.innerText;
          const nome = colNome.textContent || colNome.innerText;
          const qtd = colQtd.textContent || colQtd.innerText;
          const corresponde = id.toUpperCase().includes(input) ||
                              nome.toUpperCase().includes(input) ||
                              qtd.toUpperCase().includes(input);
          linhas[i].style.display = corresponde ? "" : "none";
        }
      }
    }
    function toggleMenu() {
      document.querySelector("nav ul").classList.toggle("show");
    }
  </script>

  <footer>&copy; 2025 Sistema de Gestão Farmacêutica </footer>
</body>
</html>
