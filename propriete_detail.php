<?php
require_once 'init.php';

// ── Récupération de l'ID ──────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// ── Rôle et session ──────────────────────────────────────────
$est_client  = isset($_SESSION['id_utilisateur']) && $_SESSION['role'] === 'client';
$est_connecte = isset($_SESSION['id_utilisateur']);
$client_id   = $est_client ? (int)$_SESSION['id_utilisateur'] : 0;

// ── Données de la propriété ──────────────────────────────────
$stmt = $db->prepare("
    SELECT p.*,
           u.nom AS bailleur_nom, u.prenom AS bailleur_prenom,
           COALESCE(ph.chemin_photo, p.image_url) AS photo
    FROM proprietes p
    JOIN utilisateurs u ON p.id_bailleur = u.id_utilisateur
    LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
    WHERE p.id_propriete = ? AND p.statut = 'publiee'
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) { header('Location: index.php'); exit; }

// ── Toutes les photos de la propriété ────────────────────────
$stmtPhotos = $db->prepare("SELECT chemin_photo FROM photos WHERE id_propriete = ?");
$stmtPhotos->execute([$id]);
$toutes_photos = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);

// ── Favori & visite (client seulement) ───────────────────────
$is_favori     = false;
$statut_visite = null;

if ($est_client) {
    // Toggle favori
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_favori') {
        $chk = $db->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_propriete = ?");
        $chk->execute([$client_id, $id]);
        if ($chk->fetch()) {
            $db->prepare("DELETE FROM favoris WHERE id_client = ? AND id_propriete = ?")->execute([$client_id, $id]);
        } else {
            $db->prepare("INSERT INTO favoris (id_client, id_propriete) VALUES (?, ?)")->execute([$client_id, $id]);
        }
        header("Location: propriete_detail.php?id=$id");
        exit;
    }

    $chkFav = $db->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_propriete = ?");
    $chkFav->execute([$client_id, $id]);
    $is_favori = (bool)$chkFav->fetch();

    $chkVis = $db->prepare("SELECT statut FROM demandes_visite WHERE id_client = ? AND id_propriete = ?");
    $chkVis->execute([$client_id, $id]);
    $row = $chkVis->fetch(PDO::FETCH_ASSOC);
    $statut_visite = $row ? $row['statut'] : null;
}

// ── Helpers ───────────────────────────────────────────────────
function typeBienIconDetail($t) {
    switch (strtolower($t ?? '')) {
        case 'villa': return '🏡';
        case 'appartement': return '🏢';
        case 'terrain': return '🌍';
        case 'commerce': return '🏪';
        case 'batiment': return '🏗️';
        default: return '🏠';
    }
}
function formatPrixDetail($p) {
    return number_format((float)$p, 0, ',', ' ') . ' FCFA';
}

// Page de retour selon le rôle
$vient_de_index = !isset($_GET['from']) || $_GET['from'] === 'index';
$page_retour = $est_client && !$vient_de_index ? 'client.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($p['titre']) ?> — Habitat Horizon</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --or: #C9A84C;
    --noir: #0E0E0E;
    --bg: #F4F2EE;
    --blanc: #ffffff;
    --muted: #888;
    --border: #e8e8e8;
    --success: #2B8A3E;
    --danger: #C0392B;
    --warn: #C9A84C;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--noir);
    min-height: 100vh;
}

/* ── HEADER ── */
.header {
    background: var(--blanc);
    border-bottom: 1px solid var(--border);
    padding: 14px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}
.brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.brand-circle { width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--or); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.brand-name { font-family: 'Playfair Display', serif; font-size: 1.2rem; font-weight: 700; color: var(--noir); }
.brand-name span { color: var(--or); }
.btn-retour {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 20px; background: var(--noir); color: var(--blanc);
    border-radius: 4px; text-decoration: none; font-size: 0.82rem; font-weight: 600;
    transition: background 0.2s;
}
.btn-retour:hover { background: var(--or); color: var(--noir); }

/* ── LAYOUT ── */
.page-wrap {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 5%;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 32px;
    align-items: start;
}

