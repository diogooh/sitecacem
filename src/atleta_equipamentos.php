<?php
session_start();
require 'db.php';

// Verifica se o usuário está logado e é atleta
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'atleta') {
    header('Location: login.php');
    exit();
}

$atleta_id = $_SESSION['user_id'];

// Buscar dados do atleta para a sidebar
$stmt = $conn->prepare('SELECT nome, cip, foto_perfil FROM users WHERE id = ?');
$stmt->bind_param('i', $atleta_id);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Processar novo pedido de equipamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_pedido'])) {
    $tipo_equipamento = $_POST['tipo_equipamento'];
    $tamanho = isset($_POST['tamanho']) ? $_POST['tamanho'] : null;
    $numero = isset($_POST['numero']) ? $_POST['numero'] : null;
    $observacoes = $_POST['observacoes'];
    // Ajustar inserção conforme tipo
    $stmt = $conn->prepare('INSERT INTO pedidos_equipamentos (atleta_id, tipo_equipamento, tamanho, numero, observacoes) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $atleta_id, $tipo_equipamento, $tamanho, $numero, $observacoes);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        $msg_sucesso = 'Pedido enviado com sucesso!';
    } else {
        $msg_erro = 'Erro ao enviar pedido.';
    }
}

// Buscar equipamentos atuais do atleta
$stmt = $conn->prepare('SELECT * FROM equipamentos WHERE atleta_id = ?');
$stmt->bind_param('i', $atleta_id);
$stmt->execute();
$result_equip = $stmt->get_result();
$equip = $result_equip->fetch_assoc();
$stmt->close();

