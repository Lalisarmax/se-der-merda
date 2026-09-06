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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            $senha_valida = false;
            if (password_get_info($user['senha'])['algo']) {
                $senha_valida = password_verify($senha, $user['senha']);
            } else {
                $senha_valida = ($user['senha'] == md5($senha));
            }
            
            if ($senha_valida) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_tipo'] = $user['tipo'];
                $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? '';
                
                if ($user['tipo'] == 'cuidador') {
                    $sql2 = "SELECT id FROM cuidadores WHERE usuario_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row = $result2->fetch_assoc()) {
                        $_SESSION['cuidador_id'] = $row['id'];
                    }
                }
                
                ?>
                <script>
                    localStorage.setItem('userLoggedIn', 'true');
                    localStorage.setItem('userType', '<?php echo addslashes($user['tipo']); ?>');
                    localStorage.setItem('userName', '<?php echo addslashes($user['nome']); ?>');
                    localStorage.setItem('userId', '<?php echo $user['id']; ?>');
                    
                    console.log('✅ Login salvo no localStorage');
                    console.log('userLoggedIn:', localStorage.getItem('userLoggedIn'));
                    console.log('userType:', localStorage.getItem('userType'));
                    console.log('userName:', localStorage.getItem('userName'));
                    
                    <?php if ($user['tipo'] == 'cuidador'): ?>
                        window.location.href = 'painel-cuidador.php';
                    <?php else: ?>
                        window.location.href = 'paciente-visualizacao.php';
                    <?php endif; ?>
                </script>
                <?php
                exit;
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeCare · Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           RESET E VARIÁVEIS
           ============================================================ */
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
            --shadow-lg: 0 20px 60px rgba(0,20,30,0.15);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: 0.3s ease;
            --red: #e74c3c;
            --green: #27ae60;
            --gradient-1: linear-gradient(135deg, #0b2b40 0%, #1a4b66 50%, #2d6b8f 100%);
            --gradient-2: linear-gradient(135deg, #3a7ca5 0%, #5a9cc5 100%);
        }

        /* ============================================================
           FUNDO DA PÁGINA: GRADIENTE AZUL (IGUAL À PÁGINA INICIAL)
           ============================================================ */
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gradient-1);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: rgba(255,255,255,0.05);
            transform: rotate(-20deg);
            pointer-events: none;
        }

        /* ============================================================
           CARD BRANCO CENTRALIZADO
           ============================================================ */
        .login-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }
        .login-container {
            background: var(--white);
            padding: 48px 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.15);
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-2);
        }

        .login-container .logo {
            text-align: center;
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 8px;
        }
        .login-container h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .login-container .subtitle {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 28px;
            font-size: 0.95rem;
        }

        /* ============================================================
           ALERTA DE ERRO
           ============================================================ */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert i {
            font-size: 1.1rem;
        }

        /* ============================================================
           FORMULÁRIO
           ============================================================ */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-group label i {
            color: var(--secondary);
            margin-right: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--gray-100);
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }

        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
            transition: var(--transition);
        }
        .password-toggle:hover {
            color: var(--primary);
        }

        /* ============================================================
           BOTÃO
           ============================================================ */
        .btn-login {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 4px;
            font-family: inherit;
        }
        .btn-login:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }

        /* ============================================================
           LINK CADASTRO
           ============================================================ */
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        .register-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        .register-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        /* ============================================================
           CREDENCIAIS DE TESTE
           ============================================================ */
        .test-credentials {
            margin-top: 24px;
            padding: 16px 20px;
            background: var(--gray-100);
            border-radius: var(--radius-sm);
            border: 1px dashed var(--gray-200);
            font-size: 0.8rem;
            color: var(--gray-600);
        }
        .test-credentials strong {
            color: var(--primary);
        }
        .test-credentials .row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .test-credentials .col {
            flex: 1;
            min-width: 130px;
        }
        .test-credentials code {
            background: var(--white);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            border: 1px solid var(--gray-200);
            display: inline-block;
            margin-top: 2px;
        }

        /* ============================================================
           RESPONSIVO
           ============================================================ */
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 20px;
            }
            .login-container .logo {
                font-size: 2.4rem;
            }
            .login-container h2 {
                font-size: 1.5rem;
            }
            .test-credentials .row {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="logo"><i class="fas fa-heartbeat"></i></div>
            <h2>Bem-vindo</h2>
            <p class="subtitle">Faça login para acessar sua conta</p>

            <?php if (!empty($erro)): ?>
                <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> E-mail</label>
                    <input type="email" name="email" placeholder="seu@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Senha</label>
                    <div class="password-wrapper">
                        <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required />
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <div class="register-link">
                Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
            </div>

            <div class="test-credentials">
                <strong>🧪 Contas de teste</strong>
                <div class="row">
                    <div class="col">
                        <strong>Paciente</strong><br>
                        <code>paciente@homecare.com</code><br>
                        Senha: <code>1234teste</code>
                    </div>
                    <div class="col">
                        <strong>Cuidador</strong><br>
                        <code>cuidador@homecare.com</code><br>
                        Senha: <code>1234teste</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const eye = document.getElementById('eyeIcon');
            if (senha.type === 'password') {
                senha.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                senha.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
