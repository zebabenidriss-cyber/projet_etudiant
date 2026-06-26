<?php
require_once 'init.php';
require_once 'header.php';

/* ============================================================
   RÉCUPÉRATION DES PROPRIÉTÉS
============================================================ */
/* ============================================================
   RÉCUPÉRATION DES PROPRIÉTÉS AVEC FILTRES
============================================================ */
try {
  // 1. Base de la requête
  $sql = 'SELECT p.id_propriete, p.titre, p.type_bien, p.zone, p.prix, p.modele, p.superficie,
               COALESCE(ph.chemin_photo, p.image_url) AS photo
        FROM proprietes p
        LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
        WHERE p.statut = "publiee"';

  $params = [];

  // 2. Ajout dynamique des conditions
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

  // 3. Tri et limite
  $sql .= ' ORDER BY p.id_propriete DESC LIMIT 6';

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $dernieres_annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  $dernieres_annonces = [];
  error_log("Erreur annonces : " . $e->getMessage());
}

/* ============================================================
   SESSION USER
============================================================ */
$est_logge = false; // force toujours vers connexion

$action_favori = $est_logge ? 'ajouter_favori.php?id=' : 'connexion.php';
$action_visite = $est_logge ? 'faire_demande.php?id=' : 'connexion.php';

/* ============================================================
   HELPERS SAFE (ANTI WARNING PHP 8+)
============================================================ */
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrixIndex($p) {
    return number_format((float)$p, 0, ',', ' ') . ' FCFA';
}

function typeBienIconIndex($t) {
    switch (strtolower($t ?? '')) {
        case 'villa': return 'fa-house';
        case 'appartement': return 'fa-building';
        case 'terrain': return 'fa-mountain-sun';
        case 'commerce': return 'fa-store';
        case 'batiment': return 'fa-warehouse';
        default: return 'fa-building';
    }
}
?>

