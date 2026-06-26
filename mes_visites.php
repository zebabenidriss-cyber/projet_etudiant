<?php
require_once 'init.php';

// 1. Sécurité : accès client uniquement
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit;
}
$client_id = (int) $_SESSION['id_utilisateur'];

// 2. Récupération des infos du client (pour le menu) et des visites
$stmtUser = $db->prepare('SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?');
$stmtUser->execute([$client_id]);
$client = $stmtUser->fetch();

$stmt = $db->prepare("
    SELECT dv.*, 
           COALESCE(p.titre, 'Propriété supprimée') AS titre,
           COALESCE(p.zone, '-') AS zone,
           COALESCE(p.type_bien, '-') AS type_bien,
           COALESCE(p.modele, '-') AS modele,
           u_agent.nom AS agent_nom, u_agent.prenom AS agent_prenom, u_agent.telephone AS agent_tel,
           u_bailleur.nom AS bailleur_nom, u_bailleur.prenom AS bailleur_prenom
    FROM demandes_visite dv
    LEFT JOIN proprietes p ON p.id_propriete = dv.id_propriete
    LEFT JOIN utilisateurs u_agent ON u_agent.id_utilisateur = dv.id_agent
    LEFT JOIN utilisateurs u_bailleur ON u_bailleur.id_utilisateur = p.id_bailleur
    WHERE dv.id_client = ?
    ORDER BY dv.date_demande DESC
");
$stmt->execute([$client_id]);
$visites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes visites - Habitat Horizon</title>
    <style>
        body { margin: 0; font-family: 'DM Sans', sans-serif; background: #F4F2EE; }
        .top-header-integrated { display: flex; align-items: center; background: #ffffff; padding: 12px 40px; border-bottom: 1px solid #e8e8e8; height: 71px; }
        .dash-wrap { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 71px); }
        
        /* Sidebar et bloc utilisateur */
        .dash-sidebar { background: #0E0E0E; color: #FAFAF8; padding: 32px 0; display: flex; flex-direction: column; }
        .dash-nav { list-style: none; padding: 0; flex: 1; }
        .dash-nav li a { display: block; padding: 12px 24px; color: #888; text-decoration: none; border-left: 3px solid transparent; }
        .dash-nav li a.active { color: #FAFAF8; background: #161616; border-left-color: #C9A84C; }
        
        .dash-sidebar-user { padding: 20px 24px; border-top: 1px solid #1e1e1e; }
        .user-name { font-size: 0.85rem; font-weight: 600; color: #FAFAF8; display: block; }
        .btn-deconnexion { display: block; margin-top: 12px; padding: 8px 14px; background: #222; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.78rem; text-align: center; }
        .btn-deconnexion:hover { background: #C0392B; }
        
        .dash-main { padding: 36px 40px; }
        .dash-page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; color: #0E0E0E; margin-bottom: 24px; }
        
        .visite-table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .visite-table th { background: #fafaf8; padding: 16px; text-align: left; font-size: 0.85rem; color: #666; }
        .visite-table td { padding: 16px; border-top: 1px solid #eee; font-size: 0.9rem; }
        .pill { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .statut-validee { background: #e8f8f0; color: #27ae60; }
        .statut-refusee { background: #fdeaea; color: #c0392b; }
        .statut-attente { background: #fef5e7; color: #d35400; }
        .motif-box { background: #fff5f5; padding: 10px; border-left: 4px solid #c0392b; margin-top: 8px; font-size: 0.8rem; color: #c0392b; }
        .msg-valide { background: #f0fff4; padding: 10px; border-left: 4px solid #48bb78; margin-top: 8px; font-size: 0.8rem; color: #2f855a; }
    </style>
</head>
<body>

<div class="top-header-integrated">
    <div class="brand-identity" style="font-weight:700;">Habitat-<span>Horizon</span></div>
</div>

<div class="dash-wrap">
  <aside class="dash-sidebar">
    <ul class="dash-nav">
      <li><a href="client.php">📊 Vue d'ensemble</a></li>
      <li><a href="Demande.php" class="active">📅 Mes visites</a></li>
      <li><a href="Favoris.php">❤️ Mes favoris</a></li>
    </ul>
    
    <div class="dash-sidebar-user">
      <span class="user-name"><?= htmlspecialchars(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? 'Client')) ?></span>
    
    </div>
  </aside>

  <main class="dash-main">
    <h1 class="dash-page-title">📅 Mes demandes de visite</h1>

    <table class="visite-table">
        <thead>
            <tr>
                <th>Propriété</th>
                <th>Bailleur</th>
                <th>Date souhaitée</th>
                <th>Statut</th>
                <th>Infos Agent</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($visites as $v): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($v['titre']) ?></strong><br>
                    <small style="color:#666;">
                        <?= htmlspecialchars($v['type_bien']) ?> · 
                        <?= htmlspecialchars($v['modele'] ?? '') ?> · 
                        Zone: <?= htmlspecialchars($v['zone']) ?>
                    </small>
                </td>
                <td style="font-weight:600; color:#C9A84C;">
                    <?= htmlspecialchars(($v['bailleur_prenom'] ?? '') . ' ' . ($v['bailleur_nom'] ?? 'Non renseigné')) ?>
                </td>
                <td><?= !empty($v['date_souhaitee']) ? date('d/m/Y', strtotime($v['date_souhaitee'])) : 'Non précisée' ?></td>
                <td>
                    <span class="pill statut-<?= htmlspecialchars($v['statut']) ?>"><?= ucfirst($v['statut']) ?></span>
                    <?php if ($v['statut'] === 'refusee'): ?>
    <div class="motif-box">
        <strong>Motif :</strong> 
        <?= htmlspecialchars($v['message_agent'] ?? 'Aucun motif précisé.') ?>
    </div>
<?php endif; ?>
                    <?php if ($v['statut'] === 'validee'): ?>
                        <div class="msg-valide">✅ Vous serez contacté ultérieurement.</div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($v['agent_nom'])): ?>
                        <?= htmlspecialchars($v['agent_prenom'] . ' ' . $v['agent_nom']) ?><br>
                        <small>📞 <?= htmlspecialchars($v['agent_tel'] ?? 'N/A') ?></small>
                    <?php else: ?>
                        <small style="color:#999;">En attente de traitement...</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
  </main>
</div>

</body>
</html>