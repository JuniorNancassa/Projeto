<?php
session_start();

// Verifica se usuário está logado e se é admin
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: Arial, sans-serif; }
    body {
    margin:0;
    font-family: Arial, sans-serif;
    background:#f4f4f4;
    display:flex;
    flex-direction:column;
    min-height:100vh;
}

/* Conteúdo principal */
main {
    flex:1; /* Faz o main ocupar o espaço disponível */
    margin-left:250px; /* Para sidebar fixa */
    padding:30px;
}

    /* Sidebar */
    .sidebar {
        width:250px;
        background:#0d6efd;
        display:flex;
        flex-direction:column;
        min-height:100vh;
        padding-top:20px;
        position:fixed;
    }
    .sidebar .logo {
        text-align:center;
        font-weight:bold;
        font-size:22px;
        color:white;
        margin-bottom:30px;
    }
    .sidebar ul { list-style:none; flex:1; padding-left:0; }
    .sidebar ul li { width:100%; }
    .sidebar ul li a {
        display:flex;
        align-items:center;
        gap:10px;
        padding:12px 20px;
        color:white;
        text-decoration:none;
        font-weight:500;
        transition:0.3s;
        border-radius:6px;
    }
    .sidebar ul li a:hover { background:white; color:#0d6efd; }

    /* Dropdown submenu */
    .dropdown-btn {
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:12px 20px;
        color:white;
        font-weight:500;
        border:none;
        background:none;
        width:100%;
        text-align:left;
        border-radius:6px;
        outline:none;
        transition:0.3s;
    }
    .dropdown-btn:hover { background:white; color:#0d6efd; }
    .dropdown-container { display:none; flex-direction:column; padding-left:20px; }
    .dropdown-container a { padding:8px 20px; font-size:0.95rem; color:white; border-radius:6px; }
    .dropdown-container a:hover { background:white; color:#0d6efd; }

    main h2 { text-align:center; margin-bottom:30px; color:#333; }

    .opcoes {
        display:flex;
        flex-wrap:wrap;
        justify-content:space-between;
        gap:20px;
    }

    .card {
        flex:1 1 calc(33% - 20px);
        min-width:220px;
        padding:30px;
        border-radius:12px;
        text-align:center;
        box-shadow:0 4px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        font-size:1.1rem;
        color:white;
        background:#3498db;
    }

    .card-usuarios { background:#1abc9c; }
    .card-medicamentos { background:#3498db; }
    .card-estoque { background:#e67e22; }
    .card-historico { background:#9b59b6; }
    .card-relatorios { background:#f39c12; }
    .card-fornecedores { background:#e74c3c; }

    .card:hover { transform:translateY(-5px); box-shadow:0 6px 18px rgba(0,0,0,0.2); }
    .card a { text-decoration:none; font-weight:bold; color:white; font-size:1.2rem; display:flex; align-items:center; justify-content:center; gap:12px; }

    /* Footer */
footer {
    background:#0d6efd;
    color:white;
    text-align:center;
    padding:20px;
    border-radius:12px;
}

    @media(max-width:1024px) {
        .card { flex:1 1 calc(50% - 20px); }
    }
    @media(max-width:768px) {
        body { flex-direction:column; }
        .sidebar { width:100%; position:relative; min-height:auto; }
        main { margin-left:0; padding:20px; }
        .opcoes { justify-content:center; }
        .card { flex:1 1 100%; }
    }
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">Farmácia</div>
    <ul>
        <li><a href="menu_admin.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
        <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
        <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Medicamentos</a></li>
        <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
        <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
        <li>
            <button class="dropdown-btn"><i class="bi bi-graph-up"></i> Histórico <i class="bi bi-caret-down-fill"></i></button>
            <div class="dropdown-container">
                <a href="historico.php">Histórico de Venda</a>
                <a href="venda_usuario.php">Venda por Usuário</a>
                <a href="total_dia.php">Total do Dia</a>
                <a href="venda_medicamento.php">Venda por Medicamento</a>
                <a href="historico_fornecedor.php">Histórico de Fornecedor</a>
            </div>
        </li>
        <li>
            <button class="dropdown-btn"><i class="bi bi-file-earmark-text"></i> Relatórios <i class="bi bi-caret-down-fill"></i></button>
            <div class="dropdown-container">
                <a href="relatorio_estoque.php">Relatório de Estoque</a>
                <a href="relatorio_historico.php">Relatório Histórico</a>
            </div>
        </li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
    </ul>
</div>

<main>
    <h2>Bem-vindo, Administrador 👨‍💼</h2>
    <div class="opcoes">
        <div class="card card-usuarios"><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Gerenciar Usuários</a></div>
        <div class="card card-medicamentos"><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Cadastrar Medicamentos</a></div>
        <div class="card card-estoque"><a href="estoque.php"><i class="bi bi-box-seam"></i> Controle de Estoque</a></div>
        <div class="card card-historico"><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico de Vendas</a></div>
        <div class="card card-relatorios"><a href="relatorios.php"><i class="bi bi-file-earmark-text"></i> Gerar Relatórios</a></div>
        <div class="card card-fornecedores"><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></div>
    </div>
</main>

<footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>

<script>
    // Dropdown sidebar
    var dropdown = document.getElementsByClassName("dropdown-btn");
    for (let i = 0; i < dropdown.length; i++) {
        dropdown[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var container = this.nextElementSibling;
            if (container.style.display === "flex") {
                container.style.display = "none";
            } else {
                container.style.display = "flex";
            }
        });
    }
</script>

</body>
</html>
