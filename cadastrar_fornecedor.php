<?php
session_start();

// Proteção admin
if(!isset($_SESSION['usuario_logado']) || $_SESSION['tipo_usuario']!=='admin'){
    echo "<script>alert('Você não tem permissão!'); window.location.href='dashboard.php';</script>";
    exit;
}

// Conexão
$conn = new mysqli("localhost","root","","farmacia");
if($conn->connect_error) die("Erro: ".$conn->connect_error);

// Mensagem
$mensagem="";

// Cadastrar
if(isset($_POST['cadastrar'])){
    $nome=$conn->real_escape_string($_POST['nome']);
    $telefone=$conn->real_escape_string($_POST['telefone']);
    $email=$conn->real_escape_string($_POST['email']);
    $endereco=$conn->real_escape_string($_POST['endereco']);

    $sql="INSERT INTO fornecedores (nome,telefone,email,endereco,criado_em) 
          VALUES ('$nome','$telefone','$email','$endereco',NOW())";
    $mensagem=$conn->query($sql)?"Fornecedor cadastrado!":"Erro: ".$conn->error;
}

// Atualizar
if(isset($_POST['atualizar'])){
    $id=(int)$_POST['id'];
    $nome=$conn->real_escape_string($_POST['nome']);
    $telefone=$conn->real_escape_string($_POST['telefone']);
    $email=$conn->real_escape_string($_POST['email']);
    $endereco=$conn->real_escape_string($_POST['endereco']);

    $sql="UPDATE fornecedores SET nome='$nome', telefone='$telefone', email='$email', endereco='$endereco' WHERE id=$id";
    $mensagem=$conn->query($sql)?"Atualizado!":"Erro: ".$conn->error;
}

// Deletar
if(isset($_POST['deletar'])){
    $id=(int)$_POST['id'];
    $conn->query("DELETE FROM fornecedores WHERE id=$id");
    $mensagem="Fornecedor removido!";
}

// Buscar para editar
$editar=null;
if(isset($_GET['editar'])){
    $id=(int)$_GET['editar'];
    $res=$conn->query("SELECT * FROM fornecedores WHERE id=$id");
    $editar=$res->fetch_assoc();
}

