<?php
require_once 'init.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'agent') {
    header("Location: connexion.php");
    exit;
}
$id_agent = (int)$_SESSION['id_utilisateur'];
$id_client_actif = (int)($_GET['client'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['contenu'] ?? ''))) {
    $id_dest = (int)$_POST['id_destinataire'];
    $contenu = trim($_POST['contenu']);
    $db->prepare("INSERT INTO messages (id_expediteur, id_destinataire, contenu) VALUES (?, ?, ?)")
       ->execute([$id_agent, $id_dest, $contenu]);
    header("Location: messagerie_agent.php?client=$id_dest");
    exit;
}

$stmt_clients = $db->prepare("
    SELECT u.id_utilisateur, u.nom, u.prenom,
        (SELECT COUNT(*) FROM messages WHERE id_expediteur = u.id_utilisateur AND id_destinataire = ? AND lu = 0) AS non_lus
    FROM utilisateurs u
    WHERE u.id_agent = ? AND u.role = 'client'
");
$stmt_clients->execute([$id_agent, $id_agent]);
$liste_clients = $stmt_clients->fetchAll();

$messages = [];
$client_actif = null;

if ($id_client_actif > 0) {
    $db->prepare("UPDATE messages SET lu = 1 WHERE id_destinataire = ? AND id_expediteur = ?")
       ->execute([$id_agent, $id_client_actif]);

    $stmt = $db->prepare("
        SELECT m.*, u.prenom, u.nom
        FROM messages m
        JOIN utilisateurs u ON u.id_utilisateur = m.id_expediteur
        WHERE (m.id_expediteur = ? AND m.id_destinataire = ?)
           OR (m.id_expediteur = ? AND m.id_destinataire = ?)
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$id_agent, $id_client_actif, $id_client_actif, $id_agent]);
    $messages = $stmt->fetchAll();

    $sc = $db->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?");
    $sc->execute([$id_client_actif]);
    $client_actif = $sc->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messagerie — Agent Habitat Horizon</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
body { background: #0F1923; color: #E8EDF2; min-height: 100vh; display: flex; flex-direction: column; }
.topbar { background: #162230; border-bottom: 1px solid #243648; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; height: 56px; flex-shrink: 0; }
.topbar .brand { color: #D4A853; font-weight: 700; font-size: 0.95rem; }
.topbar a { color: #7A95A8; text-decoration: none; font-size: 0.82rem; padding: 6px 12px; border: 1px solid #243648; border-radius: 6px; transition: all 0.2s; }
.topbar a:hover { color: #D4A853; border-color: #D4A853; }
.chat-layout { display: flex; flex: 1; height: calc(100vh - 56px); overflow: hidden; }
.clients-list { width: 240px; background: #162230; border-right: 1px solid #243648; overflow-y: auto; flex-shrink: 0; display: flex; flex-direction: column; }
.clients-list h3 { padding: 14px 16px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: #7A95A8; border-bottom: 1px solid #243648; flex-shrink: 0; }
.client-item { display: flex; align-items: center; gap: 10px; padding: 13px 16px; text-decoration: none; color: #E8EDF2; border-bottom: 1px solid #1e2e3d; transition: background 0.15s; position: relative; }
.client-item:hover { background: #1C2E3D; }
.client-item.active { background: #1C2E3D; border-left: 3px solid #D4A853; }
.client-avatar { width: 34px; height: 34px; background: #D4A853; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0F1923; font-size: 0.8rem; flex-shrink: 0; }
.client-name { font-size: 0.85rem; font-weight: 600; }
.notif-badge { position: absolute; right: 12px; background: #E67E22; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; }
.chat-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.chat-header { background: #1C2E3D; padding: 13px 18px; border-bottom: 1px solid #243648; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.chat-avatar-h { width: 36px; height: 36px; background: #D4A853; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0F1923; font-size: 0.85rem; flex-shrink: 0; }
.chat-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
.msg-row { display: flex; align-items: flex-end; gap: 8px; max-width: 100%; }
.msg-row.moi { flex-direction: row-reverse; }
.msg-row.autre { flex-direction: row; }
.msg-avatar-s { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.62rem; font-weight: 700; flex-shrink: 0; align-self: flex-end; }
.msg-row.moi .msg-avatar-s { background: #D4A853; color: #0F1923; }
.msg-row.autre .msg-avatar-s { background: #243648; color: #D4A853; }
.msg-content { display: flex; flex-direction: column; max-width: 68%; }
.msg-row.moi .msg-content { align-items: flex-end; }
.msg-row.autre .msg-content { align-items: flex-start; }
.msg-bubble { padding: 10px 14px; border-radius: 18px; font-size: 0.86rem; line-height: 1.55; word-break: break-word; white-space: pre-wrap; display: inline-block; max-width: 100%; }
.msg-row.moi .msg-bubble { background: #D4A853; color: #0F1923; border-bottom-right-radius: 4px; }
.msg-row.autre .msg-bubble { background: #1C2E3D; color: #E8EDF2; border-bottom-left-radius: 4px; }
.msg-time { font-size: 0.64rem; color: #7A95A8; margin-top: 4px; padding: 0 4px; }
.chat-form { padding: 12px 16px; border-top: 1px solid #243648; display: flex; gap: 10px; align-items: center; background: #162230; flex-shrink: 0; }
.chat-form input { flex: 1; padding: 11px 16px; background: #0F1923; border: 1.5px solid #243648; border-radius: 24px; color: #E8EDF2; font-size: 0.86rem; outline: none; transition: border-color 0.2s; min-width: 0; }
.chat-form input:focus { border-color: #D4A853; }
.chat-form button { padding: 11px 20px; background: #D4A853; color: #0F1923; border: none; border-radius: 24px; font-weight: 700; cursor: pointer; font-size: 0.86rem; white-space: nowrap; flex-shrink: 0; }
.chat-form button:hover { background: #e8c96a; }
.no-client { flex: 1; display: flex; align-items: center; justify-content: center; color: #7A95A8; text-align: center; flex-direction: column; gap: 12px; }
.no-client .icon { font-size: 2.5rem; }
@media (max-width: 640px) {
    .clients-list { width: 70px; }
    .client-name { display: none; }
    .clients-list h3 { display: none; }
    .client-item { justify-content: center; padding: 12px 8px; }
    .chat-form button { padding: 10px 14px; font-size: 0.8rem; }
    .msg-content { max-width: 80%; }
}
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">Habitat-Horizon · Messagerie</div>
    <a href="agent.php">← Tableau de bord</a>
</div>

<div class="chat-layout">

    <div class="clients-list">
        <h3>Mes Clients</h3>
        <?php if (empty($liste_clients)): ?>
            <div style="padding:16px;font-size:0.78rem;color:#7A95A8;">Aucun client affecté.</div>
        <?php else: ?>
            <?php foreach ($liste_clients as $c): ?>
            <a href="?client=<?= $c['id_utilisateur'] ?>"
               class="client-item <?= $id_client_actif === (int)$c['id_utilisateur'] ? 'active' : '' ?>">
                <div class="client-avatar">
                    <?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?>
                </div>
                <div class="client-name"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                <?php if ($c['non_lus'] > 0): ?>
                    <div class="notif-badge"><?= $c['non_lus'] ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="chat-area">
        <?php if (!$id_client_actif || !$client_actif): ?>
            <div class="no-client">
                <div class="icon">💬</div>
                <p style="font-size:0.9rem;">Sélectionnez un client pour voir la conversation.</p>
            </div>
        <?php else: ?>
            <div class="chat-header">
                <div class="chat-avatar-h">
                    <?= strtoupper(substr($client_actif['prenom'],0,1).substr($client_actif['nom'],0,1)) ?>
                </div>
                <div>
                    <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($client_actif['prenom'].' '.$client_actif['nom']) ?></div>
                    <div style="font-size:0.72rem;color:#7A95A8;margin-top:2px;">Client</div>
                </div>
            </div>

            <div class="chat-messages" id="chatBox">
                <?php if (empty($messages)): ?>
                    <div class="no-client">
                        <div class="icon">💬</div>
                        <p style="font-size:0.88rem;">Aucun message pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m):
                        $est_moi = ((int)$m['id_expediteur'] === $id_agent);
                        $initiales = strtoupper(substr($m['prenom'],0,1).substr($m['nom'],0,1));
                    ?>
                    <div class="msg-row <?= $est_moi ? 'moi' : 'autre' ?>">
                        <div class="msg-avatar-s"><?= $initiales ?></div>
                        <div class="msg-content">
                            <div class="msg-bubble"><?= htmlspecialchars($m['contenu']) ?></div>
                            <div class="msg-time"><?= date('d/m H:i', strtotime($m['date_envoi'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form class="chat-form" method="POST">
                <input type="hidden" name="id_destinataire" value="<?= $id_client_actif ?>">
                <input type="text" name="contenu" placeholder="Écrire un message..." autocomplete="off" required>
                <button type="submit">Envoyer ➤</button>
            </form>
        <?php endif; ?>
    </div>

</div>

<script>
const box = document.getElementById('chatBox');
if (box) box.scrollTop = box.scrollHeight;
</script>
</body>
</html>