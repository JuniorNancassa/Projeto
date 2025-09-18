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

// Buscar fornecedores
$fornecedores = $conn->query("SELECT id,nome FROM fornecedores ORDER BY nome ASC");

// Cadastrar
if(isset($_POST['cadastrar'])){
    $nome=$conn->real_escape_string($_POST['nome']);
    $descricao=$conn->real_escape_string($_POST['descricao']);
    $quantidade=(int)$_POST['quantidade'];
    $preco=(float)$_POST['preco'];
    $validade=$conn->real_escape_string($_POST['validade']);
    $categoria=$conn->real_escape_string($_POST['categoria']);
    $fornecedor=(int)$_POST['fornecedor'];
    $codigo_barras=$conn->real_escape_string($_POST['codigo_barras']);

    if(strtotime($validade)<strtotime(date("Y-m-d"))){
        echo "<script>alert('Data inválida!'); window.history.back();</script>"; exit;
    }

    $sql="INSERT INTO medicamentos (nome,descricao,quantidade,preco,validade,categoria,fornecedor,codigo_barras)
          VALUES ('$nome','$descricao','$quantidade','$preco','$validade','$categoria','$fornecedor','$codigo_barras')";
    
    if($conn->query($sql)){
        $mensagem="Medicamento cadastrado!";

        // -----------------------------
        // REGISTRAR HISTÓRICO FORNECEDOR
        // -----------------------------
        $data_registro = date("Y-m-d H:i:s");
        $sql_hist="INSERT INTO historico_fornecedores (id_fornecedor, medicamento, quantidade, preco, data_registro)
                   VALUES ('$fornecedor','$nome','$quantidade','$preco','$data_registro')";
        $conn->query($sql_hist);

        // -----------------------------
        // REGISTRAR FLUXO DE CAIXA (saída)
        // -----------------------------
        $tipo_movimento = "saida"; // compra é saída de caixa
        $descricao_mov = "Compra de medicamento: $nome (Fornecedor ID: $fornecedor)";
        $valor_total = $quantidade * $preco;

        $sql_fc="INSERT INTO fluxo_caixa (tipo, descricao, valor, data_movimento)
                 VALUES ('$tipo_movimento','$descricao_mov','$valor_total','$data_registro')";
        $conn->query($sql_fc);

    } else {
        $mensagem="Erro: ".$conn->error;
    }
}

// Atualizar
if(isset($_POST['atualizar'])){
    $id=$_POST['id'];
    $nome=$conn->real_escape_string($_POST['nome']);
    $descricao=$conn->real_escape_string($_POST['descricao']);
    $quantidade_nova=(int)$_POST['quantidade'];
    $preco=(float)$_POST['preco'];
    $validade=$conn->real_escape_string($_POST['validade']);
    $categoria=$conn->real_escape_string($_POST['categoria']);
    $fornecedor=(int)$_POST['fornecedor'];
    $codigo_barras=$conn->real_escape_string($_POST['codigo_barras']);

    // Buscar quantidade antiga
    $res_antiga = $conn->query("SELECT quantidade FROM medicamentos WHERE id='$id'");
    $row_antiga = $res_antiga->fetch_assoc();
    $quantidade_antiga = (int)$row_antiga['quantidade'];

    $sql="UPDATE medicamentos SET nome='$nome',descricao='$descricao',quantidade='$quantidade_nova',
          preco='$preco',validade='$validade',categoria='$categoria',fornecedor='$fornecedor',
          codigo_barras='$codigo_barras' WHERE id='$id'";

    if($conn->query($sql)){
        $mensagem="Medicamento atualizado!";

        // -----------------------------
        // SE A QUANTIDADE AUMENTOU → registrar no histórico e fluxo de caixa
        // -----------------------------
        if($quantidade_nova > $quantidade_antiga){
            $adicionado = $quantidade_nova - $quantidade_antiga;
            $data_registro = date("Y-m-d H:i:s");

            // Histórico fornecedores
            $sql_hist="INSERT INTO historico_fornecedores (id_fornecedor, medicamento, quantidade, preco, data_registro)
                       VALUES ('$fornecedor','$nome','$adicionado','$preco','$data_registro')";
            $conn->query($sql_hist);

            // Fluxo de caixa (saída)
            $tipo_movimento = "saida";
            $descricao_mov = "Abastecimento de medicamento: $nome (Fornecedor ID: $fornecedor)";
            $valor_total = $adicionado * $preco;

            $sql_fc="INSERT INTO fluxo_caixa (tipo, descricao, valor, data_movimento)
                     VALUES ('$tipo_movimento','$descricao_mov','$valor_total','$data_registro')";
            $conn->query($sql_fc);
        }

    } else {
        $mensagem="Erro: ".$conn->error;
    }
}


