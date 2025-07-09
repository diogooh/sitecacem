<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header("Location: login.php");
    exit();
}

require 'db.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'aprovado' : 'rejeitado';
    $tipo = $_POST['tipo'] ?? null;

    if (!$tipo) {
        header("Location: admin-pendentes.php?error=tipo");
        exit();
    }

    // Buscar informações do usuário
    $stmt = $conn->prepare("SELECT nome, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        header("Location: admin-pendentes.php?error=1");
        exit();
    }

    // Atualização de status e tipo
    if ($status === 'aprovado') {
        $cip = $_POST['cip'] ?? null;
        if (empty($cip)) {
            header("Location: admin-pendentes.php?error=cip");
            exit();
        }
        $stmt = $conn->prepare("UPDATE users SET tipo = ?, status = ?, cip = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Erro ao preparar statement: " . $conn->error);
            header("Location: admin-pendentes.php?error=sql");
            exit();
        }
        $stmt->bind_param("sssi", $tipo, $status, $cip, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $user_id);
    }

    if ($stmt->execute()) {
        // Enviar email de notificação
        $mail = new PHPMailer(true);
        try {
            // Habilitar debug
            $mail->SMTPDebug = 2; // Habilita debug detalhado
            $mail->Debugoutput = 'error_log'; // Envia debug para error_log do PHP

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'antunesdiogo06@gmail.com';
            $mail->Password = 'rfde ypaa agsx ohik'; // Senha de app do Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('antunesdiogo06@gmail.com', 'ACC'); // Usando o mesmo email como remetente
            $mail->addAddress($user['email'], $user['nome']);

            $mail->CharSet = 'UTF-8'; // Garante que caracteres especiais sejam exibidos corretamente
            $mail->isHTML(true);
            $mail->Subject = 'Estado da sua Conta - ACC';
            $mail->Body = "Olá {$user['nome']},<br><br>";
            if ($status === 'aprovado') {
                $mail->Body .= "Temos o prazer de informar que a sua conta na plataforma ACC foi aprovada.<br>Já pode aceder ao sistema com as suas credenciais.<br><br>Bem-vindo à família ACC!<br><br>Com os melhores cumprimentos,<br>Associação Clube de Cacém";
            } else {
                $mail->Body .= "Lamentamos informar que a sua conta na plataforma ACC não foi aprovada.<br>Para mais informações, por favor contacte a administração do clube.<br><br>Com os melhores cumprimentos,<br>Associação Clube de Cacém";
            }

            $mail->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $mail->ErrorInfo);
        }

        header("Location: admin-pendentes.php?success=1");
    } else {
        header("Location: admin-pendentes.php?error=2");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processar Aprovação - ACC</title>
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <?php include __DIR__ . '/nav.php'; ?>
</head>
<body>
    <div class="account-pages my-5 pt-sm-0">
        <div class="container">
            <!-- end row -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Processar Aprovação</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" id="action">
                                <input type="hidden" name="user_id" id="user_id">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label">Usuário</label>
                                            <select class="form-control" id="user_selector" onchange="loadUserDetails()">
                                                <option value="">Selecione um usuário</option>
                                                <?php
                                                $stmt = $conn->prepare("SELECT id, nome FROM users WHERE tipo = 'pendente' ORDER BY nome ASC");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Adicione outros campos do formulário aqui -->
                                <button type="submit" class="btn btn-primary">Processar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
