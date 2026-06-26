<?php
// 1. Initialisation unique
require_once 'init.php';

// 2. Sécurité de session
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit;
}
$client_id = (int) $_SESSION['id_utilisateur'];

// ═══════════════════════════════════════════════════════════════════════════
// TRAITEMENT DE LA SUPPRESSION DU FAVORI
// ═══════════════════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'retirer' && isset($_GET['id'])) {
    $id_propriete_a_retirer = (int)$_GET['id'];
    
    // Utilisation de $db (créé dans init.php)
    $stmtDel = $db->prepare('DELETE FROM favoris WHERE id_client = ? AND id_propriete = ?');
    $stmtDel->execute([$client_id, $id_propriete_a_retirer]);
    
    header('Location: Favoris.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// RÉCUPÉRATION DES DONNÉES
// ═══════════════════════════════════════════════════════════════════════════
$stmtUser = $db->prepare('SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?');
$stmtUser->execute([$client_id]);
$client = $stmtUser->fetch();

$stmtFavs = $db->prepare('
    SELECT p.id_propriete AS id, p.titre, p.type_bien, p.zone, p.prix, p.modele, ph.chemin_photo AS photo
    FROM favoris f
    JOIN proprietes p ON f.id_propriete = p.id_propriete
    LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
    WHERE f.id_client = ?
    ORDER BY f.id_favori DESC
');
$stmtFavs->execute([$client_id]);
$favoris = $stmtFavs->fetchAll();
?>

<style>
/* HEADER INTÉGRÉ UNIQUE */
.top-header-integrated { display: flex; align-items: center; background-color: #ffffff; padding: 12px 40px; border-bottom: 1px solid #e8e8e8; font-family: 'DM Sans', sans-serif; height: 71px; }
.brand-identity { display: flex; align-items: center; gap: 15px; }
.brand-logo-circle { width: 46px; height: 46px; border-radius: 50%; border: 2px solid #C9A84C; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: #fff; }
.brand-text-wrapper { display: flex; flex-direction: column; }
.brand-main-title { font-size: 1.3rem; font-weight: 700; color: #0E0E0E; font-family: 'Playfair Display', serif; line-height: 1.2; }
.brand-main-title span { color: #C9A84C; }
.brand-subtitle { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; color: #999; }

/* LAYOUT DASHBOARD */
.dash-wrap { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 71px); background: #F4F2EE; font-family: 'DM Sans', sans-serif; }
.dash-sidebar { background: #0E0E0E; padding: 32px 0; display: flex; flex-direction: column; position: sticky; top: 71px; height: calc(100vh - 71px); }
.dash-sidebar-logo { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: #FAFAF8; padding: 0 24px 28px; border-bottom: 1px solid #1e1e1e; margin-bottom: 20px; }
.dash-sidebar-logo span { color: #C9A84C; }
.dash-nav { list-style: none; padding: 0; margin: 0; flex: 1; }
.dash-nav li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #888; text-decoration: none; font-size: 0.875rem; font-weight: 500; border-left: 3px solid transparent; }
.dash-nav li a:hover, .dash-nav li a.active { color: #FAFAF8; background: #161616; border-left-color: #C9A84C; }
.dash-sidebar-user { padding: 20px 24px; border-top: 1px solid #1e1e1e; }
.dash-sidebar-user .user-name { font-size: 0.85rem; font-weight: 600; color: #FAFAF8; display: block; }
.btn-deconnexion { display: block; margin-top: 12px; padding: 8px 14px; background: #222; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.78rem; text-align: center; }
.btn-deconnexion:hover { background: #C0392B; }
.dash-main { padding: 36px 40px; }
.dash-page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: #0E0E0E; margin-bottom: 24px; }

.grid-favoris { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.card-fav { background: #fff; border-radius: 6px; border: 1px solid #e8e8e8; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
.card-img { width: 100%; height: 150px; object-fit: cover; background: #eee; }
.card-body { padding: 16px; }
.card-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 6px; color: #0E0E0E; }
.card-price { color: #C9A84C; font-weight: 700; }
.card-actions { padding: 12px 16px; background: #fafaf8; border-top: 1px solid #f0f0f0; text-align: center; }
.btn-remove { color: #C0392B; text-decoration: none; font-size: 0.8rem; font-weight: 600; transition: color 0.2s; }
.btn-remove:hover { color: #E74C3C; }
.empty-state { text-align: center; padding: 48px; color: #aaa; grid-column: 1 / -1; }
</style>

<div class="top-header-integrated">
    <div class="brand-identity">
        <div class="brand-logo-circle">🏠</div>
        <div class="brand-text-wrapper">
            <div class="brand-main-title">Habitat-<span>Horizon</span></div>
            <div class="brand-subtitle">Agence Immobilière</div>
        </div>
    </div>
</div>

<div class="dash-wrap">
  <aside class="dash-sidebar">
    <div class="dash-sidebar-logo">Habitat<span>-Horizon</span></div>
    <ul class="dash-nav">
      <li><a href="client.php">📊 Vue d'ensemble</a></li>
      <li><a href="mes_visites.php">📅 Mes visites</a></li>
      <li><a href="Favoris.php" class="active">❤️ Mes favoris</a></li>
    
    </ul>
    <div class="dash-sidebar-user">
      <span class="user-name"><?= htmlspecialchars(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) ?></span>
    </div>
  </aside>

  <main class="dash-main">
    <h1 class="dash-page-title">❤️ Mes propriétés favorites</h1>

    <div class="grid-favoris">
      <?php if (empty($favoris)): ?>
        <div class="empty-state"><p>💔 Aucun bien dans vos favoris pour le moment.</p></div>
      <?php else: ?>
        <?php foreach ($favoris as $f): ?>
        <div class="card-fav">
          <div>
            <img src="<?= !empty($f['photo']) ? htmlspecialchars($f['photo']) : 'log.png' ?>" class="card-img" alt="">
            <div class="card-body">
              <div class="card-title"><?= htmlspecialchars($f['titre']) ?></div>
              <div class="card-price"><?= number_format($f['prix'], 0, ',', ' ') ?> FCFA</div>
            </div>
          </div>
          <div class="card-actions">
            <a href="?action=retirer&id=<?= $f['id'] ?>" class="btn-remove" onclick="return confirm('Voulez-vous vraiment retirer ce bien de vos favoris ?');">❌ Retirer des favoris</a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include 'footer.php'; ?>