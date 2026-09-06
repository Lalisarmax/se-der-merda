<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_tipo'] == 'cuidador') {
        header('Location: painel-cuidador.php');
    } else {
        header('Location: paciente-visualizacao.php');
    }
    exit;
}

include 'conexao.php';

$erro = '';
$sucesso = '';

function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $cpf = trim($_POST['cpf'] ?? '');
    
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo)) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($senha != $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (!empty($cpf) && !validarCPF($cpf)) {
        $erro = 'CPF inválido.';
    } else {
        $check_sql = "SELECT id FROM usuarios WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, telefone, data_nascimento, cpf) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nome, $email, $senhaHash, $tipo, $telefone, $data_nascimento, $cpf);
            
            if ($stmt->execute()) {
                $usuario_id = $conn->insert_id;
                $cadastro_completo = true;
                
                if ($tipo == 'idoso') {
                    $condicao_saude = trim($_POST['condicao_saude'] ?? '');
                    $medicamentos = trim($_POST['medicamentos'] ?? '');
                    $plano_saude = trim($_POST['plano_saude'] ?? '');
                    $contato_familiar = trim($_POST['contato_familiar'] ?? '');
                    
                    $sql2 = "UPDATE usuarios SET 
                                condicao_saude = ?, medicamentos = ?, plano_saude = ?, contato_familiar = ?
                              WHERE id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("ssssi", $condicao_saude, $medicamentos, $plano_saude, $contato_familiar, $usuario_id);
                    
                    if (!$stmt2->execute()) {
                        $cadastro_completo = false;
                        $erro = 'Erro ao salvar dados de saúde.';
                        error_log('Erro ao salvar idoso: ' . $stmt2->error);
                    }
                    
                } elseif ($tipo == 'cuidador') {
                    $registro = trim($_POST['registro'] ?? '');
                    $especialidade = trim($_POST['especialidade'] ?? '');
                    $anos_experiencia = intval($_POST['anos_experiencia'] ?? 0);
                    $disponibilidade = $_POST['disponibilidade'] ?? 'flexivel';
                    $areas_atuacao = trim($_POST['areas_atuacao'] ?? '');
                    $certificacoes = trim($_POST['certificacoes'] ?? '');
                    $curriculo = trim($_POST['curriculo'] ?? '');
                    $tipo_profissional = $_POST['tipo_profissional'] ?? '';
                    
                    $sql2 = "INSERT INTO cuidadores (usuario_id, registro_profissional, especialidade, 
                              anos_experiencia, disponibilidade, areas_atuacao, certificacoes, curriculo, tipo_profissional) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("ississsss", 
                        $usuario_id, $registro, $especialidade, $anos_experiencia,
                        $disponibilidade, $areas_atuacao, $certificacoes, $curriculo, $tipo_profissional
                    );
                    
                    if (!$stmt2->execute()) {
                        $cadastro_completo = false;
                        $erro = 'Erro ao salvar dados profissionais.';
                        error_log('Erro ao salvar cuidador: ' . $stmt2->error);
                    }
                }
                
                if ($cadastro_completo) {
                    $sucesso = 'Cadastro realizado com sucesso! Redirecionando para o login...';
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000);
                    </script>";
                } else {
                    $delete_sql = "DELETE FROM usuarios WHERE id = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("i", $usuario_id);
                    $delete_stmt->execute();
                }
            } else {
                $erro = 'Erro ao cadastrar: ' . $stmt->error;
                error_log('Erro ao cadastrar usuário: ' . $stmt->error);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Cadastro</title>
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
        
        .register-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
        }
        .register-container {
            background: var(--white);
            padding: 48px 44px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            max-width: 720px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .register-container .logo-icon {
            text-align: center;
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 8px;
        }
        .register-container h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .register-container .subtitle {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert i { font-size: 1.2rem; }
        
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            margin: 24px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-200);
        }
        .section-title i { color: var(--secondary); }
        
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-group label .required { color: var(--red); margin-left: 2px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
            background: var(--gray-50);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .btn-register {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
        }
        .btn-register:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        .login-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
        
        .account-type {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        .account-option {
            position: relative;
            cursor: pointer;
        }
        .account-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .account-option .option-box {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 16px 12px;
            text-align: center;
            transition: var(--transition);
            background: var(--white);
        }
        .account-option .option-box:hover { border-color: var(--secondary); }
        .account-option input[type="radio"]:checked + .option-box {
            border-color: var(--secondary);
            background: var(--gray-50);
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .account-option .option-box .icon {
            font-size: 2rem;
            color: var(--secondary);
            display: block;
        }
        .account-option .option-box .title {
            font-weight: 600;
            color: var(--primary);
            display: block;
            font-size: 1rem;
        }
        .account-option .option-box .desc {
            font-size: 0.8rem;
            color: var(--gray-600);
            display: block;
        }
        
        .dynamic-fields {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .dynamic-fields.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-divider {
            border: none;
            border-top: 2px dashed var(--gray-200);
            margin: 20px 0;
        }
        
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
            .register-container { padding: 32px 24px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .account-type { grid-template-columns: 1fr; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .register-container { padding: 24px 16px; }
            .perfil-nome { display: none; }
        }
    </style>
</head>
<body>
    <?php
    $foto_perfil = '';
    if (isset($_SESSION['user_id'])) {
        $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
    }
    ?>
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
                        <a href="painel-cuidador.php" id="painelLink">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Painel</span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="active btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
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

    <div class="register-wrapper">
        <div class="register-container">
            <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
            <h2>Crie sua conta</h2>
            <p class="subtitle">Escolha o tipo de conta e preencha os dados específicos</p>

            <?php if (!empty($erro)): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            <?php if (!empty($sucesso)): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                <div class="account-type">
                    <label class="account-option">
                        <input type="radio" name="tipo" value="idoso" checked />
                        <div class="option-box">
                            <span class="icon"><i class="fas fa-user"></i></span>
                            <span class="title">Conta de Idoso</span>
                            <span class="desc">Para quem deseja receber cuidados</span>
                        </div>
                    </label>
                    <label class="account-option">
                        <input type="radio" name="tipo" value="cuidador" />
                        <div class="option-box">
                            <span class="icon"><i class="fas fa-user-nurse"></i></span>
                            <span class="title">Conta de Cuidador</span>
                            <span class="desc">Para quem deseja oferecer cuidados</span>
                        </div>
                    </label>
                </div>

                <div class="section-title"><i class="fas fa-user"></i> Dados Pessoais</div>

                <div class="form-group">
                    <label>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="nome" placeholder="Seu nome completo" required />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="seu@email.com" required />
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" name="telefone" placeholder="(11) 99999-9999" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Data de Nascimento</label>
                        <input type="date" name="data_nascimento" />
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" name="cpf" placeholder="000.000.000-00" maxlength="14" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Senha <span class="required">*</span></label>
                        <input type="password" name="senha" id="senha" placeholder="Mínimo 6 caracteres" required />
                    </div>
                    <div class="form-group">
                        <label>Confirmar Senha <span class="required">*</span></label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Digite novamente" required />
                    </div>
                </div>

                <div id="idosoFields" class="dynamic-fields active">
                    <div class="section-title"><i class="fas fa-notes-medical"></i> Informações de Saúde</div>

                    <div class="form-group">
                        <label>Condições de Saúde</label>
                        <textarea name="condicao_saude" placeholder="Descreva condições de saúde, alergias ou necessidades especiais"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Medicamentos em Uso</label>
                            <input type="text" name="medicamentos" placeholder="Liste os medicamentos que utiliza" />
                        </div>
                        <div class="form-group">
                            <label>Plano de Saúde</label>
                            <input type="text" name="plano_saude" placeholder="Nome do plano de saúde" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contato de um familiar</label>
                        <input type="text" name="contato_familiar" placeholder="Nome e telefone de um familiar" />
                    </div>
                </div>

                <div id="cuidadorFields" class="dynamic-fields">
                    <div class="section-title"><i class="fas fa-briefcase"></i> Informações Profissionais</div>

                    <div class="form-group">
                        <label>Tipo de Profissional</label>
                        <select name="tipo_profissional">
                            <option value="">Selecione...</option>
                            <option value="enfermeiro">Enfermeiro(a)</option>
                            <option value="tecnico_enfermagem">Técnico(a) de Enfermagem</option>
                            <option value="auxiliar_enfermagem">Auxiliar de Enfermagem</option>
                            <option value="cuidador">Cuidador(a) de Idosos</option>
                            <option value="fisioterapeuta">Fisioterapeuta</option>
                            <option value="psicologo">Psicólogo(a)</option>
                            <option value="nutricionista">Nutricionista</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Número do Registro Profissional</label>
                        <input type="text" name="registro" placeholder="COREN, CRP, CRN, etc." />
                    </div>

                    <div class="form-group">
                        <label>Especializações</label>
                        <input type="text" name="especialidade" placeholder="Ex: Geriatria, Cuidados Paliativos, etc." />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Anos de Experiência</label>
                            <select name="anos_experiencia">
                                <option value="0">Menos de 1 ano</option>
                                <option value="1">1 a 3 anos</option>
                                <option value="3">3 a 5 anos</option>
                                <option value="5">5 a 10 anos</option>
                                <option value="10">Mais de 10 anos</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Disponibilidade</label>
                            <select name="disponibilidade">
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                                <option value="noite">Noite</option>
                                <option value="integral">Integral</option>
                                <option value="plantao">Plantão 12x36</option>
                                <option value="flexivel" selected>Flexível</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Áreas de Atuação</label>
                        <input type="text" name="areas_atuacao" placeholder="Ex: Cuidados com Idosos, Cuidados Paliativos, etc." />
                    </div>

                    <div class="form-group">
                        <label>Certificações e Cursos</label>
                        <input type="text" name="certificacoes" placeholder="Liste suas certificações relevantes" />
                    </div>

                    <div class="form-group">
                        <label>Currículo / Resumo Profissional</label>
                        <textarea name="curriculo" placeholder="Descreva brevemente sua experiência e habilidades"></textarea>
                    </div>
                </div>

                <hr class="form-divider" />

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Criar Conta
                </button>
            </form>

            <div class="login-link">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 <span>HomeCare</span> · Cuidado que transforma vidas</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="tipo"]');
            const idosoFields = document.getElementById('idosoFields');
            const cuidadorFields = document.getElementById('cuidadorFields');

            function toggleFields() {
                const selected = document.querySelector('input[name="tipo"]:checked');
                if (selected) {
                    if (selected.value === 'idoso') {
                        idosoFields.classList.add('active');
                        cuidadorFields.classList.remove('active');
                        cuidadorFields.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = true; });
                        idosoFields.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = false; });
                    } else {
                        cuidadorFields.classList.add('active');
                        idosoFields.classList.remove('active');
                        idosoFields.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = true; });
                        cuidadorFields.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = false; });
                    }
                }
            }

            radioButtons.forEach(radio => {
                radio.addEventListener('change', toggleFields);
            });
            toggleFields();

            const senhaInput = document.getElementById('senha');
            const confirmarInput = document.getElementById('confirmar_senha');
            
            function validarSenha() {
                const senha = senhaInput.value;
                const confirmar = confirmarInput.value;
                
                if (senha.length > 0 && senha.length < 6) {
                    senhaInput.style.borderColor = 'var(--red)';
                } else if (senha.length >= 6) {
                    senhaInput.style.borderColor = 'var(--green)';
                } else {
                    senhaInput.style.borderColor = '';
                }
                
                if (confirmar.length > 0 && senha !== confirmar) {
                    confirmarInput.style.borderColor = 'var(--red)';
                } else if (confirmar.length > 0 && senha === confirmar) {
                    confirmarInput.style.borderColor = 'var(--green)';
                } else {
                    confirmarInput.style.borderColor = '';
                }
            }
            
            senhaInput.addEventListener('input', validarSenha);
            confirmarInput.addEventListener('input', validarSenha);

            document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 9) {
                    value = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6, 9) + '-' + value.substring(9);
                } else if (value.length > 6) {
                    value = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6);
                } else if (value.length > 3) {
                    value = value.substring(0, 3) + '.' + value.substring(3);
                }
                e.target.value = value;
            });

            document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 10) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
                } else if (value.length > 6) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 6) + '-' + value.substring(6);
                } else if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                e.target.value = value;
            });

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
