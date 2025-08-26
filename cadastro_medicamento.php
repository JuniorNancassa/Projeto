<?php
session_start();

// Proteção para garantir que apenas admins acessem a página
if (!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Função para conectar ao banco
function conectar_banco() {
    $conn = new mysqli("localhost", "root", "", "farmacia");
    if ($conn->connect_error) {
        die("Erro: " . $conn->connect_error);
    }
    return $conn;
}

$conn = conectar_banco();
$mensagem = "";

// INSERIR novo medicamento
if (isset($_POST['cadastrar'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $quantidade = (int) $_POST['quantidade'];
    $preco = (float) $_POST['preco'];
    $validade = $conn->real_escape_string($_POST['validade']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $fornecedor = $conn->real_escape_string($_POST['fornecedor']);

    // Validação da data de validade
    if (strtotime($validade) < strtotime(date("Y-m-d"))) {
        echo "<script>alert('Data de validade inválida! Não pode ser anterior a hoje.'); window.history.back();</script>";
        exit;
    }

    $sql = "INSERT INTO medicamentos (nome, descricao, quantidade, preco, validade, categoria, fornecedor)
            VALUES ('$nome', '$descricao', '$quantidade', '$preco', '$validade', '$categoria', '$fornecedor')";
    $mensagem = $conn->query($sql) ? "Medicamento cadastrado!" : "Erro: " . $conn->error;
}

// ATUALIZAR medicamento existente
if (isset($_POST['atualizar'])) {
    $id = (int) $_POST['id'];
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $quantidade = (int) $_POST['quantidade'];
    $preco = (float) $_POST['preco'];
    $validade = $conn->real_escape_string($_POST['validade']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $fornecedor = $conn->real_escape_string($_POST['fornecedor']);

    // Validação da data de validade
    if (strtotime($validade) < strtotime(date("Y-m-d"))) {
        echo "<script>alert('Data de validade inválida! Não pode ser anterior a hoje.'); window.history.back();</script>";
        exit;
    }

    $sql = "UPDATE medicamentos SET nome='$nome', descricao='$descricao', quantidade='$quantidade',
            preco='$preco', validade='$validade', categoria='$categoria', fornecedor='$fornecedor' WHERE id=$id";
    $mensagem = $conn->query($sql) ? "Atualizado com sucesso!" : "Erro: " . $conn->error;
}

// DELETAR medicamento
if (isset($_POST['deletar'])) {
    $id = (int) $_POST['id'];
    $conn->query("DELETE FROM medicamentos WHERE id=$id");
    $mensagem = "Medicamento removido!";
}

// BUSCAR dados para edição
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conn->query("SELECT * FROM medicamentos WHERE id=$id");
    $editar = $res->fetch_assoc();
}

// LISTAR medicamentos cadastrados
$dados = $conn->query("SELECT * FROM medicamentos");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Cadastro de Medicamentos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
/* Estilos base */
body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f7f7f7; }

/* Navegação */
nav { background-color: #0d6efd; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
nav .logo { font-weight: bold; font-size: 18px; }
nav ul { list-style: none; display: flex; gap: 20px; padding-left: 0; margin: 0; flex-wrap: wrap; }
nav ul li a { color: white; text-decoration: none; font-weight: 500; }
nav ul li a:hover { color: rgb(13,14,13); }
.menu-toggle { display: none; font-size: 26px; background: none; border: none; color: white; cursor: pointer; }

/* Ajustes mobile */
@media (max-width: 768px) {
    nav { flex-direction: column; align-items: flex-start; }
    .menu-toggle { display: block; margin-top: 10px; }
    nav ul { display: none; width: 100%; flex-direction: column; gap: 10px; padding: 10px 0; }
    nav ul.ativo { display: flex; }
    nav ul li { width: 100%; }
    nav ul li a { display: block; padding: 10px; width: 100%; background-color: #0d6efd; border-top: 1px solid rgba(255,255,255,0.2); }
}

/* Container principal */
.container { max-width: 1000px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
h2 { text-align: center; }

/* Formulário */
form label { font-weight: bold; display: block; margin-top: 15px; }
form input, form textarea, form select { width: 100%; padding: 8px; border: 1px solid #ccc; margin-top: 5px; border-radius: 5px; }
button { margin-top: 20px; padding: 10px 16px; background-color: #4CAF50; color: white; border: none; font-weight: bold; border-radius: 5px; cursor: pointer; }
button:hover { background-color: #43a047; }
.mensagem { text-align: center; color: green; font-weight: bold; margin: 15px 0; }

/* Tabela */
table { width: 100%; margin-top: 30px; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background-color: #0d6efd; color: white; }
.btn { padding: 5px 10px; border: none; font-size: 14px; cursor: pointer; border-radius: 4px; }
.btn.editar { background-color: #3498db; color: white; }
.btn.editar:hover { background-color: #2980b9; }
.btn.excluir { background-color: #e74c3c; color: white; }
.btn.excluir:hover { background-color: #c0392b; }

/* Destaques de validade */
.vencido { background-color: #ffcccc; color: #b30000; font-weight: bold; }
.proximo { background-color: #fff3cd; color: #856404; font-weight: bold; }

/* Rodapé */
footer { margin-top: 60px; background-color: #0d6efd; color: white; text-align: center; padding: 20px; font-size: 14px; }

/* RESPONSIVIDADE tabela */
@media (max-width: 768px) {
    .container { margin: 15px; padding: 20px; }
    table, thead, tbody, th, td, tr { display: block; }
    thead { display: none; }
    tr { margin-bottom: 20px; border-bottom: 1px solid #ccc; }
    td { position: relative; padding-left: 50%; text-align: right; }
    td::before { content: attr(data-label); position: absolute; left: 10px; width: 45%; padding-right: 10px; font-weight: bold; text-align: left; }
}
</style>
</head>
<body>

<!-- Barra de navegação -->
<nav>
    <div class="logo">💊 Sistema Farmácia</div>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    <ul id="menu">
        <li><a href="dashboard.php">🏠 Início</a></li>
        <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
        <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
        <li><a href="venda.php">🛒 Venda</a></li>
        <li><a href="historico.php">📈 Histórico</a></li>
        <li><a href="estoque.php">📦 Estoque</a></li>
        <li><a href="pagina_inicial.php">🚪 Sair</a></li>
    </ul>
</nav>

<!-- Área principal -->
<div class="container">
    <h2><?php echo $editar ? "Editar Medicamento" : "Cadastrar Medicamento"; ?></h2>

    <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="post" onsubmit="return validarData();">
        <?php if ($editar): ?>
            <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
        <?php endif; ?>

        <label>Nome:</label>
        <input type="text" name="nome" value="<?php echo $editar['nome'] ?? ''; ?>" required>

        <label>Descrição:</label>
        <textarea name="descricao" required><?php echo $editar['descricao'] ?? ''; ?></textarea>

        <label>Categoria:</label>
        <select name="categoria" required>
            <option value="">Selecione</option>
            <option value="Analgésico" <?php if (($editar['categoria'] ?? '') == 'Analgésico') echo 'selected'; ?>>Analgésico</option>
            <option value="Antibiótico" <?php if (($editar['categoria'] ?? '') == 'Antibiótico') echo 'selected'; ?>>Antibiótico</option>
            <option value="Anti-inflamatório" <?php if (($editar['categoria'] ?? '') == 'Anti-inflamatório') echo 'selected'; ?>>Anti-inflamatório</option>
            <option value="Antialérgico" <?php if (($editar['categoria'] ?? '') == 'Antialérgico') echo 'selected'; ?>>Antialérgico</option>
            <option value="Outro" <?php if (($editar['categoria'] ?? '') == 'Outro') echo 'selected'; ?>>Outro</option>
        </select>

        <label>Fornecedor:</label>
        <input type="text" name="fornecedor" value="<?php echo $editar['fornecedor'] ?? ''; ?>" required>

        <label>Quantidade:</label>
        <input type="number" name="quantidade" value="<?php echo $editar['quantidade'] ?? ''; ?>" required>

        <label>Preço:</label>
        <input type="number" step="0.01" name="preco" value="<?php echo $editar['preco'] ?? ''; ?>" required>

        <label>Validade:</label>
        <input type="date" name="validade" value="<?php echo $editar['validade'] ?? ''; ?>" required>

        <button type="submit" name="<?php echo $editar ? 'atualizar' : 'cadastrar'; ?>">
            <?php echo $editar ? 'Atualizar' : 'Cadastrar'; ?>
        </button>
    </form>

    <!-- Tabela medicamentos -->
    <h2>Medicamentos Cadastrados</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th><th>Descrição</th><th>Categoria</th><th>Fornecedor</th><th>Quantidade</th><th>Preço</th><th>Validade</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($m = $dados->fetch_assoc()): ?>
            <?php
                $hoje = date('Y-m-d');
                $validade = $m['validade'];
                $classe_alerta = "";
                $mensagem_alerta = "";
                if ($validade < $hoje) { $classe_alerta="vencido"; $mensagem_alerta="Medicamento vencido!"; }
                elseif ((strtotime($validade) - strtotime($hoje)) <= 2592000) { $classe_alerta="proximo"; $mensagem_alerta="Vence em menos de 30 dias!"; }
            ?>
            <tr>
                <td data-label="Nome"><?php echo $m['nome']; ?></td>
                <td data-label="Descrição"><?php echo $m['descricao']; ?></td>
                <td data-label="Categoria"><?php echo $m['categoria']; ?></td>
                <td data-label="Fornecedor"><?php echo $m['fornecedor']; ?></td>
                <td data-label="Quantidade"><?php echo $m['quantidade']; ?></td>
                <td data-label="Preço"><?php echo number_format($m['preco'], 2, ',', '.'); ?></td>
                <td data-label="Validade" class="<?php echo $classe_alerta; ?>">
                    <?php echo date('d/m/Y', strtotime($validade)); ?>
                    <?php if ($mensagem_alerta): ?><br><small><?php echo $mensagem_alerta; ?></small><?php endif; ?>
                </td>
                <td data-label="Ações">
                    <a href="?editar=<?php echo $m['id']; ?>" class="btn editar">✏️</a>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <button type="submit" name="deletar" class="btn excluir" onclick="return confirm('Confirmar exclusão?')">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Rodapé -->
<footer>&copy; 2025 Sistema de Gestão Farmacêutica. Todos os direitos reservados.</footer>

<script>
// Menu hambúrguer
function toggleMenu() {
    const menu = document.getElementById("menu");
    menu.classList.toggle("ativo");
}

// Validação de data no front-end
function validarData() {
    const inputValidade = document.querySelector('input[name="validade"]');
    const hoje = new Date().toISOString().split("T")[0];
    if (inputValidade.value < hoje) {
        alert("Data de validade inválida! Não pode ser anterior a hoje.");
        return false;
    }
    return true;
}

// Define min automático no campo de validade
document.addEventListener("DOMContentLoaded", function() {
    const inputValidade = document.querySelector('input[name="validade"]');
    inputValidade.setAttribute("min", new Date().toISOString().split("T")[0]);
});
</script>

</body>
</html>
