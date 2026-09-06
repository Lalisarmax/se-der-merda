<?php
session_start();
include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}

$servicos = [];
$sql_servicos = "SELECT * FROM servicos WHERE ativo = 1 ORDER BY id LIMIT 6";
$result_servicos = $conn->query($sql_servicos);
if ($result_servicos) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos[] = $row;
    }
}

if (empty($servicos)) {
    $servicos = [
        [
            'nome' => 'Cuidados Domiciliares',
            'descricao' => 'Assistência completa no conforto do seu lar, com profissionais qualificados e humanizados.',
            'icone' => 'fa-home'
        ],
        [
            'nome' => 'Acompanhamento de Idosos',
            'descricao' => 'Suporte personalizado para idosos, garantindo qualidade de vida e bem-estar diário.',
            'icone' => 'fa-heart'
        ],
        [
            'nome' => 'Enfermagem Especializada',
            'descricao' => 'Cuidados de enfermagem com expertise em geriatria e cuidados paliativos.',
            'icone' => 'fa-user-md'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Cuidado que transforma vidas</title>
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
            --green: #27ae60;
            --red: #e74c3c;
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
        
        .hero {
            padding: 80px 0 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
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
        
        .section { padding: 60px 0; }
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
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .service-card {
            background: var(--white);
            padding: 32px 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }
        .service-card .icon {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 12px;
            display: block;
        }
        .service-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .service-card p {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .about-section {
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }
        .about-grid .content h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
        }
        .about-grid .content p {
            color: var(--gray-600);
            margin-bottom: 16px;
        }
        .about-grid .image {
            display: flex;
            justify-content: center;
        }
        .about-grid .image i {
            font-size: 8rem;
            color: var(--gray-200);
        }
        .about-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        .about-stats .stat {
            text-align: center;
        }
        .about-stats .stat .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        .about-stats .stat .label {
            font-size: 0.8rem;
            color: var(--gray-600);
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
            .hero .container { grid-template-columns: 1fr; text-align: center; }
            .hero h1 { font-size: 2.2rem; }
            .hero p { margin: 0 auto 24px; }
            .hero-buttons { justify-content: center; }
            .about-grid { grid-template-columns: 1fr; text-align: center; }
            .about-stats { grid-template-columns: 1fr 1fr; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
            .hero h1 { font-size: 1.8rem; }
            .services-grid { grid-template-columns: 1fr; }
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
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="servicos.php"><i class="fas fa-briefcase"></i> Serviços</a></li>
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

    <section class="hero">
        <div class="container">
            <div>
                <h1>Cuidado que <span>transforma</span> vidas</h1>
                <p>Conectamos profissionais de saúde qualificados a quem precisa de cuidados especiais, com carinho e excelência.</p>
                <div class="hero-buttons">
                    <a href="cadastro.php" class="btn-hero btn-hero-primary">
                        <i class="fas fa-user-plus"></i> Começar agora
                    </a>
                    <a href="servicos.php" class="btn-hero btn-hero-secondary">
                        <i class="fas fa-info-circle"></i> Saiba mais
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-heartbeat"></i>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Nossos Serviços</h2>
                <p>Oferecemos soluções completas para o cuidado com a saúde e bem-estar</p>
            </div>
            <div class="services-grid">
                <?php foreach ($servicos as $servico): ?>
                    <div class="service-card">
                        <span class="icon"><i class="fas <?php echo $servico['icone'] ?? 'fa-heart'; ?>"></i></span>
                        <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($servico['descricao']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="about-section section">
        <div class="container">
            <div class="about-grid">
                <div>
                    <h2>Quem somos</h2>
                    <p>A HomeCare é uma plataforma que conecta cuidadores e profissionais de saúde a pessoas que precisam de assistência especializada no conforto de suas casas.</p>
                    <p>Nossa missão é proporcionar qualidade de vida e bem-estar através de cuidados humanizados, com profissionais qualificados e comprometidos.</p>
                    <div class="about-stats">
                        <div class="stat">
                            <div class="number">100+</div>
                            <div class="label">Profissionais</div>
                        </div>
                        <div class="stat">
                            <div class="number">500+</div>
                            <div class="label">Pacientes atendidos</div>
                        </div>
                        <div class="stat">
                            <div class="number">98%</div>
                            <div class="label">Satisfação</div>
                        </div>
                    </div>
                </div>
                <div class="image">
                    <i class="fas fa-hands-helping"></i>
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
