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
// GESTION DES ACTIONS (Favoris et Visites)
// ═══════════════════════════════════════════════════════════════════════════
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id_prop = (int)$_GET['id'];

    // Action : Toggle Favori
    if ($action === 'toggle_favori') {
        $checkFav = $db->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_propriete = ?");
        $checkFav->execute([$client_id, $id_prop]);
        
        if ($checkFav->fetch()) {
            $del = $db->prepare("DELETE FROM favoris WHERE id_client = ? AND id_propriete = ?");
            $del->execute([$client_id, $id_prop]);
        } else {
            $ins = $db->prepare("INSERT INTO favoris (id_client, id_propriete) VALUES (?, ?)");
            $ins->execute([$client_id, $id_prop]);
        }
    }

    // Action : Demande de visite
   

    header("Location: client.php");
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// RÉCUPÉRATION DES DONNÉES D'AFFICHAGE
// ═══════════════════════════════════════════════════════════════════════════

// Infos Client
$stmtUser = $db->prepare('SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ? AND role = "client"');
$stmtUser->execute([$client_id]);
$client = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Liste Propriétés (MODIFIÉ : Ajout du COALESCE pour gérer Agent et Bailleur)
// 1. Base de la requête
$sql = 'SELECT p.id_propriete, p.titre, p.type_bien, p.zone, p.prix, p.modele, p.superficie,
               COALESCE(ph.chemin_photo, p.image_url) AS photo
        FROM proprietes p
        LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
        WHERE p.statut = "publiee"';

$params = [];

// 2. Ajout dynamique des conditions si les filtres sont utilisés
if (!empty($_GET['type'])) {
    $sql .= ' AND p.type_bien = :type';
    $params['type'] = $_GET['type'];
}

if (!empty($_GET['option'])) {
    $sql .= ' AND p.modele = :option';
    $params['option'] = $_GET['option'];
}

if (!empty($_GET['zone'])) {
    $sql .= ' AND p.zone LIKE :zone';
    $params['zone'] = '%' . $_GET['zone'] . '%';
}

$sql .= ' ORDER BY p.id_propriete DESC';

// 3. Préparation et exécution
$stmtProps = $db->prepare($sql);
$stmtProps->execute($params);
$proprietes = $stmtProps->fetchAll(PDO::FETCH_ASSOC);

// Favoris et Visites du client (pour l'affichage)
$stmtUserFavs = $db->prepare("SELECT id_propriete FROM favoris WHERE id_client = ?");
$stmtUserFavs->execute([$client_id]);
$mes_favoris_ids = $stmtUserFavs->fetchAll(PDO::FETCH_COLUMN);

$stmtUserVisits = $db->prepare("SELECT id_propriete, statut FROM demandes_visite WHERE id_client = ?");
$stmtUserVisits->execute([$client_id]);
$mes_visites_status = $stmtUserVisits->fetchAll(PDO::FETCH_KEY_PAIR);

// Compteurs KPI
$stmt_kpi = $db->prepare("SELECT COUNT(*) FROM demandes_visite WHERE id_client = ?");
$stmt_kpi->execute([$client_id]);
$nb_visites = (int)$stmt_kpi->fetchColumn();

$stmt_kpi = $db->prepare("SELECT COUNT(*) FROM demandes_visite WHERE id_client = ? AND statut = 'validee'");
$stmt_kpi->execute([$client_id]);
$nb_validees = (int)$stmt_kpi->fetchColumn();

$stmt_kpi = $db->prepare("SELECT COUNT(*) FROM demandes_visite WHERE id_client = ? AND statut = 'attente'");
$stmt_kpi->execute([$client_id]);
$nb_en_attente = (int)$stmt_kpi->fetchColumn();

$stmt_kpi = $db->prepare("SELECT COUNT(*) FROM favoris WHERE id_client = ?");
$stmt_kpi->execute([$client_id]);
$nb_favoris = (int)$stmt_kpi->fetchColumn();
?>



<style>

/* Reset local pour éviter les conflits */

.dash-wrap *, .dash-wrap *::before, .dash-wrap *::after { box-sizing: border-box; }



/* STYLE DU NOUVEAU HEADER RECRÉÉ */

