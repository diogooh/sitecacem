<?php
session_start(); // Habilitado session_start

// Verifica se o utilizador está logado e tem permissão para aceder
if (!isset($_SESSION['user_id'])) {
    error_log("Debug: User ID not set. Redirecting to login.");
    header("Location: login.php");
    exit();
}

$allowed_types = ['atleta', 'treinador', 'dirigente', 'admin']; // Adicionado 'admin' também

if (!in_array($_SESSION['tipo'], $allowed_types)) {
     error_log("Debug: User type not allowed ({$_SESSION['tipo']}). Redirecting to login.");
     header("Location: login.php");
    exit();
}

require 'db.php';

error_log("Debug: Session check passed. Processing POST request.");

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Debug: Request method is POST.");
    // Recupera o user_id (sempre necessário)
    $user_id = $_POST['user_id'] ?? null;

    if (empty($user_id)) {
        error_log("Error: user_id is empty or null in POST request.");
        header("Location: perfil_atleta.php?error=no_user_id");
        exit();
    }

    $update_fields = [];
    $params = [];
    $param_types = '';

    // Verifica se é uma atualização da área de administração para informações desportivas
    $is_admin_update = isset($_POST['admin_update']) && $_POST['admin_update'] === 'true';
    
    error_log("Debug: is_admin_update is " . ($is_admin_update ? 'true' : 'false') . ".");

    if ($is_admin_update) {
        // Atualização de informações desportivas por admin
        error_log("Debug: Processing admin update.");
        
        // Verificar se os campos esperados existem no POST
        $escalao = isset($_POST['escalao']) ? $_POST['escalao'] : null;
        $posicao = isset($_POST['posicao']) ? $_POST['posicao'] : null;
        $numero = isset($_POST['numero']) ? $_POST['numero'] : null;
        $lateralidade = isset($_POST['lateralidade']) ? $_POST['lateralidade'] : null;

        if (isset($escalao)) { $update_fields[] = "escalao = ?"; $params[] = $escalao; $param_types .= 's'; }
        if (isset($posicao)) { $update_fields[] = "posicao = ?"; $params[] = $posicao; $param_types .= 's'; }
        if (isset($numero)) { $update_fields[] = "numero = ?"; $params[] = $numero; $param_types .= 'i'; }
        if (isset($lateralidade)) { $update_fields[] = "pe_dominante = ?"; $params[] = $lateralidade; $param_types .= 's'; }

        // Nota: O escalão está ligado a escalao_id na tabela users. A atualização direta pelo nome aqui
        // pode não ser a melhor abordagem se houver uma tabela separada para escalões. 
        // Idealmente, selecionaria o escalao_id baseado no nome ou usaria um selectbox com IDs.
        // Para simplificar agora, estamos a guardar o nome diretamente na coluna 'escalao'.
        // Se a base de dados usa escalao_id, esta lógica precisará ser ajustada para buscar o ID correto.

    } else {
        // Atualização de informações pessoais pelo próprio atleta
        error_log("Debug: Processing athlete personal info update.");

        // Verificar se os campos esperados existem no POST
        $nome = isset($_POST['nome']) ? $_POST['nome'] : null;
        $data_nascimento = isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $telefone = isset($_POST['telefone']) ? $_POST['telefone'] : null;
        $nif = isset($_POST['nif']) ? $_POST['nif'] : null;
        $morada = isset($_POST['morada']) ? $_POST['morada'] : null;

        if (isset($nome)) { $update_fields[] = "nome = ?"; $params[] = $nome; $param_types .= 's'; }
        if (isset($data_nascimento)) { $update_fields[] = "data_nascimento = ?"; $params[] = $data_nascimento; $param_types .= 's'; }
        if (isset($email)) { $update_fields[] = "email = ?"; $params[] = $email; $param_types .= 's'; }
        if (isset($telefone)) { $update_fields[] = "telefone = ?"; $params[] = $telefone; $param_types .= 's'; }
        if (isset($nif)) { $update_fields[] = "nif = ?"; $params[] = $nif; $param_types .= 's'; }
        if (isset($morada)) { $update_fields[] = "morada = ?"; $params[] = $morada; $param_types .= 's'; }

        // Lidar com o upload da foto de perfil (apenas na atualização pelo atleta)
        if (isset($_FILES['profile-photo-upload']) && $_FILES['profile-photo-upload']['error'] == UPLOAD_ERR_OK) {
            error_log("Debug: File upload detected. File info: " . json_encode($_FILES['profile-photo-upload']));

            $upload_dir = '../uploads/'; // Diretório de upload (ajustar conforme a estrutura de pastas)
            
            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    error_log("Error: Failed to create upload directory: " . $upload_dir);
                    header("Location: perfil_atleta.php?id=" . $user_id . "&error=upload_dir_creation_failed");
                    exit();
                }
                error_log("Debug: Created upload directory: " . $upload_dir);
            }

            $file_tmp = $_FILES['profile-photo-upload']['tmp_name'];
            
            // Log detalhado do arquivo recebido
            error_log("Debug: File upload details:");
            error_log("Debug: - Original name: " . $_FILES['profile-photo-upload']['name']);
            error_log("Debug: - MIME type: " . $_FILES['profile-photo-upload']['type']);
            error_log("Debug: - Size: " . $_FILES['profile-photo-upload']['size']);
            error_log("Debug: - Error code: " . $_FILES['profile-photo-upload']['error']);
            
            // Gerar um nome de ficheiro único para evitar colisões
            $file_ext = strtolower(pathinfo($_FILES['profile-photo-upload']['name'], PATHINFO_EXTENSION));
            error_log("Debug: - File extension: " . $file_ext);
            
            // Normalizar a extensão do arquivo
            if ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                $file_ext = 'jpg'; // Padronizar para .jpg
            }
            
            $new_file_name = 'perfil_' . $user_id . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $new_file_name;
            $db_file_path = 'uploads/' . $new_file_name;

            error_log("Debug: - New filename: " . $new_file_name);
            error_log("Debug: - Target file: " . $target_file);
            error_log("Debug: - DB file path: " . $db_file_path);

            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            // Verifica se é uma imagem real
            $check = getimagesize($file_tmp);
            if($check !== false) {
                error_log("Debug: File is an image - " . $check["mime"] . ".");
                $uploadOk = 1;
            } else {
                error_log("Error: File is not an image.");
                header("Location: perfil_atleta.php?id=" . $user_id . "&error=not_image");
                exit();
            }

            // Verifica o tamanho do ficheiro (ex: 5MB)
            if ($_FILES['profile-photo-upload']['size'] > 5000000) {
                error_log("Error: File is too large.");
                header("Location: perfil_atleta.php?id=" . $user_id . "&error=file_too_large");
                exit();
            }

            // Permite certos formatos de ficheiro
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if(!in_array($imageFileType, $allowed_types)) {
                error_log("Error: Invalid file type: " . $imageFileType . ". Allowed types: " . implode(", ", $allowed_types));
                header("Location: perfil_atleta.php?id=" . $user_id . "&error=invalid_file_type");
                exit();
            }

            // Verifica se $uploadOk está definido como 0 por algum erro
            if ($uploadOk == 0) {
                error_log("Error: Upload not OK. uploadOk = " . $uploadOk);
                header("Location: perfil_atleta.php?id=" . $user_id . "&error=upload_failed");
                exit();
            } else {
                // Tenta mover o arquivo
                if (move_uploaded_file($file_tmp, $target_file)) {
                    error_log("Debug: File moved successfully to " . $target_file);
                    
                    // Verificar se o arquivo existe após o move
                    if (file_exists($target_file)) {
                        error_log("Debug: File exists after move: " . $target_file);
                        error_log("Debug: File size after move: " . filesize($target_file));
                        error_log("Debug: File permissions: " . substr(sprintf('%o', fileperms($target_file)), -4));
                    } else {
                        error_log("Error: File does not exist after move: " . $target_file);
                    }

                    // Ficheiro carregado com sucesso, atualiza o caminho na base de dados
                    $update_fields[] = "foto_perfil = ?";
                    $params[] = $db_file_path; // Usar o caminho relativo para a DB
                    $param_types .= 's';
                    
                    error_log("Debug: Adding foto_perfil to update fields with value: " . $db_file_path);
                } else {
                    $move_error = error_get_last();
                    error_log("Error: Failed to move uploaded file. PHP error: " . json_encode($move_error));
                    error_log("Error: Upload directory permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4));
                    header("Location: perfil_atleta.php?id=" . $user_id . "&error=move_upload_failed");
                    exit();
                }
            }
        }
    }

    // Constrói a query SQL dinamicamente
    if (!empty($update_fields)) {
        error_log("Debug: Building SQL query.");
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $user_id; // Adiciona o user_id aos parâmetros
        $param_types .= 'i'; // Adiciona o tipo para user_id (integer)

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            error_log("Debug: SQL query prepared: " . $sql);
            error_log("Debug: Binding parameters: types=" . $param_types . ", values=" . json_encode($params));
            
            // Liga os parâmetros dinamicamente (usando o operador spread para simplificar)
            $stmt->bind_param($param_types, ...$params);

            if ($stmt->execute()) {
                error_log("Debug: Database update successful. Redirecting.");
                // Redireciona de volta para a página correta com uma mensagem de sucesso (opcional)
                $redirect_url = $is_admin_update ? "admin_atleta_perfil.php?id=" . $user_id . "&success=sports_info_updated" : "perfil_atleta.php?id=" . $user_id . "&success=profile_updated";
                header("Location: " . $redirect_url);
                exit();
            } else {
                $error_message = "Erro ao atualizar o registo na base de dados: " . $stmt->error;
                error_log("Error: Database update failed. " . $error_message);
                $redirect_url = $is_admin_update ? "admin_atleta_perfil.php?id=" . $user_id . "&error=" . urlencode($error_message) : "perfil_atleta.php?id=" . $user_id . "&error=" . urlencode($error_message);
                header("Location: " . $redirect_url);
                exit();
            }
            $stmt->close();
        } else {
            $error_message = "Erro na preparação da query SQL: " . $conn->error;
            error_log("Error: Query preparation failed. " . $error_message);
            $redirect_url = $is_admin_update ? "admin_atleta_perfil.php?id=" . $user_id . "&error=" . urlencode($error_message) : "perfil_atleta.php?id=" . $user_id . "&error=" . urlencode($error_message);
            header("Location: " . $redirect_url);
            exit();
        }
    } else {
        error_log("Debug: No fields to update. Redirecting.");
        $redirect_url = $is_admin_update ? "admin_atleta_perfil.php?id=" . $user_id : "perfil_atleta.php?id=" . $user_id;
        header("Location: " . $redirect_url);
        exit();
    }

    $conn->close();
} else {
    error_log("Debug: Request method not POST. Redirecting to athlete profile or admin dashboard.");
    $redirect_url = isset($_SESSION['tipo']) && ($_SESSION['tipo'] == 'treinador' || $_SESSION['tipo'] == 'dirigente' || $_SESSION['tipo'] == 'admin') ? "admin_dashboard.php" : "perfil_atleta.php";
    header("Location: " . $redirect_url);
    exit();
}
?> 