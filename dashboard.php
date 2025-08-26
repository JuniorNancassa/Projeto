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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            background: url('dashboard-bg.jpg') no-repeat center center/cover;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.9);
            padding: 10px 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin: 10px;
            position: relative;
        }

        .navbar .menu-toggle {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
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

        @media (max-width: 768px) {
            .navbar ul {
                flex-direction: column;
                width: 100%;
                display: none;
            }

            .navbar ul.show {
                display: flex;
            }

            .navbar .menu-toggle {
                display: block;
            }
        }

        .container {
            text-align: center;
            padding: 50px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 1s ease-out;
            z-index: 1;
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

        .footer {
            margin-top: auto;
            background-color: rgba(255, 255, 255, 0.95);
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            font-size: 14px;
            border-top: 1px solid #ccc;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }

        .footer p {
            margin: 5px 0;
            text-align: center;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
            margin: 0 5px;
            font-weight: bold;
        }

        .footer a:hover {
            text-decoration: underline;
        }

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
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menu</button>
    <ul id="menu">
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
    <p>&copy; <?php echo date('Y'); ?> Farmácia. Todos os direitos reservados.</p>
    <p>Desenvolvido por <a href="fernandojuniornancassa@gmail.com">Natypanah Fernando Nancassa</a></p>
</footer>

<!-- Script para toggle do menu -->
<script>
function toggleMenu() {
    const menu = document.getElementById("menu");
    menu.classList.toggle("show");
}
</script>

</body>
</html>
