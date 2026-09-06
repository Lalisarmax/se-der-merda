<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'conexao.php';
$mensagem = '';
$erro = '';
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'] ?? '';

// Buscar dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Buscar dados do cuidador
$dados_profissionais = [];
if ($user_tipo == 'cuidador') {
    $sql2 = "SELECT * FROM cuidadores WHERE usuario_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows > 0) {
        $dados_profissionais = $result2->fetch_assoc();
    }
}

// Buscar dados do idoso
$dados_saude = [];
if ($user_tipo == 'idoso') {
    $dados_saude = [
        'condicao_saude' => $usuario['condicao_saude'] ?? '',
        'medicamentos' => $usuario['medicamentos'] ?? '',
        'plano_saude' => $usuario['plano_saude'] ?? '',
        'contato_familiar' => $usuario['contato_familiar'] ?? '',
        'data_nascimento' => $usuario['data_nascimento'] ?? '',
        'cpf' => $usuario['cpf'] ?? ''
    ];
}

// Processar upload de foto
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
    $resultado = salvarFotoPerfil($_FILES['foto_perfil'], $user_id, $conn);
    
    if (!empty($resultado['erro'])) {
        $erro = $resultado['erro'];
    } else {
        $caminho_completo = $resultado['caminho'];
        
        if (!empty($usuario['foto_perfil'])) {
            $foto_antiga = $usuario['foto_perfil'];
            if (file_exists($foto_antiga) && is_file($foto_antiga)) {
                unlink($foto_antiga);
            }
        }
        
        $sql_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
        $stmt_foto = $conn->prepare($sql_foto);
        $stmt_foto->bind_param("si", $caminho_completo, $user_id);
        if ($stmt_foto->execute()) {
            $mensagem = 'Foto de perfil atualizada com sucesso!';
            $usuario['foto_perfil'] = $caminho_completo;
        } else {
            $erro = 'Erro ao salvar foto no banco de dados.';
            if (file_exists($caminho_completo)) {
                unlink($caminho_completo);
            }
        }
    }
}

// Processar remoção de foto
if (isset($_POST['remover_foto'])) {
    if (!empty($usuario['foto_perfil'])) {
        $foto_antiga = $usuario['foto_perfil'];
        if (file_exists($foto_antiga) && is_file($foto_antiga)) {
            unlink($foto_antiga);
        }
    }
    
    $sql_remove = "UPDATE usuarios SET foto_perfil = NULL WHERE id = ?";
    $stmt_remove = $conn->prepare($sql_remove);
    $stmt_remove->bind_param("i", $user_id);
    if ($stmt_remove->execute()) {
        $mensagem = 'Foto removida com sucesso!';
        $usuario['foto_perfil'] = null;
    } else {
        $erro = 'Erro ao remover foto.';
    }
}