/* ── GALERIE ── */
.galerie { background: var(--blanc); border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
.photo-principale {
    width: 100%; height: 420px; object-fit: cover; display: block;
}
.photo-placeholder {
    width: 100%; height: 420px;
    background: linear-gradient(135deg, #1a1a1a, #2e2e2e);
    display: flex; align-items: center; justify-content: center;
    font-size: 5rem;
}
.miniatures {
    display: flex; gap: 8px; padding: 12px;
    overflow-x: auto; background: #fafaf8;
    border-top: 1px solid var(--border);
}
.miniature {
    width: 80px; height: 60px; object-fit: cover;
    border-radius: 4px; cursor: pointer; flex-shrink: 0;
    border: 2px solid transparent; transition: border-color 0.2s;
}
.miniature:hover, .miniature.active { border-color: var(--or); }

/* ── DESCRIPTION ── */
.section-desc {
    background: var(--blanc);
    border-radius: 8px;
    border: 1px solid var(--border);
    padding: 24px;
    margin-top: 20px;
}
.section-desc h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--noir);
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}
.desc-texte {
    font-size: 0.88rem;
    color: #555;
    line-height: 1.8;
    white-space: pre-line;
}

/* ── FICHE LATÉRALE ── */
.fiche {
    background: var(--blanc);
    border-radius: 8px;
    border: 1px solid var(--border);
    overflow: hidden;
    position: sticky;
    top: 80px;
}
.fiche-header {
    background: var(--noir);
    padding: 20px 22px;
}
.fiche-badge {
    display: inline-block;
    font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    padding: 3px 10px; border-radius: 3px;
    background: var(--or); color: var(--noir);
    margin-bottom: 10px;
}
.fiche-titre {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem; font-weight: 700;
    color: #ffffff; line-height: 1.3;
}
.fiche-body { padding: 20px 22px; }
.fiche-prix {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem; font-weight: 700;
    color: var(--or); margin-bottom: 20px;
}

.detail-liste { list-style: none; margin-bottom: 22px; }
.detail-liste li {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid #f0f0f0;
    font-size: 0.85rem;
}
.detail-liste li:last-child { border-bottom: none; }
.detail-liste .label { color: var(--muted); }
.detail-liste .valeur { font-weight: 600; color: var(--noir); }

