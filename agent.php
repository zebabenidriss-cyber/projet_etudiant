<?php
require_once 'init.php';

/* ================= SÉCURITÉ SESSION ================= */

/* ================= FONCTION SAFE ================= */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'agent') {
    header("Location: connexion.php");
    exit;
}
/* ================= AGENT ================= */


$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['id_utilisateur']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);


$agent_id = (int)$agent['id_utilisateur'];

/* ================= VARIABLES ================= */
$message = '';
$msg_type = '';
$tab = $_GET['tab'] ?? 'annonces';

/* ================= BAILLEURS ================= */
$stmt = $db->query("SELECT id_utilisateur, nom, prenom, telephone FROM utilisateurs WHERE role='bailleur'");
$bailleurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= STATS ================= */
// On compte les 'attente' ET les 'affectee' qui appartiennent à cet agent
$stmt = $db->prepare("SELECT COUNT(*) FROM proprietes WHERE statut='affectee' AND id_agent = ?");
$stmt->execute([$agent_id]);
$nb_annonces_attente = (int)$stmt->fetchColumn();
$stmt_nv = $db->prepare("SELECT COUNT(*) FROM demandes_visite WHERE statut='attente' AND id_agent = ?");
$stmt_nv->execute([$agent_id]);
$nb_visites_attente = (int)$stmt_nv->fetchColumn();
$stmt_nb = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role='client' AND id_agent = ?");
$stmt_nb->execute([$agent_id]);
$nb_clients = (int)$stmt_nb->fetchColumn();
$nb_publiees         = (int)$db->query("SELECT COUNT(*) FROM proprietes WHERE statut='publiee'")->fetchColumn();
$mes_proprietes = $db->prepare("
    SELECT p.*, u.nom as bailleur_nom, u.prenom as bailleur_prenom
    FROM proprietes p
    JOIN utilisateurs u ON p.id_bailleur = u.id_utilisateur
    WHERE p.id_agent = ?
    AND p.statut IN ('publiee', 'refusee', 'affectee')
    ORDER BY p.id_propriete DESC
");
$mes_proprietes->execute([$agent_id]);
$mes_proprietes = $mes_proprietes->fetchAll(PDO::FETCH_ASSOC);
$mes_demandes = $db->prepare("
    SELECT v.*, p.titre as prop_titre, p.zone as prop_zone,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_tel
    FROM demandes_visite v
    JOIN proprietes p ON v.id_propriete = p.id_propriete
    JOIN utilisateurs c ON v.id_client = c.id_utilisateur
    WHERE v.id_agent = ?
    AND v.statut IN ('validee', 'refusee')
    ORDER BY v.id_visite DESC
");
$mes_demandes->execute([$agent_id]);
$mes_demandes = $mes_demandes->fetchAll(PDO::FETCH_ASSOC);

/* ================= DONNÉES IMPORTANTES (MANQUAIENT CHEZ TOI) ================= */
// On utilise prepare() au lieu de query() pour pouvoir passer l'ID de l'agent connecté
// 1. Utilise des points d'interrogation distincts
$stmt_annonces = $db->prepare("
    SELECT p.*, u.nom as bailleur_nom, u.prenom as bailleur_prenom, u.telephone as bailleur_tel,
           d.chemin_doc, d.nom_original
    FROM proprietes p
    JOIN utilisateurs u ON p.id_bailleur = u.id_utilisateur
    LEFT JOIN documents d ON d.id_propriete = p.id_propriete
    WHERE (p.statut = 'attente' AND p.id_agent = ?) 
       OR (p.statut = 'affectee' AND p.id_agent = ?)
    GROUP BY p.id_propriete
    ORDER BY p.id_propriete DESC
");

// 2. Passe un tableau contenant l'ID deux fois
$stmt_annonces->execute([$agent_id, $agent_id]);
$annonces = $stmt_annonces->fetchAll(PDO::FETCH_ASSOC);

$stmt_vis = $db->prepare("
    SELECT v.*, p.titre as prop_titre, p.zone as prop_zone,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_tel
    FROM demandes_visite v
    JOIN proprietes p ON v.id_propriete = p.id_propriete
    JOIN utilisateurs c ON v.id_client = c.id_utilisateur
    WHERE v.statut='attente' AND v.id_agent = ?
    ORDER BY v.id_visite DESC
");
$stmt_vis->execute([$agent_id]);
$visites = $stmt_vis->fetchAll(PDO::FETCH_ASSOC);

$clients = $db->prepare("
    SELECT *
    FROM utilisateurs
    WHERE role='client' AND id_agent = ?
");
$clients->execute([$agent_id]);
$clients = $clients->fetchAll(PDO::FETCH_ASSOC);

/* ================= AJOUT PROPRIÉTÉ ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter_propriete') {

    $id_bailleur = (int)($_POST['id_bailleur'] ?? 0);

    if ($id_bailleur > 0) {

        $titre     = $_POST['titre'] ?? '';
        $type      = $_POST['type_bien'] ?? '';
        $modele    = $_POST['modele'] ?? '';
        $zone      = $_POST['zone'] ?? '';
        $prix      = (float)($_POST['prix'] ?? 0);
        $desc      = $_POST['description'] ?? '';

        /* ===== IMAGE ===== */
        if (!empty($_FILES['photo']['name'])) {

            $imageName = time() . '_' . basename($_FILES['photo']['name']);
            $path = "uploads/proprietes/" . $imageName;

            if (!is_dir("uploads/proprietes")) {
                mkdir("uploads/proprietes", 0777, true);
            }

            move_uploaded_file($_FILES['photo']['tmp_name'], $path);

           $stmt = $db->prepare("
    INSERT INTO proprietes
    (id_bailleur, id_agent, titre, type_bien, modele, zone, prix, description, image_url, statut)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'publiee')
");

$stmt->execute([
    $id_bailleur,
    $agent_id,   // ← lie la propriété à l'agent connecté
    $titre,
    $type,
    $modele,
    $zone,
    $prix,
    $desc,
    $path
]);

            $message = "Propriété ajoutée";
            $msg_type = "success";

        } else {
            $message = "Image obligatoire";
            $msg_type = "error";
        }
   
     }
}

/* ================= TRAITEMENT DES FORMULAIRES ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1. Traitement Visite
  if (($_POST['action'] ?? '') === 'statut_visite') {
      $id_visite = (int)$_POST['id_visite'];
      $statut    = $_POST['statut'];
      $motif     = ($statut === 'refusee') ? ($_POST['motif'] ?? null) : null;

      $stmt = $db->prepare("UPDATE demandes_visite SET statut = ?, message_agent = ? WHERE id_visite = ?");
      $stmt->execute([$statut, $motif, $id_visite]);
      
      header("Location: agent.php?tab=visites");
      exit;
  }

  // 2. Traitement Statut Propriété
  if (($_POST['action'] ?? '') === 'statut_propriete') {
      $id_prop = (int)$_POST['id_propriete'];
      $statut  = $_POST['statut'];
      $comment = $_POST['commentaire'] ?? '';

      $stmt = $db->prepare("UPDATE proprietes SET statut = ?, commentaire = ? WHERE id_propriete = ?");
      $stmt->execute([$statut, $comment, $id_prop]);
      
      header("Location: agent.php?tab=annonces");
      exit;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de Bord — Agent Habitat Horizon</title>
<style>
  /* ===== RESET & BASE ===== */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:        #0F1923;
    --surface:   #162230;
    --card:      #1C2E3D;
    --border:    #243648;
    --accent:    #D4A853;
    --accent2:   #2A6496;
    --text:      #E8EDF2;
    --muted:     #7A95A8;
    --success:   #27AE60;
    --danger:    #C0392B;
    --warn:      #E67E22;
    --radius:    10px;
    --font:      'Segoe UI', system-ui, sans-serif;
  }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font);
    font-size: 14px;
    min-height: 100vh;
  }

  /* ===== TOPBAR ===== */
  .topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .logo {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: .5px;
  }
  .logo span { color: var(--text); font-weight: 300; }
  .agent-info {
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .85rem;
  }
  .avatar {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    color: var(--bg);
    font-size: .95rem;
  }

  /* ===== LAYOUT ===== */
  .container { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.5rem 3rem; }

  /* ===== STATS CARDS ===== */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.75rem;
  }
  .stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.2rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: .4rem;
  }
  .stat-card .label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .6px; }
  .stat-card .value { font-size: 2rem; font-weight: 700; color: var(--accent); line-height: 1; }
  .stat-card .sub { font-size: .78rem; color: var(--muted); }
  .stat-card.warn .value { color: var(--warn); }
  .stat-card.ok   .value { color: var(--success); }

  /* ===== ALERT ===== */
  .alert {
    border-radius: var(--radius);
    padding: .85rem 1.2rem;
    margin-bottom: 1.25rem;
    font-size: .88rem;
  }
  .alert.success { background: rgba(39,174,96,.15); border: 1px solid var(--success); color: #6fcf97; }
  .alert.error   { background: rgba(192,57,43,.15);  border: 1px solid var(--danger); color: #e57373; }

  /* ===== TABS ===== */
  .tabs {
    display: flex;
    gap: .3rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.5rem;
    overflow-x: auto;
  }
  .tab-btn {
    background: none; border: none;
    color: var(--muted);
    padding: .7rem 1.2rem;
    cursor: pointer;
    font-size: .88rem;
    font-family: var(--font);
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    transition: color .2s, border-color .2s;
    text-decoration: none;
    display: inline-block;
  }
  .tab-btn:hover { color: var(--text); }
  .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }
  .badge {
    display: inline-block;
    background: var(--warn);
    color: #fff;
    border-radius: 20px;
    padding: 1px 7px;
    font-size: .7rem;
    font-weight: 700;
    margin-left: .35rem;
    vertical-align: middle;
  }

  /* ===== SECTION TITLE ===== */
  .section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--accent);
    margin-bottom: 1rem;
    padding-bottom: .5rem;
    border-bottom: 1px solid var(--border);
  }

  /* ===== TABLE ===== */
  .table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--surface); }
  th {
    padding: .75rem 1rem;
    text-align: left;
    color: var(--muted);
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    white-space: nowrap;
  }
  td {
    padding: .75rem 1rem;
    border-top: 1px solid var(--border);
    vertical-align: middle;
  }
  tr:hover td { background: rgba(255,255,255,.03); }

  /* ===== BADGES STATUT ===== */
  .pill {
    display: inline-block;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: .75rem;
    font-weight: 600;
  }
  .pill-attente  { background: rgba(230,126,34,.2);  color: var(--warn); }
  .pill-validee,
  .pill-publiee  { background: rgba(39,174,96,.2);  color: var(--success); }
  .pill-refusee  { background: rgba(192,57,43,.2);  color: var(--danger); }
  .pill-affectee { background: rgba(42,100,150,.2); color: #5dade2; }

  /* ===== FORMS inline ===== */
  .action-form { display: flex; gap: .5rem; align-items: flex-start; flex-wrap: wrap; }
  .action-form textarea {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    padding: .4rem .6rem;
    font-size: .78rem;
    resize: vertical;
    min-height: 54px;
    width: 170px;
  }
  .btn {
    border: none;
    border-radius: 6px;
    padding: .45rem .9rem;
    font-size: .78rem;
    font-family: var(--font);
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
    transition: opacity .15s;
  }
  .btn:hover { opacity: .85; }
  .btn-success { background: var(--success); color: #fff; }
  .btn-danger  { background: var(--danger);  color: #fff; }
  .btn-primary { background: var(--accent);  color: var(--bg); }
  .btn-sm { padding: .35rem .75rem; font-size: .75rem; }

  /* ===== ADD PROPERTY FORM ===== */
  .form-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
  }
  .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .form-group { display: flex; flex-direction: column; gap: .35rem; }
  .form-group label { font-size: .78rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
  .form-group input,
  .form-group select,
  .form-group textarea {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    padding: .55rem .8rem;
    font-size: .88rem;
    font-family: var(--font);
    transition: border-color .2s;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
  }
  .form-group textarea { resize: vertical; min-height: 80px; }
  .form-group select option { background: var(--card); }

  /* ===== CLIENT CARDS ===== */
  .client-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
  }
  .client-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.1rem 1.3rem;
  }
  .client-card .name { font-weight: 700; font-size: 1rem; margin-bottom: .3rem; }
  .client-card .meta { font-size: .78rem; color: var(--muted); }
  .client-card .meta a { color: var(--accent); text-decoration: none; }

  /* ===== EMPTY STATE ===== */
  .empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--muted);
    font-size: .9rem;
  }
  .empty .icon { font-size: 2.5rem; margin-bottom: .75rem; }

  /* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .topbar {
    padding: 0 .75rem;
    height: auto;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px .75rem;
  }
  .logo { font-size: .95rem; }
  .agent-info { gap: .5rem; font-size: .78rem; }
  .agent-info > div:nth-child(3) { display: none; } /* masque texte nom/rôle */
  .container { padding: .75rem; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: .6rem; }
  .stat-card { padding: .9rem 1rem; }
  .stat-card .value { font-size: 1.5rem; }
  .tabs { gap: 0; }
  .tab-btn { padding: .6rem .7rem; font-size: .78rem; }
  .action-form { flex-direction: column; }
  .action-form textarea { width: 100%; }
  .table-wrap { font-size: 12px; }
  th, td { padding: .5rem .6rem; }
  .form-grid { grid-template-columns: 1fr; }
  .client-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  /* Masquer colonnes secondaires */
  table thead th:nth-child(5),
  table tbody td:nth-child(5),
  table thead th:nth-child(6),
  table tbody td:nth-child(6) { display: none; }
  .topbar a[href="messagerie_agent.php"] { 
    padding: 6px 10px; 
    font-size: .75rem; 
  }
  .avatar { width: 30px; height: 30px; font-size: .8rem; }
  .form-group[style*="span 2"] { grid-column: span 1; }
}
</style>
</head>
<body>

