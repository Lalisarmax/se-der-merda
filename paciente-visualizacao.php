<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'conexao.php';

$user_id = $_SESSION['user_id'];
$foto_perfil = getFotoPerfil($conn, $user_id);
$user_nome = $_SESSION['user_nome'] ?? 'Usuário';

// Buscar dados do paciente
$dados = [];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $dados = $result->fetch_assoc();
}

// Buscar anotações do paciente
$anotacoes = [];
if (isset($dados['id'])) {
    $sql_anot = "SELECT * FROM anotacoes WHERE paciente_id = ? ORDER BY criado_em DESC LIMIT 5";
    $stmt_anot = $conn->prepare($sql_anot);
    $stmt_anot->bind_param("i", $user_id);
    $stmt_anot->execute();
    $result_anot = $stmt_anot->get_result();
    while ($row = $result_anot->fetch_assoc()) {
        $anotacoes[] = $row;
    }
}

// Buscar medicamentos com horários (mock - você pode criar uma tabela)
$medicamentos = [];
if (!empty($dados['medicamentos'])) {
    $medicamentos = explode(',', $dados['medicamentos']);
}

// Dados mockados para o gráfico (evolução)
$datas = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
$valores = [60, 65, 70, 75, 72, 80]; // exemplo: evolução de bem-estar

