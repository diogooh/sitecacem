<?php
session_start();
include_once 'db.php'; // Include your database connection

// Redirect if not logged in or not an athlete
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'atleta') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$athlete_escalao = '';

// Get athlete's escalao
$stmt = $conn->prepare("SELECT escalao_id FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $athlete_escalao = $row['escalao_id'];
}
$stmt->close();

// Buscar informações do atleta
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar mensagens não lidas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE atleta_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_assoc();
} else {
    $mensagens = ['count' => 0];
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Treinos - Clube Cacem</title>
    <link rel="stylesheet" href="/sitecacem/src/dashboard_atleta.css">
    <link rel="stylesheet" href="/sitecacem/src/treinos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-layout {
            display: flex !important;
            min-height: 100vh;
            background: #f4f6fb;
        }
        .dashboard-sidebar {
            width: 270px !important;
            background: linear-gradient(135deg, #004080 0%, #002b57 100%) !important;
            color: #fff !important;
            min-height: 100vh !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        }
        .sidebar-header {
            padding: 40px 20px 20px 20px !important;
            text-align: center !important;
        }
        .sidebar-header img {
            width: 110px !important;
            height: 110px !important;
            border-radius: 50% !important;
            border: 4px solid #fff !important;
            margin-bottom: 10px !important;
            object-fit: cover !important;
        }
        .sidebar-header h3 {
            color: #fff !important;
            font-size: 1.3em !important;
            margin: 0 0 5px 0 !important;
        }
        .sidebar-header p {
            color: #b3c6e0 !important;
            font-size: 1em !important;
            margin: 0 !important;
        }
        .sidebar-menu {
            display: flex !important;
            flex-direction: column !important;
            gap: 5px !important;
            margin-top: 30px !important;
        }
        .sidebar-menu .menu-item {
            color: #fff !important;
            padding: 14px 30px !important;
            text-decoration: none !important;
            font-size: 1.08em !important;
            border: none !important;
            background: none !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            transition: background 0.2s !important;
        }
        .sidebar-menu .menu-item.active, .sidebar-menu .menu-item:hover {
            background: rgba(255,255,255,0.12) !important;
        }
        .logout-section {
            margin-top: auto !important;
            padding: 30px 20px !important;
        }
        .logout-btn {
            width: 100% !important;
            background: #dc3545 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 0 !important;
            font-size: 1.1em !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: background 0.2s !important;
        }
        .logout-btn:hover {
            background: #b52a37 !important;
        }
        @media (max-width: 900px) {
            .dashboard-layout {
                flex-direction: column !important;
            }
            .dashboard-sidebar {
                width: 100% !important;
                min-height: auto !important;
                flex-direction: row !important;
                overflow-x: auto !important;
            }
            .sidebar-menu {
                flex-direction: row !important;
                gap: 0 !important;
                margin-top: 0 !important;
            }
            .sidebar-menu .menu-item {
                padding: 10px 12px !important;
                font-size: 1em !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo !empty($atleta['foto_perfil']) ? '/sitecacem/' . ltrim(str_replace(['../uploads/', 'uploads/'], 'uploads/', $atleta['foto_perfil']), '/') : '/sitecacem/img/default-avatar.png'; ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($atleta['nome']); ?></h3>
                <p>CIPA: <?php echo htmlspecialchars($atleta['cip'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard_atleta.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_atleta.php" class="menu-item">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="treinos.php" class="menu-item active">
                    <i class="fas fa-running"></i> Treinos
                </a>
                <a href="jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Jogos
                </a>
                <a href="mensagens.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php if ($mensagens['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pagamentos.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Pagamentos
                </a>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <h1>Meus Treinos</h1>

            <div class="treinos-list">
                <?php
                if ($athlete_escalao) {
                    $stmt = $conn->prepare("SELECT
                                                t.data,
                                                t.hora_inicio,
                                                t.hora_fim,
                                                t.local,
                                                t.treinador, -- Get the trainer's name directly from treinos
                                                t.id, -- Include treino ID
                                                t.descricao, -- Include training description
                                                t.anexo, -- Include training attachment
                                                m.nome AS nome_modalidade,
                                                e.nome AS nome_escalao
                                            FROM
                                                treinos t
                                            JOIN
                                                modalidades m ON t.modalidade_id = m.id
                                            JOIN
                                                users u ON t.treinador = u.nome -- Join treinos to users on trainer name
                                            JOIN
                                                escaloes e ON u.escalao_id = e.id -- Join users to escaloes on escalao_id
                                            WHERE
                                                u.escalao_id = ?
                                                AND u.tipo = 'treinador' -- Ensure the user is a trainer
                                            ORDER BY
                                                t.data DESC, t.hora_inicio DESC");

                    if (!$stmt) {
                        die("Erro na preparação da query (main treinos query): " . $conn->error);
                    }
                    $stmt->bind_param("i", $athlete_escalao);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="treino-card">';
                            echo '<h3>' . htmlspecialchars($row['nome_modalidade']) . ' - ' . htmlspecialchars($row['nome_escalao']) . '</h3>';
                            echo '<p><strong><i class="fas fa-calendar-alt"></i> Data:</strong> ' . date('d/m/Y', strtotime($row['data'])) . '</p>';
                            echo '<p><strong><i class="fas fa-clock"></i> Hora:</strong> ' . substr($row['hora_inicio'], 0, 5) . ' - ' . substr($row['hora_fim'], 0, 5) . '</p>';
                            echo '<p><strong><i class="fas fa-map-marker-alt"></i> Local:</strong> ' . htmlspecialchars($row['local']) . '</p>';
                            echo '<p><strong><i class="fas fa-user-tie"></i> Treinador:</strong> ' . htmlspecialchars($row['treinador']) . '</p>';
                            echo '<button class="btn-details" data-id="' . $row['id'] . '" data-descricao="' . htmlspecialchars($row['descricao'] ?? '') . '" data-anexo="' . htmlspecialchars($row['anexo'] ?? '') . '">Ver Detalhes</button>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Não há treinos agendados para o seu escalão.</p>';
                    }
                    $stmt->close();
                } else {
                    echo '<p>Não foi possível determinar o seu escalão para exibir os treinos.</p>';
                }
                $conn->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Modal Structure -->
    <div id="treinoDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Detalhes do Treino</h2>
            <p><strong>Descrição:</strong> <span id="modalDescricao"></span></p>
            <p id="modalAnexoContainer"><strong>Anexo:</strong> <a id="modalAnexoLink" href="#" target="_blank">Ver Anexo</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('treinoDetailsModal');
            const closeButton = document.querySelector('.close-button');
            const treinoCards = document.querySelectorAll('.btn-details');

            treinoCards.forEach(card => {
                card.addEventListener('click', function() {
                    const descricao = this.getAttribute('data-descricao');
                    const anexo = this.getAttribute('data-anexo');

                    document.getElementById('modalDescricao').innerText = descricao;

                    const anexoContainer = document.getElementById('modalAnexoContainer');
                    const anexoLink = document.getElementById('modalAnexoLink');

                    if (anexo) {
                        anexoLink.href = 'uploads/' + anexo;
                        anexoContainer.style.display = 'block';
                    } else {
                        anexoContainer.style.display = 'none';
                    }

                    modal.style.display = 'block';
                });
            });

            closeButton.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 