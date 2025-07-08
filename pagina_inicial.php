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
    }

    .sobre {
      background: white;
      color: #333;
      border-radius: 10px;
      padding: 20px;
      max-width: 500px;
      width: 100%;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      animation: fadeIn 1.5s ease-in-out;
    }

    .sobre h2 {
      margin-bottom: 10px;
      color: #0d6efd;
    }

    @media (max-width: 600px) {
      h1 { font-size: 24px; }
      .icon { font-size: 36px; }
      .btn { padding: 10px; }
    }
  </style>
</head>

<body>

  <div class="container">
    <img src="far.png" alt="Ícone de Farmácia" class="illustration" />
    <div class="icon"><i class="fas fa-capsules"></i></div>
    <h1>Bem-vindo ao Sistema de Gestão Farmacêutica</h1>
    <p>Controle, otimize e monitore sua farmácia com segurança e eficiência.</p>
    <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Entrar</a>
    <a href="#sobre" class="btn"><i class="fas fa-info-circle"></i> Sobre o sistema</a>
  </div>

  <div class="sobre" id="sobre">
    <h2>Sobre o Sistema</h2>
    <p>Este sistema foi desenvolvido para auxiliar farmácias na gestão de estoque, vendas, validade de medicamentos, usuários e relatórios em PDF. Ele garante eficiência, controle e segurança no atendimento farmacêutico.</p>
  </div>

</body>
</html>
