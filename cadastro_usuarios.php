<?php

session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}


function conectar_banco() {
    $conn = new mysqli("localhost", "root", "", "farmacia");
    if ($conn->connect_error) {
        die("Erro: " . $conn->connect_error);
    }
    return $conn;
}

$conn = conectar_banco();
$mensagem = "";

// Criar novo usuário
if (isset($_POST['cadastrar'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES ('$nome', '$email', '$senha', '$tipo')";
    $mensagem = $conn->query($sql) ? "Usuário cadastrado!" : "Erro: " . $conn->error;
}

// Atualizar usuário
if (isset($_POST['atualizar'])) {
    $id = (int) $_POST['id'];
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $tipo = $_POST['tipo_usuario'];
    $sql = "UPDATE usuarios SET nome='$nome', email='$email', tipo_usuario='$tipo' WHERE id=$id";
    $mensagem = $conn->query($sql) ? "Usuário atualizado!" : "Erro: " . $conn->error;
}

// Deletar usuário
if (isset($_POST['deletar'])) {
    $id = (int) $_POST['id'];
    $conn->query("DELETE FROM usuarios WHERE id=$id");
    $mensagem = "Usuário removido!";
}

// Buscar dados para editar
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conn->query("SELECT * FROM usuarios WHERE id=$id");
    $usuario_editar = $res->fetch_assoc();
}

// Listar usuários
$usuarios = $conn->query("SELECT * FROM usuarios");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Usuários</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f2f2f2; }
        nav { background: #0d6efd; color: white; padding: 12px 20px; display: flex; justify-content: space-between; }
        nav ul { display: flex; list-style: none; gap: 20px; }
        nav ul li a { color: white; text-decoration: none; font-weight: bold; }
        nav ul li a:hover { color:rgb(10, 10, 10); }

        .container { max-width: 900px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        h2 { text-align: center; }

        form label { display: block; margin-top: 15px; font-weight: bold; }

         form select { 
          width: 100%;
          padding: 8px;
           margin-top: 5px;
           }

           input{
            width: 97%;
            padding: 8px;
           margin-top: 5px;
           }

        button { padding: 10px 15px; margin-top: 15px; background: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; }

        button:hover { 
          background: #45a049;
         }

        table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #0d6efd; }
        .msg { text-align: center; font-weight: bold; margin: 10px 0; color: green; }

     .btn {
    padding: 6px 10px;
    font-size: 16px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    border-radius: 5px;
    margin: 0 2px;
    display: inline-block;
}

.btn.editar {
    background-color: #3498db;
    color: white;
}

.btn.editar:hover {
    background-color: #2980b9;
}

.btn.excluir {
    background-color: #e74c3c;
    color: white;
}

.btn.excluir:hover {
    background-color: #c0392b;
}


    footer {
    background-color: #0d6efd;
    color: white;
    text-align: center;
    padding: 15px 0;
    margin-top: 40px;
    font-size: 14px;
    position: relative;
    bottom: 0;
    width: 100%;
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
    <h2><?php echo $usuario_editar ? "Editar Usuário" : "Cadastrar Usuário"; ?></h2>
    
    <?php if ($mensagem): ?>
        <div class="msg"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if ($usuario_editar): ?>
            <input type="hidden" name="id" value="<?php echo $usuario_editar['id']; ?>">
        <?php endif; ?>
        <label>Nome:</label>
        <input type="text" name="nome" required value="<?php echo $usuario_editar['nome'] ?? ''; ?>">

        <label>E-mail:</label>
        <input type="email" name="email" required value="<?php echo $usuario_editar['email'] ?? ''; ?>">

        <?php if (!$usuario_editar): ?>
        <label>Senha:</label>
        <input type="password" name="senha" required>
        <?php endif; ?>

        <label>Tipo:</label>
        <select name="tipo_usuario">
            <option value="funcionario" <?php if (($usuario_editar['tipo_usuario'] ?? '') == 'funcionario') echo 'selected'; ?>>Funcionário</option>
            <option value="admin" <?php if (($usuario_editar['tipo_usuario'] ?? '') == 'admin') echo 'selected'; ?>>Administrador</option>
        </select>

        <button type="submit" name="<?php echo $usuario_editar ? 'atualizar' : 'cadastrar'; ?>">
            <?php echo $usuario_editar ? 'Atualizar' : 'Cadastrar'; ?>
        </button>
    </form>

    <h2>Usuários Cadastrados</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th><th>Email</th><th>Tipo</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
            <tr>
                <td><?php echo $u['nome']; ?></td>
                <td><?php echo $u['email']; ?></td>
                <td><?php echo ucfirst($u['tipo_usuario']); ?></td>
                <td>
                <a href="?editar=<?php echo $u['id']; ?>" class="btn editar">✏️</a> |
                    <form method="post" style="display:inline">
                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                        <button type="submit" name="deletar" class="btn excluir" onclick="return confirm('Tem certeza que deseja excluir?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
<footer>
    <p>&copy; 2025 Sistema de Gestão Farmacêutica. Todos os direitos reservados.</p>
</footer>

</html>