// Processar atualização de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['remover_foto']) && !isset($_FILES['foto_perfil'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome)) {
        $erro = 'O nome é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (!empty($senha) && $senha != $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } elseif (!empty($senha) && strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        if (!empty($senha)) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nome, $email, $telefone, $senhaHash, $user_id);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $email, $telefone, $user_id);
        }
        
        if ($stmt->execute()) {
            if ($user_tipo == 'idoso') {
                $condicao_saude = trim($_POST['condicao_saude'] ?? '');
                $medicamentos = trim($_POST['medicamentos'] ?? '');
                $plano_saude = trim($_POST['plano_saude'] ?? '');
                $contato_familiar = trim($_POST['contato_familiar'] ?? '');
                $data_nascimento = $_POST['data_nascimento'] ?? null;
                $cpf = trim($_POST['cpf'] ?? '');
                
                $sql_saude = "UPDATE usuarios SET 
                                condicao_saude = ?, medicamentos = ?, plano_saude = ?, 
                                contato_familiar = ?, data_nascimento = ?, cpf = ?
                              WHERE id = ?";
                $stmt_saude = $conn->prepare($sql_saude);
                $stmt_saude->bind_param("ssssssi", 
                    $condicao_saude, $medicamentos, $plano_saude, 
                    $contato_familiar, $data_nascimento, $cpf, $user_id
                );
                $stmt_saude->execute();
                
                $dados_saude['condicao_saude'] = $condicao_saude;
                $dados_saude['medicamentos'] = $medicamentos;
                $dados_saude['plano_saude'] = $plano_saude;
                $dados_saude['contato_familiar'] = $contato_familiar;
                $dados_saude['data_nascimento'] = $data_nascimento;
                $dados_saude['cpf'] = $cpf;
            }
            
            if ($user_tipo == 'cuidador') {
                $registro = trim($_POST['registro'] ?? '');
                $especialidade = trim($_POST['especialidade'] ?? '');
                $anos_experiencia = intval($_POST['anos_experiencia'] ?? 0);
                $disponibilidade = $_POST['disponibilidade'] ?? '';
                $areas_atuacao = trim($_POST['areas_atuacao'] ?? '');
                $certificacoes = trim($_POST['certificacoes'] ?? '');
                $curriculo = trim($_POST['curriculo'] ?? '');
                
                $check_sql = "SELECT id FROM cuidadores WHERE usuario_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $sql_prof = "UPDATE cuidadores SET 
                                    registro_profissional = ?, especialidade = ?, anos_experiencia = ?,
                                    disponibilidade = ?, areas_atuacao = ?, certificacoes = ?, curriculo = ?
                                  WHERE usuario_id = ?";
                    $stmt_prof = $conn->prepare($sql_prof);
                    $stmt_prof->bind_param("ssissssi", 
                        $registro, $especialidade, $anos_experiencia,
                        $disponibilidade, $areas_atuacao, $certificacoes, $curriculo, $user_id
                    );
                } else {
                    $sql_prof = "INSERT INTO cuidadores (usuario_id, registro_profissional, especialidade, 
                                    anos_experiencia, disponibilidade, areas_atuacao, certificacoes, curriculo) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_prof = $conn->prepare($sql_prof);
                    $stmt_prof->bind_param("ississss", 
                        $user_id, $registro, $especialidade, $anos_experiencia,
                        $disponibilidade, $areas_atuacao, $certificacoes, $curriculo
                    );
                }
                $stmt_prof->execute();
                
                $dados_profissionais['registro_profissional'] = $registro;
                $dados_profissionais['especialidade'] = $especialidade;
                $dados_profissionais['anos_experiencia'] = $anos_experiencia;
                $dados_profissionais['disponibilidade'] = $disponibilidade;
                $dados_profissionais['areas_atuacao'] = $areas_atuacao;
                $dados_profissionais['certificacoes'] = $certificacoes;
                $dados_profissionais['curriculo'] = $curriculo;
            }
            
            $_SESSION['user_nome'] = $nome;
            $_SESSION['user_email'] = $email;
            $mensagem = 'Perfil atualizado com sucesso!';
            $usuario['nome'] = $nome;
            $usuario['email'] = $email;
            $usuario['telefone'] = $telefone;
        } else {
            $erro = 'Erro ao atualizar: ' . $stmt->error;
            error_log('Erro ao atualizar perfil: ' . $stmt->error);
        }
    }
}

