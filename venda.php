<?php
session_start();
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nome_usuario'])) {
    echo "<script>alert('Você precisa estar logado.'); window.location.href='login.php';</script>";
    exit;
}

define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','farmacia');

function conectar_banco(){
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if($conn->connect_error) die('Falha na conexão: '.$conn->connect_error);
    return $conn;
}

$mensagem = "";
$conn = conectar_banco();

// PROCESSAR VENDA
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vender'])){
    $id_medicamento   = (int)($_POST['id_medicamento'] ?? 0);
    $quantidade_venda = (int)($_POST['quantidade_venda'] ?? 0);
    $preco_unitario   = (float)($_POST['preco_unitario'] ?? 0);
    $valor_recebido   = (float)($_POST['valor_recebido'] ?? 0);
    $id_usuario       = (int)$_SESSION['id_usuario'];
    $subtotal         = $quantidade_venda * $preco_unitario;

    if($valor_recebido < $subtotal){
        $mensagem = "Erro: Valor recebido é inferior ao total da venda!";
    } else {
        $stmt = $conn->prepare("SELECT quantidade, nome FROM medicamentos WHERE id=?");
        $stmt->bind_param('i', $id_medicamento);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows==0){
            $mensagem="Medicamento não encontrado!";
        } else {
            $row = $res->fetch_assoc();
            $estoque_atual = (int)$row['quantidade'];

            if($quantidade_venda > $estoque_atual){
                $mensagem = "Quantidade excede estoque disponível!";
            } else {
                $novo_estoque = $estoque_atual - $quantidade_venda;
                $up = $conn->prepare("UPDATE medicamentos SET quantidade=? WHERE id=?");
                $up->bind_param('ii',$novo_estoque,$id_medicamento);
                $up->execute();
                $up->close();

                $data_venda = date('Y-m-d H:i:s');
                $troco = $valor_recebido - $subtotal;

                $ins = $conn->prepare("INSERT INTO vendas (id_medicamento, quantidade, preco_unitario, id_usuario, subtotal, valor_recebido, troco, data_venda) VALUES (?,?,?,?,?,?,?,?)");
                $ins->bind_param('iiidddds',$id_medicamento,$quantidade_venda,$preco_unitario,$id_usuario,$subtotal,$valor_recebido,$troco,$data_venda);

                if($ins->execute()){
                    $mensagem="Venda realizada! Troco: ".number_format($troco,2,',','.');

                    // ✅ Registrar no fluxo de caixa
                    $descricao = "Venda de medicamento: " . $row['nome'];
                    $valor = $subtotal;
                    $tipo_movimento = "entrada";
                    $fc = $conn->prepare("INSERT INTO fluxo_caixa (tipo, descricao, valor, data_movimento) VALUES (?,?,?,?)");
                    $fc->bind_param('ssds', $tipo_movimento, $descricao, $valor, $data_venda);
                    $fc->execute();
                    $fc->close();
                } else {
                    $mensagem="Erro: ".$ins->error;
                }
                $ins->close();
            }
        }
        $stmt->close();
    }
}