<header class="topbar">
  <div class="logo">Habitat<span>Horizon</span> &nbsp;·&nbsp; <small style="color:var(--muted);font-size:.75rem">Espace Agent</small></div>
    <a href="messagerie_agent.php" style="background:#2A6496;color:white;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;">💬 Messagerie</a>
  <div class="agent-info">
    <div class="avatar"><?= strtoupper(substr($agent['prenom'],0,1) . substr($agent['nom'],0,1)) ?></div>
    <div>
      <div style="font-weight:600"><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></div>
      <div style="color:var(--muted);font-size:.75rem">Agent immobilier</div>
    </div>
    <a href="index.php" style="margin-left:1rem;background:#C0392B;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:0.78rem;font-weight:600;">🚪 Déconnexion</a>
  </div>
</header>

<div class="container">

  <div class="stats-grid">
    <div class="stat-card warn">
      <div class="label">Propriétés Manager</div>
      <div class="value"><?= $nb_annonces_attente ?></div>
      <div class="sub">À publier ou refuser</div>
    </div>
    <div class="stat-card warn">
      <div class="label">Visites en attente</div>
      <div class="value"><?= $nb_visites_attente ?></div>
      <div class="sub">Demandes non traitées</div>
    </div>
    <div class="stat-card">
      <div class="label">Clients affectés</div>
      <div class="value"><?= $nb_clients ?></div>
      <div class="sub">Sous votre gestion</div>
    </div>
    <div class="stat-card ok">
      <div class="label">Propriétés publiées</div>
      <div class="value"><?= $nb_publiees ?></div>
      <div class="sub">Annonces actives</div>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <nav class="tabs">
    <a href="?tab=annonces"  class="tab-btn <?= $tab==='annonces'  ? 'active' : '' ?>">
      Propriétés reçues <?php if ($nb_annonces_attente > 0): ?><span class="badge"><?= $nb_annonces_attente ?></span><?php endif; ?>
    </a>
    <a href="?tab=visites"   class="tab-btn <?= $tab==='visites'   ? 'active' : '' ?>">
      Demandes de Visite <?php if ($nb_visites_attente > 0): ?><span class="badge"><?= $nb_visites_attente ?></span><?php endif; ?>
    </a>
    <a href="?tab=clients"   class="tab-btn <?= $tab==='clients'   ? 'active' : '' ?>">
      Mes Clients
    </a>
    <a href="?tab=ajouter"   class="tab-btn <?= $tab==='ajouter'   ? 'active' : '' ?>">
      + Ajouter Propriété
    </a>
    <a href="?tab=mes_proprietes" class="tab-btn <?= $tab==='mes_proprietes' ? 'active' : '' ?>">
    Liste des Propriétés
