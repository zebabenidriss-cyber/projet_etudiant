<?php
require_once 'init.php';

/* =========================================================
   VARIABLES DE BASE (CORRECTION ERREURS PHP)
========================================================= */
$vue = $_GET['vue'] ?? 'liste';
$filtre_statut = $_GET['statut'] ?? 'tous';

/* =========================================================
   LABELS
========================================================= */
$type_labels = [
    'villa'=>'Villa', 'appartement'=>'Appartement', 'r_plus_1'=>'R+1',
    'r_plus_2'=>'R+2', 'r_plus_3'=>'R+3', 'terrain'=>'Terrain',
    'commerce'=>'Commerce', 'batiment'=>'Bâtiment'
];

$statut_labels = [
    'attente' => 'En attente',
    'publiee' => 'Publiée',
    'affectee' => 'Affectée',
    'refusee' => 'Refusée',
    'retiree' => 'Retirée'
];

/* =========================================================
   SESSION
========================================================= */
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'bailleur') {
    header("Location: connexion.php");
    exit;
}

$id_bailleur  = (int) $_SESSION['id_utilisateur'];
$nom_bailleur = htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']);

/* =========================================================
   ACTION FORMULAIRE (AJOUT ANNONCE)
========================================================= */
$msg_success = '';
$msg_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'supprimer') {
    $id = (int)($_POST['id_propriete'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM proprietes WHERE id_propriete = ? AND id_bailleur = ?")->execute([$id, $id_bailleur]);
    }
    header("Location: bailleur.php?vue=liste&deleted=1");
    exit;
}

if ($_POST['action'] === 'retirer') {
  $id = (int)($_POST['id_propriete'] ?? 0);
  if ($id > 0) {
      $prop_info = $db->prepare("SELECT titre FROM proprietes WHERE id_propriete = ? AND id_bailleur = ?");
      $prop_info->execute([$id, $id_bailleur]);
      $prop = $prop_info->fetch(PDO::FETCH_ASSOC);
      if ($prop) {
          $msg_notif = "Le bailleur " . $_SESSION['prenom'] . " " . $_SESSION['nom'] . " demande le retrait de la propriété \"" . $prop['titre'] . "\".";
          $db->prepare("INSERT INTO notifications (destinataire, message, id_propriete) VALUES ('manager', ?, ?)")->execute([$msg_notif, $id]);
      }
  }
  header("Location: bailleur.php?vue=liste&requested=1");
  exit;
}
if ($_POST['action'] === 'modifier') {
  $id    = (int)($_POST['id_propriete'] ?? 0);
  $titre = trim($_POST['titre'] ?? '');
  $type  = $_POST['type_bien'] ?? '';
  $mod   = $_POST['modele'] ?? '';
  $zone  = trim($_POST['zone'] ?? '');
  $prix  = (float)($_POST['prix'] ?? 0);
  $desc  = trim($_POST['description'] ?? '');
  $sup   = $_POST['superficie'] !== '' ? (float)$_POST['superficie'] : null;

  if ($id > 0 && $titre && $type && $mod && $zone && $prix) {
      $db->prepare("UPDATE proprietes SET titre=?, type_bien=?, modele=?, zone=?, superficie=?, prix=?, description=? WHERE id_propriete=? AND id_bailleur=?")
         ->execute([$titre, $type, $mod, $zone, $sup, $prix, $desc, $id, $id_bailleur]);

      if (!empty($_FILES['photo']['name'])) {
          $dir = "uploads/photos/";
          $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
          if (in_array($ext, ['jpg','jpeg','png','webp'])) {
              $file = uniqid('prop_').'.'.$ext;
              if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$file)) {
                  $db->prepare("UPDATE photos SET chemin_photo=? WHERE id_propriete=? AND est_principale=1")->execute([$dir.$file, $id]);
              }
          }
      }
      header("Location: bailleur.php?vue=liste&modified=1");
      exit;
  }
}

    if ($_POST['action'] === 'deposer') {

        $titre      = trim($_POST['titre'] ?? '');
        $type_bien  = $_POST['type_bien'] ?? '';
        $modele     = $_POST['modele'] ?? '';
        $zone       = trim($_POST['zone'] ?? '');
        $prix       = (float)($_POST['prix'] ?? 0);
        $desc       = trim($_POST['description'] ?? '');

        if (!$titre || !$type_bien || !$modele || !$zone || !$prix) {
            $msg_error = "Veuillez remplir tous les champs obligatoires.";
        } else {

            $stmt = $db->prepare("
                INSERT INTO proprietes
                (id_bailleur, titre, type_bien, modele, zone, prix, description, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'attente')
            ");

            $stmt->execute([
                $id_bailleur,
                $titre,
                $type_bien,
                $modele,
                $zone,
                $prix,
                $desc
            ]);

            $new_id = $db->lastInsertId();

            /* ================= DOCUMENT JUSTIFICATIF ================= */
if (!empty($_FILES['doc_justificatif']['name'])) {
    
  $dir_docs = "uploads/documents/";
  if (!is_dir($dir_docs)) mkdir($dir_docs, 0755, true);

  $ext_doc = strtolower(pathinfo($_FILES['doc_justificatif']['name'], PATHINFO_EXTENSION));

  // Vérifier si c'est bien un PDF
  if ($ext_doc === 'pdf') {
      $file_doc = 'doc_' . $new_id . '_' . uniqid() . '.' . $ext_doc;
      $path_doc = $dir_docs . $file_doc;

      if (move_uploaded_file($_FILES['doc_justificatif']['tmp_name'], $path_doc)) {
        // On insère le document dans la table 'documents'
        $nom_original = $_FILES['doc_justificatif']['name'];
        $stmt_doc = $db->prepare("
            INSERT INTO documents (id_propriete, nom_original, chemin_doc, type_doc) 
            VALUES (?, ?, ?, 'autre')
        ");
        $stmt_doc->execute([$new_id, $nom_original, $path_doc]);
    }
  }
}

            /* ================= IMAGE ================= */
            if (!empty($_FILES['photo']['name'])) {

                $dir = "uploads/photos/";
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg','jpeg','png','webp'])) {

                    $file = uniqid('prop_').'.'.$ext;
                    $path = $dir.$file;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $path)) {

                        $db->prepare("
                            INSERT INTO photos (id_propriete, chemin_photo, est_principale)
                            VALUES (?, ?, 1)
                        ")->execute([$new_id, $path]);
                    }
                }
            }
            header("Location: bailleur.php?vue=liste&success=1");
            exit;
        }
    }
}

