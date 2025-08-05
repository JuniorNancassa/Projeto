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
$sql = "SELECT id, nome, quantidade FROM medicamentos";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque de Medicamentos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
      margin: 0;
    }
    header{
      background-color: #0d6efd;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 1.8rem;
      font-weight: bold;
    }
    nav {
            background-color: #0d6efd;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            font-weight: bold;
            font-size: 18px;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        nav ul li a:hover {
            color:rgb(13, 14, 13);
        }
    main {
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      text-align: center;
      padding: 10px;
    }
    th {
      background-color: #0d6efd;
      color: white;
    }
    .status-icon {
      width: 12px;
      height: 12px;
      display: inline-block;
      border-radius: 50%;
      margin-right: 6px;
    }
    .red { background-color: red; }
    .yellow { background-color: gold; }
    .green { background-color: green; }
    .search-container {
      text-align: center;
      margin-bottom: 20px;
    }
    .search-container input {
      padding: 8px;
      width: 300px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    footer {
            margin-top: 60px;
            background-color: #0d6efd;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }
  </style>
</head>
<body>
  <header>📦 Estoque de Medicamentos</header>
<nav>
    <ul>
      <li><a href="dashboard.php">🏠 Início</a></li>
      <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
      <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
      <li><a href="venda.php">🛒 Venda</a></li>
      <li><a href="historico.php">📈 Histórico</a></li>
      <li><a href="estoque.php">📦 Estoque</a></li>
      <li><a href="pagina_inicial.php">🚪 Sair</a></li>
    </ul>
  </nav>

  <main>
    <div class="search-container">
      <input type="text" id="searchInput" onkeyup="filtrarMedicamentos()" placeholder="Buscar medicamento...">
    </div>
    <div style="text-align: right; margin-bottom: 10px;">
  <form action="relatorio_estoque.php" method="post" target="_blank">
    <button type="submit" style="background-color:#0d6efd; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">
      📄 Gerar Relatório PDF
    </button>
  </form>
</div>


    <table id="medicamentosTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Quantidade</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $qtd = (int)$row['quantidade'];
                if ($qtd <= 50) {
                    $status = "<span class='status-icon red'></span><span style='color:red;'>Baixo</span>";
                } elseif ($qtd <= 100) {
                    $status = "<span class='status-icon yellow'></span><span style='color:gold;'>Médio</span>";
                } else {
                    $status = "<span class='status-icon green'></span><span style='color:green;'>Cheio</span>";
                }

                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['nome']}</td>
                        <td>{$qtd}</td>
                        <td>{$status}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>Nenhum medicamento encontrado.</td></tr>";
        }
        mysqli_close($conn);
        ?>
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
</script>

</body>
<footer>
    &copy; 2025 Sistema de Gestão Farmacêutica 
  </footer>
</html>
