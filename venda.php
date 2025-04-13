<?php
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

// Processa a venda
if (isset($_POST['vender'])) {
    $id_medicamento = $_POST['id_medicamento'];
    $quantidade_venda = $_POST['quantidade_venda'];
    $preco_unitario = $_POST['preco_unitario'] ?? 0;

    $conn = conectar_banco();
    $sql = "SELECT quantidade FROM medicamentos WHERE id = $id_medicamento";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        echo "<script>alert('Medicamento não encontrado');</script>";
    } else {
        $estoque_atual = $row['quantidade'];
        if ($estoque_atual <= 0) {
            echo "<script>alert('Medicamento sem estoque');</script>";
        } else if ($quantidade_venda > $estoque_atual) {
            echo "<script>alert('Quantidade desejada excede o estoque disponível');</script>";
        } else {
            $novo_estoque = $estoque_atual - $quantidade_venda;
            $sql_update = "UPDATE medicamentos SET quantidade = $novo_estoque WHERE id = $id_medicamento";
            mysqli_query($conn, $sql_update);
            echo "<script>alert('Venda realizada com sucesso! Estoque atualizado.');</script>";
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Venda de Medicamentos</title>
  <style>
    /* Reset básico */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f2f2f2;
      color: #333;
    }
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
    .container {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      justify-content: space-between;
    }
    .form-section, .table-section {
      flex: 1 1 500px;
      background: #fafafa;
      padding: 15px;
      border-radius: 8px;
    }
    .form-section form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    label {
      font-weight: bold;
      margin-bottom: 5px;
    }
    input[type="text"],
    input[type="number"],
    select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 16px;
      width: 100%;
    }
    button {
      padding: 12px;
      border: none;
      border-radius: 4px;
      background-color: #4CAF50;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #45a049;
    }
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
    th {
      background-color: #4CAF50;
      color: #fff;
    }
    /* Media queries para responsividade */
    @media (max-width: 1024px) {
      .container {
        flex-direction: column;
      }
      nav ul {
        justify-content: center;
      }
    }
    @media (max-width: 600px) {
      header {
        font-size: 20px;
        padding: 15px;
      }
      nav ul li a {
        padding: 8px 10px;
        font-size: 14px;
      }
      input[type="text"],
      input[type="number"],
      select {
        font-size: 14px;
      }
      button {
        font-size: 14px;
        padding: 10px;
      }
      th, td {
        padding: 8px;
      }
      .search-container input[type="text"] {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <header>Venda de Medicamentos</header>
  
  <!-- Menu de navegação -->
  <nav>
    <ul>
      <li><a href="dashboard.php">Início</a></li>
      <li><a href="cadastro_usuarios.php">Cadastrar Usuário</a></li>
      <li><a href="cadastro_medicamento.php">Cadastrar Medicamento</a></li>
      <li><a href="venda.php">Venda</a></li>
      <li><a href="historico.php">Histórico</a></li>
      <li><a href="estoque.php">Estoque</a></li>
      <li><a href="logout.php">Sair</a></li>
    </ul>
  </nav>
  
  <main>
    <div class="container">
      <!-- Seção do Formulário -->
      <div class="form-section">
        <form action="venda.php" method="post">
          <label for="id_medicamento">Selecione o Medicamento:</label>
          <select name="id_medicamento" id="id_medicamento" required onchange="updatePreco()">
            <option value="">Escolha um medicamento</option>
            <?php
              $conn = conectar_banco();
              $sql = "SELECT id, nome, preco, quantidade FROM medicamentos";
              $resultForm = mysqli_query($conn, $sql);
              if (mysqli_num_rows($resultForm) > 0) {
                  while($row = mysqli_fetch_assoc($resultForm)) {
                      echo "<option value='" . $row["id"] . "' data-price='" . $row["preco"] . "' data-stock='" . $row["quantidade"] . "'>" 
                           . $row["nome"] . " - FCFA " . number_format($row["preco"], 2, ',', '.') . "</option>";
                  }
              } else {
                  echo "<option value=''>Nenhum medicamento cadastrado</option>";
              }
              mysqli_close($conn);
            ?>
          </select>

          <label for="preco_unitario">Preço Unitário:</label>
          <input type="number" step="0.01" name="preco_unitario" id="preco_unitario" readonly required>
          
          <label for="quantidade_venda">Quantidade para Venda:</label>
          <input type="number" name="quantidade_venda" id="quantidade_venda" min="1" required>
          
          <button type="submit" name="vender">Realizar Venda</button>
        </form>
      </div>
      
      <!-- Seção da Tabela com Barra de Pesquisa -->
      <div class="table-section">
        <div class="search-container">
          <input type="text" id="searchInput" placeholder="Buscar medicamento..." onkeyup="filtrarMedicamentos()">
        </div>
        <table id="medicamentosTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Preço</th>
              <th>Estoque</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $conn = conectar_banco();
              $sql = "SELECT id, nome, preco, quantidade FROM medicamentos";
              $resultTable = mysqli_query($conn, $sql);
              if (mysqli_num_rows($resultTable) > 0) {
                  while($row = mysqli_fetch_assoc($resultTable)) {
                      echo "<tr>";
                      echo "<td>" . $row["id"] . "</td>";
                      echo "<td>" . $row["nome"] . "</td>";
                      echo "<td>FCFA " . number_format($row["preco"], 2, ',', '.') . "</td>";
                      echo "<td>" . $row["quantidade"] . "</td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='4'>Nenhum medicamento encontrado.</td></tr>";
              }
              mysqli_close($conn);
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
  
  <script>
    // Atualiza o campo de preço unitário com base na seleção do medicamento
    function updatePreco() {
      var select = document.getElementById("id_medicamento");
      var precoInput = document.getElementById("preco_unitario");
      var selectedOption = select.options[select.selectedIndex];
      precoInput.value = selectedOption.getAttribute("data-price") || "";
    }
    
    // Filtra os medicamentos na tabela de acordo com a pesquisa
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
    
    // Atualiza o preço ao carregar a página
    document.addEventListener("DOMContentLoaded", updatePreco);
  </script>
</body>
</html>
