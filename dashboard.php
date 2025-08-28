<?php
session_start();
// Simulação de usuário logado
if(!isset($_SESSION['nome_usuario'])) $_SESSION['nome_usuario']="Natypanah Fernando";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Farmacêutico</title>
<style>
body { margin:0; font-family: Arial, sans-serif; background:#ecf0f1; transition: margin-left 0.3s; }

/* --- Sidebar --- */
.sidebar {
     width:220px; 
     background:#2c3e50;
     color:white;
     position:fixed;
     height:100%; 
     padding-top:20px;
     transition: width 0.3s; 
     overflow:hidden; 
  }
.sidebar.collapsed { width:60px; }
.sidebar a { 
    color:white;
     display:block;
      padding:12px 20px;
      text-decoration:none; 
      white-space:nowrap; 
      overflow:hidden; 
      text-overflow:ellipsis;
       transition:0.3s; }
.sidebar a:hover{ 
    background:#34495e;
 }
.main{
     margin-left:220px; 
     padding:20px; 
     transition: 
     margin-left 0.3s; }
.main.collapsed{ margin-left:60px; }

/* --- Hamburger animado ajustado --- */
#hamburger{
  position: fixed; top: 3px; left: 25px; /* Ajuste do topo */
  width: 35px; height: 25px; 
  cursor: pointer; 
  z-index: 10001; 
  display: flex; flex-direction: column; justify-content: space-between;
}
#hamburger .bar{
  height: 4px; width: 100%; 
  background: #ffffff; /* Barras brancas visíveis */
  border-radius: 2px; 
  transition: 0.3s;
}
#hamburger.open .bar:nth-child(1){ transform: rotate(45deg) translate(5px,5px);}
#hamburger.open .bar:nth-child(2){ opacity:0; }
#hamburger.open .bar:nth-child(3){ transform: rotate(-45deg) translate(6px,-6px);}

/* --- Card de boas-vindas --- */
#welcome-card{
  background: linear-gradient(135deg, #6a11cb, #2575fc);
  color:white; padding:20px; border-radius:16px; margin-bottom:20px;
  box-shadow:0 8px 20px rgba(0,0,0,0.25); font-size:18px; display:flex; align-items:center; gap:12px;
  opacity:0; transform:translateY(-20px); animation:fadeInCard 0.8s forwards;
  position:relative;
}
#welcome-card .icon{ font-size:28px; animation:bounce 1.5s infinite; }
#welcome-close{ position:absolute; top:8px; right:12px; cursor:pointer; font-weight:bold; font-size:18px; }

@keyframes fadeInCard{ to{ opacity:1; transform:translateY(0); } }
@keyframes bounce{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }

/* --- Botão flutuante do Chatbot --- */
#chatbot-button{ position: fixed; bottom:25px; right:25px; background:#007bff; color:white; font-size:26px; padding:16px; border-radius:50%; cursor:pointer; box-shadow:0 6px 15px rgba(0,0,0,0.3); z-index:9999; transition: transform 0.3s, box-shadow 0.3s; }
#chatbot-button:hover{ transform: scale(1.1); box-shadow:0 8px 20px rgba(0,0,0,0.4);}
#chatbot-button.new-msg{ animation:pulse 1s infinite; }
@keyframes pulse{0%{transform:scale(1);}50%{transform:scale(1.2);}100%{transform:scale(1);}}