if (isset($_GET['success']))  $msg_success = "✅ Annonce déposée avec succès !";
if (isset($_GET['deleted']))  $msg_success = "🗑️ Annonce supprimée.";
if (isset($_GET['retired']))   $msg_success = "🔒 Annonce retirée avec succès.";
if (isset($_GET['requested'])) $msg_success = "📨 Demande de retrait envoyée au manager.";
if (isset($_GET['modified'])) $msg_success = "✏️ Annonce modifiée avec succès.";
/* =========================================================
   STATS (IMPORTANT FIX)
========================================================= */
$stats = [
    'total' => 0,
    'attente' => 0,
    'publiee' => 0,
    'refusee' => 0,
    'retiree' => 0,
    'affectee' => 0
];

$stmt = $db->prepare("
    SELECT statut, COUNT(*) as nb
    FROM proprietes
    WHERE id_bailleur = ?
    GROUP BY statut
");
$stmt->execute([$id_bailleur]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $stats[$row['statut']] = $row['nb'];
    $stats['total'] += $row['nb'];
}

/* =========================================================
   LISTE PROPRIÉTÉS + IMAGE PRINCIPALE
========================================================= */
if ($filtre_statut !== 'tous') {
  $sql = "
      SELECT p.*, ph.chemin_photo AS photo_principale
      FROM proprietes p
      LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
      WHERE p.id_bailleur = ?
      AND TRIM(p.statut) = TRIM(?)
  ";
  $stmt = $db->prepare($sql);
  $stmt->execute([$id_bailleur, $filtre_statut]);
} else {
  $sql = "
      SELECT p.*, ph.chemin_photo AS photo_principale
      FROM proprietes p
      LEFT JOIN photos ph ON ph.id_propriete = p.id_propriete AND ph.est_principale = 1
      WHERE p.id_bailleur = ?
      GROUP BY p.id_propriete
  ";
  $stmt = $db->prepare($sql);
  $stmt->execute([$id_bailleur]);
}
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Espace Bailleur – Gestion Immobilière</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ── TOKENS ── */
:root {
  --navy:   #0f1f35;
  --navy2:  #162840;
  --gold:   #d4a853;
  --gold2:  #b8902e;
  --white:  #ffffff;
  --off:    #f6f5f0;
  --gray:   #8a95a3;
  --text:   #1a2332;
  --border: #e4e1d8;
  --green:  #2e7d52;
  --red:    #c0392b;
  --orange: #d4692b;
  --shadow: 0 2px 16px rgba(15,31,53,.10);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--off); color: var(--text); min-height: 100vh; }