// CARREGAR LISTA DE MEDICAMENTOS
$medicamentos = $conn->query("SELECT id, nome, preco, quantidade FROM medicamentos");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Venda de Medicamentos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f1f4f9;margin:0;}
header{background:#0d6efd;color:white;padding:1.5rem;text-align:center;font-size:1.8rem;font-weight:bold;}
nav{background:#0d6efd;color:white;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;position:relative;}
nav .logo{font-weight:bold;font-size:18px;}
nav ul{list-style:none;display:flex;gap:20px;margin:0;padding:0;}
nav ul li a{color:white;text-decoration:none;font-weight:500;}
nav ul li a:hover{color:#000;}
nav .menu-toggle{display:none;cursor:pointer;font-size:1.8rem;user-select:none;transition:transform 0.3s;}
nav .menu-toggle.active{transform:rotate(90deg);}
.container{display:flex;flex-direction:column;gap:2rem;padding:2rem;max-width:1200px;margin:auto;}
.form-section, .table-section{background:white;padding:2rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
form{display:flex;flex-direction:column;gap:1rem;}
form label{font-weight:600;}
form input, form select, form button{padding:0.8rem;border:1px solid #ccc;border-radius:8px;font-size:1rem;}
form button{background:#3498db;color:white;font-weight:bold;border:none;cursor:pointer;}
form button:hover{background:#2980b9;}
#video{border:1px solid #ccc;border-radius:8px;}
.mensagem{font-weight:bold;color:green;}
.table-responsive{overflow-x:auto;}
footer {
    background: #0d6efd;
    color: white;
    text-align: center;
    padding: 1rem;
    margin-top: 2rem;
    font-weight: 500;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
footer:hover {background: #084298;transition: background 0.3s;}
@media(max-width:768px){
    nav ul{display:none;flex-direction:column;width:100%;margin-top:10px;background:#0d6efd;padding:10px;border-radius:8px;}
    nav ul.active{display:flex;}
    nav .menu-toggle{display:block;}
}
</style>
</head>
<body>

<header>Venda de Medicamentos</header>
<nav>
    <span class="logo">Sistema Farmacêutico</span>
    <span class="menu-toggle">&#9776;</span>
    <ul>
      <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
        <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
        <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Cadastrar Medicamentos</a></li>
        <li><a href="venda.php">🛒 Venda</a></li>
        <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
        <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
        <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
        <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
    </ul>
</nav>

<div class="container">
    <?php if($mensagem): ?><p class="mensagem"><?=$mensagem?></p><?php endif; ?>
    <div class="form-section">
        <form method="post">
            <label>Código de Barras:</label>
            <div class="input-group mb-3">
              <input type="text" id="codigo_barras" name="codigo_barras" class="form-control" placeholder="Escaneie ou digite manualmente" readonly required>
              <button type="button" id="btnScan" class="btn btn-primary">📷 Escanear</button>
            </div>
            <div id="reader" style="display:none; width:100%; max-width:400px; margin-bottom:10px;"></div>

            <label>Selecione o Medicamento:</label>
            <select name="id_medicamento" id="id_medicamento" required onchange="updatePreco()">
                <option value="">Escolha um medicamento</option>
                <?php
                $conn = conectar_banco();
                $res = $conn->query("SELECT id, nome, preco, quantidade FROM medicamentos");
                while($m=$res->fetch_assoc()){
                    echo "<option value='{$m['id']}' data-price='{$m['preco']}'>{$m['nome']} - FCFA ".number_format($m['preco'],2,',','.')."</option>";
                }
                $conn->close();
                ?>
            </select>

            <label>Preço Unitário:</label>
            <input type="number" step="0.01" name="preco_unitario" id="preco_unitario" readonly>

            <label>Quantidade:</label>
            <input type="number" name="quantidade_venda" id="quantidade_venda" min="1" required>

            <label>Valor Recebido:</label>
            <input type="number" step="0.01" name="valor_recebido" id="valor_recebido" required>

            <label>Subtotal:</label>
            <input type="text" id="subtotal" readonly>

            <label>Troco:</label>
            <input type="text" id="troco" readonly>

            <button type="submit" name="vender">Realizar Venda</button>
        </form>
    </div>

    <div class="table-section table-responsive">
        <h3>Vendas Realizadas</h3>
        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Medicamento</th><th>Qtd</th><th>Preço Unit.</th><th>Subtotal</th><th>Recebido</th><th>Troco</th><th>Data</th></tr></thead>
            <tbody>
            <?php
            $conn = conectar_banco();
            $res = $conn->query("SELECT v.id, m.nome, v.quantidade, v.preco_unitario, v.subtotal, v.valor_recebido, v.troco, v.data_venda 
                                 FROM vendas v 
                                 JOIN medicamentos m ON v.id_medicamento=m.id 
                                 ORDER BY v.data_venda DESC");
            while($row=$res->fetch_assoc()){
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nome']}</td>
                    <td>{$row['quantidade']}</td>
                    <td>".number_format($row['preco_unitario'],2,',','.')."</td>
                    <td>".number_format($row['subtotal'],2,',','.')."</td>
                    <td>".number_format($row['valor_recebido'],2,',','.')."</td>
                    <td>".number_format($row['troco'],2,',','.')."</td>
                    <td>{$row['data_venda']}</td>
                </tr>";
            }
            $conn->close();
            ?>
            </tbody>
        </table>
    </div>
</div>

<footer>&copy; 2025 Sistema de Gestão Farmacêutica</footer>

<script>
function updatePreco(){
    const sel = document.getElementById('id_medicamento');
    const preco = parseFloat(sel.options[sel.selectedIndex]?.getAttribute('data-price')) || 0;
    document.getElementById('preco_unitario').value = preco.toFixed(2);
    calcularTotais();
}
function calcularTotais(){
    const preco = parseFloat(document.getElementById('preco_unitario').value) || 0;
    const qtd = parseInt(document.getElementById('quantidade_venda').value) || 0;
    const recebido = parseFloat(document.getElementById('valor_recebido').value) || 0;
    const subtotal = preco * qtd;
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('troco').value = (recebido - subtotal).toFixed(2);
}
document.getElementById('quantidade_venda').addEventListener('input',calcularTotais);
document.getElementById('valor_recebido').addEventListener('input',calcularTotais);

// MENU HAMBURGER
const toggle = document.querySelector('.menu-toggle');
toggle.addEventListener('click', () => {
    document.querySelector('nav ul').classList.toggle('active');
    toggle.textContent = toggle.textContent === "✖" ? "☰" : "✖";
});
</script>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
const btnScan = document.getElementById('btnScan');
const reader = document.getElementById('reader');
let html5QrcodeScanner = null;

function iniciarScanner(cameraFacing = "environment") {
    html5QrcodeScanner = new Html5Qrcode("reader");

    html5QrcodeScanner.start(
        { facingMode: cameraFacing },
        { fps: 10, qrbox: 250 },
        async (decodedText) => {
            console.log("QR Code detectado:", decodedText);

            // Preenche campo com código escaneado
            document.getElementById('codigo_barras').value = decodedText;

            // Consulta medicamento no PHP
            try {
                const resp = await fetch('busca_medicamento.php?codigo=' + encodeURIComponent(decodedText));
                const data = await resp.json();

                if (data.success) {
                    // Seleciona medicamento pelo ID retornado
                    const select = document.getElementById('id_medicamento');
                    select.value = data.id;

                    // Preenche preço unitário
                    document.getElementById('preco_unitario').value = parseFloat(data.preco).toFixed(2);

                    // Recalcula totais
                    calcularTotais();
                } else {
                    alert(data.msg || "Medicamento não encontrado.");
                }
            } catch (e) {
                alert("Erro ao buscar medicamento: " + e.message);
            }

            // Para o scanner depois da leitura
            await html5QrcodeScanner.stop().catch(() => {});
            reader.style.display = 'none';
        },
        (error) => {
            // Erros de leitura podem ser ignorados
            console.log("Erro leitura QR:", error);
        }
    ).catch(async (err) => {
        console.warn("Erro ao iniciar com", cameraFacing, ":", err);

        if (cameraFacing === "environment") {
            // tenta frontal se traseira falhar
            await iniciarScanner("user");
        } else {
            alert("Não foi possível acessar nenhuma câmera. Verifique permissões.");
            reader.style.display = 'none';
        }
    });
}

btnScan.addEventListener('click', () => {
    if (reader.style.display === 'none') {
        reader.style.display = 'block';
        iniciarScanner("environment"); // tenta traseira primeiro
    } else {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().catch(() => {});
        }
        reader.style.display = 'none';
    }
});
</script>

</script>


</body>
</html>
