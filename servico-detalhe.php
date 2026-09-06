<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}

$servico_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$servico = null;

if ($servico_id > 0) {
    $sql = "SELECT * FROM servicos WHERE id = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $servico = $result->fetch_assoc();
    }
}

if (!$servico) {
    header('Location: servicos.php');
    exit;
}

$servicos_relacionados = [];
$sql_rel = "SELECT * FROM servicos WHERE ativo = 1 AND id != ? LIMIT 3";
$stmt_rel = $conn->prepare($sql_rel);
$stmt_rel->bind_param("i", $servico_id);
$stmt_rel->execute();
$result_rel = $stmt_rel->get_result();
while ($row = $result_rel->fetch_assoc()) {
    $servicos_relacionados[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · <?php echo htmlspecialchars($servico['nome']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0b2b40;
            --primary-light: #1a4b66;
            --secondary: #3a7ca5;
            --gray-50: #fafbfc;
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
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --red: #e74c3c;
            --green: #27ae60;
            --orange: #f39c12;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 60px 0 40px;
        }
        .page-header .breadcrumb {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 12px;
        }
        .page-header .breadcrumb a {
            color: white;
            text-decoration: none;
        }
        .page-header .breadcrumb a:hover { text-decoration: underline; }
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .page-header .icon {
            font-size: 4rem;
            color: rgba(255,255,255,0.2);
            float: right;
        }
        
        .section { padding: 60px 0; }
        
        .servico-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }
        .servico-desc h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 16px;
        }
        .servico-desc p {
            color: var(--gray-600);
            margin-bottom: 16px;
        }
        .servico-desc .beneficios {
            background: var(--gray-50);
            padding: 20px 24px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            margin: 16px 0;
        }
        .servico-desc .beneficios h3 {
            color: var(--primary);
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .servico-desc .beneficios ul {
            list-style: none;
            padding: 0;
        }
        .servico-desc .beneficios ul li {
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
        }
        .servico-desc .beneficios ul li i {
            color: var(--green);
        }
        
        .servico-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .sidebar-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .sidebar-card h3 {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .sidebar-card .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .sidebar-card .info-item:last-child { border-bottom: none; }
        .sidebar-card .info-item .label { color: var(--gray-600); font-size: 0.85rem; }
        .sidebar-card .info-item .value { font-weight: 500; }
        
        .btn-contato {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--secondary);
            color: white;
            padding: 14px 28px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 1rem;
        }
        .btn-contato:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(58,124,165,0.3);
        }
        .btn-contato i { font-size: 1.1rem; }
        
        .btn-cadastro-servico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 14px 28px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 1rem;
        }
        .btn-cadastro-servico:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }
        
        .relacionados {
            margin-top: 40px;
        }
        .relacionados h3 {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        .relacionados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .relacionado-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }
        .relacionado-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }
        .relacionado-card .icon {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 8px;
            display: block;
        }
        .relacionado-card h4 {
            color: var(--primary);
            margin-bottom: 4px;
        }
        .relacionado-card p {
            color: var(--gray-600);
            font-size: 0.85rem;
        }
        
        .footer {
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            padding: 28px 0;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-top: auto;
        }
        .footer span { font-weight: 500; color: var(--primary); }
        
        @media (max-width: 768px) {
            .header .container { flex-direction: column; gap: 10px; }
            .nav-list { gap: 12px; justify-content: center; }
            .page-header h1 { font-size: 1.8rem; }
            .servico-content { grid-template-columns: 1fr; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
            .page-header .icon { font-size: 2.5rem; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
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
                    <li><a href="servicos.php" class="active"><i class="fas fa-briefcase"></i> Serviços</a></li>
                    <li><a href="sobre.php"><i class="fas fa-users"></i> Sobre</a></li>
                    <li><a href="contato.php"><i class="fas fa-envelope"></i> Contato</a></li>
                    
                    <li id="painelMenu" style="display: none;">
                        <a href="painel-cuidador.php" id="painelLink">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Painel</span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, Usuário</span>
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

    <section class="page-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Início</a> &gt; 
                <a href="servicos.php">Serviços</a> &gt; 
                <?php echo htmlspecialchars($servico['nome']); ?>
            </div>
            <div class="icon"><i class="fas <?php echo !empty($servico['icone']) ? $servico['icone'] : 'fa-heart'; ?>"></i></div>
            <h1><?php echo htmlspecialchars($servico['nome']); ?></h1>
            <p><?php echo htmlspecialchars($servico['descricao'] ?? ''); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="servico-content">
                <div class="servico-desc">
                    <h2>Sobre este serviço</h2>
                    <p><?php echo nl2br(htmlspecialchars($servico['descricao'] ?? 'Descrição detalhada do serviço.')); ?></p>
                    
                    <?php if (!empty($servico['beneficios'])): ?>
                    <div class="beneficios">
                        <h3><i class="fas fa-star" style="color: var(--orange);"></i> Benefícios</h3>
                        <ul>
                            <?php 
                            $beneficios = explode("\n", $servico['beneficios']);
                            foreach ($beneficios as $beneficio):
                                if (trim($beneficio)):
                            ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($beneficio)); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($servico['quando_contratar'])): ?>
                    <div class="beneficios" style="border-color: var(--secondary);">
                        <h3><i class="fas fa-lightbulb" style="color: var(--orange);"></i> Quando contratar</h3>
                        <p><?php echo nl2br(htmlspecialchars($servico['quando_contratar'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($servico['equipe'])): ?>
                    <div class="beneficios" style="border-color: var(--green);">
                        <h3><i class="fas fa-users" style="color: var(--secondary);"></i> Nossa Equipe</h3>
                        <p><?php echo nl2br(htmlspecialchars($servico['equipe'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="servico-sidebar">
                    <div class="sidebar-card">
                        <h3><i class="fas fa-info-circle" style="color: var(--secondary);"></i> Informações</h3>
                        <div class="info-item">
                            <span class="label">Disponibilidade</span>
                            <span class="value">Sob consulta</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Atendimento</span>
                            <span class="value">Domiciliar</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Profissionais</span>
                            <span class="value">Especializados</span>
                        </div>
                    </div>
                    
                    <div class="sidebar-card" style="background: var(--gray-50);">
                        <h3><i class="fas fa-hand-holding-heart" style="color: var(--secondary);"></i> Quer contratar?</h3>
                        <p style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 12px;">
                            Entre em contato conosco para mais informações sobre este serviço.
                        </p>
                        <a href="contato.php" class="btn-contato">
                            <i class="fas fa-envelope"></i> Falar com especialista
                        </a>
                    </div>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="sidebar-card" style="background: var(--primary); color: white; border-color: var(--primary);">
                        <h3 style="color: white;"><i class="fas fa-user-plus" style="color: white;"></i> Comece agora</h3>
                        <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 12px;">
                            Crie sua conta e tenha acesso a todos os serviços.
                        </p>
                        <a href="cadastro.php" class="btn-cadastro-servico" style="background: white; color: var(--primary);">
                            <i class="fas fa-user-plus"></i> Criar conta
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($servicos_relacionados)): ?>
            <div class="relacionados">
                <h3><i class="fas fa-briefcase" style="color: var(--secondary);"></i> Serviços Relacionados</h3>
                <div class="relacionados-grid">
                    <?php foreach ($servicos_relacionados as $rel): ?>
                        <a href="servico-detalhe.php?id=<?php echo $rel['id']; ?>" class="relacionado-card">
                            <span class="icon"><i class="fas <?php echo !empty($rel['icone']) ? $rel['icone'] : 'fa-heart'; ?>"></i></span>
                            <h4><?php echo htmlspecialchars($rel['nome']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($rel['descricao'] ?? '', 0, 80)) . '...'; ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 <span>HomeCare</span> · Cuidado que transforma vidas</p>
        </div>
    </footer>

    <script>
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
        });
    </script>
</body>
</html>
