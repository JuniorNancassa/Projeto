<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Pegando o nome do usuário da sessão
$nome_usuario = $_SESSION['nome_usuario'];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Resetando estilos padrões */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        /* Corpo com fundo */
        body {
            background: url('dashboard-bg.jpg') no-repeat center center/cover;
            background-size: cover;
        }

        /* Overlay para melhor legibilidade */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            display: flex;
            justify-content: center;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin: 10px;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .navbar ul li {
            display: inline;
        }

        .navbar ul li a {
            color: #333;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .navbar ul li a:hover {
            color: gray;
        }

        /* Estilo do container principal */
        .container {
            text-align: center;
            padding: 50px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            margin: 100px auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 1s ease-out;
        }

        h1 {
            color: #4CAF50;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .welcome-message {
            font-size: 18px;
            color: #666;
        }

        /* Footer estilizado */
        .footer {
            background-color: rgba(255, 255, 255, 0.9);
            color: black;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }

            .navbar ul {
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            }

            .container {
                padding: 30px 10px;
            }
        }

        /* Animação */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="overlay"></div>

<!-- Navbar -->
<nav class="navbar">
    <ul>
        <li><a href="dashboard.php">🏠Início</a></li>
        <li><a href="cadastro_usuarios.php">👤Cadastrar Usuário</a></li>
        <li><a href="cadastro_medicamento.php">💊Cadastrar Medicamento</a></li>
        <li><a href="venda.php">🛒Venda</a></li>
        <li><a href="historico.php">📈Histórico</a></li>
        <li><a href="estoque.php">📦Estoque</a></li>
        <li><a href="pagina_inicial.php" class="logout">🚪Sair</a></li>
    </ul>
</nav>

<!-- Conteúdo principal -->
<div class="container">
    <h1>Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
    <p class="welcome-message">Aqui você pode gerenciar o estoque, acompanhar vendas e gerar relatórios.</p>
</div>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2025 Farmácia. Todos os direitos reservados.</p>
</footer>

</body>
</html>