// Deletar
if(isset($_POST['deletar'])){
    $id=(int)$_POST['id'];
    $conn->query("DELETE FROM medicamentos WHERE id=$id");
    $mensagem="Medicamento removido!";
}

// Buscar para editar
$editar=null;
if(isset($_GET['editar'])){
    $id=(int)$_GET['editar'];
    $res=$conn->query("SELECT * FROM medicamentos WHERE id=$id");
    $editar=$res->fetch_assoc();
}

// Listar medicamentos com fornecedor
$dados=$conn->query("SELECT m.*, f.nome AS fornecedor_nome FROM medicamentos m LEFT JOIN fornecedores f ON m.fornecedor=f.id");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Medicamentos</title>
<!-- Importando icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
.container{max-width:1000px;margin:30px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
.mensagem{text-align:center;color:green;font-weight:bold;margin:15px 0;}
table{width:100%;margin-top:30px;border-collapse:collapse;}
th,td{padding:10px;text-align:left;border:1px solid #ddd;}
th{background:#0d6efd;color:white;}
.btn-editar{background:#3498db;color:white;}
.btn-editar:hover{background:#2980b9;}
.btn-excluir{background:#e74c3c;color:white;}
.btn-excluir:hover{background:#c0392b;}
.vencido{background:#ffcccc;color:#b30000;font-weight:bold;}
.proximo{background:#fff3cd;color:#856404;font-weight:bold;}
@media(max-width:768px){table,thead,tbody,th,td,tr{display:block;}thead{display:none;}tr{margin-bottom:20px;border-bottom:1px solid #ccc;}td{position:relative;padding-left:50%;text-align:right;}td::before{content:attr(data-label);position:absolute;left:10px;width:45%;padding-right:10px;font-weight:bold;text-align:left;}}
#reader{display:none;width:100%;max-width:400px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;}
</style>
</head>
<body>

<nav>
  <div class="logo">💊 Sistema Farmácia</div>
  <button class="menu-toggle" id="btnMenu">☰</button>
  <ul id="menu">
    <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
        <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
        <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Cadastrar Medicamentos</a></li>
        <li><a href="venda.php"> 🛒Venda</a></li>
        <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
        <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
        <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
        <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
  </ul>
</nav>

<div class="container">
<h2><?php echo $editar?"Editar Medicamento":"Cadastrar Medicamento";?></h2>
<?php if($mensagem):?><p class="mensagem"><?php echo $mensagem;?></p><?php endif;?>

<form method="post" onsubmit="return validarData();">
<?php if($editar):?><input type="hidden" name="id" value="<?php echo $editar['id'];?>"><?php endif;?>

<label>Código de Barras:</label>
<div class="input-group mb-3">
  <input type="text" id="codigo_barras" name="codigo_barras" class="form-control" placeholder="Escaneie ou digite manualmente" value="<?php echo $editar['codigo_barras']??'';?>" required>
  <button type="button" id="btnScan" class="btn btn-primary">📷 Escanear</button>
</div>
<div id="reader"></div>

<label>Nome:</label>
<input type="text" id="nome" name="nome" value="<?php echo $editar['nome']??'';?>" class="form-control" required>

<label>Descrição:</label>
<textarea id="descricao" name="descricao" class="form-control" required><?php echo $editar['descricao']??'';?></textarea>

<label>Categoria:</label>
<select id="categoria" name="categoria" class="form-select" required>
<option value="">Selecione</option>
<option value="Analgésico" <?php if(($editar['categoria']??'')=='Analgésico') echo 'selected';?>>Analgésico</option>
<option value="Antibiótico" <?php if(($editar['categoria']??'')=='Antibiótico') echo 'selected';?>>Antibiótico</option>
<option value="Anti-inflamatório" <?php if(($editar['categoria']??'')=='Anti-inflamatório') echo 'selected';?>>Anti-inflamatório</option>
<option value="Antialérgico" <?php if(($editar['categoria']??'')=='Antialérgico') echo 'selected';?>>Antialérgico</option>
<option value="Outro" <?php if(($editar['categoria']??'')=='Outro') echo 'selected';?>>Outro</option>
</select>

<label>Fornecedor:</label>
<select name="fornecedor" class="form-select" required>
<option value="">Selecione um fornecedor</option>
<?php while($f=$fornecedores->fetch_assoc()): ?>
<option value="<?php echo $f['id'];?>" <?php if(($editar['fornecedor']??'')==$f['id']) echo 'selected';?>><?php echo $f['nome'];?></option>
<?php endwhile; ?>
</select>

<label>Quantidade:</label>
<input type="number" name="quantidade" value="<?php echo $editar['quantidade']??'';?>" class="form-control" required>

<label>Preço:</label>
<input type="number" step="0.01" name="preco" value="<?php echo $editar['preco']??'';?>" class="form-control" required>

<label>Validade:</label>
<input type="date" name="validade" value="<?php echo $editar['validade']??'';?>" class="form-control" required>

<button type="submit" name="<?php echo $editar?'atualizar':'cadastrar';?>" class="btn btn-success mt-3"><?php echo $editar?'Atualizar':'Cadastrar';?></button>
</form>

<h2 class="mt-5">Medicamentos Cadastrados</h2>
<table class="table table-bordered table-striped">
<thead>
<tr>
<th>Nome</th><th>Descrição</th><th>Categoria</th><th>Fornecedor</th><th>Quantidade</th><th>Preço</th><th>Validade</th><th>Código Barras</th><th>Ações</th>
</tr>
</thead>
<tbody>
<?php while($m=$dados->fetch_assoc()):
$hoje=date('Y-m-d'); $validade=$m['validade']; $classe=""; $msg="";
if($validade<$hoje){$classe="vencido"; $msg="Vencido";}
elseif((strtotime($validade)-strtotime($hoje))<=2592000){$classe="proximo"; $msg="Vence em menos de 30 dias!";}
?>
<tr>
<td data-label="Nome"><?php echo $m['nome'];?></td>
<td data-label="Descrição"><?php echo $m['descricao'];?></td>
<td data-label="Categoria"><?php echo $m['categoria'];?></td>
<td data-label="Fornecedor"><?php echo $m['fornecedor_nome'];?></td>
<td data-label="Quantidade"><?php echo $m['quantidade'];?></td>
<td data-label="Preço"><?php echo number_format($m['preco'],2,',','.');?></td>
<td data-label="Validade" class="<?php echo $classe;?>"><?php echo date('d/m/Y',strtotime($validade));?><br><small><?php echo $msg;?></small></td>
<td data-label="Código Barras"><?php echo $m['codigo_barras'];?></td>
<td data-label="Ações">
<a href="?editar=<?php echo $m['id'];?>" class="btn btn-editar">✏️</a>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?php echo $m['id'];?>">
<button type="submit" name="deletar" class="btn btn-excluir" onclick="return confirm('Confirma exclusão?')">🗑️</button>
</form>
</td>
</tr>
<?php endwhile;?>
</tbody>
</table>

</div>
<footer class="text-center bg-primary text-white p-3 mt-5">&copy; 2025 Sistema Farmacêutico</footer>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
// Menu hamburger
document.getElementById('btnMenu').addEventListener('click', ()=>{
    document.getElementById('menu').classList.toggle('ativo');
});

// Validação data
function validarData(){
    const d=document.querySelector('input[name="validade"]').value;
    if(d<new Date().toISOString().split('T')[0]){ alert('Data inválida'); return false; }
    return true;
}

// Scanner de QR e códigos de barras
// QR Code scanner seguro
const btnScan = document.getElementById('btnScan');
const reader = document.getElementById('reader');
let html5QrcodeScanner = null;

// Função para iniciar o scanner
async function iniciarScanner(cameraFacing = "environment") {
    if (!reader) return;

    reader.style.display = 'block';

    // Se já houver um scanner ativo, não iniciar outro
    if (html5QrcodeScanner) return;

    html5QrcodeScanner = new Html5Qrcode("reader");

    try {
        await html5QrcodeScanner.start(
            { facingMode: cameraFacing },
            { fps: 10, qrbox: 250 },
            (decodedText) => {
                // Preenche o campo código de barras
                const inputCodigo = document.getElementById('codigo_barras');
                if (inputCodigo) inputCodigo.value = decodedText;

                // Para o scanner com segurança
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.stop()
                        .then(() => {
                            html5QrcodeScanner.clear(); // limpa instância
                            html5QrcodeScanner = null;
                            reader.style.display = 'none';
                        })
                        .catch(() => {
                            html5QrcodeScanner = null;
                            reader.style.display = 'none';
                        });
                }
            },
            (error) => {
                // Erros de leitura podem ser ignorados
                console.log("Erro leitura QR:", error);
            }
        );
    } catch (err) {
        console.warn("Erro ao iniciar scanner:", err);
        if (cameraFacing === "environment") {
            // tenta câmera frontal se traseira falhar
            iniciarScanner("user");
        } else {
            alert("Não foi possível acessar a câmera. Verifique permissões.");
            reader.style.display = 'none';
        }
    }
}

// Clique no botão de scanner
btnScan.addEventListener('click', () => {
    if (!reader) return;

    if (reader.style.display === 'none' || reader.style.display === '') {
        iniciarScanner("environment");
    } else {
        // Para o scanner com segurança
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop()
                .then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                    reader.style.display = 'none';
                })
                .catch(() => {
                    html5QrcodeScanner = null;
                    reader.style.display = 'none';
                });
        } else {
            reader.style.display = 'none';
        }
    }
});
</script>
</body>
</html>