$foto_perfil_menu = $usuario['foto_perfil'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Meu Perfil</title>
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
        
        .profile-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
        }
        .profile-container {
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
        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .profile-photo-section {
            text-align: center;
            margin-bottom: 24px;
        }
        .profile-photo-section .photo-container {
            position: relative;
            display: inline-block;
        }
        .profile-photo-section .photo-container .foto-perfil {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gray-200);
            transition: var(--transition);
            background: var(--gray-100);
        }
        .profile-photo-section .photo-container .foto-perfil:hover {
            border-color: var(--secondary);
        }
        .profile-photo-section .photo-container .upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: 3px solid var(--white);
            box-shadow: var(--shadow-sm);
        }
        .profile-photo-section .photo-container .upload-overlay:hover {
            background: var(--primary-light);
            transform: scale(1.1);
        }
        .profile-photo-section .photo-container .upload-overlay i {
            font-size: 0.9rem;
        }
        .profile-photo-section .photo-actions {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .profile-photo-section .photo-actions .btn-upload {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .profile-photo-section .photo-actions .btn-upload:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        .profile-photo-section .photo-actions .btn-remove {
            background: var(--red);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .profile-photo-section .photo-actions .btn-remove:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .profile-photo-section .photo-actions .btn-remove:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .profile-photo-section .photo-actions .btn-remove:disabled:hover {
            transform: none;
        }
        
        .profile-container h2 {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .profile-container .subtitle {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .profile-container .tipo-badge {
            display: inline-block;
            padding: 4px 20px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 auto 20px;
            text-align: center;
            width: fit-content;
        }
        .profile-container .tipo-badge.cuidador { background: #cce5ff; color: #004085; }
        .profile-container .tipo-badge.paciente { background: #d4edda; color: #155724; }
        
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
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert i { font-size: 1.2rem; }
        
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
        .form-group input:disabled {
            background: var(--gray-100);
            cursor: not-allowed;
            opacity: 0.7;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .form-divider {
            border: none;
            border-top: 2px dashed var(--gray-200);
            margin: 24px 0;
        }
        
        .btn-save {
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
        .btn-save:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }
        
        .profile-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-800);
            border: 1px solid var(--gray-200);
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }
        .btn-danger {
            background: var(--red);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .file-input-hidden { display: none; }
        
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
            .profile-container { padding: 32px 24px; }
            .profile-photo-section .photo-container .foto-perfil { width: 100px; height: 100px; }
            .profile-actions { flex-direction: column; align-items: center; }
            .profile-actions .btn-secondary,
            .profile-actions .btn-danger { width: 100%; justify-content: center; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .profile-container { padding: 24px 16px; }
            .profile-container h2 { font-size: 1.3rem; }
            .perfil-nome { display: none; }
            .profile-photo-section .photo-container .foto-perfil { width: 80px; height: 80px; }
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
                        <a href="<?php echo $user_tipo == 'cuidador' ? 'painel-cuidador.php' : 'paciente-visualizacao.php'; ?>" id="painelLink">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto"><?php echo $user_tipo == 'cuidador' ? 'Painel do Cuidador' : 'Meu Acompanhamento'; ?></span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link active">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil_menu) && file_exists($foto_perfil_menu) && is_file($foto_perfil_menu)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil_menu); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, <?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?></span>
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

    <div class="profile-wrapper">
        <div class="profile-container">
            <div class="profile-photo-section">
                <div class="photo-container">
                    <?php 
                    $foto_atual = verificarFotoPerfil($usuario['foto_perfil'] ?? '');
                    ?>
                    <img src="<?php echo htmlspecialchars($foto_atual); ?>" alt="Foto de perfil" class="foto-perfil" id="previewFoto" />
                    <label for="uploadFoto" class="upload-overlay" title="Alterar foto">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                
                <div class="photo-actions">
                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                        <input type="file" name="foto_perfil" id="uploadFoto" class="file-input-hidden" accept="image/*" onchange="this.form.submit()" />
                        <button type="button" class="btn-upload" onclick="document.getElementById('uploadFoto').click();">
                            <i class="fas fa-upload"></i> Alterar Foto
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="remover_foto" class="btn-remove" <?php echo (empty($usuario['foto_perfil']) || !file_exists($usuario['foto_perfil']) || !is_file($usuario['foto_perfil'])) ? 'disabled' : ''; ?>>
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </form>
                </div>
            </div>

            <h2>Meu Perfil</h2>
            <p class="subtitle">Gerencie suas informações pessoais</p>
            
            <div style="text-align: center;">
                <span class="tipo-badge <?php echo $user_tipo == 'cuidador' ? 'cuidador' : 'paciente'; ?>">
                    <i class="fas <?php echo $user_tipo == 'cuidador' ? 'fa-briefcase' : 'fa-heart'; ?>"></i>
                    <?php echo $user_tipo == 'cuidador' ? 'Profissional de Saúde' : 'Paciente'; ?>
                </span>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="section-title">
                    <i class="fas fa-user"></i> Dados Pessoais
                </div>

                <div class="form-group">
                    <label>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope" style="color: var(--secondary); margin-right: 4px;"></i> E-mail <span class="required">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required />
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone" style="color: var(--secondary); margin-right: 4px;"></i> Telefone</label>
                        <input type="tel" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="(11) 99999-9999" />
                    </div>
                </div>

                <?php if ($user_tipo == 'idoso'): ?>
                    <div class="section-title">
                        <i class="fas fa-notes-medical"></i> Informações de Saúde
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Nascimento</label>
                            <input type="date" name="data_nascimento" value="<?php echo htmlspecialchars($dados_saude['data_nascimento'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>CPF</label>
                            <input type="text" name="cpf" value="<?php echo htmlspecialchars($dados_saude['cpf'] ?? ''); ?>" placeholder="000.000.000-00" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Condições de Saúde</label>
                        <textarea name="condicao_saude" placeholder="Descreva condições de saúde, alergias ou necessidades especiais"><?php echo htmlspecialchars($dados_saude['condicao_saude'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Medicamentos em Uso</label>
                            <input type="text" name="medicamentos" value="<?php echo htmlspecialchars($dados_saude['medicamentos'] ?? ''); ?>" placeholder="Liste os medicamentos que utiliza" />
                        </div>
                        <div class="form-group">
                            <label>Plano de Saúde</label>
                            <input type="text" name="plano_saude" value="<?php echo htmlspecialchars($dados_saude['plano_saude'] ?? ''); ?>" placeholder="Nome do plano de saúde" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contato de um Familiar</label>
                        <input type="text" name="contato_familiar" value="<?php echo htmlspecialchars($dados_saude['contato_familiar'] ?? ''); ?>" placeholder="Nome e telefone de um familiar" />
                    </div>

                <?php elseif ($user_tipo == 'cuidador'): ?>
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i> Informações Profissionais
                    </div>

                    <div class="form-group">
                        <label>Registro Profissional</label>
                        <input type="text" name="registro" value="<?php echo htmlspecialchars($dados_profissionais['registro_profissional'] ?? ''); ?>" placeholder="COREN, CRP, CRN, etc." />
                    </div>

                    <div class="form-group">
                        <label>Especialidade</label>
                        <input type="text" name="especialidade" value="<?php echo htmlspecialchars($dados_profissionais['especialidade'] ?? ''); ?>" placeholder="Ex: Geriatria, Cuidados Paliativos, etc." />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Anos de Experiência</label>
                            <select name="anos_experiencia">
                                <option value="0" <?php echo ($dados_profissionais['anos_experiencia'] ?? 0) == 0 ? 'selected' : ''; ?>>Menos de 1 ano</option>
                                <option value="1" <?php echo ($dados_profissionais['anos_experiencia'] ?? 0) == 1 ? 'selected' : ''; ?>>1 a 3 anos</option>
                                <option value="3" <?php echo ($dados_profissionais['anos_experiencia'] ?? 0) == 3 ? 'selected' : ''; ?>>3 a 5 anos</option>
                                <option value="5" <?php echo ($dados_profissionais['anos_experiencia'] ?? 0) == 5 ? 'selected' : ''; ?>>5 a 10 anos</option>
                                <option value="10" <?php echo ($dados_profissionais['anos_experiencia'] ?? 0) == 10 ? 'selected' : ''; ?>>Mais de 10 anos</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Disponibilidade</label>
                            <select name="disponibilidade">
                                <option value="manha" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'manha' ? 'selected' : ''; ?>>Manhã</option>
                                <option value="tarde" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'tarde' ? 'selected' : ''; ?>>Tarde</option>
                                <option value="noite" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'noite' ? 'selected' : ''; ?>>Noite</option>
                                <option value="integral" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'integral' ? 'selected' : ''; ?>>Integral</option>
                                <option value="plantao" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'plantao' ? 'selected' : ''; ?>>Plantão 12x36</option>
                                <option value="flexivel" <?php echo ($dados_profissionais['disponibilidade'] ?? '') == 'flexivel' ? 'selected' : ''; ?>>Flexível</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Áreas de Atuação</label>
                        <input type="text" name="areas_atuacao" value="<?php echo htmlspecialchars($dados_profissionais['areas_atuacao'] ?? ''); ?>" placeholder="Ex: Cuidados com Idosos, Cuidados Paliativos, etc." />
                    </div>

                    <div class="form-group">
                        <label>Certificações e Cursos</label>
                        <input type="text" name="certificacoes" value="<?php echo htmlspecialchars($dados_profissionais['certificacoes'] ?? ''); ?>" placeholder="Liste suas certificações relevantes" />
                    </div>

                    <div class="form-group">
                        <label>Currículo / Resumo Profissional</label>
                        <textarea name="curriculo" placeholder="Descreva brevemente sua experiência e habilidades"><?php echo htmlspecialchars($dados_profissionais['curriculo'] ?? ''); ?></textarea>
                    </div>

                <?php endif; ?>

                <hr class="form-divider" />

                <div class="section-title">
                    <i class="fas fa-lock"></i> Segurança
                </div>

                <div style="font-size: 0.8rem; color: var(--gray-600); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: var(--secondary);"></i>
                    Deixe em branco se não quiser alterar a senha
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nova Senha</label>
                        <input type="password" name="senha" placeholder="Mínimo 6 caracteres" />
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" placeholder="Digite a senha novamente" />
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>

            <div class="profile-actions">
                <?php if ($user_tipo == 'cuidador'): ?>
                    <a href="painel-cuidador.php" class="btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Voltar ao Painel
                    </a>
                <?php else: ?>
                    <a href="paciente-visualizacao.php" class="btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Voltar ao Painel
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
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
            document.getElementById('uploadFoto')?.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('previewFoto').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
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
