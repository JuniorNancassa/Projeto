<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bem-vindo | Sistema Farmacêutico</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #0d6efd, #4CAF50);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      text-align: center;
      padding: 20px;
    }

    /* Container principal */
    .container {
      background: rgba(0, 0, 0, 0.3);
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      animation: fadeIn 1s ease-in-out;
      max-width: 500px;
      width: 100%;
      margin-bottom: 30px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h1 {
      font-size: 30px;
      margin-bottom: 15px;
    }

    p {
      font-size: 16px;
      margin-bottom: 25px;
    }

    .btn {
      display: block;
      width: 100%;
      background-color: white;
      color: #0d6efd;
      padding: 12px;
      margin-bottom: 15px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      transition: background 0.3s, transform 0.2s;
    }

    .btn:hover {
      background-color: #f1f1f1;
      transform: scale(1.03);
    }

    .icon {
      font-size: 50px;
      margin-bottom: 20px;
      color: #ffffff;
    }

    .illustration {
      width: 100px;
      margin: 0 auto 20px;
      border-radius: 5px;
    }

    /* Modal Sobre */
    .modal {
      display: none; /* oculto por padrão */
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 999;
    }

    .modal-content {
      background: #fff;
      color: #333;
      padding: 20px;
      border-radius: 12px;
      max-width: 500px;
      width: 90%;
      animation: fadeIn 0.5s ease-in-out;
      text-align: left;
    }

    .modal-content h2 {
      margin-bottom: 10px;
      color: #0d6efd;
    }

    .close-btn {
      float: right;
      font-size: 20px;
      cursor: pointer;
      color: #333;
    }

    /* Menu superior */
    .navbar {
      position: absolute;
      top: 20px;
      left: 20px;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .navbar a {
      color: white;
      font-weight: bold;
      text-decoration: none;
    }

    .hamburger {
      display: none;
      flex-direction: column;
      cursor: pointer;
    }

    .hamburger div {
      width: 25px;
      height: 3px;
      background: white;
      margin: 4px 0;
      transition: 0.3s;
    }

    .nav-links {
      display: flex;
      gap: 20px;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .nav-links {
        display: none;
        position: absolute;
        top: 50px;
        left: 20px;
        flex-direction: column;
        background: rgba(0,0,0,0.8);
        padding: 10px;
        border-radius: 8px;
      }

      .nav-links.active {
        display: flex;
      }

      .hamburger {
        display: flex;
      }
    }

    @media (max-width: 600px) {
      h1 { font-size: 24px; }
      .icon { font-size: 36px; }
      .btn { padding: 10px; }
    }
  </style>
</head>

<body>

  <!-- Navbar 
  <div class="navbar">
    <div class="hamburger" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
    <div class="nav-links" id="navLinks">
      <a href="login.php">Entrar</a>
      <a href="#" onclick="openModal()">Sobre</a>
    </div>
  </div>-->

  <div class="container">
    <img src="novo_cruz.png" alt="Ícone de Farmácia" class="illustration" />
    <div class="icon"><i class="fas fa-capsules"></i></div>
    <h1>Bem-vindo ao Sistema de Gestão Farmacêutica</h1>
    <p>Controle, otimize e monitore sua farmácia com segurança e eficiência.</p>
    <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Entrar</a>
    <button class="btn" onclick="openModal()"><i class="fas fa-info-circle"></i> Sobre o sistema</button>
  </div>

  <!-- Modal Sobre -->
  <div class="modal" id="sobreModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2>Sobre o Sistema</h2>
      <p>Este sistema foi desenvolvido para auxiliar farmácias na gestão de estoque, vendas, validade de medicamentos, usuários e relatórios em PDF. Ele garante eficiência, controle e segurança no atendimento farmacêutico.</p>
    </div>
  </div>

  <script>
    // Abrir modal
    function openModal() {
      document.getElementById("sobreModal").style.display = "flex";
    }

    // Fechar modal
    function closeModal() {
      document.getElementById("sobreModal").style.display = "none";
    }

    // Fechar modal clicando fora
    window.onclick = function(event) {
      const modal = document.getElementById("sobreModal");
      if (event.target === modal) {
        modal.style.display = "none";
      }
    }

    // Menu hambúrguer
    function toggleMenu() {
      document.getElementById("navLinks").classList.toggle("active");
    }
  </script>

</body>
</html>
