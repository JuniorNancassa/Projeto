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

        // Verifica se a senha está correta (⚠️ se usar hash, troque para password_verify)
        if ($senha === $row['senha']) {
            // Regenerar ID de sessão para segurança
            session_regenerate_id(true);

            // Criar sessão do usuário
            $_SESSION['id'] = $row['id'];
            $_SESSION['id_usuario'] = $row['id'];
            $_SESSION['nome_usuario'] = $row['nome'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
            $_SESSION['usuario_logado'] = true;

            // 🔹 Redireciona conforme tipo de usuário
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
