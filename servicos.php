<?php
session_start();
include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}

// Buscar serviços do banco (até 6)
$servicos = [];
$sql_servicos = "SELECT DISTINCT id, nome, descricao, icone FROM servicos WHERE ativo = 1 ORDER BY id LIMIT 6";
$result_servicos = $conn->query($sql_servicos);
if ($result_servicos && $result_servicos->num_rows > 0) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos[] = $row;
    }
}

// Fallback com 6 serviços
if (empty($servicos)) {
    $servicos = [
        ['id' => 1, 'nome' => 'Cuidados Domiciliares', 'descricao' => 'Assistência completa no conforto do lar.', 'icone' => 'fa-home'],
        ['id' => 2, 'nome' => 'Acompanhamento de Idosos', 'descricao' => 'Suporte para qualidade de vida.', 'icone' => 'fa-heart'],
        ['id' => 3, 'nome' => 'Enfermagem Especializada', 'descricao' => 'Cuidados técnicos com excelência.', 'icone' => 'fa-user-md'],
        ['id' => 4, 'nome' => 'Fisioterapia Domiciliar', 'descricao' => 'Reabilitação no conforto do lar.', 'icone' => 'fa-bone'],
        ['id' => 5, 'nome' => 'Cuidados Paliativos', 'descricao' => 'Conforto e qualidade de vida em momentos especiais.', 'icone' => 'fa-hand-holding-heart'],
        ['id' => 6, 'nome' => 'Terapia Ocupacional', 'descricao' => 'Atividades terapêuticas para autonomia e bem-estar.', 'icone' => 'fa-brain']
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Serviços</title>
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
           FILTRO
           ============================================================ */
        .filter-section {
            padding: 24px 0 0;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            background: var(--white);
            padding: 6px 14px 6px 18px;
            border-radius: 60px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            max-width: 450px;
        }
        .filter-bar i {
            color: var(--gray-400);
            font-size: 0.9rem;
        }
        .filter-bar input {
            border: none;
            padding: 8px 0;
            font-size: 0.85rem;
            flex: 1;
            background: transparent;
            outline: none;
            font-family: inherit;
            color: var(--gray-700);
        }
        .filter-bar input::placeholder {
            color: var(--gray-400);
            font-size: 0.8rem;
        }
        .filter-bar .btn-filter {
            padding: 6px 16px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .filter-bar .btn-filter:hover {
            background: var(--primary-light);
        }
        
        .categories {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin: 12px 0 24px;
        }
        .categories .cat {
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--gray-600);
            background: var(--white);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            transition: var(--transition);
        }
        .categories .cat:hover {
            border-color: var(--secondary);
            color: var(--secondary);
        }
        .categories .cat.active {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        /* ============================================================
           SERVIÇOS GRID - 6 CARDS
           ============================================================ */
        .section {
            padding: 24px 0 60px;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .service-card {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 28px 20px 20px;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-2);
            opacity: 0;
            transition: var(--transition);
        }
        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            border-color: var(--secondary-light);
        }
        .service-card:hover::before {
            opacity: 1;
        }
        
        .service-card .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 16px;
            transition: var(--transition);
        }
        .service-card:hover .card-icon {
            transform: scale(1.05) rotate(-5deg);
        }
        .service-card .card-icon.blue { background: var(--gradient-2); }
        .service-card .card-icon.purple { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .service-card .card-icon.green { background: linear-gradient(135deg, #059669, #34d399); }
        .service-card .card-icon.orange { background: linear-gradient(135deg, #d97706, #fbbf24); }
        .service-card .card-icon.pink { background: linear-gradient(135deg, #db2777, #f472b6); }
        .service-card .card-icon.teal { background: linear-gradient(135deg, #0d9488, #2dd4bf); }
        
        .service-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .service-card .desc {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.4;
            max-width: 90%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .service-card .card-footer {
            margin-top: 16px;
            padding-top: 12px;
            width: 100%;
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }
        .service-card .saiba-mais {
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.8rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .service-card:hover .saiba-mais {
            color: var(--primary-light);
            gap: 8px;
        }
        .service-card .saiba-mais i {
            transition: var(--transition);
        }
        .service-card:hover .saiba-mais i {
            transform: translateX(4px);
        }
        
        /* ============================================================
           ANIMAÇÃO
           ============================================================ */
        .service-card {
            opacity: 0;
            animation: fadeUp 0.5s ease forwards;
        }
        .service-card:nth-child(1) { animation-delay: 0.05s; }
        .service-card:nth-child(2) { animation-delay: 0.1s; }
        .service-card:nth-child(3) { animation-delay: 0.15s; }
        .service-card:nth-child(4) { animation-delay: 0.2s; }
        .service-card:nth-child(5) { animation-delay: 0.25s; }
        .service-card:nth-child(6) { animation-delay: 0.3s; }
        
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
        @media (max-width: 768px) {
            .header .container { flex-direction: column; gap: 10px; }
            .nav-list { gap: 12px; justify-content: center; }
            .hero { padding: 50px 0 40px; }
            .hero .container { grid-template-columns: 1fr; text-align: center; }
            .hero h1 { font-size: 2.2rem; }
            .hero p { margin: 0 auto 24px; }
            .hero-buttons { justify-content: center; }
            .services-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
            .filter-bar { max-width: 100%; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
            .service-card { padding: 20px 16px; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
            .hero h1 { font-size: 1.8rem; }
            .hero-image i { font-size: 6rem; }
            .services-grid { grid-template-columns: 1fr; }
            .service-card .card-icon { width: 48px; height: 48px; font-size: 1.3rem; }
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

    <!-- ============================================================
    HERO
    ============================================================ -->
    <section class="hero">
        <div class="container">
            <div>
                <h1>Serviços que <span>cuidam</span> de quem você ama</h1>
                <p>Conectamos profissionais de saúde qualificados para oferecer o melhor cuidado no conforto do lar.</p>
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
                <i class="fas fa-heartbeat"></i>
            </div>
        </div>
    </section>

    <!-- ============================================================
    FILTRO
    ============================================================ -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar serviço..." />
                <button class="btn-filter" onclick="filtrarServicos()"><i class="fas fa-filter"></i></button>
            </div>
            <div class="categories">
                <span class="cat active" data-cat="todos" onclick="filtrarCategoria('todos', this)">Todos</span>
                <span class="cat" data-cat="domiciliares" onclick="filtrarCategoria('domiciliares', this)">🏠 Lar</span>
                <span class="cat" data-cat="saude" onclick="filtrarCategoria('saude', this)">❤️ Saúde</span>
                <span class="cat" data-cat="reabilitacao" onclick="filtrarCategoria('reabilitacao', this)">🔄 Reabilitação</span>
                <span class="cat" data-cat="paliativos" onclick="filtrarCategoria('paliativos', this)">🕊️ Paliativos</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
    SERVIÇOS - 6 CARDS
    ============================================================ -->
    <section class="section">
        <div class="container">
            <div class="services-grid" id="servicosGrid">
                <?php 
                $cores = ['blue', 'purple', 'green', 'orange', 'pink', 'teal'];
                $i = 0;
                foreach ($servicos as $servico): 
                    $cor = $cores[$i % count($cores)];
                    $i++;
                ?>
                    <a href="servico-detalhe.php?id=<?php echo $servico['id'] ?? 1; ?>" class="service-card" data-nome="<?php echo strtolower($servico['nome']); ?>">
                        <span class="card-icon <?php echo $cor; ?>">
                            <i class="fas <?php echo $servico['icone'] ?? 'fa-heart'; ?>"></i>
                        </span>
                        <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
                        <p class="desc"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                        <div class="card-footer">
                            <span class="saiba-mais">
                                Saiba mais <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
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

        // ============================================================
        // BUSCA
        // ============================================================
        function filtrarServicos() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.service-card');
            
            cards.forEach(card => {
                const nome = card.getAttribute('data-nome') || '';
                const desc = card.querySelector('.desc')?.textContent?.toLowerCase() || '';
                
                card.style.display = (nome.includes(input) || desc.includes(input)) ? 'flex' : 'none';
            });
        }

        document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') filtrarServicos();
        });

        // ============================================================
        // CATEGORIAS (incluindo paliativos)
        // ============================================================
        function filtrarCategoria(categoria, element) {
            document.querySelectorAll('.categories .cat').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            const cards = document.querySelectorAll('.service-card');
            
            if (categoria === 'todos') {
                cards.forEach(card => card.style.display = 'flex');
                return;
            }
            
            const mapCategoria = {
                'domiciliares': ['cuidados domiciliares', 'acompanhamento'],
                'saude': ['enfermagem'],
                'reabilitacao': ['fisioterapia', 'terapia ocupacional'],
                'paliativos': ['cuidados paliativos']
            };
            
            const keywords = mapCategoria[categoria] || [];
            
            cards.forEach(card => {
                const nome = card.getAttribute('data-nome') || '';
                const desc = card.querySelector('.desc')?.textContent?.toLowerCase() || '';
                
                const match = keywords.some(keyword => 
                    nome.includes(keyword) || desc.includes(keyword)
                );
                
                card.style.display = match ? 'flex' : 'none';
            });
        }
    </script>
</body>
</html>