/* ── BOUTONS ACTION ── */
.btn-action-group { display: flex; flex-direction: column; gap: 10px; }
.btn-fav, .btn-visite, .btn-connexion {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 13px; border-radius: 4px; font-size: 0.88rem;
    font-weight: 600; text-decoration: none; cursor: pointer;
    border: none; font-family: 'DM Sans', sans-serif;
    transition: all 0.2s; width: 100%;
}
.btn-fav {
    background: var(--blanc); color: var(--noir);
    border: 1px solid var(--border);
}
.btn-fav:hover, .btn-fav.active { border-color: var(--danger); color: var(--danger); background: #fff5f5; }
.btn-fav.active { background: #fff0f0; }

.btn-visite { background: var(--noir); color: var(--blanc); }
.btn-visite:hover { background: var(--or); color: var(--noir); }

.btn-connexion { background: var(--or); color: var(--noir); }
.btn-connexion:hover { background: #e8c96a; }

/* Badges statut visite */
.status-badge {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 12px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;
}
.status-badge--attente { background: #FFF8E7; color: var(--warn); border: 1px solid rgba(201,168,76,0.3); }
.status-badge--valide  { background: #EDFBF2; color: var(--success); border: 1px solid rgba(43,138,62,0.3); }
.status-badge--refuse  { background: #FFF0F0; color: var(--danger); border: 1px solid rgba(192,57,43,0.3); }

/* ── RESPONSIVE ── */
@media (max-width: 820px) {
    .page-wrap { grid-template-columns: 1fr; }
    .fiche { position: static; }
    .photo-principale { height: 280px; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <a href="<?= $page_retour ?>" class="brand">
        <div class="brand-circle">🏠</div>
        <div class="brand-name">Habitat-<span>Horizon</span></div>
    </a>
    <a href="<?= $page_retour ?>" class="btn-retour">← Retour</a>
</header>

<!-- CONTENU -->
<div class="page-wrap">

    <!-- COLONNE GAUCHE : Photo + Description -->
    <div>
        <!-- Galerie -->
        <div class="galerie">
            <?php
            // Photo principale : priorité à la table photos, sinon image_url
            $photo_src = !empty($p['photo']) ? $p['photo'] : null;
            ?>
            <?php if ($photo_src): ?>
                <img src="<?= htmlspecialchars($photo_src) ?>" class="photo-principale" id="photo-main" alt="<?= htmlspecialchars($p['titre']) ?>">
            <?php else: ?>
                <div class="photo-placeholder"><?= typeBienIconDetail($p['type_bien']) ?></div>
            <?php endif; ?>

            <!-- Miniatures si plusieurs photos -->
            <?php if (count($toutes_photos) > 1): ?>
            <div class="miniatures">
                <?php foreach ($toutes_photos as $i => $ph): ?>
                    <img src="<?= htmlspecialchars($ph) ?>"
                         class="miniature <?= $i === 0 ? 'active' : '' ?>"
                         onclick="changerPhoto(this)"
                         alt="Photo <?= $i+1 ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="section-desc">
            <h3>📋 Description</h3>
            <p class="desc-texte"><?= !empty($p['description']) ? htmlspecialchars($p['description']) : 'Aucune description disponible.' ?></p>
        </div>
    </div>

    <!-- COLONNE DROITE : Fiche + Actions -->
    <div>
        <div class="fiche">
            <div class="fiche-header">
                <div class="fiche-badge"><?= htmlspecialchars($p['modele']) ?></div>
                <div class="fiche-titre"><?= htmlspecialchars($p['titre']) ?></div>
            </div>

            <div class="fiche-body">
                <div class="fiche-prix"><?= formatPrixDetail($p['prix']) ?></div>

                <ul class="detail-liste">
                    <li>
                        <span class="label">Type</span>
                        <span class="valeur"><?= typeBienIconDetail($p['type_bien']) ?> <?= ucfirst(str_replace('_', ' ', $p['type_bien'])) ?></span>
                    </li>
                    <li>
                        <span class="label">Zone</span>
                        <span class="valeur">📍 <?= htmlspecialchars($p['zone']) ?></span>
                    </li>
                    <?php if (!empty($p['superficie'])): ?>
                    <li>
                        <span class="label">Superficie</span>
                        <span class="valeur"><?= htmlspecialchars($p['superficie']) ?> m²</span>
                    </li>
                    <?php endif; ?>
                    <li>
                        <span class="label">Option</span>
                        <span class="valeur"><?= ucfirst($p['modele']) ?></span>
                    </li>
                </ul>

                <!-- BOUTONS selon le contexte -->
                <div class="btn-action-group">

                    <?php if ($est_client && !$vient_de_index): ?>
                        <!-- Client connecté -->
                        <a href="?id=<?= $id ?>&action=toggle_favori"
                           class="btn-fav <?= $is_favori ? 'active' : '' ?>">
                            <?= $is_favori ? '❤️ Retiré des favoris' : '🤍 Ajouter aux favoris' ?>
                        </a>

                        <?php if ($statut_visite === 'attente'): ?>
                            <div class="status-badge status-badge--attente">⏳ Demande en attente</div>
                        <?php elseif ($statut_visite === 'validee'): ?>
                            <div class="status-badge status-badge--valide">✅ Visite validée</div>
                        <?php elseif ($statut_visite === 'refusee'): ?>
                            <div class="status-badge status-badge--refuse">❌ Visite refusée</div>
                        <?php else: ?>
                            <a href="Demande.php?id=<?= $id ?>" class="btn-visite">📅 Demander une visite</a>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Visiteur non connecté (index) -->
                        <a href="connexion.php" class="btn-fav">🤍 Ajouter aux favoris</a>
                        <a href="connexion.php" class="btn-connexion">📅 Demander une visite</a>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Changer la photo principale au clic sur une miniature
function changerPhoto(miniature) {
    document.getElementById('photo-main').src = miniature.src;
    document.querySelectorAll('.miniature').forEach(m => m.classList.remove('active'));
    miniature.classList.add('active');
}
</script>

</body>
</html>
