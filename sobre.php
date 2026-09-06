<?php
session_start();
include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Sobre Nós</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0b2b40;
            --primary-light: #1a4b66;
            --secondary: #3a7ca5;
            --secondary-light: #5a9cc5;
            --gray-100: #f5f8fa;
            --gray-200: #e9edf0;
            --gray-300: #d1d9e0;
            --gray-600: #4a5b66;
            --gray-800: #1f2a33;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,20,30,0.08);
            --shadow-lg: 0 20px 60px rgba(0,20,30,0.12);
            --radius: 16px;
            --transition: 0.3s ease;
            --red: #e74c3c;
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
           HERO - IGUAL AO DA PÁGINA INICIAL
           ============================================================ */
        .hero {
            padding: 80px 0 60px;
            background: var(--gradient-1);
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: rgba(255,255,255,0.05);
            transform: rotate(-20deg);
        }
        .hero .container {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 16px;
        }
        .hero h1 span { color: var(--secondary); }
        .hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 24px;
            max-width: 500px;
        }
        .hero-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-hero {
            padding: 14px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-primary {
            background: var(--secondary);
            color: white;
        }
        .btn-hero-primary:hover {
            background: #2d6b8f;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(58,124,165,0.3);
        }
        .btn-hero-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            backdrop-filter: blur(4px);
        }
        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        .hero-image {
            display: flex;
            justify-content: center;
        }
        .hero-image i {
            font-size: 10rem;
            color: rgba(255,255,255,0.1);
        }
        
        /* ============================================================
           SEÇÃO SOBRE
           ============================================================ */
        .section {
            padding: 60px 0;
        }
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .section-title h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .section-title p {
            color: var(--gray-600);
            max-width: 600px;
            margin: 8px auto 0;
            font-size: 1rem;
        }
        
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }
        .about-grid .text h3 {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .about-grid .text p {
            color: var(--gray-600);
            margin-bottom: 16px;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .about-grid .image {
            display: flex;
            justify-content: center;
        }
        .about-grid .image i {
            font-size: 10rem;
            color: var(--gray-300);
            transition: var(--transition);
        }
        .about-grid .image i:hover {
            color: var(--secondary);
            transform: scale(1.02);
        }
        
        /* ============================================================
           MISSÃO, VISÃO, VALORES - APENAS 4 CARDS
           ============================================================ */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 12px;
        }
        .value-card {
            background: var(--white);
            padding: 32px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .value-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-light);
        }
        .value-card .icon {
            font-size: 2.5rem;
            color: var(--secondary);
            display: block;
            margin-bottom: 12px;
            transition: var(--transition);
        }
        .value-card:hover .icon {
            transform: scale(1.1) rotate(-5deg);
        }
        .value-card h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .value-card p {
            font-size: 0.85rem;
            color: var(--gray-600);
            line-height: 1.5;
        }
        
        /* ============================================================
           ESTATÍSTICAS
           ============================================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 12px;
        }
        .stat-card {
            background: var(--white);
            padding: 32px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card .number {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--primary);
        }
        .stat-card .label {
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-top: 4px;
        }
        
        /* ============================================================
           ANIMAÇÃO
           ============================================================ */
        .value-card, .stat-card {
            opacity: 0;
            animation: fadeUp 0.5s ease forwards;
        }
        .value-card:nth-child(1) { animation-delay: 0.05s; }
        .value-card:nth-child(2) { animation-delay: 0.1s; }
        .value-card:nth-child(3) { animation-delay: 0.15s; }
        .value-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ============================================================
           FOOTER
           ============================================================ */
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
        
        /* ============================================================
           RESPONSIVO
           ============================================================ */
        @media (max-width: 1024px) {
            .values-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .header .container { flex-direction: column; gap: 10px; }
            .nav-list { gap: 12px; justify-content: center; }
            .hero { padding: 50px 0 40px; }
            .hero .container { grid-template-columns: 1fr; text-align: center; }
            .hero h1 { font-size: 2.2rem; }
            .hero p { margin: 0 auto 24px; }
            .hero-buttons { justify-content: center; }
            .about-grid { grid-template-columns: 1fr; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .values-grid { grid-template-columns: 1fr 1fr; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
            .hero h1 { font-size: 1.8rem; }
            .hero-image i { font-size: 6rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .values-grid { grid-template-columns: 1fr; }
            .stat-card .number { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <!-- ============================================================
    HEADER
    ============================================================ -->
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
                    <li><a href="sobre.php" class="active"><i class="fas fa-users"></i> Sobre</a></li>
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

    <!-- ============================================================
    HERO - IGUAL AO DA PÁGINA INICIAL
    ============================================================ -->
    <section class="hero">
        <div class="container">
            <div>
                <h1>Quem somos <span>HomeCare</span></h1>
                <p>Conectamos profissionais de saúde a quem precisa de cuidados especiais, com carinho e excelência.</p>
                <div class="hero-buttons">
                    <a href="cadastro.php" class="btn-hero btn-hero-primary">
                        <i class="fas fa-user-plus"></i> Começar agora
                    </a>
                    <a href="contato.php" class="btn-hero btn-hero-secondary">
                        <i class="fas fa-envelope"></i> Fale conosco
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-hands-helping"></i>
            </div>
        </div>
    </section>

    <!-- ============================================================
    SOBRE NÓS
    ============================================================ -->
    <section class="section">
        <div class="container">
            <div class="about-grid">
                <div class="text">
                    <h3>Nossa História</h3>
                    <p>A HomeCare nasceu com o propósito de transformar a forma como as pessoas recebem cuidados de saúde. Percebemos que muitos pacientes e idosos preferem receber assistência especializada no conforto de suas casas.</p>
                    <p>Por isso, criamos uma plataforma que conecta profissionais de saúde qualificados a quem precisa de cuidados, garantindo atendimento humanizado, seguro e de excelência.</p>
                    <h3>Nossa Missão</h3>
                    <p>Proporcionar qualidade de vida e bem-estar através de cuidados humanizados, conectando profissionais a pessoas que precisam de assistência especializada.</p>
                    <h3>Nossa Visão</h3>
                    <p>Ser referência nacional em cuidados domiciliares, reconhecida pela qualidade, humanização e inovação.</p>
                </div>
                <div class="image">
                    <i class="fas fa-hands-helping"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
    NOSSOS VALORES - APENAS 4 CARDS
    ============================================================ -->
    <section class="section" style="background: var(--white); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200);">
        <div class="container">
            <div class="section-title">
                <h2>Nossos Valores</h2>
                <p>Princípios que guiam nosso trabalho diário</p>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <span class="icon"><i class="fas fa-heart"></i></span>
                    <h4>Humanização</h4>
                    <p>Tratamos cada pessoa com respeito, empatia e dignidade.</p>
                </div>
                <div class="value-card">
                    <span class="icon"><i class="fas fa-star"></i></span>
                    <h4>Excelência</h4>
                    <p>Buscamos a melhor qualidade em cada atendimento.</p>
                </div>
                <div class="value-card">
                    <span class="icon"><i class="fas fa-shield-alt"></i></span>
                    <h4>Segurança</h4>
                    <p>Garantimos um ambiente seguro e confiável para todos.</p>
                </div>
                <div class="value-card">
                    <span class="icon"><i class="fas fa-handshake"></i></span>
                    <h4>Confiança</h4>
                    <p>Construímos relacionamentos baseados na transparência.</p>
                </div>
            </div>
        </div>
    </section>

   
    <!-- ============================================================
    FOOTER
    ============================================================ -->
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
            } else {
                if (painelMenu) painelMenu.style.display = 'none';
                if (perfilMenu) perfilMenu.style.display = 'none';
                if (logoutMenu) logoutMenu.style.display = 'none';
                if (loginMenu) loginMenu.style.display = 'block';
                if (cadastroMenu) cadastroMenu.style.display = 'block';
            }
        });
    </script>
</body>
</html>
