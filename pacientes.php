<?php
// Configurações de conexão com o Supabase (PostgreSQL via PDO)
// Substitua o host abaixo pelo host real de banco de dados do seu painel do Supabase (ex: db.gytbotbtdbgemucmybtm.supabase.co ou o IP/Host do pooler)
$host = "db.gytbotbtdbgemucmybtm.supabase.co"; 
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "sb_publishable_L94mxiPRpFIbejpmJdeCiA_RUyxUGlh";

$mensagem = "";
$pdo = null;

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    // Caso ocorra erro na conexão
    $conexao_erro = "Erro de conexão: " . $e->getMessage();
}

// 1. LÓGICA DE CADASTRO (Quando o formulário é enviado via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'])) {
    if ($pdo) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $idade = !empty($_POST['idade']) ? $_POST['idade'] : null;
        $peso_inicial = !empty($_POST['peso_inicial']) ? $_POST['peso_inicial'] : null;
        $objetivo = $_POST['objetivo'];

        try {
            $sql = "INSERT INTO pacientes (nome, email, telefone, idade, peso_inicial, objetivo) 
                    VALUES (:nome, :email, :telefone, :idade, :peso_inicial, :objetivo)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':telefone' => $telefone,
                ':idade' => $idade,
                ':peso_inicial' => $peso_inicial,
                ':objetivo' => $objetivo
            ]);

            $mensagem = "<div class='alert sucesso'>Paciente cadastrado com sucesso!</div>";
        } catch (PDOException $e) {
            $mensagem = "<div class='alert erro'>Erro ao cadastrar: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensagem = "<div class='alert erro'>Não foi possível salvar pois a conexão com o banco falhou.</div>";
    }
}

// 2. LÓGICA DE LISTAGEM (Buscar todos os pacientes cadastrados)
$pacientes = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM pacientes ORDER BY criado_em DESC");
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_busca = "Erro ao carregar pacientes.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Pacientes - Sistema de Nutricionista</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .grid-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            color: #495057;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .input-group input, .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-primary {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #218838;
        }
        .btn-sm {
            background-color: #007bff;
            color: white;
            padding: 6px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .sucesso { background-color: #d4edda; color: #155724; }
        .erro { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <h1>Painel de Controle - Pacientes</h1>

    <!-- Exibe mensagens de feedback (sucesso ou erro ao salvar) -->
    <?php echo $mensagem; ?>
    <?php if(isset($conexao_erro)) echo "<div class='alert erro'>$conexao_erro</div>"; ?>

    <div class="grid-layout">
        
        <!-- COLUNA DA ESQUERDA: Listagem Dinâmica -->
        <div class="card">
            <h3>Lista de Pacientes Cadastrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Objetivo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pacientes) > 0): ?>
                        <?php foreach ($pacientes as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nome']); ?></td>
                                <?php 
                                    // Mostra telefone ou email como contato principal
                                    $contato = !empty($p['telefone']) ? $p['telefone'] : $p['email'];
                                ?>
                                <td><?php echo htmlspecialchars($contato); ?></td>
                                <td><?php echo htmlspecialchars($p['objetivo']); ?></td>
                                <td><a href="ver_dieta.php?id=<?php echo $p['id']; ?>" class="btn-sm">Ver Dieta</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #777;">Nenhum paciente cadastrado ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- COLUNA DA DIREITA: Formulário de Cadastro (Envia para si mesmo) -->
        <div class="card">
            <h3>Novo Paciente</h3>
            <form action="pacientes.php" method="POST">
                <div class="input-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required placeholder="Ex: Ana Souza">
                </div>

                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="ana@email.com">
                </div>

                <div class="input-group">
                    <label for="telefone">Telefone / WhatsApp</label>
                    <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999">
                </div>

                <div class="input-group">
                    <label for="idade">Idade</label>
                    <input type="number" id="idade" name="idade" placeholder="Anos">
                </div>

                <div class="input-group">
                    <label for5="peso_inicial">Peso Inicial (kg)</label>
                    <input type="number" step="0.1" id="peso_inicial" name="peso_inicial" placeholder="Ex: 70.5">
                </div>

                <div class="input-group">
                    <label for="objetivo">Objetivo</label>
                    <select id="objetivo" name="objetivo">
                        <option value="Emagrecimento">Emagrecimento</option>
                        <option value="Hipertrofia">Hipertrofia</option>
                        <option value="Reeducação Alimentar">Reeducação Alimentar</option>
                        <option value="Manutenção">Manutenção</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Salvar Paciente</button>
            </form>
        </div>

    </div>
</div>

</body>
</html>