</a>
<a href="?tab=mes_demandes" class="tab-btn <?= $tab==='mes_demandes' ? 'active' : '' ?>">
    Mes Demandes
</a>
  </nav>

  <?php if ($tab === 'annonces'): ?>
    <h2 class="section-title">Propriétés du manager à publier / refuser (EF-D1)</h2>

    <?php if (empty($annonces)): ?>
      <div class="empty"><div class="icon">📋</div>Aucune propriété en attente d'évaluation.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
        <thead>
  <tr>
    <th>#</th>
    <th>Titre</th>
    <th>Document</th> <th>Type</th>
    <th>Modèle</th>
    <th>Zone</th>
    <th>Prix (FCFA)</th>
    <th>Bailleur</th>
    <th>Statut</th>
    <th>Action</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($annonces as $a): ?>
  <tr>
    <td style="color:var(--muted)">#<?= $a['id_propriete'] ?></td>
    <td>
      <strong><?= htmlspecialchars($a['titre']) ?></strong>
    </td>
    
    <td>
        <?php if (!empty($a['chemin_doc'])): ?>
            <a href="<?= htmlspecialchars($a['chemin_doc']) ?>" 
               target="_blank" 
               class="btn btn-primary btn-sm">
               👁️ <?= htmlspecialchars($a['nom_original'] ?? 'Ouvrir') ?>
            </a>
        <?php else: ?>
            <span style="color:var(--muted); font-size: 12px;">Aucun</span>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars(str_replace('_',' ', $a['type_bien'])) ?></td>
              <td><?= htmlspecialchars($a['modele']) ?></td>
              <td><?= htmlspecialchars($a['zone']) ?></td>
              <td><?= number_format($a['prix'], 0, ',', ' ') ?></td>
              <td>
              <?= htmlspecialchars(($a['bailleur_prenom'] ?? '') . ' ' . ($a['bailleur_nom'] ?? '')) ?>
              <?php if (!empty($a['bailleur_tel'])): ?>
                  <br><small style="color:var(--muted)"><?= htmlspecialchars($a['bailleur_tel']) ?></small>
                <?php endif; ?>
              </td>
              <td><span class="pill pill-<?= $a['statut'] ?>"><?= $a['statut'] ?></span></td>
              <td>
                <form method="POST" class="action-form" onsubmit="return validerMotifAnnonce(this);">
                  <input type="hidden" name="action" value="statut_propriete">
                  <input type="hidden" name="id_propriete" value="<?= $a['id_propriete'] ?>">
                  <textarea name="commentaire" id="comment_<?= $a['id_propriete'] ?>" placeholder="Motif obligatoire en cas de refus…"></textarea>
                  <div style="display:flex;flex-direction:column;gap:.35rem">
                    <button type="submit" name="statut" value="publiee" class="btn btn-success btn-sm">✔ Publier</button>
                    <button type="submit" name="statut" value="refusee" class="btn btn-danger btn-sm">✘ Refuser</button>
                  </div>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'visites'): ?>
    <h2 class="section-title">Demandes de visite — Valider / Refuser (EF-D2)</h2>

    <?php if (empty($visites)): ?>
      <div class="empty"><div class="icon">🏠</div>Aucune demande de visite affectée.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Propriété</th>
              <th>Client</th>
              <th>Message client</th>
              <th>Date souhaitée</th>
              <th>Demande le</th>
              <th>Statut</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($visites as $v): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($v['prop_titre'] ?? 'Bien inconnu') ?></strong><br>
        <small><?= htmlspecialchars($v['prop_zone'] ?? 'Zone inconnue') ?></small>
    </td>

    <td>
        <?= htmlspecialchars(($v['client_prenom'] ?? '') . ' ' . ($v['client_nom'] ?? 'Client')) ?>
        <br><small><?= htmlspecialchars($v['client_tel'] ?? 'Non renseigné') ?></small>
    </td>

    <td><?= htmlspecialchars(mb_substr($v['message_client'] ?? '', 0, 50)) ?>...</td>

    <td><?= htmlspecialchars($v['date_souhaitee'] ?? 'Non définie') ?></td>

    <td><?= htmlspecialchars($v['date_demande'] ?? '') ?></td>

    <td>
        <span class="badge-<?= htmlspecialchars($v['statut'] ?? 'attente') ?>">
            <?= ucfirst(htmlspecialchars($v['statut'] ?? 'attente')) ?>
        </span>
    </td>

    <td>
    <form method="POST">
    <input type="hidden" name="action" value="statut_visite">
    <input type="hidden" name="id_visite" value="<?= $v['id_visite'] ?>">
    
    <button type="submit" name="statut" value="validee" class="btn-success">✔ Valider</button>
    
    <textarea name="motif" placeholder="Motif du refus..." style="width:100px; font-size:10px;"></textarea>
    <button type="submit" name="statut" value="refusee" class="btn-danger">✘ Refuser</button>