/* --- Chatbot container --- */
#chatbot-container{ position:fixed; bottom:90px; right:25px; width:320px; max-height:420px; background:#fff; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,0.35); display:flex; flex-direction:column; overflow:hidden; z-index:10000; transform:translateY(50px); opacity:0; transition:transform 0.3s ease, opacity 0.3s ease; }
#chatbot-container.active{ transform:translateY(0); opacity:1; }
#chatbot-header{ background:#007bff; color:white; padding:12px; font-weight:bold; display:flex; justify-content:space-between; align-items:center; font-size:16px; }
#chatbot-close{ cursor:pointer; font-size:20px; transition:0.2s; }
#chatbot-close:hover{ color:#ffdddd; }
#chatbot-messages{ flex:1; padding:12px; overflow-y:auto; background:#f7f7f7; display:flex; flex-direction:column; gap:6px; }
#chatbot-input-area{ display:flex; border-top:1px solid #ddd; }
#chatbot-input{ flex:1; padding:10px; border:none; outline:none; font-size:14px; }
#chatbot-send{ background:#007bff; color:white; border:none; padding:10px 14px; cursor:pointer; font-size:16px; transition:0.2s; }
#chatbot-send:hover{ background:#0056b3; }
.message{ padding:8px 12px; border-radius:12px; max-width:80%; word-wrap:break-word; opacity:0; transform:translateY(10px); animation:fadeInUp 0.3s forwards;}
.message.bot{ background:#e0e0e0; align-self:flex-start; }
.message.user{ background:#007bff; color:white; align-self:flex-end; }
#typing-indicator{ display:none; font-size:14px; color:#555; align-self:flex-start; padding:6px; font-style:italic; }
@keyframes fadeInUp{ to{ opacity:1; transform:translateY(0); } }

/* Responsivo */
@media(max-width:480px){
  .sidebar{ width:60px; }
  .main{ margin-left:60px; padding:10px; }
  #chatbot-container{ width:260px; right:10px; }
}
</style>
</head>
<body>

<!-- Hamburger topo -->
<div id="hamburger">
  <span class="bar"></span>
  <span class="bar"></span>
  <span class="bar"></span>
</div>

<div class="sidebar" id="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="estoque.php">Estoque</a>
  <a href="venda.php">Vendas</a>
  <a href="cadastro_medicamento.php">Medicamentos</a>
  <a href="cadastro_usuarios.php">Usuários</a>
  <a href="pagina_inicial.php">Sair</a>
</div>

<div class="main" id="main">
  <!-- Card de boas-vindas -->
  <?php if(isset($_SESSION['nome_usuario'])): ?>
    <div id="welcome-card">
      <div class="icon">👋</div>
      <div>Olá, <b><?php echo htmlspecialchars($_SESSION['nome_usuario']); ?></b>! Bem-vindo ao Dashboard.</div>
      <div id="welcome-close">×</div>
    </div>
  <?php endif; ?>

  <h1>Bem-vindo ao Dashboard</h1>
  <p>Gerencie estoque, vendas e medicamentos de forma prática.</p>
</div>

<!-- Chatbot -->
<div id="chatbot-button">💬</div>
<div id="chatbot-container">
  <div id="chatbot-header">
    💊 Assistente Farmácia
    <span id="chatbot-close">×</span>
  </div>
  <div id="chatbot-messages">
    <div class="message bot">Olá! Pergunte sobre estoque, vendas ou medicamentos.</div>
    <div id="typing-indicator">Digitando...</div>
  </div>
  <div id="chatbot-input-area">
    <input type="text" id="chatbot-input" placeholder="Digite sua mensagem...">
    <button id="chatbot-send">➤</button>
  </div>
</div>

<audio id="notification-sound" src="https://www.myinstants.com/media/sounds/notification.mp3" preload="auto"></audio>

<script>
// Hamburger e toggle sidebar
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");

hamburger.addEventListener("click", ()=>{
  sidebar.classList.toggle("collapsed");
  main.classList.toggle("collapsed");
  hamburger.classList.toggle("open");
});

// Card de boas-vindas
const welcomeCard = document.getElementById("welcome-card");
const welcomeClose = document.getElementById("welcome-close");
if(welcomeCard){
  welcomeClose.addEventListener("click", ()=>{ welcomeCard.style.display="none"; });
  setTimeout(()=>{
    welcomeCard.style.transition="opacity 1s";
    welcomeCard.style.opacity=0;
    setTimeout(()=>{ welcomeCard.style.display="none"; },1000);
  },5000);
}

// Chatbot
const chatbotButton = document.getElementById("chatbot-button");
const chatbotContainer = document.getElementById("chatbot-container");
const chatbotClose = document.getElementById("chatbot-close");
const chatbotSend = document.getElementById("chatbot-send");
const chatbotInput = document.getElementById("chatbot-input");
const chatbotMessages = document.getElementById("chatbot-messages");
const typingIndicator = document.getElementById("typing-indicator");
const notificationSound = document.getElementById("notification-sound");

let chatHistory = JSON.parse(localStorage.getItem("chatHistory")||"[]");
chatHistory.forEach(item=>{ addMessage(item.sender, item.text, false); });

chatbotButton.addEventListener("click", ()=>{
  chatbotContainer.classList.add("active");
  chatbotButton.style.display="none";
  chatbotButton.classList.remove("new-msg");
  setTimeout(()=>{ chatbotInput.focus(); }, 300);
});
chatbotClose.addEventListener("click", ()=>{
  chatbotContainer.classList.remove("active");
  setTimeout(()=>{ chatbotButton.style.display="block"; }, 300);
});
chatbotSend.addEventListener("click", sendMessage);
chatbotInput.addEventListener("keypress",(e)=>{ if(e.key==="Enter") sendMessage(); });

function addMessage(sender,text,save=true){
  typingIndicator.style.display="none";
  let msg = document.createElement("div");
  msg.classList.add("message",sender);
  chatbotMessages.appendChild(msg);
  chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  if(sender==="bot"){ let i=0; let interval = setInterval(()=>{ msg.innerText+=text[i]; i++; chatbotMessages.scrollTop = chatbotMessages.scrollHeight; if(i>=text.length) clearInterval(interval); },20); }
  else{ msg.innerText=text; }
  if(save) saveChat(sender,text);
}
function saveChat(sender,text){ chatHistory.push({sender,text}); localStorage.setItem("chatHistory",JSON.stringify(chatHistory)); }
function sendMessage(){
  let text = chatbotInput.value.trim();
  if(!text) return;
  addMessage("user",text);
  chatbotInput.value="";
  typingIndicator.style.display="block";
  fetch("chatbot_handler.php",{ method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify({message:text}) })
  .then(res=>res.json())
  .then(data=>{ if(!chatbotContainer.classList.contains("active")){ chatbotButton.classList.add("new-msg"); notificationSound.play(); } addMessage("bot",data.reply); })
  .catch(err=>{ addMessage("bot","⚠️ Erro ao conectar com a IA."); });
}
</script>

</body>
</html>