// Listar fornecedores
$dados=$conn->query("SELECT * FROM fornecedores ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Fornecedores</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f7f7f7;}
nav{background:#0d6efd;color:white;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
nav .logo{font-weight:bold;font-size:18px;}
nav ul{list-style:none;display:flex;gap:20px;padding-left:0;margin:0;flex-wrap:wrap;}
nav ul li a{color:white;text-decoration:none;font-weight:500;}
nav ul li a:hover{color:#0d0e0d;}
.menu-toggle{display:none;font-size:26px;background:none;border:none;color:white;cursor:pointer;}
@media(max-width:768px){
    nav{flex-direction:column;align-items:flex-start;}
    .menu-toggle{display:block;margin-top:10px;}
    nav ul{display:none;width:100%;flex-direction:column;gap:10px;padding:10px 0;}
    nav ul.ativo{display:flex;}
    nav ul li{width:100%;}
    nav ul li a{display:block;padding:10px;width:100%;background:#0d6efd;border-top:1px solid rgba(255,255,255,0.2);}
}
.container{max-width:800px;margin:30px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
.mensagem{text-align:center;color:green;font-weight:bold;margin:15px 0;}
table{width:100%;margin-top:30px;border-collapse:collapse;}
th,td{padding:10px;text-align:left;border:1px solid #ddd;}
th{background:#0d6efd;color:white;}
.btn-editar{background:#3498db;color:white;}
.btn-editar:hover{background:#2980b9;}
.btn-excluir{background:#e74c3c;color:white;}
.btn-excluir:hover{background:#c0392b;}
@media(max-width:768px){table,thead,tbody,th,td,tr{display:block;}thead{display:none;}tr{margin-bottom:20px;border-bottom:1px solid #ccc;}td{position:relative;padding-left:50%;text-align:right;}td::before{content:attr(data-label);position:absolute;left:10px;width:45%;padding-right:10px;font-weight:bold;text-align:left;}}
</style>
</head>
<body>

<nav>
  <div class="logo">💼 Sistema Farmácia</div>
  <button class="menu-toggle" id="btnMenu">☰</button>
  <ul id="menu">
    <li><a href="dashboard.php">🏠 Início</a></li>
    <li><a href="cadastro_usuarios.php">👤 Usuários</a></li>
    <li><a href="cadastro_medicamento.php">💊 Medicamentos</a></li>
    <li><a href="cadastro_fornecedor.php">📦 Fornecedores</a></li>
    <li><a href="venda.php">🛒 Venda</a></li>
    <li><a href="historico.php">📈 Histórico</a></li>
    <li><a href="estoque.php">📦 Estoque</a></li>
    <li><a href="pagina_inicial.php">🚪 Sair</a></li>
  </ul>
</nav>

<div class="container">
<h2><?php echo $editar?"Editar Fornecedor":"Cadastrar Fornecedor";?></h2>
<?php if($mensagem):?><p class="mensagem"><?php echo $mensagem;?></p><?php endif;?>

<form method="post" onsubmit="return validarFormulario();">
<?php if($editar):?><input type="hidden" name="id" value="<?php echo $editar['id'];?>"><?php endif;?>

<label>Nome:</label>
<input type="text" name="nome" value="<?php echo $editar['nome']??'';?>" class="form-control" required>

<label>Telefone:</label>
<input type="text" id="telefone" name="telefone" value="<?php echo $editar['telefone']??'';?>" class="form-control" maxlength="20" placeholder="Apenas números">

<label>Email:</label>
<input type="email" id="email" name="email" value="<?php echo $editar['email']??'';?>" class="form-control">

<label>Endereço:</label>
<input type="text" name="endereco" value="<?php echo $editar['endereco']??'';?>" class="form-control">

<button type="submit" name="<?php echo $editar?'atualizar':'cadastrar';?>" class="btn btn-success mt-3"><?php echo $editar?'Atualizar':'Cadastrar';?></button>
</form>

<h2 class="mt-5">Fornecedores Cadastrados</h2>
<table class="table table-bordered table-striped">
<thead>
<tr><th>Nome</th><th>Telefone</th><th>Email</th><th>Endereço</th><th>Ações</th></tr>
</thead>
<tbody>
<?php while($f=$dados->fetch_assoc()): ?>
<tr>
<td data-label="Nome"><?php echo $f['nome'];?></td>
<td data-label="Telefone"><?php echo $f['telefone'];?></td>
<td data-label="Email"><?php echo $f['email'];?></td>
<td data-label="Endereço"><?php echo $f['endereco'];?></td>
<td data-label="Ações">
<a href="?editar=<?php echo $f['id'];?>" class="btn btn-editar">✏️</a>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?php echo $f['id'];?>">
<button type="submit" name="deletar" class="btn btn-excluir" onclick="return confirm('Confirma exclusão?')">🗑️</button>
</form>
</td>
</tr>
<?php endwhile;?>
</tbody>
</table>
</div>

<footer class="text-center bg-primary text-white p-3 mt-5">&copy; 2025 Sistema Farmacêutico</footer>

<script>
// Menu hamburger
document.getElementById('btnMenu').addEventListener('click', ()=>{
    document.getElementById('menu').classList.toggle('ativo');
});

// Validação de formulário
function validarFormulario(){
    const telefone = document.getElementById('telefone').value.trim();
    const email = document.getElementById('email').value.trim();

    // Apenas números no telefone
    const regexTel = /^[0-9]{8,20}$/;
    if(telefone && !regexTel.test(telefone)){
        alert("Telefone inválido! Apenas números (mínimo 8 dígitos).");
        return false;
    }

    // Validação de email
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(email && !regexEmail.test(email)){
        alert("Email inválido!");
        return false;
    }

    return true;
}

// Evita digitar letras no telefone
document.getElementById('telefone').addEventListener('input', function(e){
    this.value = this.value.replace(/[^0-9]/g,'');
});
</script>
</body>
</html>
