<?php
session_start();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Acesso negado!'); window.location.href='dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Menu Administrador</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    :root {
        --cor-principal: #0d6efd;
        --cor-secundaria: #f4f4f4;
        --cor-texto: #333;
        --cor-branco: #fff;
        --cor-hover: #0056b3;
    }

    body {
        margin: 0;
        font-family: 'Poppins', Arial, sans-serif;
        background: var(--cor-secundaria);
        color: var(--cor-texto);
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        transition: background 0.3s, color 0.3s;
    }

    /* ======== MODO ESCURO ======== */
    body.dark-mode {
        --cor-principal: #1f1f1f;
        --cor-secundaria: #121212;
        --cor-texto: #f4f4f4;
        --cor-branco: #1e1e1e;
        --cor-hover: #333;
        background: var(--cor-secundaria);
        color: var(--cor-texto);
    }

    /* Sidebar */
    .sidebar {
        width: 250px;
        background: var(--cor-principal);
        color: var(--cor-branco);
        position: fixed;
        height: 100%;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: width 0.3s ease;
        z-index: 1000;
    }

    .sidebar.minimizada {
        width: 80px;
    }

    .logo {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 30px;
        transition: opacity 0.3s ease;
    }

    .sidebar.minimizada .logo {
        opacity: 0;
    }

    .menu-toggle {
        position: absolute;
        top: 20px;
        right: -15px;
        background: var(--cor-principal);
        color: white;
        border: none;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
    }

    .sidebar ul li a {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--cor-branco);
        padding: 12px 15px;
        border-radius: 8px;
        font-weight: 500;
        transition: 0.3s;
    }

    .sidebar ul li a:hover {
        background: var(--cor-hover);
    }

    .sidebar.minimizada ul li a span {
        display: none;
    }

    /* Botão modo escuro */
    .dark-toggle {
        background: none;
        border: 1px solid var(--cor-branco);
        color: var(--cor-branco);
        border-radius: 6px;
        padding: 8px 10px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 20px;
        transition: 0.3s;
        width: 100%;
    }

    .dark-toggle:hover {
        background: var(--cor-hover);
    }

    /* Conteúdo principal */
    main {
        flex: 1;
        margin-left: 250px;
        padding: 40px;
        padding-bottom: 80px; /* espaço para o footer fixo */
        transition: margin-left 0.3s ease;
    }

    .sidebar.minimizada + main {
        margin-left: 80px;
    }

    h2 {
        text-align: center;
        color: var(--cor-principal);
        margin-bottom: 40px;
    }

    .cards-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        justify-content: center;
        max-width: 1000px;
        margin: 0 auto;
    }

    .card {
        background: var(--cor-branco);
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        box-shadow: 0 5px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, color 0.3s ease;
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .card a {
        text-decoration: none;
        color: var(--cor-texto);
        font-weight: 600;
        font-size: 1.1rem;
    }

    .card i {
        font-size: 2.2rem;
        color: var(--cor-principal);
        margin-bottom: 12px;
        display: block;
    }

    /* Ajuste do modo escuro nos cards */
    body.dark-mode .card {
        background: #1e1e1e;
        color: #f4f4f4;
        box-shadow: 0 5px 10px rgba(255,255,255,0.05);
    }

    body.dark-mode .card i {
        color: #0d6efd;
    }

    footer {
        text-align: center;
        padding: 15px;
        background: var(--cor-principal);
        color: var(--cor-branco);
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        font-size: 0.9rem;
    }

    /* Responsivo */
    @media (max-width: 992px) {
        .cards-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            left: -250px;
        }
        .sidebar.ativa {
            left: 0;
        }
        main {
            margin-left: 0;
            padding: 20px;
        }
        .menu-toggle {
            top: 15px;
            left: 15px;
        }
        .cards-container {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>

<!-- Menu Lateral -->
<aside class="sidebar" id="sidebar">
    <button class="menu-toggle" id="menu-toggle"><i class="bi bi-list"></i></button>
    <div>
        <div class="logo">Admin</div>
    </div>
    <div>
        <button id="dark-mode" class="dark-toggle"><i class="bi bi-moon-fill"></i> Modo Escuro</button>
        <ul>
            <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i><span>Sair</span></a></li>
        </ul>
    </div>
</aside>

<!-- Conteúdo -->
<main>
    <h2>Bem-vindo, Administrador 👨‍💼</h2>

    <div class="cards-container">
        <div class="card"><i class="bi bi-person-fill"></i><a href="cadastro_usuarios.php">Gerenciar Usuários</a></div>
        <div class="card"><i class="bi bi-capsule"></i><a href="cadastro_medicamento.php">Cadastrar Medicamentos</a></div>
        <div class="card"><i class="bi bi-box-seam"></i><a href="estoque.php">Controle de Estoque</a></div>
        <div class="card"><i class="bi bi-graph-up"></i><a href="historico.php">Histórico de Vendas</a></div>
        <div class="card"><i class="bi bi-graph-up"></i><a href="historico_fornecedores.php">Histórico dos Fornecedores</a></div>
        <div class="card"><i class="bi bi-building"></i><a href="cadastrar_fornecedor.php">Fornecedores</a></div>
        <div class="card"><i class="bi bi-cash-stack"></i><a href="fluxo_caixa.php">Fluxo de Caixa</a></div>
    </div>
</main>

<footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>

<script>
    const toggleBtn = document.getElementById("menu-toggle");
    const sidebar = document.getElementById("sidebar");
    const darkBtn = document.getElementById("dark-mode");
    const body = document.body;

    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("minimizada");
        sidebar.classList.toggle("ativa");
    });

    // Modo escuro/claro
    darkBtn.addEventListener("click", () => {
        body.classList.toggle("dark-mode");
        const icon = darkBtn.querySelector("i");
        if (body.classList.contains("dark-mode")) {
            darkBtn.innerHTML = '<i class="bi bi-sun-fill"></i> Modo Claro';
        } else {
            darkBtn.innerHTML = '<i class="bi bi-moon-fill"></i> Modo Escuro';
        }
    });
</script>

</body>
</html>
