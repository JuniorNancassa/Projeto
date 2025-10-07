<?php
session_start();
if(!isset($_SESSION['nome_usuario'])) $_SESSION['nome_usuario']="Natypanah Fernando";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Farmacêutico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
  --primary: #2575fc;
  --secondary: #6a11cb;
  --bg: #f4f7fb;
  --text: #2d3436;
  --card-bg: #ffffff;
  --sidebar-bg: #2c3e50;
  --sidebar-hover: #34495e;
}

/* --- Modo escuro --- */
body.dark {
  --bg: #1e1e1e;
  --text: #f4f4f4;
  --card-bg: #2a2a2a;
  --sidebar-bg: #111;
  --sidebar-hover: #222;
}

body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: background 0.3s, color 0.3s, margin-left 0.3s;
}

/* --- Sidebar --- */
.sidebar {
  width: 240px;
  background: var(--sidebar-bg);
  color: white;
  position: fixed;
  height: 100%;
  padding-top: 20px;
  transition: all 0.3s ease;
  overflow-y: auto;
  left: 0;
  z-index: 1000;
}
.sidebar.collapsed { left: -240px; }
.sidebar.active { left: 0; } /* para mobile */
.sidebar li { list-style: none; }
.sidebar a {
  color: white;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  text-decoration: none;
  transition: 0.3s;
}
.sidebar a i { font-size: 18px; }
.sidebar a:hover {
  background: var(--sidebar-hover);
  padding-left: 28px;
}

/* --- Rodapé do menu (modo escuro) --- */
.sidebar-footer {
  position: absolute;
  bottom: 20px;
  width: 100%;
  text-align: center;
}
#dark-mode-toggle {
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 20px;
  padding: 8px 16px;
  cursor: pointer;
  font-size: 15px;
  transition: background 0.3s;
}
#dark-mode-toggle:hover { background: var(--secondary); }

/* --- Main Content --- */
.main {
  margin-left: 240px;
  padding: 40px;
  transition: all 0.3s ease;
}
.main.expanded { margin-left: 0; }

/* --- Hamburger --- */
#hamburger {
  position: fixed;
  top: 3px;
  left: 16px;
  width: 32px;
  height: 26px;
  cursor: pointer;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
#hamburger .bar {
  height: 4px;
  width: 100%;
  background: var(--text);
  border-radius: 2px;
  transition: 0.3s;
}
#hamburger.open .bar:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
#hamburger.open .bar:nth-child(2) { opacity: 0; }
#hamburger.open .bar:nth-child(3) { transform: rotate(-45deg) translate(6px, -6px); }

/* --- Card Boas-vindas --- */
#welcome-card {
  background: linear-gradient(135deg, var(--secondary), var(--primary));
  color: white;
  padding: 20px;
  border-radius: 16px;
  margin-bottom: 30px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.25);
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
  animation: fadeInCard 0.8s forwards;
}
#welcome-card .icon { font-size: 28px; animation: bounce 1.5s infinite; }
#welcome-close {
  position: absolute;
  top: 8px;
  right: 12px;
  cursor: pointer;
  font-weight: bold;
  font-size: 18px;
}
@keyframes fadeInCard { to { opacity: 1; transform: translateY(0); } }
@keyframes bounce { 0%,100%{ transform:translateY(0);}50%{ transform:translateY(-6px);} }

/* --- Cards de resumo --- */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 20px;
}
.card {
  background: var(--card-bg);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  text-align: center;
  transition: transform 0.2s;
}
.card:hover { transform: translateY(-6px); }
.card i {
  font-size: 30px;
  color: var(--primary);
  margin-bottom: 10px;
}

/* --- Chatbot --- */
#chatbot-btn {
  position: fixed;
  bottom: 25px;
  right: 25px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 50%;
  width: 55px;
  height: 55px;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  z-index: 3000;
}
#chatbot-btn:hover { background: var(--secondary); }

.chat-window {
  position: fixed;
  bottom: 90px;
  right: 25px;
  width: 320px;
  height: 420px;
  background: var(--card-bg);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 3001;
}
.chat-window header {
  background: var(--primary);
  color: white;
  text-align: center;
  padding: 10px;
  font-weight: bold;
}
.chat-messages {
  flex: 1;
  padding: 10px;
  overflow-y: auto;
}
.chat-input {
  display: flex;
  border-top: 1px solid #ccc;
}
.chat-input input {
  flex: 1;
  border: none;
  padding: 10px;
  outline: none;
  background: var(--bg);
  color: var(--text);
}
.chat-input button {
  background: var(--primary);
  border: none;
  color: white;
  padding: 10px 14px;
  cursor: pointer;
}

/* --- Responsividade --- */
@media (max-width: 768px) {
  .sidebar {
    left: -240px;
    width: 200px;
  }
  .sidebar.active { left: 0; }
  .main {
    margin-left: 0;
    padding: 15px;
  }
  #hamburger {
    top: 3px;
    left: 10px;
    width: 28px;
  }
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  #welcome-card {
    flex-direction: column;
    text-align: center;
    font-size: 16px;
    top: 28px;
  }
  #chatbot-btn {
    width: 50px;
    height: 50px;
    font-size: 22px;
    bottom: 20px;
    right: 20px;
  }
  .chat-window {
    width: 90%;
    right: 5%;
    bottom: 80px;
    height: 350px;
  }
}
</style>
</head>
<body>