<style>
  /* ===== UTILITAIRES ===== */
  .container { max-width: 1200px; margin: 0 auto; padding: 0 5%; }
  .or { color: #C9A84C; }

  .btn-or {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 28px;
    background: #C9A84C;
    color: #0E0E0E;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.25s;
    letter-spacing: 0.02em;
  }
  .btn-or:hover { background: #E8C96A; }

  .btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: transparent;
    color: #FAFAF8;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #444;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.25s;
  }
  .btn-outline:hover { border-color: #C9A84C; color: #C9A84C; }

  .section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 700;
    color: #0E0E0E;
    line-height: 1.25;
    margin-bottom: 30px;
  }
  .section-title.light { color: #FAFAF8; }

  .section-sub { color: #6B6B6B; font-size: 0.95rem; max-width: 520px; margin-bottom: 40px; }

  /* ===== BARRE DE RECHERCHE FILTRE ===== */
  .search-bar-wrap { background: #F2EFE9; padding: 40px 5%; border-bottom: 1px solid #e0dbd3; }
  .search-bar { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
  .search-field { display: flex; flex-direction: column; gap: 6px; }
  .search-field label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #6B6B6B; }
  
  .search-field select, .search-field input {
    padding: 11px 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    color: #0E0E0E;
    background: white;
    outline: none;
    transition: border-color 0.2s;
  }
  .search-field select:focus, .search-field input:focus { border-color: #C9A84C; }

  .btn-search {
    padding: 11px 28px;
    background: #0E0E0E;
    color: white;
    border: none;
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.25s;
  }
  .btn-search:hover { background: #C9A84C; color: #0E0E0E; }

  /* ===== GRILLE DE BIENS ===== */
  .section-annonces { padding: 60px 0; background: #FAFAF8; }
  .annonces-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

  .prop-card {
    background: white;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #eee;
    transition: all 0.25s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .prop-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.05); }
  
  .prop-img { position: relative; height: 210px; overflow: hidden; background: #1a1a1a; }
  .prop-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
  
  .prop-img-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #1a1a1a 0%, #2e2e2e 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: #C9A84C;
  }

  .prop-option {
    position: absolute; top: 12px; right: 12px;
    background: rgba(14,14,14,0.9); color: white;
    font-size: 0.7rem; font-weight: 600; padding: 4px 10px;
    border-radius: 2px; text-transform: uppercase;
  }
  .prop-option.location { background: #C0392B; }

  .prop-body { padding: 18px; flex-grow: 1; }
  .prop-type { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: #C9A84C; font-weight: 600; margin-bottom: 6px; }
  .prop-titre { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 600; color: #0E0E0E; margin-bottom: 8px; }
  .prop-zone { font-size: 0.8rem; color: #6B6B6B; display: flex; align-items: center; gap: 5px; margin-bottom: 14px; }

  .prop-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #f0f0f0; }
  .prop-prix { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; color: #0E0E0E; }
  .prop-superficie { font-size: 0.78rem; color: #6B6B6B; display: flex; align-items: center; gap: 4px; }

  .prop-actions { display: flex; gap: 8px; padding: 12px 18px; background: #fafaf8; border-top: 1px solid #eee; }
  .btn-card-action { flex: 1; text-align: center; padding: 9px; font-size: 0.78rem; font-weight: 600; border-radius: 4px; text-decoration: none; transition: all 0.2s; }
  .btn-card-action--fav { background: #fff; color: #0E0E0E; border: 1px solid #ddd; }
  .btn-card-action--fav:hover { border-color: #C0392B; color: #C0392B; }
  .btn-card-action--visite { background: #0E0E0E; color: #fff; }
  .btn-card-action--visite:hover { background: #C9A84C; color: #0E0E0E; }

  .empty-state { text-align: center; padding: 48px; color: #aaa; grid-column: 1 / -1; }

  /* ===== CTA BAILLEUR ===== */
  .section-cta { background: #0E0E0E; padding: 80px 5%; text-align: center; position: relative; }
  .section-cta .section-sub { color: #888; margin: 0 auto 36px; }
  .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 1024px) { .annonces-grid { grid-template-columns: repeat(2, 1fr); } .search-bar { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 640px) { .annonces-grid { grid-template-columns: 1fr; } .search-bar { grid-template-columns: 1fr; } }
</style>

<!-- ===== RECHERCHE ===== -->
<div class="search-bar-wrap">
  <form class="search-bar" method="GET" action="index.php">
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
      <input type="text" name="zone" id="zone" placeholder="Ex: Ouaga 2000, Pissy...">
    </div>
    <button type="submit" class="btn-search">
      <i class="fas fa-search"></i> Filtrer
    </button>
  </form>
</div>

<!-- ===== CATALOGUE ===== -->
<section class="section-annonces">
  <div class="container">
    
    <h2 class="section-title">Nos Propriétés</h2>

    <div class="annonces-grid">
      <?php if(empty($dernieres_annonces)): ?>
        <div class="empty-state">
          <i class="fas fa-folder-open" style="font-size:2.5rem; margin-bottom:12px;"></i>
          <p>Aucun bien n'est répertorié pour le moment.</p>
        </div>
      <?php else: ?>
        <?php foreach($dernieres_annonces as $p): ?>
        <div class="prop-card" onclick="window.location='propriete_detail.php?id=<?= $p['id_propriete'] ?>&from=index'" style="cursor:pointer;">
  <div>
    <div class="prop-img">
              <?php if(!empty($p['photo'])): ?>
  <img src="<?= htmlspecialchars(ltrim($p['photo'], '/')) ?>" alt="">
<?php else: ?>
                <div class="prop-img-placeholder">
                  <i class="fas <?= typeBienIconIndex($p['type_bien']) ?>"></i>
                </div>
              <?php endif; ?>
              <span class="prop-option <?= strtolower($p['modele']) ?>">
                <?= htmlspecialchars($p['modele']) ?>
              </span>
            </div>
            
            <div class="prop-body">
              <div class="prop-type"><?= htmlspecialchars($p['type_bien']) ?></div>
              <div class="prop-titre"><?= htmlspecialchars($p['titre']) ?></div>
              <div class="prop-zone">
                <i class="fas fa-location-dot" style="color:#C9A84C"></i>
                <?= htmlspecialchars($p['zone']) ?>
              </div>
              <div class="prop-footer">
                <div class="prop-prix">
                  <?= formatPrixIndex($p['prix']) ?>
                </div>
                <div class="prop-superficie">
                  <i class="fas fa-ruler-combined"></i>
                  <?= $p['superficie'] ? htmlspecialchars($p['superficie']) . ' m²' : '—' ?>
                </div>
              </div>
            </div>
          </div>

          <div class="prop-actions">
            <a href="<?= $action_favori . ($est_logge ? $p['id_propriete'] : '') ?>" class="btn-card-action btn-card-action--fav">
              <i class="fas fa-heart"></i> Favoris
            </a>
            <a href="<?= $action_visite . ($est_logge ? $p['id_propriete'] : '') ?>" class="btn-card-action btn-card-action--visite">
              <i class="fas fa-calendar-days"></i> Demander visite
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== ZONE PROPRIÉTAIRE ===== -->
<section class="section-cta">
  <h2 class="section-title light" style="margin-bottom:12px">
    Vous êtes propriétaire ?
  </h2>
  <p class="section-sub">
    Confiez la gestion de vos biens immobiliers à Habitat Horizon pour louer ou vendre en toute sérénité.
  </p>
  <div class="cta-btns">
    <a href="inscription.php?role=bailleur" class="btn-or">
      <i class="fas fa-plus"></i> Déposer une annonce
    </a>
  </div>
</section>

<?php include 'footer.php'; ?>