</form>
    </td>
</tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'clients'): ?>
    <h2 class="section-title">Clients affectés (EF-D3)</h2>

    <?php if (empty($clients)): ?>
      <div class="empty"><div class="icon">👥</div>Aucun client ne vous est encore affecté.</div>
    <?php else: ?>
      <div class="client-grid">
        <?php foreach ($clients as $c): ?>
        <div class="client-card">
          <div class="name"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
          <div class="meta">
            <?php if ($c['email']): ?>
              <div>📧 <a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></div>
            <?php endif; ?>
            <?php if ($c['telephone']): ?>
              <div>📞 <?= htmlspecialchars($c['telephone']) ?></div>
            <?php endif; ?>
            <div style="margin-top:.5rem;color:var(--muted)">Affecté le <?= !empty($c['date_affectation']) 
    ? date('d/m/Y', strtotime($c['date_affectation'])) 
    : '—' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php elseif ($tab === 'ajouter'): ?>
    <h2 class="section-title">Ajouter une propriété agence (EF-D4)</h2>
    <p style="color:var(--muted);font-size:.85rem;margin-bottom:1.25rem">
      Les propriétés ajoutées ici sont directement publiées sans validation intermédiaire.
    </p>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert success" style="margin-bottom: 1.5rem;">
            ✅ Propriété ajoutée avec succès !
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajouter_propriete">
            
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2">
                    <label>Photo de la propriété *</label>
                    <input type="file" name="photo" accept=".jpeg, .jpg, .png" required>
                </div>

                <div class="form-group">
                    <label>Bailleur *</label>
                    <select name="id_bailleur" required>
                        <option value="">— Choisir un bailleur —</option>
                        <?php foreach ($bailleurs as $b): ?>
                            <option value="<?= $b['id_utilisateur'] ?>">
                                <?= htmlspecialchars($b['prenom'] . ' ' . $b['nom']) ?> (<?= htmlspecialchars($b['telephone']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2">
                    <label>Titre de l'annonce *</label>
                    <input type="text" name="titre" placeholder="Ex : Villa 5 pièces à Ouaga 2000" required>
                </div>

                <div class="form-group">
                    <label>Type de bien *</label>
                    <select name="type_bien" required>
                        <option value="">— Choisir —</option>
                        <option value="villa">Villa</option>
                        <option value="appartement">Appartement</option>
                        <option value="r_plus_1">R+1</option>
                        <option value="terrain">Terrain</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Modèle *</label>
                    <select name="modele" required>
                        <option value="">— Choisir —</option>
                        <option value="vente">Vente</option>
                        <option value="location">Location</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Zone / Quartier *</label>
                    <input type="text" name="zone" placeholder="Ex : Ouaga 2000" required>
                </div>

                <div class="form-group">
                    <label>Superficie (m²)</label>
                    <input type="number" name="superficie" step="0.01" min="0" placeholder="Ex : 150">
                </div>

                <div class="form-group">
                    <label>Prix (FCFA) *</label>
                    <input type="number" name="prix" min="1" placeholder="Ex : 25000000" required>
                </div>

                <div class="form-group" style="grid-column: span 2">
                    <label>Description</label>
                    <textarea name="description" placeholder="Décrivez la propriété..."></textarea>
                </div>
            </div> 
            
            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">📤 Publier l'annonce</button>
            </form> 
    </div>

  <?php elseif ($tab === 'mes_proprietes'): ?>
    <h2 class="section-title">Mes Propriétés affectées / traitées</h2>

    <?php if (empty($mes_proprietes)): ?>
        <div class="empty"><div class="icon">🏠</div>Aucune propriété ne vous a encore été affectée.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Zone</th>
                        <th>Prix (FCFA)</th>
                        <th>Bailleur</th>
                        <th>Statut</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mes_proprietes as $mp): ?>
                    <tr>
                        <td style="color:var(--muted)">#<?= $mp['id_propriete'] ?></td>
                        <td><strong><?= htmlspecialchars($mp['titre']) ?></strong></td>
                        <td><?= htmlspecialchars(str_replace('_',' ', $mp['type_bien'])) ?></td>
                        <td><?= htmlspecialchars($mp['zone']) ?></td>
                        <td><?= number_format($mp['prix'], 0, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($mp['bailleur_prenom'].' '.$mp['bailleur_nom']) ?></td>
                        <td><span class="pill pill-<?= $mp['statut'] ?>"><?= ucfirst($mp['statut']) ?></span></td>
                        <td style="font-size:0.8rem;color:var(--muted);">
                            <?= !empty($mp['commentaire']) ? htmlspecialchars($mp['commentaire']) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php elseif ($tab === 'mes_demandes'): ?>
    <h2 class="section-title">Mes Demandes de visite traitées</h2>

    <?php if (empty($mes_demandes)): ?>
        <div class="empty"><div class="icon">📅</div>Aucune demande traitée pour le moment.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Propriété</th>
                        <th>Client</th>
                        <th>Message client</th>
                        <th>Date souhaitée</th>
                        <th>Demandé le</th>
                        <th>Statut</th>
                        <th>Motif refus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mes_demandes as $md): ?>
                    <tr>
                        <td style="color:var(--muted)">#<?= $md['id_visite'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($md['prop_titre']) ?></strong><br>
                            <small style="color:var(--muted)"><?= htmlspecialchars($md['prop_zone']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($md['client_prenom'].' '.$md['client_nom']) ?><br>
                            <small style="color:var(--muted)"><?= htmlspecialchars($md['client_tel'] ?? '—') ?></small>
                        </td>
                        <td style="font-size:0.8rem;max-width:150px;">
                            <?= htmlspecialchars(mb_substr($md['message_client'] ?? '—', 0, 60)) ?>
                        </td>
                        <td><?= htmlspecialchars($md['date_souhaitee'] ?? '—') ?></td>
                        <td style="font-size:0.8rem;"><?= date('d/m/Y', strtotime($md['date_demande'])) ?></td>
                        <td><span class="pill pill-<?= $md['statut'] ?>"><?= ucfirst($md['statut']) ?></span></td>
                        <td style="font-size:0.8rem;color:var(--muted);max-width:150px;">
                            <?= !empty($md['message_agent']) ? htmlspecialchars($md['message_agent']) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

  <?php endif; ?>

</div><script>
// Sécurité Frontend pour le refus des propriétés du manager
function validerMotifAnnonce(formulaire) {
    const boutonClique = document.activeElement.value;
    const motif = formulaire.querySelector('textarea[name="commentaire"]').value.trim();
    
    if (boutonClique === 'refusee' && motif === '') {
        alert("Attention : Vous devez obligatoirement spécifier un motif pour refuser cette propriété.");
        formulaire.querySelector('textarea[name="commentaire"]').focus();
        return false;
    }
    return true;
}

// Déploiement du champ de motif de refus pour les visites
function activerChampRefus(boutonRefus) {
    const conteneurForm = boutonRefus.closest('.action-form');
    const zoneTexte = conteneurForm.querySelector('textarea[name="motif_refus_visite"]');
    const boutonConfirmer = conteneurForm.querySelector('.final-refuse');
    const boutonValider = conteneurForm.querySelector('button[value="validee"]');

    zoneTexte.style.display = 'block';
    boutonConfirmer.style.display = 'inline-block';
    boutonRefus.style.display = 'none';
    boutonValider.style.display = 'none';
    zoneTexte.focus();
}

// Sécurité Frontend pour le refus des visites clients
function validerMotifVisite(formulaire) {
    const boutonClique = document.activeElement.value;
    if (boutonClique === 'refusee') {
        const motif = formulaire.querySelector('textarea[name="motif_refus_visite"]').value.trim();
        if (motif === '') {
            alert("Attention : Un motif écrit est exigé pour valider le refus de cette demande de visite.");
            return false;
        }
    }
    return true;
}
</script>
</body>
</html>