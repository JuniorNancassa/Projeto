<?php
session_start();

// Permissão: apenas admin pode acessar esta página
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Função de conexão
function conectar_banco() {
    $conn = new mysqli("localhost", "root", "", "farmacia");
    if ($conn->connect_error) die("Erro: " . $conn->connect_error);
    return $conn;
}

$conn = conectar_banco();
$mensagem = "";

// 🔹 Criar novo usuário
if (isset($_POST['cadastrar'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar_senha'];
    $tipo = $_POST['tipo_usuario'];

    if ($senha !== $confirmar) {
        $mensagem = "⚠️ As senhas não coincidem!";
    } else {
        // Verificar se e-mail já existe
        $verifica = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $verifica->bind_param("s", $email);
        $verifica->execute();
        $verifica->store_result();

        if ($verifica->num_rows > 0) {
            $mensagem = "⚠️ Este e-mail já está cadastrado!";
        } else {
            // Criptografar senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (?, ?, ?, ?)");
            $sql->bind_param("ssss", $nome, $email, $senha_hash, $tipo);

            if ($sql->execute()) {
                $mensagem = "✅ Usuário cadastrado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao cadastrar: " . $conn->error;
            }

            $sql->close();
        }
        $verifica->close();
    }
}

// 🔹 Atualizar usuário
if (isset($_POST['atualizar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $tipo = $_POST['tipo_usuario'];

    $sql = $conn->prepare("UPDATE usuarios SET nome=?, email=?, tipo_usuario=? WHERE id=?");
    $sql->bind_param("sssi", $nome, $email, $tipo, $id);
    $mensagem = $sql->execute() ? "✅ Usuário atualizado com sucesso!" : "❌ Erro ao atualizar.";
    $sql->close();
}

// 🔹 Deletar usuário
if (isset($_POST['deletar'])) {
    $id = (int) $_POST['id'];
    $sql = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $mensagem = "🗑️ Usuário removido!";
    $sql->close();
}

// 🔹 Buscar usuário para edição
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conn->prepare("SELECT * FROM usuarios WHERE id=?");
    $res->bind_param("i", $id);
    $res->execute();
    $usuario_editar = $res->get_result()->fetch_assoc();
    $res->close();
}

// 🔹 Listar usuários
$usuarios = $conn->query("SELECT * FROM usuarios");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gestão de Usuários</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: Arial, sans-serif; margin: 0; background: #f2f2f2; }
nav { background: #0d6efd; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
nav .logo { font-weight: bold; font-size: 18px; }
nav ul { display: flex; list-style: none; gap: 20px; padding-left:0; margin:0; flex-wrap: wrap; }
nav ul li a { color: white; text-decoration: none; font-weight: bold; }
nav ul li a:hover { color:#111; }
.menu-toggle { display: none; font-size: 26px; background:none; border:none; color:white; cursor:pointer; }

@media (max-width:768px){
    .menu-toggle { display:block; }
    nav ul { display:none; width:100%; flex-direction:column; gap:10px; padding:10px 0; }
    nav ul.ativo { display:flex; }
    nav ul li a { display:block; padding:10px; background-color:#0d6efd; border-top:1px solid rgba(255,255,255,0.2); }
}

.container { max-width: 900px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
h2 { text-align: center; }

form label { display: block; margin-top: 15px; font-weight: bold; }
form select, input { width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border:1px solid #ccc; }
.senha-container { position: relative; }
.toggle-senha { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; color: #0d6efd; }
button { padding: 10px 15px; margin-top: 15px; background: #0d6efd; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: 600; }
button:hover { background: #0b5ed7; }

table { width: 100%; margin-top: 30px; border-collapse: collapse; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
th { background-color: #0d6efd; color:white; }

.msg { text-align: center; font-weight: bold; margin: 10px 0; color: #0d6efd; }

.btn { padding: 6px 10px; font-size: 16px; border: none; cursor: pointer; text-decoration:none; border-radius:5px; margin:0 2px; display:inline-block; }
.btn.editar { background-color: #198754; color: white; }
.btn.excluir { background-color: #dc3545; color:white; }

footer { background-color:#0d6efd; color:white; text-align:center; padding:15px 0; margin-top:40px; font-size:14px; }

@media (max-width:768px){
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr { margin-bottom:15px; border-bottom:1px solid #ccc; }
    td { position: relative; padding-left:50%; text-align:right; }
    td::before { content: attr(data-label); position:absolute; left:10px; width:45%; font-weight:bold; text-align:left; }
}
</style>
</head>
<body>

<nav>
    <div class="logo">👤 Sistema Farmácia</div>
    <button class="menu-toggle" id="menu-btn" onclick="toggleMenu()">☰</button>
    <ul id="menu">
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
    <h2><?php echo $usuario_editar ? "Editar Usuário" : "Cadastrar Usuário"; ?></h2>
    
    <?php if ($mensagem): ?>
        <div class="msg"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if ($usuario_editar): ?>
            <input type="hidden" name="id" value="<?php echo $usuario_editar['id']; ?>">
        <?php endif; ?>

        <label>Nome Completo:</label>
        <input type="text" name="nome" required value="<?php echo $usuario_editar['nome'] ?? ''; ?>">

        <label>E-mail:</label>
        <input type="email" name="email" required value="<?php echo $usuario_editar['email'] ?? ''; ?>">

        <?php if (!$usuario_editar): ?>
        <label>Senha:</label>
        <div class="senha-container">
            <input type="password" name="senha" id="senha" required>
            <span class="toggle-senha" onclick="toggleSenha('senha')"><i class="bi bi-eye"></i></span>
        </div>

        <label>Confirmar Senha:</label>
        <div class="senha-container">
            <input type="password" name="confirmar_senha" id="confirmar_senha" required>
            <span class="toggle-senha" onclick="toggleSenha('confirmar_senha')"><i class="bi bi-eye"></i></span>
        </div>
        <?php endif; ?>

        <label>Tipo:</label>
        <select name="tipo_usuario" required>
            <option value="">Selecione</option>
            <option value="admin" <?php if (($usuario_editar['tipo_usuario'] ?? '') == 'admin') echo 'selected'; ?>>Administrador</option>
            <option value="vendedor" <?php if (($usuario_editar['tipo_usuario'] ?? '') == 'vendedor') echo 'selected'; ?>>Vendedor</option>
            <option value="assistenteAdmin" <?php if (($usuario_editar['tipo_usuario'] ?? '') == 'assistenteAdmin') echo 'selected'; ?>>Assistente Admin</option>
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
                <td data-label="Nome"><?php echo $u['nome']; ?></td>
                <td data-label="Email"><?php echo $u['email']; ?></td>
                <td data-label="Tipo"><?php echo ucfirst($u['tipo_usuario']); ?></td>
                <td data-label="Ações">
                    <a href="?editar=<?php echo $u['id']; ?>" class="btn editar"><i class="bi bi-pencil-square"></i></a>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                        <button type="submit" name="deletar" class="btn excluir" onclick="return confirm('Tem certeza que deseja excluir?')"><i class="bi bi-trash"></i></button>
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

<script>
function toggleMenu() {
    const menu = document.getElementById("menu");
    const btn = document.getElementById("menu-btn");
    menu.classList.toggle("ativo");
    btn.textContent = menu.classList.contains("ativo") ? "✖" : "☰";
}
function toggleSenha(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>
