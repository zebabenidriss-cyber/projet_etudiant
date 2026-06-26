<?php
require_once 'init.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: connexion.php");
    exit;
}
$id_client = (int)$_SESSION['id_utilisateur'];
$id_agent  = 0;

$stmt = $db->prepare("SELECT id_agent FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$id_client]);
$row = $stmt->fetch();
if (!empty($row['id_agent'])) {
    $id_agent = (int)$row['id_agent'];
}

$stmt = $db->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$id_agent]);
$agent = $stmt->fetch();

$stmt = $db->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$id_client]);
$client = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['contenu'] ?? ''))) {
    $contenu = trim($_POST['contenu']);
    $db->prepare("INSERT INTO messages (id_expediteur, id_destinataire, contenu) VALUES (?, ?, ?)")
       ->execute([$id_client, $id_agent, $contenu]);
    header("Location: service_client.php");
    exit;
}

$db->prepare("UPDATE messages SET lu = 1 WHERE id_destinataire = ? AND id_expediteur = ?")
   ->execute([$id_client, $id_agent]);

$stmt = $db->prepare("
    SELECT m.*, u.prenom, u.nom
    FROM messages m
    JOIN utilisateurs u ON u.id_utilisateur = m.id_expediteur
    WHERE (m.id_expediteur = ? AND m.id_destinataire = ?)
       OR (m.id_expediteur = ? AND m.id_destinataire = ?)
    ORDER BY m.date_envoi ASC
");
$stmt->execute([$id_client, $id_agent, $id_agent, $id_client]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Client — Habitat Horizon</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
body { background: #F4F2EE; min-height: 100vh; display: flex; flex-direction: column; }
.topbar { background: #0E0E0E; color: white; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.topbar .brand { font-size: 1rem; font-weight: 700; color: #C9A84C; }
.topbar a { color: #aaa; text-decoration: none; font-size: 0.82rem; padding: 6px 12px; border: 1px solid #333; border-radius: 6px; transition: all 0.2s; }
.topbar a:hover { color: #C9A84C; border-color: #C9A84C; }
.chat-wrap { flex: 1; max-width: 700px; width: 100%; margin: 20px auto; background: white; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: hidden; height: calc(100vh - 100px); }
.chat-header { background: #0E0E0E; color: white; padding: 14px 18px; display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.chat-avatar { width: 38px; height: 38px; background: #C9A84C; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0E0E0E; font-size: 0.9rem; flex-shrink: 0; }
.chat-header-info .name { font-weight: 600; font-size: 0.9rem; }
.chat-header-info .role { font-size: 0.72rem; color: #aaa; margin-top: 2px; }
.chat-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background: #f5f5f7; }
.msg-row { display: flex; align-items: flex-end; gap: 8px; max-width: 100%; }
.msg-row.moi { flex-direction: row-reverse; }
.msg-row.autre { flex-direction: row; }
.msg-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; flex-shrink: 0; align-self: flex-end; }
.msg-row.moi .msg-avatar { background: #C9A84C; color: #0E0E0E; }
.msg-row.autre .msg-avatar { background: #0E0E0E; color: #C9A84C; }
.msg-content { display: flex; flex-direction: column; max-width: 70%; }
.msg-row.moi .msg-content { align-items: flex-end; }
.msg-row.autre .msg-content { align-items: flex-start; }
.msg-bubble { padding: 10px 14px; border-radius: 18px; font-size: 0.86rem; line-height: 1.55; word-break: break-word; white-space: pre-wrap; display: inline-block; max-width: 100%; }
.msg-row.moi .msg-bubble { background: #C9A84C; color: #0E0E0E; border-bottom-right-radius: 4px; }
.msg-row.autre .msg-bubble { background: #e5e5ea; color: #0E0E0E; border-bottom-left-radius: 4px; }
.msg-time { font-size: 0.65rem; color: #999; margin-top: 4px; padding: 0 4px; }
.chat-form { padding: 12px 16px; border-top: 1px solid #eee; display: flex; gap: 10px; align-items: center; background: white; flex-shrink: 0; }
.chat-form input { flex: 1; padding: 11px 16px; border: 1.5px solid #ddd; border-radius: 24px; font-size: 0.86rem; outline: none; transition: border-color 0.2s; min-width: 0; }
.chat-form input:focus { border-color: #C9A84C; }
.chat-form button { padding: 11px 20px; background: #C9A84C; color: #0E0E0E; border: none; border-radius: 24px; font-weight: 700; cursor: pointer; font-size: 0.86rem; white-space: nowrap; flex-shrink: 0; }
.chat-form button:hover { background: #e8cc7a; }
.empty-chat { text-align: center; color: #aaa; margin: auto; font-size: 0.88rem; }
.empty-chat .icon { font-size: 2.5rem; margin-bottom: 10px; }
@media (max-width: 600px) {
    .chat-wrap { margin: 0; border-radius: 0; height: calc(100vh - 56px); }
    .topbar { padding: 10px 14px; }
    .msg-content { max-width: 80%; }
    .chat-form { padding: 10px 12px; }
    .chat-form button { padding: 10px 14px; font-size: 0.8rem; }
}
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">Habitat-Horizon · Service Client</div>
    <a href="client.php">← Retour</a>
</div>

<div class="chat-wrap">
    <div class="chat-header">
        <div class="chat-avatar">
            <?= strtoupper(substr($agent['prenom'],0,1).substr($agent['nom'],0,1)) ?>
        </div>
        <div class="chat-header-info">
            <div class="name"><?= htmlspecialchars($agent['prenom'].' '.$agent['nom']) ?></div>
            <div class="role">Votre agent immobilier</div>
        </div>
    </div>

    <div class="chat-messages" id="chatBox">
        <?php if (empty($messages)): ?>
            <div class="empty-chat">
                <div class="icon">💬</div>
                <p>Commencez la conversation avec votre agent.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m):
                $est_moi = ((int)$m['id_expediteur'] === $id_client);
                $initiales = strtoupper(substr($m['prenom'],0,1).substr($m['nom'],0,1));
            ?>
            <div class="msg-row <?= $est_moi ? 'moi' : 'autre' ?>">
                <div class="msg-avatar"><?= $initiales ?></div>
                <div class="msg-content">
                    <div class="msg-bubble"><?= htmlspecialchars($m['contenu']) ?></div>
                    <div class="msg-time"><?= date('d/m H:i', strtotime($m['date_envoi'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="chat-form" method="POST">
        <input type="text" name="contenu" placeholder="Écrivez votre message..." autocomplete="off" required>
        <button type="submit">Envoyer ➤</button>
    </form>
</div>

<script>
const box = document.getElementById('chatBox');
if (box) box.scrollTop = box.scrollHeight;
</script>
</body>
</html>