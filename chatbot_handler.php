<?php
header("Content-Type: application/json; charset=utf-8");

// Recebe a mensagem do frontend
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = strtolower(trim($data["message"] ?? ""));

if (!$userMessage) {
    echo json_encode(["reply" => "Mensagem vazia."]);
    exit;
}

// 🔹 Conexão com banco
$mysqli = new mysqli("localhost","root","","farmacia");
if ($mysqli->connect_errno) {
    echo json_encode(["reply" => "⚠️ Erro ao conectar ao banco de dados: ".$mysqli->connect_error]);
    exit;
}

function consultaBanco($mysqli, $sql){
    $res = $mysqli->query($sql);
    return ($res && $res->num_rows>0) ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// 🔹 Respostas pré-definidas
$resposta = "";
if(strpos($userMessage,"estoque")!==false){
    preg_match("/estoque do (.+)/",$userMessage,$matches);
    if(isset($matches[1])){
        $med = $mysqli->real_escape_string($matches[1]);
        $dados = consultaBanco($mysqli,"SELECT nome,quantidade FROM medicamentos WHERE nome LIKE '%$med%'");
        $resposta = !empty($dados) ? "📦 O estoque de {$dados[0]['nome']} é de {$dados[0]['quantidade']} unidades." : "❌ Não encontrei esse medicamento no estoque.";
    } else {
        $dados = consultaBanco($mysqli,"SELECT nome,quantidade FROM medicamentos ORDER BY quantidade ASC LIMIT 5");
        $resposta = "📊 Medicamentos com menor estoque:\n";
        foreach($dados as $d){ $resposta.="- {$d['nome']}: {$d['quantidade']} unidades\n"; }
    }
    echo json_encode(["reply"=>$resposta]); exit;
}
elseif(strpos($userMessage,"venda")!==false){
    if(strpos($userMessage,"hoje")!==false){
        $dados = consultaBanco($mysqli,"SELECT SUM(total) as total_vendas FROM vendas WHERE DATE(data_venda)=CURDATE()");
        $resposta = "💰 Total de vendas hoje: ".($dados[0]['total_vendas']??0)." CFA.";
    } elseif(strpos($userMessage,"usuario")!==false){
        preg_match("/usuario (.+)/",$userMessage,$matches);
        if(isset($matches[1])){
            $user = $mysqli->real_escape_string($matches[1]);
            $dados = consultaBanco($mysqli,"SELECT u.nome,SUM(v.total) as total_vendas FROM vendas v JOIN usuarios u ON v.id_usuario=u.id WHERE u.nome LIKE '%$user%' GROUP BY u.nome");
            $resposta = !empty($dados) ? "👤 O usuário {$dados[0]['nome']} vendeu um total de {$dados[0]['total_vendas']} CFA." : "❌ Não encontrei vendas desse usuário.";
        } else { $resposta = "Por favor, informe o nome do usuário."; }
    } else {
        $dados = consultaBanco($mysqli,"SELECT m.nome,SUM(iv.quantidade) as qtd FROM itens_venda iv JOIN medicamentos m ON iv.id_medicamento=m.id GROUP BY m.nome ORDER BY qtd DESC LIMIT 5");
        $resposta = "🔥 Medicamentos mais vendidos:\n";
        foreach($dados as $d){ $resposta.="- {$d['nome']}: {$d['qtd']} unidades\n"; }
    }
    echo json_encode(["reply"=>$resposta]); exit;
}
elseif(strpos($userMessage,"medicamento")!==false){
    $dados = consultaBanco($mysqli,"SELECT nome,preco FROM medicamentos LIMIT 5");
    $resposta = "💊 Alguns medicamentos cadastrados:\n";
    foreach($dados as $d){ $resposta.="- {$d['nome']} (Preço: {$d['preco']} CFA)\n"; }
    echo json_encode(["reply"=>$resposta]); exit;
}

// 🔹 Integração OpenAI GPT
$apiKey = "SUA_API_KEY_AQUI"; // <-- substitua aqui
$url = "https://api.openai.com/v1/chat/completions";

$postData = [
    "model"=>"gpt-3.5-turbo",
    "messages"=>[
        ["role"=>"system","content"=>"Você é um assistente farmacêutico para ajudar o usuário."],
        ["role"=>"user","content"=>$userMessage]
    ],
    "max_tokens"=>200,
    "temperature"=>0.7
];

$ch = curl_init($url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_HTTPHEADER,[
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($postData));

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if(!$response){
    echo json_encode(["reply"=>"⚠️ Erro ao conectar com a IA. cURL Error: $curlError"]);
    exit;
}

// Decodifica JSON e verifica segurança
$result = json_decode($response,true);
if(isset($result["choices"][0]["message"]["content"])){
    $resposta = $result["choices"][0]["message"]["content"];
} elseif(isset($result["choices"][0]["text"])){
    $resposta = $result["choices"][0]["text"];
} else {
    $resposta = "⚠️ Não consegui interpretar a resposta da IA. HTTP Code: $httpCode";
    // DEBUG opcional:
    // file_put_contents("debug_openai.json",$response);
}

echo json_encode(["reply"=>$resposta]);
exit;
?>