/* ── SIDEBAR ── */
.sidebar {
  width: 256px; background: var(--navy); position: fixed;
  top: 0; left: 0; bottom: 0; z-index: 100;
  display: flex; flex-direction: column;
}
.sidebar-logo {
  padding: 28px 24px 22px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.brand { font-family: 'Playfair Display', serif; font-size: 19px; color: var(--white); line-height: 1.2; }
.brand span { color: var(--gold); }
.brand-sub { font-size: 10px; color: var(--gray); letter-spacing: .1em; text-transform: uppercase; margin-top: 4px; }

.sidebar-nav { flex: 1; padding: 18px 0; overflow-y: auto; }
.nav-label { font-size: 9.5px; font-weight: 600; letter-spacing: .13em; text-transform: uppercase; color: var(--gray); padding: 14px 24px 5px; }
.nav-item {
  display: flex; align-items: center; gap: 11px; padding: 10px 24px;
  color: rgba(255,255,255,.55); font-size: 13.5px; text-decoration: none;
  border-left: 3px solid transparent; transition: all .17s;
}
.nav-item:hover { color: var(--white); background: rgba(255,255,255,.05); }
.nav-item.active { color: var(--gold); background: rgba(212,168,83,.09); border-left-color: var(--gold); font-weight: 500; }
.nav-icon { width: 16px; text-align: center; opacity: .8; }

.sidebar-footer { padding: 18px 24px; border-top: 1px solid rgba(255,255,255,.08); }
.user-info { display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; color: var(--navy); flex-shrink: 0; }
.user-name { font-size: 13px; color: var(--white); font-weight: 500; }
.user-role { font-size: 11px; color: var(--gray); }

/* ── MAIN ── */
.main { margin-left: 256px; min-height: 100vh; }
.topbar {
  background: var(--white); border-bottom: 1px solid var(--border);
  padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
}
.topbar-title { font-size: 15px; font-weight: 600; color: var(--text); }
.btn-gold {
  background: var(--gold); color: var(--navy); border: none; border-radius: 7px;
  padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background .17s; text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
}
.btn-gold:hover { background: var(--gold2); }

.content { padding: 28px 32px; }

/* ── STATS ── */
.stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 28px; }
.stat-card {
  background: var(--white); border-radius: 10px; padding: 18px 20px;
  box-shadow: var(--shadow); border-top: 3px solid var(--border);
}
.stat-card.gold  { border-top-color: var(--gold); }
.stat-card.green { border-top-color: var(--green); }
.stat-card.orange{ border-top-color: var(--orange); }
.stat-card.red   { border-top-color: var(--red); }
.stat-card.gray  { border-top-color: var(--gray); }
.stat-nb { font-family: 'Playfair Display', serif; font-size: 30px; font-weight: 700; color: var(--text); line-height: 1; }
.stat-lbl { font-size: 11.5px; color: var(--gray); margin-top: 5px; font-weight: 500; }

