<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuração do banco - HOSTINGER
$servername = "localhost";
$username = "u426514541_tccdocrlh";
$password = "Safelife2026.";
$dbname = "u426514541_safelife";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("❌ Erro de conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

function getFotoPerfil($conn, $user_id) {
    $sql = "SELECT foto_perfil FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['foto_perfil'] ?? '';
}

function verificarFotoPerfil($foto) {
    if (!empty($foto) && file_exists($foto) && is_file($foto)) {
        return $foto;
    }
    return 'default-avatar.png';
}

function listarPacientes($conn, $cuidador_id) {
    try {
        $sql = "SELECT * FROM pacientes WHERE cuidador_id = ? ORDER BY nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cuidador_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pacientes = [];
        while ($row = $result->fetch_assoc()) {
            $pacientes[] = $row;
        }
        
        return [
            'success' => true,
            'pacientes' => $pacientes
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'pacientes' => [],
            'error' => $e->getMessage()
        ];
    }
}

function adicionarPaciente($conn, $cuidador_id, $nome, $idade, $condicao, $medicamentos, $responsavel, $telefone) {
    try {
        $sql = "INSERT INTO pacientes (cuidador_id, nome, idade, condicao, medicamentos, responsavel, telefone, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $cuidador_id, $nome, $idade, $condicao, $medicamentos, $responsavel, $telefone);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function adicionarAnotacao($conn, $cuidador_id, $paciente_id, $tipo, $titulo, $descricao) {
    try {
        $sql = "INSERT INTO anotacoes (cuidador_id, paciente_id, tipo, titulo, descricao) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $cuidador_id, $paciente_id, $tipo, $titulo, $descricao);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function listarAnotacoes($conn, $cuidador_id) {
    try {
        $sql = "SELECT a.*, p.nome as paciente_nome 
                FROM anotacoes a 
                LEFT JOIN pacientes p ON a.paciente_id = p.id 
                WHERE a.cuidador_id = ? 
                ORDER BY a.criado_em DESC 
                LIMIT 50";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cuidador_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $anotacoes = [];
        while ($row = $result->fetch_assoc()) {
            $anotacoes[] = $row;
        }
        
        return ['success' => true, 'anotacoes' => $anotacoes];
    } catch (Exception $e) {
        return ['success' => false, 'anotacoes' => [], 'error' => $e->getMessage()];
    }
}

function salvarFotoPerfil($arquivo, $user_id, $conn) {
    $erro = '';
    $caminho_completo = '';
    
    if ($arquivo['error'] != 0) {
        return ['erro' => 'Erro no upload do arquivo.', 'caminho' => ''];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
    
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime_type, $tipos_permitidos)) {
        return ['erro' => 'Formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP.', 'caminho' => ''];
    }
    
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        return ['erro' => 'A imagem deve ter no máximo 5MB.', 'caminho' => ''];
    }
    
    $pasta_uploads = 'uploads/';
    if (!file_exists($pasta_uploads)) {
        mkdir($pasta_uploads, 0777, true);
    }
    
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_arquivo = 'perfil_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
    $caminho_completo = $pasta_uploads . $nome_arquivo;
    
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        return ['erro' => 'Erro ao fazer upload da imagem.', 'caminho' => ''];
    }
    
    return ['erro' => '', 'caminho' => $caminho_completo];
}
?>