// Contato do cuidador (mock)
$cuidador_nome = 'Dra. Ana Silva';
$cuidador_telefone = '(11) 99999-8888';
$cuidador_email = 'ana.silva@homecare.com';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Meu Acompanhamento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0b2b40;
            --primary-light: #1a4b66;
            --secondary: #3a7ca5;
            --secondary-light: #5a9cc5;
            --gray-100: #f5f8fa;
            --gray-200: #e9edf0;
            --gray-600: #4a5b66;
            --gray-800: #1f2a33;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,20,30,0.08);
            --shadow-lg: 0 20px 60px rgba(0,20,30,0.12);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: 0.3s ease;
            --green: #27ae60;
            --red: #e74c3c;
            --orange: #f39c12;
            --gradient-1: linear-gradient(135deg, #0b2b40 0%, #1a4b66 50%, #2d6b8f 100%);
            --gradient-2: linear-gradient(135deg, #3a7ca5 0%, #5a9cc5 100%);
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        
        /* ============================================================
           HEADER
           ============================================================ */
        .header {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo i { color: var(--secondary); font-size: 1.6rem; }
        .logo-subtitle {
            font-weight: 400;
            font-size: 0.7rem;
            color: var(--gray-600);
            background: var(--gray-200);
            padding: 2px 12px;
            border-radius: 20px;
            margin-left: 4px;
        }
        .nav-list {
            display: flex;
            gap: 24px;
            list-style: none;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-list a {
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            padding: 6px 12px;
            border-radius: 30px;
        }
        .nav-list a:hover { color: var(--primary); background: var(--gray-100); }
        .nav-list a.active { color: var(--primary); background: var(--gray-100); font-weight: 600; }
        .btn-cadastro {
            background: var(--primary);
            color: white !important;
            padding: 6px 18px !important;
            border-radius: 30px !important;
        }
        .btn-cadastro:hover { background: var(--primary-light) !important; }
        
        #painelMenu, #perfilMenu, #logoutMenu { display: none; }
        
        .perfil-link {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--gray-600);
            transition: var(--transition);
            padding: 4px 16px 4px 12px;
            border-radius: 40px;
            border: 1px solid transparent;
            background: var(--gray-50);
        }
        .perfil-link:hover {
            border-color: var(--gray-200);
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }
        .perfil-avatar { display: flex; align-items: center; gap: 8px; }
        .perfil-avatar .avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .perfil-avatar i { font-size: 1.8rem; color: var(--secondary); }
        .perfil-nome { font-weight: 500; font-size: 0.85rem; color: var(--gray-800); white-space: nowrap; }
        .logout-btn {
            color: var(--red) !important;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 30px;
            transition: var(--transition);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .logout-btn:hover { background: #fde8e8 !important; color: #c0392b !important; }
        
        /* ============================================================
           HERO
           ============================================================ */
        .dashboard-hero {
            padding: 50px 0 40px;
            background: var(--gradient-1);
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 32px;
        }
        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: rgba(255,255,255,0.05);
            transform: rotate(-20deg);
        }
        .dashboard-hero .container { position: relative; z-index: 1; }
        .dashboard-hero .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .dashboard-hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .dashboard-hero h1 i { color: var(--secondary-light); margin-right: 12px; }
        .dashboard-hero .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 8px 20px 8px 16px;
            border-radius: 60px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .dashboard-hero .user-badge i { font-size: 1.4rem; color: rgba(255,255,255,0.8); }
        .dashboard-hero .user-badge span { font-weight: 500; }
        .dashboard-hero .user-badge .btn-profile {
            color: white;
            background: rgba(255,255,255,0.15);
            border: none;
            border-radius: 30px;
            padding: 4px 14px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-family: inherit;
        }
        .dashboard-hero .user-badge .btn-profile:hover {
            background: rgba(255,255,255,0.25);
        }

        /* ============================================================
           CONTEÚDO
           ============================================================ */
        .dashboard { padding-bottom: 48px; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .card {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--secondary-light);
        }
        .card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card h3 i { color: var(--secondary); }
        .card h3 .toggle-btn {
            margin-left: auto;
            background: var(--gray-200);
            border: none;
            border-radius: 30px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }
        .card h3 .toggle-btn:hover {
            background: var(--secondary);
            color: white;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .item:last-child { border-bottom: none; }
        .item .label { color: var(--gray-600); font-size: 0.85rem; }
        .item .value { font-weight: 500; color: var(--gray-800); }
        
        .anotacao {
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .anotacao:last-child { border-bottom: none; }
        .anotacao .titulo { font-weight: 600; color: var(--primary); }
        .anotacao .desc { font-size: 0.9rem; color: var(--gray-600); margin-top: 4px; }
        .anotacao .data { font-size: 0.75rem; color: var(--gray-600); margin-top: 4px; }
        
        .medicamento-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .medicamento-item .nome { font-weight: 500; }
        .medicamento-item .horario { color: var(--gray-600); font-size: 0.85rem; }
        
        .chart-container {
            margin-top: 12px;
            transition: all 0.3s ease;
        }
        .chart-container.hidden {
            display: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: var(--gray-600);
        }
        .empty-state i { font-size: 2rem; display: block; margin-bottom: 8px; color: var(--gray-300); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-family: inherit;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-800); }
        .btn-secondary:hover { background: var(--gray-300); }
        
        .footer {
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            padding: 24px 0;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-top: auto;
        }
        .footer span { font-weight: 500; color: var(--primary); }

        @media (max-width: 768px) {
            .header .container { flex-direction: column; gap: 10px; }
            .nav-list { gap: 12px; justify-content: center; }
            .dashboard-hero { padding: 30px 0 24px; }
            .dashboard-hero h1 { font-size: 1.6rem; }
            .dashboard-hero .hero-content { flex-direction: column; align-items: flex-start; }
            .grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
            .dashboard-hero h1 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-heartbeat"></i> HomeCare
                <span class="logo-subtitle">SafeLife</span>
            </div>
            <nav>
                <ul class="nav-list">
                    <li><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="servicos.php"><i class="fas fa-briefcase"></i> Serviços</a></li>
                    <li><a href="sobre.php"><i class="fas fa-users"></i> Sobre</a></li>
                    <li><a href="contato.php"><i class="fas fa-envelope"></i> Contato</a></li>
                    
                    <li id="painelMenu" style="display: none;">
                        <a href="paciente-visualizacao.php" id="painelLink" class="active">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Meu Acompanhamento</span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil) && is_file($foto_perfil)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, <?php echo htmlspecialchars($user_nome); ?></span>
                            </div>
                        </a>
                    </li>
                    
                    <li id="logoutMenu" style="display: none;">
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="dashboard-hero">
        <div class="container">
            <div class="hero-content">
                <h1><i class="fas fa-heart"></i> Meu Acompanhamento</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user_nome); ?></span>
                    <a href="perfil.php" class="btn-profile">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard">
        <div class="container">
            <div class="grid-2">
                <!-- ============================================================
                CARD 1: DADOS PESSOAIS
                ============================================================ -->
                <div class="card">
                    <h3><i class="fas fa-id-card"></i> Dados Pessoais</h3>
                    <div class="item">
                        <span class="label">Nome</span>
                        <span class="value"><?php echo htmlspecialchars($dados['nome'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">E-mail</span>
                        <span class="value"><?php echo htmlspecialchars($dados['email'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Telefone</span>
                        <span class="value"><?php echo htmlspecialchars($dados['telefone'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">CPF</span>
                        <span class="value"><?php echo htmlspecialchars($dados['cpf'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Data de Nasc.</span>
                        <span class="value"><?php echo htmlspecialchars($dados['data_nascimento'] ?? 'Não informado'); ?></span>
                    </div>
                </div>

                <!-- ============================================================
                CARD 2: SAÚDE E MEDICAMENTOS
                ============================================================ -->
                <div class="card">
                    <h3><i class="fas fa-notes-medical"></i> Saúde & Medicamentos</h3>
                    <div class="item">
                        <span class="label">Condições</span>
                        <span class="value"><?php echo htmlspecialchars($dados['condicao_saude'] ?? 'Nenhuma'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Plano de Saúde</span>
                        <span class="value"><?php echo htmlspecialchars($dados['plano_saude'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Contato Familiar</span>
                        <span class="value"><?php echo htmlspecialchars($dados['contato_familiar'] ?? 'Não informado'); ?></span>
                    </div>
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--gray-200);">
                        <strong style="font-size:0.85rem;">Medicamentos Atuais</strong>
                        <?php if (!empty($medicamentos)): ?>
                            <div style="margin-top:4px;">
                                <?php foreach ($medicamentos as $med): ?>
                                    <div class="medicamento-item">
                                        <span class="nome"><?php echo htmlspecialchars(trim($med)); ?></span>
                                        <span class="horario"><i class="far fa-clock"></i> 8h, 14h, 20h</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="padding: 12px 0;">
                                <p style="font-size:0.9rem;">Nenhum medicamento cadastrado.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--gray-200);">
                        <a href="perfil.php" class="btn btn-primary" style="width:100%;justify-content:center;">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                    </div>
                </div>

                <!-- ============================================================
                CARD 3: EVOLUÇÃO (GRÁFICO) COM TOGGLE
                ============================================================ -->
                <div class="card" style="grid-column: 1 / -1;">
                    <h3>
                        <i class="fas fa-chart-line"></i> Evolução da Saúde
                        <button class="toggle-btn" id="toggleChartBtn">
                            <i class="fas fa-eye"></i> Ocultar Gráfico
                        </button>
                    </h3>
                    <div class="chart-container" id="chartContainer">
                        <canvas id="evolutionChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- ============================================================
                CARD 4: ANOTAÇÕES RECENTES
                ============================================================ -->
                <div class="card" style="grid-column: 1 / -1;">
                    <h3><i class="fas fa-clock"></i> Anotações Recentes do Cuidador</h3>
                    <?php if (!empty($anotacoes)): ?>
                        <?php foreach ($anotacoes as $anot): ?>
                            <div class="anotacao">
                                <div class="titulo"><?php echo htmlspecialchars($anot['titulo'] ?? 'Sem título'); ?></div>
                                <div class="desc"><?php echo nl2br(htmlspecialchars($anot['descricao'] ?? '')); ?></div>
                                <div class="data">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($anot['criado_em'] ?? 'now')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-notes-medical"></i>
                            <p>Nenhuma anotação registrada ainda.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ============================================================
                CARD 5: CONTATO DO CUIDADOR
                ============================================================ -->
                <div class="card" style="grid-column: 1 / -1;">
                    <h3><i class="fas fa-user-nurse"></i> Meu Cuidador</h3>
                    <div class="item">
                        <span class="label">Nome</span>
                        <span class="value"><?php echo htmlspecialchars($cuidador_nome); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Telefone</span>
                        <span class="value"><?php echo htmlspecialchars($cuidador_telefone); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">E-mail</span>
                        <span class="value"><?php echo htmlspecialchars($cuidador_email); ?></span>
                    </div>
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--gray-200);">
                        <span style="font-size:0.8rem; color:var(--gray-600);">
                            <i class="fas fa-info-circle"></i> Entre em contato com seu cuidador para qualquer dúvida.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 <span>HomeCare</span> · Cuidado que transforma vidas</p>
        </div>
    </footer>

    <script>
        // ============================================================
        // MENU DINÂMICO
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            const isLoggedIn = localStorage.getItem('userLoggedIn') === 'true';
            const userType = localStorage.getItem('userType');
            const userName = localStorage.getItem('userName') || 'Usuário';
            
            const painelMenu = document.getElementById('painelMenu');
            const perfilMenu = document.getElementById('perfilMenu');
            const loginMenu = document.getElementById('loginMenu');
            const logoutMenu = document.getElementById('logoutMenu');
            const cadastroMenu = document.getElementById('cadastroMenu');
            const painelLink = document.getElementById('painelLink');
            const painelTexto = document.getElementById('painelTexto');
            const perfilNome = document.getElementById('perfilNome');

            if (isLoggedIn) {
                if (painelMenu) painelMenu.style.display = 'block';
                if (perfilMenu) perfilMenu.style.display = 'block';
                if (logoutMenu) logoutMenu.style.display = 'block';
                if (loginMenu) loginMenu.style.display = 'none';
                if (cadastroMenu) cadastroMenu.style.display = 'none';
                
                if (painelLink && painelTexto) {
                    if (userType === 'cuidador') {
                        painelLink.href = 'painel-cuidador.php';
                        painelTexto.textContent = 'Painel do Cuidador';
                    } else {
                        painelLink.href = 'paciente-visualizacao.php';
                        painelTexto.textContent = 'Meu Acompanhamento';
                    }
                }
                
                if (perfilNome) {
                    let displayName = userName;
                    if (displayName.length > 18) displayName = displayName.substring(0, 18) + '...';
                    perfilNome.textContent = 'Olá, ' + displayName;
                }
            }

            // ============================================================
            // GRÁFICO DE EVOLUÇÃO
            // ============================================================
            const ctx = document.getElementById('evolutionChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($datas); ?>,
                    datasets: [{
                        label: 'Evolução do Bem-estar (%)',
                        data: <?php echo json_encode($valores); ?>,
                        backgroundColor: 'rgba(58, 124, 165, 0.2)',
                        borderColor: 'rgba(58, 124, 165, 1)',
                        borderWidth: 3,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(58, 124, 165, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: { family: 'Inter', size: 12 },
                                color: '#1f2a33'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // ============================================================
            // TOGGLE GRÁFICO
            // ============================================================
            const toggleBtn = document.getElementById('toggleChartBtn');
            const chartContainer = document.getElementById('chartContainer');
            let chartVisible = true;

            toggleBtn.addEventListener('click', function() {
                chartVisible = !chartVisible;
                if (chartVisible) {
                    chartContainer.classList.remove('hidden');
                    toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Ocultar Gráfico';
                } else {
                    chartContainer.classList.add('hidden');
                    toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Mostrar Gráfico';
                }
            });
        });
    </script>
</body>
</html>
