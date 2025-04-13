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

// Calcula a data de início e fim do mês passado
$start_date = date("Y-m-01", strtotime("first day of last month"));
$end_date   = date("Y-m-t", strtotime("last month"));

$conn = conectar_banco();
$sql = "SELECT h.id, m.nome, h.quantidade, h.data 
        FROM historico_estoque h 
        JOIN medicamentos m ON h.id_medicamento = m.id 
        WHERE h.data BETWEEN '$start_date' AND '$end_date'
        ORDER BY h.data DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consulta de Estoque do Mês Passado</title>
  <style>
    /* Reset e estilos básicos */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f2f2f2; color: #333; 
       background-image: "venda.jpg"; }
    
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
    
    h2 { text-align: center; margin-bottom: 20px; }
    
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
    
    /* Footer */
    footer {
      background-color: #2c3e50;
      color: #fff;
      text-align: center;
      padding: 15px;
      margin-top: 30px;
    }
    
    /* Responsividade */
    @media (max-width: 600px) {
      header { font-size: 20px; padding: 15px; }
      nav ul li a { padding: 8px 10px; font-size: 14px; }
      th, td { padding: 8px; font-size: 14px; }
    }
  </style>
</head>
<body>
  <header>Consulta de Estoque do Mês Passado</header>
  
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
    <h2>Estoque Registrado de <?php echo date("F Y", strtotime($start_date)); ?></h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Medicamento</th>
          <th>Quantidade</th>
          <th>Data do Registro</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
              echo "<tr>";
              echo "<td>" . $row["id"] . "</td>";
              echo "<td>" . $row["nome"] . "</td>";
              echo "<td>" . $row["quantidade"] . "</td>";
              // Exibe a data no formato dd/mm/aaaa
              echo "<td>" . date("d/m/Y", strtotime($row["data"])) . "</td>";
              echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='4'>Nenhum registro de estoque encontrado para o mês passado.</td></tr>";
        }
        mysqli_close($conn);
        ?>
      </tbody>
    </table>
  </main>
  
  <footer>
    <p>&copy; <?php echo date("Y"); ?> Farmácia. Todos os direitos reservados.</p>
  </footer>
</body>
</html>
