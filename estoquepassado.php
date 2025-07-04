<?php
// Conexão com o banco de dados
function conectar_banco() {
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "farmacia";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }
    // Setar charset para evitar problemas com acentuação
    $conn->set_charset("utf8");
    return $conn;
}

// Calcula a data de início e fim do mês passado
$start_date = date("Y-m-01", strtotime("first day of last month"));
$end_date   = date("Y-m-t", strtotime("last month"));

$conn = conectar_banco();

$sql = "SELECT h.id, m.nome, h.quantidade, h.data 
        FROM historico_estoque h 
        JOIN medicamentos m ON h.id_medicamento = m.id 
        WHERE h.data BETWEEN ? AND ?
        ORDER BY h.data DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Função para traduzir o nome do mês
function mesPorExtenso($data) {
    setlocale(LC_TIME, 'pt_BR.UTF-8', 'portuguese');
    return strftime('%B %Y', strtotime($data));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Consulta de Estoque do Mês Passado</title>
  <style>
    /* Seu CSS aqui - mantive o mesmo que enviou */
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
    footer {
      background-color: #2c3e50;
      color: #fff;
      text-align: center;
      padding: 15px;
      margin-top: 30px;
    }
    @media (max-width: 600px) {
      header { font-size: 20px; padding: 15px; }
      nav ul li a { padding: 8px 10px; font-size: 14px; }
      th, td { padding: 8px; font-size: 14px; }
    }
    /* Botão */
    .btn-pdf {
      display: block;
      margin: 15px auto;
      padding: 12px 20px;
      background-color: #4CAF50;
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s ease;
      text-align: center;
      text-decoration: none;
      width: 220px;
    }
    .btn-pdf:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
  <header>Consulta de Estoque do Mês Passado</header>

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
    <h2>Estoque Registrado de <?php echo ucfirst(mesPorExtenso($start_date)); ?></h2>
    
    <a href="relatorio_estoque_pdf.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn-pdf" target="_blank">Gerar Relatório PDF</a>
    
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
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
              echo "<td>" . htmlspecialchars($row["nome"]) . "</td>";
              echo "<td>" . htmlspecialchars($row["quantidade"]) . "</td>";
              echo "<td>" . date("d/m/Y", strtotime($row["data"])) . "</td>";
              echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='4'>Nenhum registro de estoque encontrado para o mês passado.</td></tr>";
        }
        $stmt->close();
        $conn->close();
        ?>
      </tbody>
    </table>
  </main>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> Farmácia. Todos os direitos reservados.</p>
  </footer>
</body>
</html>
