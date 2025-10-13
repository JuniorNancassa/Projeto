<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit();
}

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "farmacia");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Inicialização
$id = "";
$nome = "";
$descricao = "";
$preco = "";
$quantidade = "";
$erro = "";
$sucesso = "";

// Inserir medicamento
if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $quantidade = $_POST['quantidade'];

    if (!empty($nome) && !empty($preco)) {
        $sql = "INSERT INTO medicamentos (nome, descricao, preco, quantidade) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $nome, $descricao, $preco, $quantidade);
        if ($stmt->execute()) {
            $sucesso = "Medicamento cadastrado com sucesso!";
        } else {
            $erro = "Erro ao cadastrar medicamento!";
        }
        $stmt->close();
    } else {
        $erro = "Preencha todos os campos obrigatórios!";
    }
}

// Editar medicamento
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $result = $conn->query("SELECT * FROM medicamentos WHERE id=$id");
    $med = $result->fetch_assoc();
    $nome = $med['nome'];
    $descricao = $med['descricao'];
    $preco = $med['preco'];
    $quantidade = $med['quantidade'];
}

// Atualizar medicamento
if (isset($_POST['atualizar'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $quantidade = $_POST['quantidade'];

    $sql = "UPDATE medicamentos SET nome=?, descricao=?, preco=?, quantidade=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $nome, $descricao, $preco, $quantidade, $id);

    if ($stmt->execute()) {
        $sucesso = "Medicamento atualizado com sucesso!";
    } else {
        $erro = "Erro ao atualizar!";
    }
    $stmt->close();
}

// Excluir medicamento
if (isset($_POST['deletar'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM medicamentos WHERE id=$id");
    $sucesso = "Medicamento excluído com sucesso!";
}

// Buscar medicamentos
$result = $conn->query("SELECT * FROM medicamentos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Medicamentos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
body {
    background: #f4f6f9;
    font-family: "Segoe UI", sans-serif;
    margin:0;
    padding:0;
}

/* Menu horizontal */
nav {
    background: #0d6efd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    flex-wrap: wrap;
    color: white;
}
nav .logo {
    font-weight: bold;
    font-size: 1.3rem;
}
nav ul {
    list-style: none;
    display: flex;
    gap: 20px;
    margin: 0;
    padding-left: 0;
    flex-wrap: wrap;
}
nav ul li a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 5px;
}
nav ul li a:hover {
    background: rgba(255,255,255,0.2);
}
.menu-toggle {
    display: none;
    font-size: 1.5rem;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
}

/* Conteúdo */
.container-box {
    max-width: 1000px;
    margin: 30px auto;
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.btn-cadastrar {
    background-color: #198754;
    color: #fff;
    border: none;
    transition: all 0.3s ease;
}
.btn-cadastrar:hover {
    background-color: #157347;
    transform: scale(1.02);
}
.btn-outline-primary,
.btn-outline-danger {
    border-radius: 8px;
    transition: all 0.2s ease-in-out;
}
.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: #fff;
}
.btn-outline-danger:hover {
    background-color: #dc3545;
    color: #fff;
}
.table th {
    background-color: #0d6efd;
    color: white;
}

/* Responsividade do menu */
@media(max-width:768px) {
   nav{flex-direction:column;align-items:flex-start;}
    .menu-toggle{display:block;margin-top:10px;}
    nav ul{display:none;width:100%;flex-direction:column;gap:10px;padding:10px 0;}
    nav ul.ativo{display:flex;}
    nav ul li{width:100%;}
    nav ul li a{display:block;padding:10px;width:100%;background:#0d6efd;border-top:1px solid rgba(255,255,255,0.2);}
}
</style>
</head>
<body>

<!-- Menu horizontal -->
<nav>
    <div class="logo"><i class="bi bi-capsule"></i> Sistema Farmácia</div>
    <button class="menu-toggle" id="btnMenu">☰</button>
    <ul id="menu">
        <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
        <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
        <li><a href="cadastro_medicamento.php" class="active"><i class="bi bi-capsule"></i> Medicamentos</a></li>
        <li><a href="cadastro_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
        <li><a href="venda.php"><i class="bi bi-cart-fill"></i> Venda</a></li>
        <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
        <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
        <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
    </ul>
</nav>

<!-- Conteúdo principal -->
<div class="container-box">
    <h2 class="text-center mb-4 text-primary">Cadastro de Medicamentos</h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($nome) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Preço (CFA)</label>
                <input type="number" name="preco" step="0.01" class="form-control" value="<?= htmlspecialchars($preco) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Quantidade</label>
                <input type="number" name="quantidade" class="form-control" value="<?= htmlspecialchars($quantidade) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars($descricao) ?>">
            </div>
        </div>

        <div class="text-center mt-4">
            <?php if ($id): ?>
                <button type="submit" name="atualizar" class="btn btn-cadastrar px-5">
                    <i class="bi bi-arrow-repeat me-1"></i> Atualizar
                </button>
            <?php else: ?>
                <button type="submit" name="cadastrar" class="btn btn-cadastrar px-5">
                    <i class="bi bi-plus-circle me-1"></i> Cadastrar
                </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- 🔍 Campo de busca -->
    <div class="mb-3">
        <input type="text" id="buscar" class="form-control" placeholder="Buscar medicamento pelo nome...">
    </div>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="tabelaMedicamentos">
            <thead class="text-center">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Qtd</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['nome']) ?></td>
                        <td><?= number_format($m['preco'], 2, ',', '.') ?></td>
                        <td><?= $m['quantidade'] ?></td>
                        <td><?= htmlspecialchars($m['descricao']) ?></td>
                        <td class="text-center">
                            <a href="?editar=<?= $m['id'] ?>" class="btn btn-outline-primary btn-sm me-1" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" name="deletar" class="btn btn-outline-danger btn-sm" title="Excluir" onclick="return confirm('Confirma exclusão?')">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 🔍 Filtro de busca em tempo real
    const busca = document.getElementById('buscar');
    const linhas = document.querySelectorAll('#tabelaMedicamentos tbody tr');

    busca.addEventListener('keyup', () => {
        const valor = busca.value.toLowerCase();
        linhas.forEach(linha => {
            const nome = linha.children[1].textContent.toLowerCase();
            linha.style.display = nome.includes(valor) ? '' : 'none';
        });
    });

    // 🍔 Menu hamburger
    const menuBtn = document.getElementById('btnMenu');
    const menu = document.getElementById('menu');
    menuBtn.addEventListener('click', () => {
        menu.classList.toggle('ativo');
    });
</script>
</body>
</html>
