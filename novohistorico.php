<?php
$servername = "localhost";
$username = "root"; // Usuário padrão do XAMPP
$password = ""; // Senha padrão vazia no XAMPP
$dbname = "farmacia"; // Substitua pelo nome do seu banco de dados

// Criando a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>

<?php
date_default_timezone_set('Africa/Bissau');
$data_hoje = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Vendas</title>
    <style>
        /* Reset basic styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body and general styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
}

header {
    background-color: #2a3d5f;
    padding: 10px 0;
}

nav ul {
    display: flex;
    justify-content: center;
    list-style: none;
}

nav ul li {
    margin: 0 15px;
}

nav ul li a {
    text-decoration: none;
    color: #fff;
    font-weight: bold;
    padding: 10px 20px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

nav ul li a:hover {
    background-color: #ffcc00;
}

/* Main content styling */
main {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h1 {
    font-size: 1.8em;
    color: #2a3d5f;
    margin-bottom: 20px;
}

p {
    font-size: 1.1em;
    margin: 10px 0;
}

.success {
    color: green;
}

.error {
    color: red;
}

.info {
    color: #2a3d5f;
}

/* Footer styling */
footer {
    text-align: center;
    padding: 20px;
    background-color: #2a3d5f;
    color: white;
}

/* Responsive styling */
@media (max-width: 768px) {
    nav ul {
        flex-direction: column;
    }

    nav ul li {
        margin: 5px 0;
    }

    main {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.5em;
    }

    p {
        font-size: 1em;
    }
}

    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="dashboard.php">Início</a></li>
                <li><a href="cadastro_medicamento.php">Cadastrar Medicamento</a></li>
                <li><a href="venda.php">Realizar Venda</a></li>
                <li><a href="historico.php">Histórico de Vendas</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="content">
            <h1>Gerar Histórico de Vendas</h1>
            <?php
            // Verificar se já existe um histórico para hoje
            $sql_verificar = "SELECT id FROM historico_vendas WHERE data = ?";
            $stmt_verificar = $conn->prepare($sql_verificar);
            $stmt_verificar->bind_param('s', $data_hoje);
            $stmt_verificar->execute();
            $stmt_verificar->store_result();

            if ($stmt_verificar->num_rows == 0) {
                // Se não existir, cria um novo registro para hoje
                $sql_inserir = "INSERT INTO historico_vendas (data, total_vendas, total_receita) VALUES (?, 0, 0)";
                $stmt_inserir = $conn->prepare($sql_inserir);
                $stmt_inserir->bind_param('s', $data_hoje);
                if ($stmt_inserir->execute()) {
                    echo "<p class='success'>Histórico de vendas para hoje criado com sucesso.</p>";
                } else {
                    echo "<p class='error'>Erro ao criar histórico de vendas: " . $stmt_inserir->error . "</p>";
                }
                $stmt_inserir->close();
            } else {
                echo "<p class='info'>O histórico de vendas para hoje já existe.</p>";
            }

            $stmt_verificar->close();
            $conn->close();
            ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Sistema de Gestão Farmacêutica</p>
    </footer>
</body>
</html>