.top-header-container {

    display: flex;

    justify-content: space-between;

    align-items: center;

    background-color: #ffffff;

    padding: 12px 40px;

    border-bottom: 1px solid #e8e8e8;

    font-family: 'DM Sans', sans-serif;

}

.brand-identity { display: flex; align-items: center; gap: 15px; }

.brand-logo-circle { width: 46px; height: 46px; border-radius: 50%; border: 2px solid #C9A84C; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: #fff; }

.brand-text-wrapper { display: flex; flex-direction: column; }

.brand-main-title { font-size: 1.3rem; font-weight: 700; color: #0E0E0E; font-family: 'Playfair Display', serif; }

.brand-main-title span { color: #C9A84C; }

.brand-subtitle { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; color: #999; }

.top-navigation-links { display: flex; gap: 32px; list-style: none; margin: 0; padding: 0; }

.top-navigation-links a { text-decoration: none; color: #555; font-weight: 500; font-size: 0.95rem; transition: color 0.2s; }

.top-navigation-links a:hover { color: #C9A84C; }

.top-header-logout-btn { background-color: #0E0E0E; color: #ffffff; padding: 10px 22px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 0.88rem; transition: background-color 0.2s; }

.top-header-logout-btn:hover { background-color: #C0392B; }



/* STYLE GENERAL DU CONTENU ET SIDEBAR */

.dash-wrap {

  display: grid;

  grid-template-columns: 240px 1fr;

  min-height: calc(100vh - 71px);

  background: #F4F2EE;

  font-family: 'DM Sans', sans-serif;

}

.dash-sidebar { background: #0E0E0E; padding: 32px 0; display: flex; flex-direction: column; position: sticky; top: 0; height: calc(100vh - 71px); overflow-y: auto; }

.dash-sidebar-logo { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: #FAFAF8; padding: 0 24px 28px; border-bottom: 1px solid #1e1e1e; margin-bottom: 20px; }

.dash-sidebar-logo span { color: #C9A84C; }

.dash-nav { list-style: none; padding: 0; margin: 0; flex: 1; }

.dash-nav li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #888; text-decoration: none; font-size: 0.875rem; font-weight: 500; border-left: 3px solid transparent; transition: all 0.2s; }

.dash-nav li a:hover, .dash-nav li a.active { color: #FAFAF8; background: #161616; border-left-color: #C9A84C; }

.dash-sidebar-user { padding: 20px 24px; border-top: 1px solid #1e1e1e; }

.dash-sidebar-user .user-name { font-size: 0.85rem; font-weight: 600; color: #FAFAF8; display: block; margin-bottom: 2px; }

.dash-sidebar-user .user-role { font-size: 0.72rem; color: #C9A84C; text-transform: uppercase; letter-spacing: 0.08em; }

.btn-deconnexion { display: block; margin-top: 12px; padding: 8px 14px; background: #222; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.78rem; text-align: center; transition: background 0.2s; }

.btn-deconnexion:hover { background: #C0392B; }



.dash-main { padding: 36px 40px; overflow-y: auto; }

.dash-page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: #0E0E0E; margin-bottom: 4px; }

.dash-page-sub { font-size: 0.85rem; color: #888; margin-bottom: 32px; }



/* Grille KPI */

.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 36px; }

.kpi-card { background: #fff; border-radius: 6px; padding: 20px 22px; border: 1px solid #e8e8e8; display: flex; align-items: center; gap: 16px; }

.kpi-icon { width: 44px; height: 44px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

.kpi-icon--or    { background: #FFF8E7; }

.kpi-icon--vert  { background: #EDFBF2; }

.kpi-icon--bleu  { background: #EEF4FF; }

.kpi-icon--rouge { background: #FFF0F0; }

.kpi-val { font-size: 1.6rem; font-weight: 700; color: #0E0E0E; line-height: 1; }

.kpi-label { font-size: 0.75rem; color: #888; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.06em; }



.section-title { font-size: 1rem; font-weight: 700; color: #0E0E0E; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

.section-title::after { content: ''; flex: 1; height: 1px; background: #e8e8e8; }



/* Grille Biens */

.favoris-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 36px; }

.favori-card { background: #fff; border-radius: 6px; border: 1px solid #e8e8e8; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s; position: relative; }

.favori-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,0.05); }

.favori-img { width: 100%; height: 160px; object-fit: cover; }

.favori-img-placeholder { width: 100%; height: 160px; background: #EEF4FF; display: flex; align-items: center; justify-content: center; font-size: 2rem; }

.favori-body { padding: 16px; flex-grow: 1; }

.favori-titre { font-size: 0.95rem; font-weight: 700; color: #0E0E0E; margin-bottom: 6px; }

.favori-meta { font-size: 0.8rem; color: #888; margin-bottom: 12px; }

.favori-prix { font-size: 1rem; font-weight: 700; color: #C9A84C; }

.favori-option { font-size: 0.68rem; background: #0E0E0E; color: #fff; padding: 2px 8px; border-radius: 3px; float: right; text-transform: uppercase; }



/* Badge Flottant pour l'état Favori ❤️ */

.badge-liked { position: absolute; top: 12px; right: 12px; background: rgba(255, 255, 255, 0.9); padding: 5px 8px; border-radius: 20px; font-size: 0.85rem; box-shadow: 0 2px 10px rgba(0,0,0,0.15); z-index: 5; }



.card-actions { display: flex; gap: 8px; padding: 12px 16px; border-top: 1px solid #f0f0f0; background: #fafaf8; }

.btn-action { flex: 1; text-align: center; padding: 8px 10px; font-size: 0.78rem; font-weight: 600; border-radius: 4px; text-decoration: none; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }

.btn-action--fav { background: #fff; color: #0E0E0E; border: 1px solid #e8e8e8; }

.btn-action--fav:hover { border-color: #C0392B; color: #C0392B; }

.btn-action--visite { background: #0E0E0E; color: #fff; }

.btn-action--visite:hover { background: #C9A84C; color: #0E0E0E; }



/* Styles d'états de Demande */

.status-badge { flex: 1; text-align: center; padding: 8px 10px; font-size: 0.78rem; font-weight: 600; border-radius: 4px; border: 1px solid transparent; cursor: default; }

.status-badge--attente { background: #FFF8E7; color: #C9A84C; border-color: rgba(201,168,76,0.3); }

.status-badge--valide { background: #EDFBF2; color: #2B8A3E; border-color: rgba(43,138,62,0.3); }



.empty-state { text-align: center; padding: 48px 24px; color: #aaa; grid-column: 1 / -1; }

.empty-state .empty-icon { font-size: 2.4rem; margin-bottom: 12px; }



/* ── MOBILE HEADER ── */
@media (max-width: 768px) {
  .top-header-container {
    padding: 10px 16px;
    flex-wrap: wrap;
    gap: 10px;
  }
  .top-navigation-links {
    gap: 14px;
  }
  .top-navigation-links a {
    font-size: 0.82rem;
  }
  .brand-main-title { font-size: 1.1rem; }

  /* ── LAYOUT ── */
  .dash-wrap {
    grid-template-columns: 1fr;
  }

  /* ── SIDEBAR → BARRE BAS ── */
  .dash-sidebar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    top: auto;
    height: auto;
    flex-direction: row;
    padding: 0;
    z-index: 200;
    border-top: 1px solid #1e1e1e;
  }
  .dash-sidebar-logo,
  .dash-sidebar-user { display: none; }
  .dash-nav {
    display: flex;
    flex-direction: row;
    width: 100%;
  }
  .dash-nav li { flex: 1; }
  .dash-nav li a {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 6px;
    font-size: 0.65rem;
    gap: 4px;
    border-left: none;
    border-top: 3px solid transparent;
    text-align: center;
  }
  .dash-nav li a.active,
  .dash-nav li a:hover {
    border-left: none;
    border-top-color: #C9A84C;
  }

  /* ── MAIN ── */
  .dash-main {
    padding: 20px 16px 90px; /* 90px pour ne pas être caché par la navbar bas */
  }
  .dash-page-title { font-size: 1.2rem; }

  /* ── KPI ── */
  .kpi-grid {
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 24px;
  }
  .kpi-card { padding: 14px 12px; gap: 10px; }
  .kpi-val { font-size: 1.3rem; }
  .kpi-label { font-size: 0.68rem; }
  .kpi-icon { width: 36px; height: 36px; font-size: 1rem; }

  /* ── GRILLE BIENS ── */
  .favoris-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
  .favori-img { height: 200px; }

  /* ── SEARCH BAR ── */
  .search-bar-wrap { padding: 20px 16px; }
  .search-bar {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  .btn-search {
    width: 100%;
    justify-content: center;
    padding: 11px;
  }
}

@media (max-width: 480px) {
  .top-navigation-links { display: none; } /* masqué, accès via sidebar bas */
  .top-header-container { justify-content: center; }
  .kpi-grid { grid-template-columns: 1fr 1fr; }
  .card-actions { flex-direction: column; gap: 6px; }
  .btn-action { width: 100%; }
  .favori-body { padding: 12px; }
  .favori-titre { font-size: 0.88rem; }
}

.search-bar-wrap { background: #F2EFE9; padding: 40px 5%; border-bottom: 1px solid #e0dbd3; }

.search-bar { 
  max-width: 1200px; 
  margin: 0 auto; 
  display: grid; 
  /* Définit 4 colonnes pour un alignement horizontal */
  grid-template-columns: 1fr 1fr 1fr auto; 
  gap: 12px; 
  align-items: end; 
}

.search-field { display: flex; flex-direction: column; gap: 6px; }
.search-field label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: #6B6B6B; }

.search-field select, .search-field input {
  padding: 11px 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
}

</style>



<div class="top-header-container">

    <div class="brand-identity">

        <div class="brand-logo-circle">🏠</div>

        <div class="brand-text-wrapper">

            <div class="brand-main-title">Habitat-<span>Horizon</span></div>

            <div class="brand-subtitle">Agence Immobilière</div>

        </div>

    </div>

   

    <ul class="top-navigation-links">

        <li><a href="mes_visites.php">Demande</a></li>

        <li><a href="Favoris.php">Favoris</a></li>

        <li><a href="service_client.php">Service client</a></li>

    </ul>



</div>



<div class="dash-wrap">



  <aside class="dash-sidebar">

    <div class="dash-sidebar-logo">

      Habitat<span>-Horizon</span>

    </div>



    <ul class="dash-nav">

      <li>

        <a href="client.php" class="active">

          📊 Vue d'ensemble

        </a>

      </li>

      <li>

        <a href="mes_visites.php">

          📅 Mes visites

        </a>

      </li>

      <li>

        <a href="Favoris.php">

          ❤️ Mes favoris

        </a>

      </li>

    </ul>



    <div class="dash-sidebar-user">

      <span class="user-name"><?= htmlspecialchars(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? 'Client')) ?></span>

      <span class="user-role">Client</span>

      <a href="index.php" class="btn-deconnexion">Se déconnecter</a>

    </div>

  </aside>



  <main class="dash-main">

    <div>

      <h1 class="dash-page-title">Bonjour, <?= htmlspecialchars($client['prenom'] ?? 'Utilisateur') ?> 👋</h1>

      <p class="dash-page-sub">Voici les dernières propriétés publiées par nos bailleurs.</p>

    </div>



    <div class="kpi-grid">

      <div class="kpi-card">

        <div class="kpi-icon kpi-icon--or">📋</div>

        <div>

          <div class="kpi-val"><?= $nb_visites ?></div>

          <div class="kpi-label">Demandes</div>

        </div>

      </div>

      <div class="kpi-card">

        <div class="kpi-icon kpi-icon--vert">✅</div>

        <div>

          <div class="kpi-val"><?= $nb_validees ?></div>

          <div class="kpi-label">Validées</div>

        </div>

      </div>

      <div class="kpi-card">

        <div class="kpi-icon kpi-icon--bleu">⏳</div>

        <div>

          <div class="kpi-val"><?= $nb_en_attente ?></div>

          <div class="kpi-label">En attente</div>

        </div>

      </div>

      <div class="kpi-card">

        <div class="kpi-icon kpi-icon--rouge">❤️</div>

        <div>

          <div class="kpi-val"><?= $nb_favoris ?></div>

          <div class="kpi-label">Favoris</div>

        </div>

      </div>

    </div>



    <h2 class="section-title">🏠 Les logements disponibles</h2>



    <div class="favoris-grid">

      <?php if (empty($proprietes)): ?>

        <div class="empty-state">

          <div class="empty-icon">📭</div>

          <p>Aucun bien n'a été publié par les bailleurs pour le moment.</p>

        </div>

      <?php else: ?>

        <?php foreach ($proprietes as $p):

            // Vérifications d'états pour le logement courant

            $is_favori = in_array($p['id_propriete'], $mes_favoris_ids);

            $statut_visite = isset($mes_visites_status[$p['id_propriete']]) ? $mes_visites_status[$p['id_propriete']] : null;

        ?>

       <div class="favori-card" onclick="window.location='propriete_detail.php?id=<?= $p['id_propriete'] ?>'" style="cursor:pointer;">
  <div>
    <?php if ($is_favori): ?>

                <div class="badge-liked">❤️</div>

            <?php endif; ?>



             <?php if (!empty($p['photo'])): ?>
  <img src="<?= htmlspecialchars(ltrim($p['photo'], '/')) ?>" class="favori-img" alt="">
<?php else: ?>

              <div class="favori-img-placeholder"><?= typeBienIcon($p['type_bien']) ?></div>

            <?php endif; ?>

           

            <div class="favori-body">

              <div class="favori-titre"><?= htmlspecialchars($p['titre']) ?></div>

              <div class="favori-meta">

                <?= typeBienIcon($p['type_bien']) ?> <?= ucfirst(str_replace('_', ' ', $p['type_bien'])) ?> · <?= htmlspecialchars($p['zone']) ?>

              </div>

              <div>

                <span class="favori-prix"><?= formatPrix((float)$p['prix']) ?></span>

                <span class="favori-option"><?= htmlspecialchars($p['modele']) ?></span>

              </div>

            </div>

          </div>



          <div class="card-actions">

            <a href="?action=toggle_favori&id=<?= $p['id_propriete'] ?>" class="btn-action btn-action--fav" style="<?= $is_favori ? 'color: #C9A84C; border-color: #C9A84C;' : '' ?>">

               <?= $is_favori ? '❤️ Aimé' : '🤍 Favori' ?>

            </a>

           

            <?php if ($statut_visite === 'attente'): ?>

                <span class="status-badge status-badge--attente">⏳ En attente</span>

            <?php elseif ($statut_visite === 'validee'): ?>

                <span class="status-badge status-badge--valide">✅ Validée</span>

                <?php elseif ($statut_visite === 'refusee'): ?>

<span class="status-badge"
      style="background:#FFF0F0;color:#C0392B;border:1px solid rgba(192,57,43,.3);">
    ❌ Refusée
</span>

<?php else: ?>

<a href="Demande.php?id=<?= $p['id_propriete'] ?>"
   class="btn-action btn-action--visite">
    📅 Visiter
</a>

<?php endif; ?>

          </div>

        </div>

        <?php endforeach; ?>

      <?php endif; ?>

    </div>

  </main>

</div>

<div class="search-bar-wrap">
  <form class="search-bar" method="GET" action="client.php">
    
    <div class="search-field">
      <label for="type">Type de bien</label>
     <select name="type" id="type">
  <option value="">Tous les types</option>
  <optgroup label="Villa">
    <option value="villa">Villa (plain-pied)</option>
    <option value="r_plus_1">Villa R+1</option>
    <option value="r_plus_2">Villa R+2</option>
    <option value="r_plus_3">Villa R+3</option>
  </optgroup>
  <optgroup label="Autres">
    <option value="appartement">Appartement</option>
    <option value="terrain">Terrain</option>
    <option value="commerce">Commerce</option>
    <option value="batiment">Bâtiment</option>
  </optgroup>
</select>
    </div>

    <div class="search-field">
      <label for="option">Option</label>
      <select name="option" id="option">
        <option value="">Vente &amp; Location</option>
        <option value="vente">Vente</option>
        <option value="location">Location</option>
      </select>
    </div>

    <div class="search-field">
      <label for="zone">Quartier / Zone</label>
      <input type="text" name="zone" id="zone" placeholder="Ex: Ouaga 2000">
    </div>

    <button type="submit" class="btn-search">
      <i class="fas fa-search"></i> Filtrer
    </button>
  </form>
</div>



<?php include 'footer.php'; ?> 

