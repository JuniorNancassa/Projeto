<?php
session_start();

// Detalhes do banco de dados
define('DB_SERVER', 'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'farmacia');

// Função para conectar ao banco de dados
function conectar_banco() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
    return $conn;
}

// Mensagem de erro
$erro = "";

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["entrar"])) {
    $conn = conectar_banco();

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Usando Prepared Statements para evitar SQL Injection
    $sql = "SELECT id, nome, email, senha, tipo_usuario FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();

        // Verifica se a senha está correta
        if ($senha === $row['senha']) {
            // Regenerar ID de sessão para segurança
            session_regenerate_id(true);

            // Criar sessão do usuário
            $_SESSION['id'] = $row['id'];
            $_SESSION['id_usuario'] = $row['id']; // Agora está correto
            $_SESSION['nome_usuario'] = $row['nome'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
            $_SESSION['usuario_logado'] = true;

            // Redireciona para o dashboard
            header("Location: dashboard.php");
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
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('login.jpg') no-repeat center center/cover;
        }
        .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: -1;
        }
        .login-container {
            width: 100%; max-width: 400px; padding: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        h1 { color: #4CAF50; font-size: 26px; margin-bottom: 20px; }
        label { font-size: 16px; color: #333; display: block; margin-top: 10px; font-weight: bold; }
        input, button {
            width: 100%; padding: 12px; margin-top: 5px; border-radius: 6px;
            font-size: 16px; transition: 0.3s; outline: none;
        }
        button {
            background-color: #4CAF50; border: none; color: white;
            font-size: 18px; cursor: pointer; transition: 0.3s;
        }
        button:hover { background-color: #45a049; }
        .erro { color: red; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="overlay"></div>

<div class="login-container">
    <h1>Login</h1>
    <?php if ($erro): ?>
        <p class="erro"><?php echo $erro; ?></p>
    <?php endif; ?>
    
    <form action="login.php" method="post">
        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>

        <button type="submit" name="entrar">Entrar</button>
    </form>
</div>

</body>
</html>