<!-- Botão hamburger -->
<div id="hamburger">
  <span class="bar"></span>
  <span class="bar"></span>
  <span class="bar"></span>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Início</a></li>
  <li><a href="cadastro_usuarios.php"><i class="bi bi-person-fill"></i> Usuários</a></li>
  <li><a href="cadastro_medicamento.php"><i class="bi bi-capsule"></i> Medicamentos</a></li>
  <li><a href="venda.php"><i class="bi bi-cart"></i> Vendas</a></li>
  <li><a href="cadastrar_fornecedor.php"><i class="bi bi-building"></i> Fornecedores</a></li>
  <li><a href="fluxo_caixa.php"><i class="bi bi-cash-stack"></i> Fluxo de Caixa</a></li>
  <li><a href="estoque.php"><i class="bi bi-box-seam"></i> Estoque</a></li>
  <li><a href="historico.php"><i class="bi bi-graph-up"></i> Histórico</a></li>
  <li><a href="historico_fornecedores.php"><i class="bi bi-graph-up"></i> Histórico dos Fornecedores</a></li>
  <li><a href="pagina_inicial.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>

  <div class="sidebar-footer">
    <button id="dark-mode-toggle">🌙 Modo Escuro</button>
  </div>
</div>

<!-- Conteúdo principal -->
<div class="main" id="main">
  <?php if(isset($_SESSION['nome_usuario'])): ?>
  <div id="welcome-card">
    <div class="icon">👋</div>
    <div>Olá, <b><?php echo htmlspecialchars($_SESSION['nome_usuario']); ?></b>! Bem-vindo ao Dashboard.</div>
    <div id="welcome-close">×</div>
  </div>
  <?php endif; ?>

  <h1>📊 Painel de Controle</h1>
  <p>Gerencie estoque, vendas e medicamentos de forma prática.</p>

  <div class="dashboard-grid">
    <div class="card">
      <i class="bi bi-box"></i>
      <h3>Estoque</h3>
      <p>Gerencie seus produtos disponíveis.</p>
    </div>
    <div class="card">
      <i class="bi bi-cart-check"></i>
      <h3>Vendas</h3>
      <p>Acompanhe transações e histórico.</p>
    </div>
    <div class="card">
      <i class="bi bi-person-fill"></i>
      <h3>Usuários</h3>
      <p>Controle os acessos ao sistema.</p>
    </div>
  </div>
</div>

<!-- Botão chatbot -->
<button id="chatbot-btn"><i class="bi bi-chat-dots"></i></button>

<!-- Janela do chatbot -->
<div class="chat-window" id="chatWindow">
  <header>Chatbot Assistente 🤖</header>
  <div class="chat-messages" id="chatMessages">
    <p><strong>Bot:</strong> Olá! Como posso te ajudar hoje?</p>
  </div>
  <div class="chat-input">
    <input type="text" id="userInput" placeholder="Digite uma mensagem...">
    <button id="sendBtn">Enviar</button>
  </div>
</div>

<script>
// --- Sidebar toggle responsivo ---
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");

hamburger.addEventListener("click", () => {
  const isMobile = window.innerWidth <= 768;

  if (isMobile) {
    sidebar.classList.toggle("active"); // para mobile
  } else {
    sidebar.classList.toggle("collapsed"); // para laptop
    main.classList.toggle("expanded");
  }

  hamburger.classList.toggle("open");
});

// --- Modo escuro ---
const darkModeToggle = document.getElementById("dark-mode-toggle");
darkModeToggle.addEventListener("click", () => {
  document.body.classList.toggle("dark");
  darkModeToggle.textContent = document.body.classList.contains("dark") ? "☀️ Modo Claro" : "🌙 Modo Escuro";
  localStorage.setItem("modoEscuro", document.body.classList.contains("dark"));
});
if(localStorage.getItem("modoEscuro") === "true") {
  document.body.classList.add("dark");
  darkModeToggle.textContent = "☀️ Modo Claro";
}

// --- Fechar card boas-vindas ---
const closeBtn = document.getElementById("welcome-close");
if(closeBtn) closeBtn.addEventListener("click", () => document.getElementById("welcome-card").remove());

// --- Chatbot ---
const chatbotBtn = document.getElementById("chatbot-btn");
const chatWindow = document.getElementById("chatWindow");
const sendBtn = document.getElementById("sendBtn");
const chatMessages = document.getElementById("chatMessages");
const userInput = document.getElementById("userInput");

chatbotBtn.addEventListener("click", () => {
  chatWindow.style.display = chatWindow.style.display === "flex" ? "none" : "flex";
  chatWindow.style.flexDirection = "column";
});

sendBtn.addEventListener("click", () => {
  const msg = userInput.value.trim();
  if (msg) {
    const userMsg = document.createElement("p");
    userMsg.innerHTML = `<strong>Você:</strong> ${msg}`;
    chatMessages.appendChild(userMsg);
    userInput.value = "";
    chatMessages.scrollTop = chatMessages.scrollHeight;

    setTimeout(() => {
      const botMsg = document.createElement("p");
      botMsg.innerHTML = `<strong>Bot:</strong> Entendi! 😊 (resposta simulada)`;
      chatMessages.appendChild(botMsg);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 600);
  }
});
</script>
</body>
</html>
