<?php
session_start();

// 🔹 Configurações do banco de dados
define('DB_SERVER', 'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'farmacia');

// 🔹 Função de conexão
function conectar_banco() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
    return $conn;
}

// 🔹 Mensagem de erro
$erro = "";

// 🔹 Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["entrar"])) {
    $conn = conectar_banco();

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Evita SQL Injection
    $sql = "SELECT id, nome, email, senha, tipo_usuario FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();

        // 🔒 Verifica se a senha está correta (com password_verify)
        if (password_verify($senha, $row['senha'])) {
            session_regenerate_id(true);

            $_SESSION['id'] = $row['id'];
            $_SESSION['id_usuario'] = $row['id'];
            $_SESSION['nome_usuario'] = $row['nome'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
            $_SESSION['usuario_logado'] = true;

            // Redirecionamento conforme tipo
            if ($row['tipo_usuario'] === 'admin') {
                header("Location: menu_admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "E-mail não encontrado!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sistema Farmacêutico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body {
        background: linear-gradient(120deg, #0d6efd, #198754);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: "Segoe UI", sans-serif;
    }
    .login-box {
        background-color: #fff;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        width: 100%;
        max-width: 400px;
        animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .login-box h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #0d6efd;
        font-weight: bold;
    }
    .btn-login {
        background-color: #0d6efd;
        color: #fff;
        width: 100%;
        font-weight: 600;
        border: none;
        transition: 0.3s;
    }
    .btn-login:hover {
        background-color: #0b5ed7;
    }
    .erro {
        color: red;
        text-align: center;
        margin-bottom: 15px;
        font-weight: 500;
    }
    .input-group-text {
        cursor: pointer;
        background-color: #fff;
        border-left: none;
    }
</style>
</head>
<body>

<div class="login-box">
    <h2>Login</h2>
    
    <?php if($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Digite seu e-mail" required>
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <div class="input-group">
                <input type="password" name="senha" id="senha" class="form-control" placeholder="Digite sua senha" required>
                <button type="button" class="input-group-text" id="mostrarSenha">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" name="entrar" class="btn btn-login mt-2">Entrar</button>
    </form>
</div>

<script>
    // 👁️ Alternar visibilidade da senha
    const senhaInput = document.getElementById('senha');
    const mostrarSenha = document.getElementById('mostrarSenha');
    const icone = mostrarSenha.querySelector('i');

    mostrarSenha.addEventListener('click', () => {
        const isPassword = senhaInput.type === "password";
        senhaInput.type = isPassword ? "text" : "password";
        icone.classList.toggle('bi-eye');
        icone.classList.toggle('bi-eye-slash');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
