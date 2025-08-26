<?php
session_start();

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nome_usuario'])) {
    echo "<script>alert('Você precisa estar logado para realizar vendas.'); window.location.href='login.php';</script>";
    exit;
}

define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'farmacia');

function conectar_banco() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die('Falha na conexão: ' . $conn->connect_error);
    return $conn;
}

// Processar venda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vender'])) {
    $id_medicamento   = (int) ($_POST['id_medicamento'] ?? 0);
    $quantidade_venda = (int) ($_POST['quantidade_venda'] ?? 0);
    $preco_unitario   = (float) ($_POST['preco_unitario'] ?? 0.0);
    $id_usuario       = (int) $_SESSION['id_usuario'];

    if ($id_medicamento <= 0 || $quantidade_venda <= 0) {
        echo "<script>alert('Dados inválidos para realizar venda.');</script>";
        exit;
    }

    $conn = conectar_banco();
    $stmt = $conn->prepare("SELECT quantidade FROM medicamentos WHERE id = ?");
    $stmt->bind_param('i', $id_medicamento);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('Medicamento não encontrado');</script>";
    } else {
        $row = $result->fetch_assoc();
        $estoque_atual = (int) $row['quantidade'];

        if ($estoque_atual <= 0) {
            echo "<script>alert('Medicamento sem estoque');</script>";
        } elseif ($quantidade_venda > $estoque_atual) {
            echo "<script>alert('Quantidade desejada excede o estoque disponível');</script>";
        } else {
            $novo_estoque = $estoque_atual - $quantidade_venda;
            $up = $conn->prepare("UPDATE medicamentos SET quantidade = ? WHERE id = ?");
            $up->bind_param('ii', $novo_estoque, $id_medicamento);
            $up->execute();
            $up->close();

            $data_venda = date('Y-m-d');
            $ins = $conn->prepare("INSERT INTO vendas (id_medicamento, quantidade, preco_unitario, id_usuario, data_venda) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param('iiids', $id_medicamento, $quantidade_venda, $preco_unitario, $id_usuario, $data_venda);
            if ($ins->execute()) echo "<script>alert('Venda realizada com sucesso! Estoque atualizado.');</script>";
            else echo "<script>alert('Erro ao registrar a venda: " . $ins->error . "');</script>";
            $ins->close();
        }
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Venda de Medicamentos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f1f4f9; color:#333; margin:0; }
header { background:#0d6efd; color:white; padding:1.5rem; text-align:center; font-size:1.8rem; font-weight:bold; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
nav { background:#0d6efd; color:white; padding:12px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
nav .logo { font-weight:bold; font-size:18px; }
nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
nav ul li a { color:white; text-decoration:none; font-weight:500; }
nav ul li a:hover { color:#000; }
nav .menu-toggle { display:none; cursor:pointer; font-size:1.8rem; }
.container { display:flex; flex-direction:column; gap:2rem; padding:2rem; max-width:1200px; margin:auto; }
.form-section, .table-section { background:white; padding:2rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
form { display:flex; flex-direction:column; gap:1rem; }
form label { font-weight:600; }
form select, form input, form button { padding:0.9rem; border:1px solid #ccc; border-radius:8px; font-size:1rem; transition:0.2s ease; }
form input:focus, form select:focus { border-color:#3498db; outline:none; box-shadow:0 0 5px rgba(52,152,219,0.3); }
form button { background:#3498db; color:white; font-weight:bold; border:none; cursor:pointer; }
form button:hover { background:#2980b9; }
.search-container input { width:100%; padding:0.8rem; border:1px solid #ccc; border-radius:8px; font-size:1rem; margin-bottom:1rem; }
table { width:100%; border-collapse:collapse; font-size:0.95rem; }
table th, table td { padding:1rem; text-align:left; border-bottom:1px solid #eee; }
table th { background:#0d6efd; color:white; font-weight:600; }
.table-responsive { overflow-x:auto; }
footer { margin-top:60px; background:#0d6efd; color:white; text-align:center; padding:20px; font-size:14px; }

/* Responsividade */
@media(max-width:992px){ .container { padding:15px; } }
@media(max-width:768px){
    nav ul { display:none; flex-direction:column; width:100%; margin-top:10px; }
    nav ul.active { display:flex; }
    nav .menu-toggle { display:block; }
    .container { flex-direction:column; }
}
</style>
</head>
<body>

<header>Venda de Medicamentos</header>
<nav>
    <span class="logo">Sistema Farmacêutico</span>
    <span class="menu-toggle">&#9776;</span>
    <ul>
      <li><a href="dashboard.php">🏠 Início</a></li>
      <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
      <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
      <li><a href="venda.php">🛒 Venda</a></li>
      <li><a href="historico.php">📈 Histórico</a></li>
      <li><a href="estoque.php">📦 Estoque</a></li>
      <li><a href="pagina_inicial.php">🚪 Sair</a></li>
    </ul>
</nav>

<div class="container">
    <div class="form-section">
        <form method="post" action="venda.php">
            <label for="id_medicamento">Selecione o Medicamento:</label>
            <select name="id_medicamento" id="id_medicamento" required onchange="updatePreco()">
                <option value="">Escolha um medicamento</option>
                <?php
                $conn = conectar_banco();
                $res = $conn->query("SELECT id, nome, preco, quantidade FROM medicamentos");
                if ($res && $res->num_rows) {
                    while ($m = $res->fetch_assoc()) {
                        echo "<option value='{$m['id']}' data-price='{$m['preco']}'>{$m['nome']} - FCFA " . number_format($m['preco'],2,',','.') . "</option>";
                    }
                } else {
                    echo "<option value=''>Nenhum medicamento cadastrado</option>";
                }
                $conn->close();
                ?>
            </select>
            <label for="preco_unitario">Preço Unitário:</label>
            <input type="number" step="0.01" name="preco_unitario" id="preco_unitario" readonly required>
            <label for="quantidade_venda">Quantidade para Venda:</label>
            <input type="number" name="quantidade_venda" id="quantidade_venda" min="1" required>
            <button type="submit" name="vender">Realizar Venda</button>
        </form>
    </div>

    <div class="table-section table-responsive">
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar medicamento..." onkeyup="filtrarMedicamentos()">
        </div>
        <table id="medicamentosTable" class="table table-striped">
            <thead>
                <tr><th>ID</th><th>Nome</th><th>Preço</th><th>Estoque</th></tr>
            </thead>
            <tbody>
                <?php
                $conn = conectar_banco();
                $res = $conn->query("SELECT id, nome, preco, quantidade FROM medicamentos");
                if ($res && $res->num_rows) {
                    while ($m = $res->fetch_assoc()) {
                        echo "<tr><td>{$m['id']}</td><td>{$m['nome']}</td><td>FCFA ".number_format($m['preco'],2,',','.')."</td><td>{$m['quantidade']}</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Nenhum medicamento encontrado.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const menuToggle = document.querySelector('.menu-toggle');
const navUl = document.querySelector('nav ul');
menuToggle.addEventListener('click',()=>{navUl.classList.toggle('active');});

function updatePreco(){
    const sel = document.getElementById('id_medicamento');
    const inp = document.getElementById('preco_unitario');
    inp.value = sel.options[sel.selectedIndex].getAttribute('data-price') || '';
}

function filtrarMedicamentos(){
    const filter = document.getElementById('searchInput').value.toUpperCase();
    const trs = document.getElementById('medicamentosTable').getElementsByTagName('tr');
    for(let i=1;i<trs.length;i++){
        const td = trs[i].getElementsByTagName('td')[1];
        trs[i].style.display = td.textContent.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', updatePreco);
</script>

<footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>
</body>
</html>