// Buscar pedidos feitos
$stmt = $conn->prepare('SELECT * FROM pedidos_equipamentos WHERE atleta_id = ? ORDER BY data_pedido DESC');
$stmt->bind_param('i', $atleta_id);
$stmt->execute();
$result_pedidos = $stmt->get_result();
$pedidos = [];
while ($row = $result_pedidos->fetch_assoc()) {
    $pedidos[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Equipamentos</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <style>
        .equip-table, .pedidos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .equip-table th, .equip-table td, .pedidos-table th, .pedidos-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .equip-table th, .pedidos-table th {
            background: #1976d2;
            color: #fff;
        }
        .equip-table tr:last-child td, .pedidos-table tr:last-child td {
            border-bottom: none;
        }
        .form-pedido {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .form-pedido label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-pedido input, .form-pedido select, .form-pedido textarea {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .form-pedido button {
            background: #1976d2;
            color: #fff;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
        }
        .msg-sucesso {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #a5d6a7;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .msg-erro {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo !empty($atleta['foto_perfil']) ? (strpos($atleta['foto_perfil'], 'uploads/') === 0 ? '/sitecacem/' . $atleta['foto_perfil'] : $atleta['foto_perfil']) : '/sitecacem/img/default-avatar.png'; ?>" alt="Perfil" style="width:110px; height:110px; border-radius:50%; border:4px solid #fff; margin-bottom:10px; object-fit:cover;">
                <h3><?php echo htmlspecialchars($atleta['nome']); ?></h3>
                <p>CIPA: <?php echo htmlspecialchars($atleta['cip'] ?? 'N/A'); ?></p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'dashboard_atleta.php' ? ' active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
                <a href="perfil_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'perfil_atleta.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> Perfil</a>
                <a href="treinos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'treinos.php' ? ' active' : '' ?>"><i class="fas fa-dumbbell"></i> Treinos</a>
                <a href="jogos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'jogos.php' ? ' active' : '' ?>"><i class="fas fa-futbol"></i> Jogos</a>
                <a href="mensagens.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'mensagens.php' ? ' active' : '' ?>"><i class="fas fa-envelope"></i> Mensagens</a>
                <a href="pagamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'pagamentos.php' ? ' active' : '' ?>"><i class="fas fa-euro-sign"></i> Pagamentos</a>
                <a href="atleta_equipamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'atleta_equipamentos.php' ? ' active' : '' ?>"><i class="fas fa-tshirt"></i> Equipamentos</a>
            </div>
            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="section-header">
                <h1>Meus Equipamentos</h1>
            </div>
            <?php if (isset($msg_sucesso)): ?>
                <div class="msg-sucesso"><?= htmlspecialchars($msg_sucesso) ?></div>
            <?php elseif (isset($msg_erro)): ?>
                <div class="msg-erro"><?= htmlspecialchars($msg_erro) ?></div>
            <?php endif; ?>
            <div class="dashboard-card">
                <h2 class="card-header">Equipamentos Atribuídos</h2>
                <table class="equip-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Equip. Jogo</th>
                            <th>Alt. A</th>
                            <th>Alt. B</th>
                            <th>Fato Treino</th>
                            <th>Mala</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($equip): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($equip['equip_jogo']) ?>
                                <?php if ($equip['equip_jogo_status'] == 'entregue'): ?>
                                    <span style="background: #e8f5e9; color: #28a745; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Entregue
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #ffc107; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($equip['alt_a']) ?>
                                <?php if ($equip['alt_a_status'] == 'entregue'): ?>
                                    <span style="background: #e8f5e9; color: #28a745; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Entregue
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #ffc107; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($equip['alt_b']) ?>
                                <?php if ($equip['alt_b_status'] == 'entregue'): ?>
                                    <span style="background: #e8f5e9; color: #28a745; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Entregue
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #ffc107; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($equip['fato_treino']) ?>
                                <?php if ($equip['fato_treino_status'] == 'entregue'): ?>
                                    <span style="background: #e8f5e9; color: #28a745; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Entregue
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #ffc107; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($equip['mala']) ?>
                                <?php if ($equip['mala_status'] == 'entregue'): ?>
                                    <span style="background: #e8f5e9; color: #28a745; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Entregue
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #ffc107; border-radius: 12px; padding: 2px 10px; font-size: 0.95em; font-weight: 600; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="5">Nenhum equipamento atribuído ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="dashboard-card">
                <h2 class="card-header">Solicitar Novo Equipamento</h2>
                <form method="post" class="form-pedido" style="padding:18px;" id="form-pedido-equip">
                    <input type="hidden" name="novo_pedido" value="1">
                    <label for="tipo_equipamento">Tipo de Equipamento:</label>
                    <select name="tipo_equipamento" id="tipo_equipamento" required onchange="toggleCamposEquipamento()">
                        <option value="">Selecione...</option>
                        <option value="equip_jogo">Equipamento de Jogo</option>
                        <option value="alt_a">Alternativo A</option>
                        <option value="alt_b">Alternativo B</option>
                        <option value="fato_treino">Fato de Treino</option>
                        <option value="mala">Mala</option>
                    </select>
                    <div id="campo-numero" style="display:none;">
                        <label for="numero">Número (camisola):</label>
                        <input type="number" name="numero" id="numero" min="1" max="99" placeholder="Ex: 10">
                    </div>
                    <div id="campo-tamanho" style="display:none;">
                        <label for="tamanho">Tamanho:</label>
                        <input type="text" name="tamanho" id="tamanho" placeholder="Ex: M, L, XL, 38...">
                    </div>
                    <label for="observacoes">Observações:</label>
                    <textarea name="observacoes" id="observacoes" rows="2" placeholder="Opcional"></textarea>
                    <button type="submit" class="btn-primary">Enviar Pedido</button>
                </form>
                <script>
                function toggleCamposEquipamento() {
                    var tipo = document.getElementById('tipo_equipamento').value;
                    var campoNumero = document.getElementById('campo-numero');
                    var campoTamanho = document.getElementById('campo-tamanho');
                    if (tipo === 'equip_jogo' || tipo === 'alt_a' || tipo === 'alt_b') {
                        campoNumero.style.display = '';
                        campoTamanho.style.display = '';
                        document.getElementById('numero').required = true;
                        document.getElementById('tamanho').required = true;
                    } else if (tipo === 'fato_treino') {
                        campoNumero.style.display = 'none';
                        campoTamanho.style.display = '';
                        document.getElementById('numero').required = false;
                        document.getElementById('tamanho').required = true;
                    } else {
                        campoNumero.style.display = 'none';
                        campoTamanho.style.display = 'none';
                        document.getElementById('numero').required = false;
                        document.getElementById('tamanho').required = false;
                    }
                }
                document.addEventListener('DOMContentLoaded', function() {
                    toggleCamposEquipamento();
                });
                </script>
            </div>
            <div class="dashboard-card">
                <h2 class="card-header">Meus Pedidos</h2>
                <table class="pedidos-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Número</th>
                            <th>Tamanho</th>
                            <th>Observações</th>
                            <th>Status</th>
                            <th>Data do Pedido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pedidos) > 0): ?>
                            <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['tipo_equipamento']) ?></td>
                                <td><?= htmlspecialchars($p['numero']) ?></td>
                                <td><?= htmlspecialchars($p['tamanho']) ?></td>
                                <td><?= htmlspecialchars($p['observacoes']) ?></td>
                                <td><?= htmlspecialchars($p['status']) ?></td>
                                <td><?= htmlspecialchars($p['data_pedido']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">Nenhum pedido realizado ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html> 