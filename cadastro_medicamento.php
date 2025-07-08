<?php
session_start();
/*if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}*/
function conectar_banco() {
    $conn = new mysqli("localhost", "root", "", "farmacia");
    if ($conn->connect_error) {
        die("Erro: " . $conn->connect_error);
    }
    return $conn;
}

$conn = conectar_banco();
$mensagem = "";

// Inserir
if (isset($_POST['cadastrar'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $quantidade = (int) $_POST['quantidade'];
    $preco = (float) $_POST['preco'];
    $validade = $conn->real_escape_string($_POST['validade']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $fornecedor = $conn->real_escape_string($_POST['fornecedor']);

    $sql = "INSERT INTO medicamentos (nome, descricao, quantidade, preco, validade, categoria, fornecedor)
            VALUES ('$nome', '$descricao', '$quantidade', '$preco', '$validade', '$categoria', '$fornecedor')";
    $mensagem = $conn->query($sql) ? "Medicamento cadastrado!" : "Erro: " . $conn->error;
}

// Atualizar
if (isset($_POST['atualizar'])) {
    $id = (int) $_POST['id'];
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $quantidade = (int) $_POST['quantidade'];
    $preco = (float) $_POST['preco'];
    $validade = $conn->real_escape_string($_POST['validade']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $fornecedor = $conn->real_escape_string($_POST['fornecedor']);

    $sql = "UPDATE medicamentos SET nome='$nome', descricao='$descricao', quantidade='$quantidade',
            preco='$preco', validade='$validade', categoria='$categoria', fornecedor='$fornecedor' WHERE id=$id";
    $mensagem = $conn->query($sql) ? "Atualizado com sucesso!" : "Erro: " . $conn->error;
}

// Deletar
if (isset($_POST['deletar'])) {
    $id = (int) $_POST['id'];
    $conn->query("DELETE FROM medicamentos WHERE id=$id");
    $mensagem = "Medicamento removido!";
}

// Editar
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conn->query("SELECT * FROM medicamentos WHERE id=$id");
    $editar = $res->fetch_assoc();
}

// Listar
$dados = $conn->query("SELECT * FROM medicamentos");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Medicamentos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif;
             margin: 0; background: #f7f7f7; }

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

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        h2 { text-align: center; }

        form label { font-weight: bold; display: block; margin-top: 15px; }
        form input, form textarea, form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            margin-top: 5px;
            border-radius: 5px;
        }

        button {
            margin-top: 20px;
            padding: 10px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover { background-color: #43a047; }

        .mensagem {
            text-align: center;
            color: green;
            font-weight: bold;
            margin: 15px 0;
        }

        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th { background-color: #0d6efd; color: white; }

        .btn {
            padding: 5px 10px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn.editar { background-color: #3498db; color: white; }
        .btn.editar:hover { background-color: #2980b9; }

        .btn.excluir { background-color: #e74c3c; color: white; }
        .btn.excluir:hover { background-color: #c0392b; }

        .vencido {
            background-color: #ffcccc;
            color: #b30000;
            font-weight: bold;
        }

        .proximo {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
        }

        footer {
            margin-top: 60px;
            background-color: #0d6efd;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            nav ul { flex-direction: column; }
            .container { margin: 15px; }
        }
    </style>
</head>
<body>

<nav>
    <ul>
      <li><a href="dashboard.php">🏠 Início</a></li>
      <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
      <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
      <li><a href="venda.php">🛒 Venda</a></li>
      <li><a href="historico.php">📈 Histórico</a></li>
      <li><a href="estoque.php">📦 Estoque</a></li>
      <li><a href="logout.php">🚪 Sair</a></li>
    </ul>
  </nav>
<div class="container">
    <h2><?php echo $editar ? "Editar Medicamento" : "Cadastrar Medicamento"; ?></h2>

    <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <form method="post">
        <?php if ($editar): ?>
            <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
        <?php endif; ?>

        <label>Nome:</label>
        <input type="text" name="nome" value="<?php echo $editar['nome'] ?? ''; ?>" required>

        <label>Descrição:</label>
        <textarea name="descricao" required><?php echo $editar['descricao'] ?? ''; ?></textarea>

        <label>Categoria:</label>
        <select name="categoria" required>
            <option value="">Selecione</option>
            <option value="Analgésico" <?php if (($editar['categoria'] ?? '') == 'Analgésico') echo 'selected'; ?>>Analgésico</option>
            <option value="Antibiótico" <?php if (($editar['categoria'] ?? '') == 'Antibiótico') echo 'selected'; ?>>Antibiótico</option>
            <option value="Anti-inflamatório" <?php if (($editar['categoria'] ?? '') == 'Anti-inflamatório') echo 'selected'; ?>>Anti-inflamatório</option>
            <option value="Antialérgico" <?php if (($editar['categoria'] ?? '') == 'Antialérgico') echo 'selected'; ?>>Antialérgico</option>
            <option value="Outro" <?php if (($editar['categoria'] ?? '') == 'Outro') echo 'selected'; ?>>Outro</option>
        </select>

        <label>Fornecedor:</label>
        <input type="text" name="fornecedor" value="<?php echo $editar['fornecedor'] ?? ''; ?>" required>

        <label>Quantidade:</label>
        <input type="number" name="quantidade" value="<?php echo $editar['quantidade'] ?? ''; ?>" required>

        <label>Preço:</label>
        <input type="number" step="0.01" name="preco" value="<?php echo $editar['preco'] ?? ''; ?>" required>

        <label>Validade:</label>
        <input type="date" name="validade" value="<?php echo $editar['validade'] ?? ''; ?>" required>

        <button type="submit" name="<?php echo $editar ? 'atualizar' : 'cadastrar'; ?>">
            <?php echo $editar ? 'Atualizar' : 'Cadastrar'; ?>
        </button>
    </form>

    <h2>Medicamentos Cadastrados</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th><th>Descrição</th><th>Categoria</th><th>Fornecedor</th><th>Quantidade</th><th>Preço</th><th>Validade</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($m = $dados->fetch_assoc()): ?>
            <?php
                $hoje = date('Y-m-d');
                $validade = $m['validade'];
                $classe_alerta = "";
                $mensagem_alerta = "";

                if ($validade < $hoje) {
                    $classe_alerta = "vencido";
                    $mensagem_alerta = "Medicamento vencido!";
                } elseif ((strtotime($validade) - strtotime($hoje)) <= 2592000) {
                    $classe_alerta = "proximo";
                    $mensagem_alerta = "Vence em menos de 30 dias!";
                }
            ?>
            <tr>
                <td><?php echo $m['nome']; ?></td>
                <td><?php echo $m['descricao']; ?></td>
                <td><?php echo $m['categoria']; ?></td>
                <td><?php echo $m['fornecedor']; ?></td>
                <td><?php echo $m['quantidade']; ?></td>
                <td><?php echo number_format($m['preco'], 2, ',', '.'); ?></td>
                <td class="<?php echo $classe_alerta; ?>">
                    <?php echo date('d/m/Y', strtotime($validade)); ?>
                    <?php if ($mensagem_alerta): ?>
                        <br><small><?php echo $mensagem_alerta; ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?editar=<?php echo $m['id']; ?>" class="btn editar">✏️</a>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <button type="submit" name="deletar" class="btn excluir" onclick="return confirm('Confirmar exclusão?')">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<footer>
    <p>&copy; 2025 Sistema de Gestão Farmacêutica. Todos os direitos reservados.</p>
</footer>

</body>
</html>