/* ── ALERTS ── */
.alert { border-radius: 8px; padding: 13px 18px; font-size: 13.5px; margin-bottom: 20px; }
.alert-success { background: #e8f5ed; color: #1e5c38; border: 1px solid #a8d5b5; }
.alert-error   { background: #fdeaea; color: #8b1c1c; border: 1px solid #f0aaaa; }

/* ── FILTRES ── */
.filters { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn {
  padding: 7px 16px; border-radius: 20px; border: 1.5px solid var(--border);
  background: var(--white); font-size: 12.5px; color: var(--gray); cursor: pointer;
  text-decoration: none; transition: all .15s; font-weight: 500;
}
.filter-btn:hover, .filter-btn.active { border-color: var(--gold); color: var(--gold); background: rgba(212,168,83,.07); }

/* ── TABLE ANNONCES ── */
.table-wrap { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
.table-head { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.table-head h2 { font-size: 14.5px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; }
thead th { padding: 11px 16px; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--gray); background: #fafaf8; border-bottom: 1px solid var(--border); }
tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fdfcf9; }
td { padding: 13px 16px; font-size: 13px; vertical-align: middle; }
.prop-thumb { width: 48px; height: 38px; object-fit: cover; border-radius: 6px; background: var(--off); display: block; }
.prop-thumb-placeholder { width: 48px; height: 38px; border-radius: 6px; background: var(--off); display: flex; align-items: center; justify-content: center; font-size: 18px; }
.prop-title { font-weight: 600; font-size: 13.5px; color: var(--text); }
.prop-meta { font-size: 11.5px; color: var(--gray); margin-top: 2px; }

/* BADGES */
.badge { display: inline-block; padding: 3px 9px; border-radius: 12px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-attente  { background: #fef3e2; color: #a05d00; }
.badge-publiee  { background: #e8f5ed; color: #1e5c38; }
.badge-affectee { background: #e8edf8; color: #1a3a80; }
.badge-refusee  { background: #fdeaea; color: #8b1c1c; }
.badge-retiree  { background: #f0f0f0; color: #666; }
.badge-location { background: #f0f0f0; color: #444; }
.badge-vente    { background: #e8edf8; color: #1a3a80; }

/* ACTIONS */
.actions { display: flex; gap: 6px; }
.btn-sm {
  padding: 5px 11px; border-radius: 6px; border: none; font-size: 11.5px;
  cursor: pointer; font-weight: 500; transition: opacity .15s;
}
.btn-sm:hover { opacity: .8; }
.btn-edit    { background: #e8edf8; color: #1a3a80; }
.btn-retire  { background: #fef3e2; color: #a05d00; }
.btn-delete  { background: #fdeaea; color: #8b1c1c; }

/* ── FORMULAIRE DÉPÔT ── */
.form-card { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
.form-header { padding: 22px 28px 18px; border-bottom: 1px solid var(--border); }
.form-header h2 { font-family: 'Playfair Display', serif; font-size: 20px; }
.form-header p  { font-size: 13px; color: var(--gray); margin-top: 5px; }
.form-body { padding: 28px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }
label { font-size: 12px; font-weight: 600; color: var(--text); letter-spacing: .03em; }
label .req { color: var(--gold); margin-left: 2px; }
input[type=text], input[type=number], input[type=file], select, textarea {
  border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 13px;
  font-size: 13.5px; font-family: 'Inter', sans-serif; color: var(--text);
  background: var(--white); transition: border-color .15s; width: 100%;
}
input:focus, select:focus, textarea:focus { outline: none; border-color: var(--gold); }
textarea { resize: vertical; min-height: 90px; }
.form-footer { padding: 0 28px 28px; display: flex; gap: 12px; align-items: center; }
.btn-outline { background: none; border: 1.5px solid var(--border); border-radius: 7px; padding: 9px 18px; font-size: 13px; color: var(--gray); cursor: pointer; text-decoration: none; font-weight: 500; }
.btn-outline:hover { border-color: var(--gold); color: var(--gold); }
.hint { font-size: 11.5px; color: var(--gray); margin-top: 4px; }

/* ── VIDE ── */
.empty { text-align: center; padding: 60px 20px; }
.empty-icon { font-size: 44px; margin-bottom: 14px; opacity: .4; }
.empty-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.empty-sub { font-size: 13px; color: var(--gray); }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  .sidebar { display: none; }
  .main { margin-left: 0; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .form-grid { grid-template-columns: 1fr; }
  .content { padding: 16px; }
  .topbar { padding: 0 16px; }
  .topbar-title { font-size: 13px; }
  .btn-gold { padding: 7px 12px; font-size: 12px; }
  table { font-size: 12px; }
  thead th, td { padding: 8px 10px; }
  .prop-thumb { width: 36px; height: 28px; }
  .actions { flex-direction: column; gap: 4px; }
  .btn-sm { font-size: 10.5px; padding: 4px 8px; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
  .stat-nb { font-size: 22px; }
  .stat-lbl { font-size: 10px; }
  .filters { gap: 5px; }
  .filter-btn { padding: 5px 10px; font-size: 11px; }
  .table-wrap { border-radius: 6px; }
  /* Masquer colonnes secondaires sur mobile */
  table thead th:nth-child(8),
  table tbody td:nth-child(8) { display: none; } /* Date */
  table thead th:nth-child(3),
  table tbody td:nth-child(3) { display: none; } /* Type */
}
</style>
</head>
<body>

<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">Habitat<span>Horizon</span></div>
    <div class="brand-sub">Espace Bailleur</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <a href="?vue=liste" class="nav-item <?= $vue==='liste' ? 'active' : '' ?>">
      <span class="nav-icon">🏠</span> Mes annonces
    </a>
    <a href="?vue=deposer" class="nav-item <?= $vue==='deposer' ? 'active' : '' ?>">
      <span class="nav-icon">➕</span> Déposer une annonce
    </a>

    <div class="nav-label" style="margin-top:10px;">Statuts</div>
    <a href="?vue=liste&statut=attente"  class="nav-item <?= ($filtre_statut==='attente'  && $vue==='liste') ? 'active' : '' ?>">
      <span class="nav-icon">⏳</span> En attente
      <?php if ($stats['attente'] > 0): ?>
        <span style="margin-left:auto;background:var(--orange);color:#fff;border-radius:10px;padding:1px 8px;font-size:10px;"><?= $stats['attente'] ?></span>
      <?php endif; ?>
    </a>
    <a href="?vue=liste&statut=publiee"  class="nav-item <?= ($filtre_statut==='publiee'  && $vue==='liste') ? 'active' : '' ?>">
      <span class="nav-icon">✅</span> Publiées
    </a>
    <a href="?vue=liste&statut=refusee"  class="nav-item <?= ($filtre_statut==='refusee'  && $vue==='liste') ? 'active' : '' ?>">
      <span class="nav-icon">❌</span> Refusées
    </a>
    <a href="?vue=liste&statut=retiree"  class="nav-item <?= ($filtre_statut==='retiree'  && $vue==='liste') ? 'active' : '' ?>">
      <span class="nav-icon">🔒</span> Retirées
    </a>
  </nav>

 <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['prenom'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= $nom_bailleur ?></div>
        <div class="user-role"><?= ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Utilisateur')) ?></div>
      </div>
    </div>
    <a href="index.php" style="display:block;margin-top:12px;padding:8px 14px;background:#C0392B;color:#fff;border-radius:6px;text-decoration:none;font-size:0.78rem;font-weight:600;text-align:center;">🚪 Se déconnecter</a>
  </div>
</aside>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <span class="topbar-title">
      <?= $vue === 'deposer' ? 'Déposer une annonce' : 'Mes annonces' ?>
    </span>
    <?php if ($vue !== 'deposer'): ?>
      <a href="?vue=deposer" class="btn-gold">＋ Nouvelle annonce</a>
    <?php endif; ?>
  </div>
  
  <div class="content">
    
    <div class="stats-container" style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="padding: 15px; background: #f4f4f4; border-radius: 8px;">
            <span>Total annonces : </span>
            <strong><?= $stats['total'] ?></strong>
        </div>
        <div class="stat-card" style="padding: 15px; background: #f4f4f4; border-radius: 8px;">
            <span>En attente : </span>
            <strong><?= $stats['attente'] ?></strong>
        </div>
    </div>

    <?php if ($msg_success): ?>
        <div class="alert-success"><?= $msg_success ?></div>
    <?php endif; ?>

    <?php if ($vue === 'liste'): ?>
        <?php endif; ?>

</div>
  <!-- CONTENU -->
  <div class="content">

    <?php if ($msg_success): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <?php if ($vue === 'liste'): ?>
    <!-- ══ VUE LISTE ══ -->

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card gold">
        <div class="stat-nb"><?= $stats['total'] ?></div>
        <div class="stat-lbl">Total annonces</div>
      </div>
      <div class="stat-card orange">
        <div class="stat-nb"><?= $stats['attente'] ?></div>
        <div class="stat-lbl">En attente</div>
      </div>
      <div class="stat-card green">
        <div class="stat-nb"><?= $stats['publiee'] ?></div>
        <div class="stat-lbl">Publiées</div>
      </div>
      <div class="stat-card red">
        <div class="stat-nb"><?= $stats['refusee'] ?></div>
        <div class="stat-lbl">Refusées</div>
      </div>
      <div class="stat-card gray">
        <div class="stat-nb"><?= $stats['retiree'] ?></div>
        <div class="stat-lbl">Retirées</div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters">

      <a href="?vue=liste&statut=tous"     class="filter-btn <?= $filtre_statut==='tous'     ? 'active' : '' ?>">Toutes</a>
      <a href="?vue=liste&statut=attente"  class="filter-btn <?= $filtre_statut==='attente'  ? 'active' : '' ?>">En attente</a>
      <a href="?vue=liste&statut=publiee"  class="filter-btn <?= $filtre_statut==='publiee'  ? 'active' : '' ?>">Publiées</a>
      <a href="?vue=liste&statut=affectee" class="filter-btn <?= $filtre_statut==='affectee' ? 'active' : '' ?>">Affectées</a>
      <a href="?vue=liste&statut=refusee"  class="filter-btn <?= $filtre_statut==='refusee'  ? 'active' : '' ?>">Refusées</a>
      <a href="?vue=liste&statut=retiree"  class="filter-btn <?= $filtre_statut==='retiree'  ? 'active' : '' ?>">Retirées</a>
    </div>

    <!-- Tableau -->
    <div class="table-wrap">
      <div class="table-head">
        <h2>
          <?php
          $lbl = $filtre_statut === 'tous' ? 'Toutes les annonces' : 'Annonces : ' . ($statut_labels[$filtre_statut] ?? $filtre_statut);
          echo htmlspecialchars($lbl);
          ?>
          <span style="color:var(--gray);font-weight:400;font-size:12px;margin-left:8px;">(<?= count($annonces) ?>)</span>
        </h2>
      </div>

      <?php if (empty($annonces)): ?>
        <div class="empty">
          <div class="empty-icon">🏘️</div>
          <div class="empty-title">Aucune annonce</div>
          <div class="empty-sub">Déposez votre première annonce pour qu'elle soit validée par un agent.</div>
          <a href="?vue=deposer" class="btn-gold" style="margin-top:16px;display:inline-flex;">＋ Déposer une annonce</a>
        </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Photo</th>
            <th>Propriété</th>
            <th>Type</th>
            <th>Option</th>
            <th>Zone</th>
            <th>Prix (F CFA)</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($annonces as $a): ?>
          <tr>
            <td>
              <?php if ($a['photo_principale'] && file_exists($a['photo_principale'])): ?>
                <img src="<?= htmlspecialchars($a['photo_principale']) ?>" class="prop-thumb" alt="photo">
              <?php else: ?>
                <div class="prop-thumb-placeholder">🏠</div>
              <?php endif; ?>
            </td>
            <td>
              <div class="prop-title"><?= htmlspecialchars($a['titre']) ?></div>
              <div class="prop-meta">
                <?= $a['superficie'] ? number_format($a['superficie'], 0, ',', ' ') . ' m²' : '– m²' ?>
              </div>
            </td>
            <td><?= htmlspecialchars($type_labels[$a['type_bien']] ?? $a['type_bien']) ?></td>
            <td>
              <span class="badge badge-<?= $a['modele'] ?>">
                <?= $a['modele'] === 'location' ? '🔑 Location' : '🏷️ Vente' ?>
              </span>
            </td>
            <td><?= htmlspecialchars($a['zone']) ?></td>
            <td style="font-weight:600;"><?= number_format($a['prix'], 0, ',', ' ') ?></td>
            <td>
              <span class="badge badge-<?= $a['statut'] ?>">
                <?= htmlspecialchars($statut_labels[$a['statut']] ?? $a['statut']) ?>
              </span>
              <?php if ($a['commentaire']): ?>
                <div style="font-size:11px;color:var(--gray);margin-top:3px;" title="<?= htmlspecialchars($a['commentaire']) ?>">
                  💬 <?= mb_strimwidth(htmlspecialchars($a['commentaire']), 0, 30, '…') ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--gray);">
              <?= date('d/m/Y', strtotime($a['date_depot'])) ?>
            </td>
            <td>
              <div class="actions">
                <?php if ($a['statut'] === 'attente'): ?>
                  <a href="?vue=modifier&id=<?= $a['id_propriete'] ?>" class="btn-sm btn-edit">✏️ Modifier</a>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette annonce ?')">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id_propriete" value="<?= $a['id_propriete'] ?>">
                    <button class="btn-sm btn-delete" type="submit">🗑️ Supprimer</button>
                  </form>

                <?php elseif ($a['statut'] === 'publiee'): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Retirer cette annonce ?')">
                    <input type="hidden" name="action" value="retirer">
                    <input type="hidden" name="id_propriete" value="<?= $a['id_propriete'] ?>">
                    <button class="btn-sm btn-retire" type="submit">🔒 Retirer</button>
                  </form>

                <?php elseif ($a['statut'] === 'refusee'): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette notice ?')">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id_propriete" value="<?= $a['id_propriete'] ?>">
                    <button class="btn-sm btn-delete" type="submit">🗑️ Supprimer</button>
                  </form>

                <?php elseif ($a['statut'] === 'retiree'): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette notice ?')">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id_propriete" value="<?= $a['id_propriete'] ?>">
                    <button class="btn-sm btn-delete" type="submit">🗑️ Supprimer</button>
                  </form>

                <?php elseif ($a['statut'] === 'affectee'): ?>
                  <span style="color:var(--gray);font-size:11px;">En cours de traitement</span>

                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php elseif ($vue === 'deposer'): ?>
    <!-- ══ VUE FORMULAIRE ══ -->
    <div class="form-card">
      <div class="form-header">
        <h2>Déposer une annonce</h2>
        <p>Renseignez les informations de votre bien. L'annonce sera soumise à validation par un agent avant publication.</p>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="deposer">
        <div class="form-body">
          <div class="form-grid">

            <!-- Titre -->
            <div class="form-group full">
              <label>Titre de l'annonce <span class="req">*</span></label>
              <input type="text" name="titre" placeholder="Ex : Belle villa F5 avec jardin à Ouaga 2000" maxlength="200" required>
            </div>

            <!-- Type de bien -->
            <div class="form-group">
              <label>Type de bien <span class="req">*</span></label>
              <select name="type_bien" required>
                <option value="">— Choisir —</option>
                <?php foreach ($type_labels as $val => $lbl): ?>
                  <option value="<?= $val ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Option -->
            <div class="form-group">
              <label>Option <span class="req">*</span></label>
              <select name="modele" required>
                <option value="">— Choisir —</option>
                <option value="vente">Vente</option>
                <option value="location">Location</option>
              </select>
            </div>

            <!-- Zone -->
            <div class="form-group">
              <label>Zone / Quartier <span class="req">*</span></label>
              <input type="text" name="zone" placeholder="Ex : Ouaga 2000, Pissy, Gounghin…" maxlength="100" required>
            </div>

            <!-- Superficie -->
            <div class="form-group">
              <label>Superficie (m²)</label>
              <input type="number" name="superficie" placeholder="Ex : 200" min="1" step="0.01">
            </div>

            <!-- Prix -->
            <div class="form-group full">
              <label>Prix (F CFA) <span class="req">*</span></label>
              <input type="number" name="prix" placeholder="Ex : 75000000" min="1" step="1" required>
              <div class="hint">Pour une location, indiquez le loyer mensuel.</div>
            </div>

            <!-- Description -->
            <div class="form-group full">
              <label>Description</label>
              <textarea name="description" placeholder="Décrivez les caractéristiques du bien : nombre de pièces, équipements, état général…"></textarea>
            </div>

            <!-- Photo -->
            <div class="form-group full">
              <label>Photo principale <span class="req">*</span></label>
              <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
              <div class="hint">Formats acceptés : JPG, PNG, WebP. Max 5 Mo.</div>
            </div>

            <div class="form-group">
    <label>Attestation de propriété ou titre foncier *</label>
    <input type="file" name="doc_justificatif" accept=".pdf" required>
</div>

          </div><!-- /form-grid -->
        </div><!-- /form-body -->

        <div class="form-footer">
          <button type="submit" class="btn-gold">📤 Soumettre l'annonce</button>
          <a href="?vue=liste" class="btn-outline">Annuler</a>
          <span style="font-size:11.5px;color:var(--gray);">
            L'annonce sera en statut <strong>« En attente »</strong> jusqu'à validation par un agent.
          </span>
        </div>
      </form>
    </div>

    <?php elseif ($vue === 'modifier'):
        $id_mod = (int)($_GET['id'] ?? 0);
        $prop = null;
        if ($id_mod > 0) {
            $s = $db->prepare("SELECT * FROM proprietes WHERE id_propriete = ? AND id_bailleur = ?");
            $s->execute([$id_mod, $id_bailleur]);
            $prop = $s->fetch(PDO::FETCH_ASSOC);
        }
        if (!$prop) {
            echo '<div class="alert alert-error">Propriété introuvable.</div>';
        } else { ?>
    <div class="form-card">
      <div class="form-header">
        <h2>Modifier l'annonce</h2>
        <p>Modifiez les informations de votre bien.</p>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="modifier">
        <input type="hidden" name="id_propriete" value="<?= $prop['id_propriete'] ?>">
        <div class="form-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Titre <span class="req">*</span></label>
              <input type="text" name="titre" value="<?= htmlspecialchars($prop['titre']) ?>" required>
            </div>
            <div class="form-group">
              <label>Type de bien <span class="req">*</span></label>
              <select name="type_bien" required>
                <?php foreach ($type_labels as $val => $lbl): ?>
                  <option value="<?= $val ?>" <?= $prop['type_bien']===$val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Option <span class="req">*</span></label>
              <select name="modele" required>
                <option value="vente"    <?= $prop['modele']==='vente'    ? 'selected' : '' ?>>Vente</option>
                <option value="location" <?= $prop['modele']==='location' ? 'selected' : '' ?>>Location</option>
              </select>
            </div>
            <div class="form-group">
              <label>Zone <span class="req">*</span></label>
              <input type="text" name="zone" value="<?= htmlspecialchars($prop['zone']) ?>" required>
            </div>
            <div class="form-group">
              <label>Superficie (m²)</label>
              <input type="number" name="superficie" value="<?= $prop['superficie'] ?>" min="1" step="0.01">
            </div>
            <div class="form-group full">
              <label>Prix (F CFA) <span class="req">*</span></label>
              <input type="number" name="prix" value="<?= $prop['prix'] ?>" min="1" required>
            </div>
            <div class="form-group full">
              <label>Description</label>
              <textarea name="description"><?= htmlspecialchars($prop['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
              <label>Nouvelle photo (optionnel)</label>
              <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
        </div>
        <div class="form-footer">
          <button type="submit" class="btn-gold">💾 Enregistrer</button>
          <a href="?vue=liste" class="btn-outline">Annuler</a>
        </div>
      </form>
    </div>
    <?php } ?>

<?php endif; ?>

</div><!-- /content -->
</div><!-- /main -->

</body>
</html>