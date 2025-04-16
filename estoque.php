<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}
// Conexão com o banco de dados
function conectar_banco() {
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "farmacia";

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Conexão falhou: " . mysqli_connect_error());
    }
    return $conn;
}

$conn = conectar_banco();
$sql = "SELECT id, nome, quantidade FROM medicamentos";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estoque de Medicamentos</title>
  <style>
    /* Reset básico */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f2f2f2; color: #333; }
    
    header {
      background-color: #2c3e50;
      padding: 20px;
      text-align: center;
      color: #fff;
      font-size: 24px;
      font-weight: bold;
    }
    
    /* Menu de navegação */
    nav {
      background-color: #34495e;
      padding: 10px 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    nav ul {
      list-style: none;
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }
    nav ul li a {
      color: #fff;
      text-decoration: none;
      font-size: 16px;
      font-weight: 500;
      padding: 8px 15px;
      transition: background-color 0.3s ease;
    }
    nav ul li a:hover {
      background-color: #4CAF50;
      border-radius: 4px;
    }
    
    main {
      max-width: 1200px;
      margin: 30px auto;
      padding: 20px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .action-buttons {
      text-align: center;
      margin-bottom: 20px;
    }
    .button {
      display: inline-block;
      padding: 10px 15px;
      margin: 5px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }
    .button:hover { background-color: #45a049; }
    
    .search-container {
      margin-bottom: 20px;
      text-align: center;
    }
    .search-container input[type="text"] {
      width: 90%;
      max-width: 400px;
      padding: 10px;
      border: 1px solid #bbb;
      border-radius: 4px;
      font-size: 16px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      text-align: center;
      padding: 12px;
      border: 1px solid #ddd;
    }
    th { background-color: #4CAF50; color: #fff; }
    
    /* Colorização dinâmica */
    .full { background-color: #c8e6c9; }    /* Verde: estoque cheio */
    .medium { background-color: #ffe082; }  /* Âmbar: estoque médio */
    .low { background-color: #ffccbc; }     /* Vermelho: estoque baixo */
    
    /* Footer */
    footer {
      background-color: #2c3e50;
      color: #fff;
      text-align: center;
      padding: 15px;
      margin-top: 30px;
    }
    
    /* Responsividade */
    @media (max-width: 1024px) {
      nav ul { justify-content: center; }
    }
    @media (max-width: 600px) {
      header { font-size: 20px; padding: 15px; }
      nav ul li a { padding: 8px 10px; font-size: 14px; }
      th, td { padding: 8px; font-size: 14px; }
      .button { padding: 8px 12px; font-size: 14px; }
      .search-container input[type="text"] { font-size: 14px; }
    }
  </style>
</head>
<body>
  <header>Estoque de Medicamentos</header>
  
  <!-- Menu de navegação -->
  <nav>
    <ul>
      <li><a href="dashboard.php">📊Início</a></li>
      <li><a href="cadastro_usuarios.php">Cadastrar Usuário</a></li>
      <li><a href="cadastro_medicamento.php">💊Cadastrar Medicamento</a></li>
      <li><a href="venda.php">Venda</a></li>
      <li><a href="historico.php">🧾Histórico</a></li>
      <li><a href="estoque.php">📦Estoque</a></li>
      <li><a href="logout.php">🚪Sair</a></li>
    </ul>
  </nav>
  
  <main>
    <div class="action-buttons">
      <a href="estoquepassado.php" class="button">Consultar Estoque do Mês Passado</a>
      <a href="relatorio_estoque.php" class="button">Gerar Relatório de Estoque</a>
    </div>
    
    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Buscar medicamento..." onkeyup="filtrarMedicamentos()">
    </div>
    
    <table id="medicamentosTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Quantidade</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Define os thresholds para colorização (ajuste conforme necessário)
                if ($row["quantidade"] >= 100) {
                    $class = "full";
                } elseif ($row["quantidade"] >= 50) {
                    $class = "medium";
                } else {
                    $class = "low";
                }
                echo "<tr class='$class'>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["nome"] . "</td>";
                echo "<td>" . $row["quantidade"] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Nenhum medicamento cadastrado</td></tr>";
        }
        mysqli_close($conn);
        ?>
      </tbody>
    </table>
  </main>
  
  <footer>
    <p>&copy; 2025 Farmácia. Todos os direitos reservados.</p>
  </footer>
  
  <script>
    // Função para filtrar os medicamentos na tabela conforme o nome
    function filtrarMedicamentos() {
      var input = document.getElementById("searchInput");
      var filter = input.value.toUpperCase();
      var table = document.getElementById("medicamentosTable");
      var tr = table.getElementsByTagName("tr");
      for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td")[1];
        if (td) {
          var txtValue = td.textContent || td.innerText;
          tr[i].style.display = (txtValue.toUpperCase().indexOf(filter) > -1) ? "" : "none";
        }
      }
    }
  </script>
</body>
</html>
