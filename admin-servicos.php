<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] != 'cuidador') {
    header('Location: login.php');
    exit;
}

include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}

$mensagem = '';
$erro = '';

$servicos = [];
$sql = "SELECT * FROM servicos ORDER BY nome ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $servicos[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fa-heart');
    $beneficios = trim($_POST['beneficios'] ?? '');
    $quando_contratar = trim($_POST['quando_contratar'] ?? '');
    $equipe = trim($_POST['equipe'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if (empty($nome)) {
        $erro = 'O nome do serviço é obrigatório.';
    } else {
        $sql_insert = "INSERT INTO servicos (nome, descricao, icone, beneficios, quando_contratar, equipe, ativo) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("ssssssi", $nome, $descricao, $icone, $beneficios, $quando_contratar, $equipe, $ativo);
        
        if ($stmt->execute()) {
            $mensagem = 'Serviço adicionado com sucesso!';
            $result = $conn->query($sql);
            $servicos = [];
            while ($row = $result->fetch_assoc()) {
                $servicos[] = $row;
            }
        } else {
            $erro = 'Erro ao adicionar serviço: ' . $stmt->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fa-heart');
    $beneficios = trim($_POST['beneficios'] ?? '');
    $quando_contratar = trim($_POST['quando_contratar'] ?? '');
    $equipe = trim($_POST['equipe'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if ($id <= 0 || empty($nome)) {
        $erro = 'Dados inválidos.';
    } else {
        $sql_update = "UPDATE servicos SET 
                       nome = ?, descricao = ?, icone = ?, 
                       beneficios = ?, quando_contratar = ?, equipe = ?, ativo = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("ssssssii", $nome, $descricao, $icone, $beneficios, $quando_contratar, $equipe, $ativo, $id);
        
        if ($stmt->execute()) {
            $mensagem = 'Serviço atualizado com sucesso!';
            $result = $conn->query($sql);
            $servicos = [];
            while ($row = $result->fetch_assoc()) {
                $servicos[] = $row;
            }
        } else {
            $erro = 'Erro ao atualizar serviço: ' . $stmt->error;
        }
    }
}

if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    if ($id > 0) {
        $sql_delete = "DELETE FROM servicos WHERE id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensagem = 'Serviço excluído com sucesso!';
            $result = $conn->query($sql);
            $servicos = [];
            while ($row = $result->fetch_assoc()) {
                $servicos[] = $row;
            }
        } else {
            $erro = 'Erro ao excluir serviço.';
        }
    }
}

$edit_servico = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    if ($id > 0) {
        $sql_edit = "SELECT * FROM servicos WHERE id = ?";
        $stmt = $conn->prepare($sql_edit);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result_edit = $stmt->get_result();
        if ($result_edit->num_rows > 0) {
            $edit_servico = $result_edit->fetch_assoc();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Gerenciar Serviços</title>
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
        
        .dashboard { padding: 32px 0 48px; }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        .dashboard-header h1 i { color: var(--secondary); margin-right: 12px; }
        
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .admin-tabs {
            display: flex;
            gap: 4px;
            background: var(--gray-200);
            border-radius: var(--radius);
            padding: 4px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-weight: 500;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            font-family: inherit;
        }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .tab-btn i { margin-right: 8px; }
        
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .form-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 16px;
        }
        .form-card h3 i { color: var(--secondary); margin-right: 8px; }
        
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 4px;
            font-size: 0.85rem;
        }
        .form-group label .required { color: var(--red); margin-left: 2px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
            background: var(--gray-50);
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-group .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .form-group .checkbox input[type="checkbox"] {
            width: auto;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-success { background: var(--green); color: white; }
        .btn-success:hover { background: #219a52; transform: translateY(-2px); }
        .btn-danger { background: var(--red); color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-warning { background: var(--orange); color: white; }
        .btn-warning:hover { background: #e08e0b; transform: translateY(-2px); }
        .btn-sm { padding: 6px 14px; font-size: 0.8rem; }
        
        .table-responsive { overflow-x: auto; }
        .table {
            width: 100%;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            border-collapse: collapse;
        }
        .table th {
            background: var(--gray-50);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.8rem;
            text-transform: uppercase;
            border-bottom: 2px solid var(--gray-200);
        }
        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        .table tr:hover td { background: var(--gray-50); }
        .table .status-badge {
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-badge.ativo { background: #d4edda; color: #155724; }
        .status-badge.inativo { background: #f8d7da; color: #721c24; }
        .table .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            .dashboard-header h1 { font-size: 1.3rem; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
            .table td, .table th { padding: 8px 10px; font-size: 0.8rem; }
            .table .actions { flex-direction: column; }
            .table .actions .btn { width: 100%; justify-content: center; }
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
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil) && is_file($foto_perfil)): ?>
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

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-cogs"></i> Gerenciar Serviços</h1>
                <a href="painel-cuidador.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <div class="admin-tabs">
                <button class="tab-btn active" data-tab="listar"><i class="fas fa-list"></i> Listar Serviços</button>
                <button class="tab-btn" data-tab="adicionar"><i class="fas fa-plus-circle"></i> Adicionar</button>
                <?php if ($edit_servico): ?>
                    <button class="tab-btn active" data-tab="editar"><i class="fas fa-edit"></i> Editar</button>
                <?php endif; ?>
            </div>

            <div id="tab-listar" class="tab-content active">
                <div class="form-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ícone</th>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($servicos) > 0): ?>
                                    <?php foreach ($servicos as $servico): ?>
                                        <tr>
                                            <td><?php echo $servico['id']; ?></td>
                                            <td><i class="fas <?php echo !empty($servico['icone']) ? $servico['icone'] : 'fa-heart'; ?>" style="font-size:1.2rem;color:var(--secondary);"></i></td>
                                            <td><strong><?php echo htmlspecialchars($servico['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(substr($servico['descricao'] ?? '', 0, 50)) . '...'; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $servico['ativo'] ? 'ativo' : 'inativo'; ?>">
                                                    <?php echo $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="?editar=<?php echo $servico['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <a href="servico-detalhe.php?id=<?php echo $servico['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>
                                                    <a href="?excluir=<?php echo $servico['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este serviço?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:40px;color:var(--gray-600);">
                                            <i class="fas fa-briefcase" style="font-size:2rem;display:block;margin-bottom:12px;color:var(--gray-200);"></i>
                                            Nenhum serviço cadastrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tab-adicionar" class="tab-content">
                <div class="form-card">
                    <h3><i class="fas fa-plus-circle"></i> Adicionar Novo Serviço</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nome <span class="required">*</span></label>
                            <input type="text" name="nome" placeholder="Nome do serviço" required />
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" placeholder="Descrição detalhada do serviço"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ícone (Font Awesome)</label>
                                <input type="text" name="icone" value="fa-heart" placeholder="fa-heart" />
                                <small style="color:var(--gray-600);font-size:0.75rem;">
                                    <i class="fas fa-info-circle"></i> 
                                    Use classes do <a href="https://fontawesome.com/icons" target="_blank" style="color:var(--secondary);">Font Awesome</a>
                                </small>
                            </div>
                            <div class="form-group">
                                <label>Ativo</label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ativo" checked /> 
                                    Serviço ativo
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Benefícios (um por linha)</label>
                            <textarea name="beneficios" placeholder="Atendimento personalizado&#10;Profissionais qualificados&#10;Acompanhamento contínuo"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Quando Contratar</label>
                            <textarea name="quando_contratar" placeholder="Quando buscar atendimento..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Equipe</label>
                            <textarea name="equipe" placeholder="Enfermeiros, técnicos, cuidadores..."></textarea>
                        </div>
                        <button type="submit" name="adicionar" class="btn btn-success" style="width:100%;justify-content:center;">
                            <i class="fas fa-save"></i> Adicionar Serviço
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($edit_servico): ?>
                <div id="tab-editar" class="tab-content active">
                    <div class="form-card">
                        <h3><i class="fas fa-edit"></i> Editar Serviço</h3>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $edit_servico['id']; ?>" />
                            <div class="form-group">
                                <label>Nome <span class="required">*</span></label>
                                <input type="text" name="nome" value="<?php echo htmlspecialchars($edit_servico['nome'] ?? ''); ?>" required />
                            </div>
                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea name="descricao"><?php echo htmlspecialchars($edit_servico['descricao'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ícone (Font Awesome)</label>
                                    <input type="text" name="icone" value="<?php echo htmlspecialchars($edit_servico['icone'] ?? 'fa-heart'); ?>" placeholder="fa-heart" />
                                </div>
                                <div class="form-group">
                                    <label>Ativo</label>
                                    <label class="checkbox">
                                        <input type="checkbox" name="ativo" <?php echo ($edit_servico['ativo'] ?? 1) ? 'checked' : ''; ?> /> 
                                        Serviço ativo
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Benefícios (um por linha)</label>
                                <textarea name="beneficios"><?php echo htmlspecialchars($edit_servico['beneficios'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Quando Contratar</label>
                                <textarea name="quando_contratar"><?php echo htmlspecialchars($edit_servico['quando_contratar'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Equipe</label>
                                <textarea name="equipe"><?php echo htmlspecialchars($edit_servico['equipe'] ?? ''); ?></textarea>
                            </div>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                                <button type="submit" name="editar" class="btn btn-primary" style="flex:1;justify-content:center;">
                                    <i class="fas fa-save"></i> Atualizar Serviço
                                </button>
                                <a href="admin-servicos.php" class="btn btn-secondary" style="flex:1;justify-content:center;background:var(--gray-200);color:var(--gray-800);padding:10px 24px;border-radius:60px;font-weight:600;text-decoration:none;text-align:center;">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
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
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                });
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
