<?php
// 1. Initialisation unique (session + connexion BDD)
require_once 'init.php';

$db->query("DELETE FROM notifications WHERE date_creation < DATE_SUB(NOW(), INTERVAL 7 DAY)");

$notifs = $db->query("SELECT * FROM notifications WHERE destinataire = 'manager' AND lu = 0 ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
$nb_notifs = count($notifs);

if (isset($_GET['lire_notif'])) {
    $db->query("UPDATE notifications SET lu = 1 WHERE destinataire = 'manager'");
    header("Location: manager.php");
    exit;
}

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'manager') {
    header("Location: connexion.php");
    exit;
}

//bloc qui vérifie si la session existe et récupère le nom et prénom du manager connecté.

if (isset($_SESSION['id_utilisateur'])) {
    // Requête pour récupérer le nom, prénom et le rôle
    $stmt = $db->prepare("SELECT nom, prenom, role FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['id_utilisateur']]);
    $user_connecte = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Si pas de session, on renvoie à la page de connexion
    header("Location: connexion.php");
    exit;
}

$message = "";

// --- SEUL ET UNIQUE BLOC DE TRAITEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Gestion des propriétés (Affecter, Publier, Refuser)
    if ($action === 'gerer_propriete') {
        $id_prop = (int) $_POST['id'];
        $choix   = $_POST['choix'];
        if ($choix === 'affecter') {
            $stmt = $db->prepare("UPDATE proprietes SET id_agent = ?, statut = 'affectee' WHERE id_propriete = ?");
            $stmt->execute([(int)$_POST['agent_id'], $id_prop]);
        } elseif ($choix === 'publier') {
            $stmt = $db->prepare("UPDATE proprietes SET statut = 'publiee', id_agent = NULL WHERE id_propriete = ?");
            $stmt->execute([$id_prop]);
        } elseif ($choix === 'refuser') {
            $stmt = $db->prepare("UPDATE proprietes SET statut = 'refusee', commentaire = ? WHERE id_propriete = ?");
            $stmt->execute([htmlspecialchars(trim($_POST['commentaire'] ?? '')), $id_prop]);
        }
    }

    // 2. Utilisateurs
    elseif ($action === 'ajouter_utilisateur') {
        $mdp = password_hash(trim($_POST['mot_de_passe']), PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([htmlspecialchars($_POST['nom']), htmlspecialchars($_POST['prenom']), htmlspecialchars($_POST['email']), $mdp, htmlspecialchars($_POST['role']), htmlspecialchars($_POST['telephone'] ?? '')]);
        $message = "✅ Utilisateur ajouté.";
    }
    elseif ($action === 'supprimer_utilisateur') {
        $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?")->execute([(int)$_POST['id']]);
        $message = "🗑️ Utilisateur supprimé.";
    }
    elseif ($action === 'modifier_utilisateur') {
        $stmt = $db->prepare("UPDATE utilisateurs SET nom=?, prenom=?, email=?, role=?, telephone=? WHERE id_utilisateur=?");
        $stmt->execute([htmlspecialchars($_POST['nom']), htmlspecialchars($_POST['prenom']), htmlspecialchars($_POST['email']), htmlspecialchars($_POST['role']), htmlspecialchars($_POST['telephone'] ?? ''), (int)$_POST['id']]);
        $message = "✏️ Utilisateur modifié.";
    }

    // 3. Propriétés (Statuts)
    elseif ($action === 'valider_propriete') {
        $db->prepare("UPDATE proprietes SET statut = 'publiee' WHERE id_propriete = ?")->execute([(int)$_POST['id']]);
        $message = "✅ Propriété publiée.";
    }
    elseif ($action === 'retirer_propriete') {
        $db->prepare("UPDATE proprietes SET statut = 'retiree' WHERE id_propriete = ?")->execute([(int)$_POST['id']]);
        $message = "🚫 Propriété retirée.";
    }
    elseif ($action === 'refuser_propriete') {
        $db->prepare("UPDATE proprietes SET statut = 'refusee', commentaire = ? WHERE id_propriete = ?")->execute([htmlspecialchars($_POST['commentaire']), (int)$_POST['id']]);
        $message = "❌ Propriété refusée.";
    }
    elseif ($action === 'affecter') {
    $agent_id  = (int)$_POST['agent_id'];
    $client_id = (int)$_POST['client_id'];

    // Table utilisateurs
    $db->prepare("UPDATE utilisateurs SET id_agent = ? WHERE id_utilisateur = ? AND role = 'client'")
       ->execute([$agent_id, $client_id]);

    // Table affectations
    $db->prepare("
        INSERT INTO affectations (id_client, id_agent) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE id_agent = VALUES(id_agent), date_affectation = NOW()
    ")->execute([$client_id, $agent_id]);

    $message = "🔗 Client affecté.";
}
    elseif ($action === 'affecter_propriete') {
        $db->prepare("UPDATE proprietes SET id_agent = ?, statut = 'affectee' WHERE id_propriete = ?")->execute([(int)$_POST['agent_id'], (int)$_POST['id_propriete']]);
        $message = "✅ Propriété affectée.";
    }

    // Redirection finale pour éviter le re-traitement
    header("Location: manager.php");
    exit;
}


// =============================================

// LECTURE DES DONNÉES

// =============================================



// Statistiques tableau de bord

$nb_proprietes   = $db->query("SELECT COUNT(*) FROM proprietes")->fetchColumn();

$nb_visites      = $db->query("SELECT COUNT(*) FROM demandes_visite")->fetchColumn();

$nb_en_attente   = $db->query("SELECT COUNT(*) FROM proprietes WHERE statut = 'attente'")->fetchColumn();

$nb_retirees = $db->query("SELECT COUNT(*) FROM proprietes WHERE statut = 'retiree'")->fetchColumn();

$nb_refusees = $db->query("SELECT COUNT(*) FROM proprietes WHERE statut = 'refusee'")->fetchColumn();


$nb_users_actifs = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role IN ('client','bailleur','agent')")->fetchColumn();



// Propriétés en attente pour le tableau de bord

$proprietes_attente = $db->query("

    SELECT p.id_propriete, p.titre, CONCAT(u.prenom,' ',u.nom) AS proprietaire

    FROM proprietes p

    LEFT JOIN utilisateurs u ON p.id_bailleur = u.id_utilisateur

    WHERE p.statut = 'attente'

    LIMIT 10

")->fetchAll(PDO::FETCH_ASSOC);



// Filtre et recherche utilisateurs

$filtre_role = isset($_GET['role']) ? $_GET['role'] : 'Tous';

$recherche   = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : '';



$sql_users    = "SELECT * FROM utilisateurs WHERE 1=1";

$params_users = [];

if ($filtre_role !== 'Tous') {

    $sql_users .= " AND role = ?";

    $params_users[] = strtolower($filtre_role);

}

if ($recherche) {

    $sql_users .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";

    $params_users[] = "%$recherche%";

    $params_users[] = "%$recherche%";

    $params_users[] = "%$recherche%";

}

$stmt_users   = $db->prepare($sql_users);

$stmt_users->execute($params_users);

$utilisateurs = $stmt_users->fetchAll(PDO::FETCH_ASSOC);



// Clients et Agents pour affectation
$clients = $db->query("SELECT id_utilisateur, nom, prenom FROM utilisateurs WHERE role = 'client'")->fetchAll(PDO::FETCH_ASSOC);
$agents  = $db->query("SELECT id_utilisateur, nom, prenom FROM utilisateurs WHERE role = 'agent'")->fetchAll(PDO::FETCH_ASSOC);


// --- ICI : AJOUTE CE BLOC ---
$toutes_proprietes = $db->query("SELECT id_propriete, titre FROM proprietes WHERE statut IN ('attente', 'publiee')")->fetchAll(PDO::FETCH_ASSOC);
// ----------------------------


// Filtre propriétés

$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : 'Toutes';

// Modifie cette ligne pour inclure la table documents
// Remplace ton bloc $sql_proprietes actuel par celui-ci :
$sql_proprietes = "
    SELECT p.*, 
           CONCAT(u.prenom,' ',u.nom) AS nom_bailleur,
           d.chemin_doc AS chemin_document,
           d.nom_original 
    FROM proprietes p 
    LEFT JOIN utilisateurs u ON p.id_bailleur = u.id_utilisateur
    LEFT JOIN documents d ON d.id_propriete = p.id_propriete
    WHERE 1=1
";
$params_proprietes = [];

if ($filtre_statut !== 'Toutes') {
    $sql_proprietes .= " AND p.statut = ?";
    $params_proprietes[] = strtolower($filtre_statut);
}

$stmt_proprietes = $db->prepare($sql_proprietes);

$stmt_proprietes->execute($params_proprietes);

$proprietes = $stmt_proprietes->fetchAll(PDO::FETCH_ASSOC);



// Données graphique barres — demandes de visite par mois

$visites_mois = $db->query("

    SELECT DATE_FORMAT(date_demande, '%b') AS mois, COUNT(*) AS total

    FROM demandes_visite

    WHERE date_demande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)

    GROUP BY MONTH(date_demande)

    ORDER BY MONTH(date_demande)

")->fetchAll(PDO::FETCH_ASSOC);



$labels_bar = json_encode(count($visites_mois) ? array_column($visites_mois, 'mois')  : ['Jan','Fév','Mar','Avr','Mai','Juin']);

$data_bar   = json_encode(count($visites_mois) ? array_column($visites_mois, 'total') : [0,0,0,0,0,0]);



// Données graphique donut — types de biens

$types_biens  = $db->query("SELECT type_bien, COUNT(*) AS total FROM proprietes GROUP BY type_bien")->fetchAll(PDO::FETCH_ASSOC);

$labels_donut = json_encode(count($types_biens) ? array_column($types_biens, 'type_bien') : ['villa','appartement','terrain','commerce']);

$data_donut   = json_encode(count($types_biens) ? array_column($types_biens, 'total')     : [0,0,0,0]);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Immobilier</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
       html { scroll-behavior: smooth; }
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial,sans-serif;
}

body{
background:#f4f6f9;
}

.container{
display:flex;
}

.main{
margin-left:250px;
padding:20px;
width:calc(100% - 250px);
overflow-x:hidden;
box-sizing:border-box;
}

section {
scroll-margin-top: 20px;
}

.sidebar{
width:250px;
height:100vh;
background:#1e293b;
color:white;
padding:20px;
position:fixed;
overflow-y:auto;
}

.sidebar h2{
margin-bottom:30px;
}

.sidebar ul{
list-style:none;
}

.sidebar li{
margin:15px 0;
}

.sidebar a{
text-decoration:none;
color:white;
}

.sidebar a:hover{
color:#60a5fa;
}

.main{
margin-left:250px;
padding:20px;
width:calc(100% - 250px);
overflow-x:hidden;
box-sizing:border-box;
}

section {
scroll-margin-top: 20px;
}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:16px;
margin-bottom:30px;
width:100%;
}

.card{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 2px 8px rgba(0,0,0,.1);
text-align:center;
}

.card h2{
color:#205eda;
margin-bottom:10px;
}

.chart-container{
display:grid;
grid-template-columns:1fr 1fr;
gap:16px;
margin-bottom:30px;
width:100%;
max-width:100%;
}

.chart-box{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 2px 8px rgba(0,0,0,.1);
}

table{
width:100%;
border-collapse:collapse;
margin-top:15px;
background:white;
}

table th,
table td{
padding:12px;
border:1px solid #ddd;
text-align:center;
}

table th{
background:#2563eb;
color:white;
}

.btn{
padding:8px 12px;
border:none;
border-radius:5px;
cursor:pointer;
transition:0.3s;
}

.btn:hover{
opacity:0.8;
}

.btn-success{
background:green;
color:white;
}

.btn-danger{
background:red;
color:white;
}

.btn-primary{
background:#2563eb;
color:white;
}

.section{
background:white;
padding:20px;
margin-top:30px;
border-radius:10px;
box-shadow:0 2px 8px rgba(0,0,0,.1);
}

.search-box{
display:flex;
gap:10px;
margin-bottom:15px;
flex-wrap:wrap;
}

input,
select{
padding:10px;
border:1px solid #ccc;
border-radius:5px;
}

.assign{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
margin-top:20px;
}

.list-box{
border:1px solid #ddd;
padding:15px;
border-radius:10px;
}

.list-box ul{
list-style:none;
}

.list-box li{
padding:10px;
margin:5px 0;
background:#f1f5f9;
cursor:pointer;
}

.list-box li:hover{
background:#dbeafe;
}

.modal{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
}

.modal-content{
background:white;
width:400px;
padding:20px;
margin:100px auto;
border-radius:10px;
}

.modal-content h2{
margin-bottom:15px;
}

.modal-content input,
.modal-content select{
width:100%;
margin-bottom:10px;
}

@media(max-width:768px){

.sidebar{
display:none;
}

.main{
margin-left:0;
}

.chart-container{
grid-template-columns:1fr;
}

.assign{
grid-template-columns:1fr;
}

.modal-content{
width:90%;
}

}
```

.sidebar {
        width: 250px;
        height: 100vh;
        background: #1e293b;
        color: white;
        padding: 20px;
        position: fixed;
        display: flex;             /* Active Flexbox */
        flex-direction: column;    /* Empile les éléments */
    }
    .sidebar-menu {
        flex-grow: 1;              /* Prend tout l'espace disponible */
        list-style: none;
        padding: 0;
    }
    .user-profile {
        text-align: center;
        padding-bottom: 20px;
        border-bottom: 1px solid #334155;
        margin-bottom: 20px;
    }
    .logout-btn {
        margin-top: auto;          /* Pousse le bouton vers le bas */
        padding-top: 20px;
        border-top: 1px solid #334155;
    }

    

.table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.table th,
.table td{
    border:1px solid #ddd;
    padding:8px;
    text-align:center;
    vertical-align:middle;
    word-wrap:break-word;
}

.col-actions{
    width:140px;
}

.table td:last-child{
    width:140px;
}

.col-doc{
    width:170px;
}

.col-doc a{
    display:block;
    width:100%;
    box-sizing:border-box;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

    </style>
</head>
<body>

<div class="container">
<div class="sidebar">
<div class="user-profile">
        <p style="font-size: 0.7rem; color: #94a3b8; margin:0;">
            <?= ucfirst(htmlspecialchars($user_connecte['role'])) ?> connecté :
        </p>
        
        <h4 style="margin: 5px 0 0 0; color: #fff;">
            <?= htmlspecialchars($user_connecte['prenom'] . ' ' . $user_connecte['nom']) ?>
        </h4>
    </div>

    <!-- Menu -->
    <ul class="sidebar-menu">
    <li>
    <ul class="sidebar-menu">
    <li><a href="#dashboard" style="scroll-behavior:smooth;">📊 Tableau de bord</a></li>
    <li>
        <a href="#notifications" style="position:relative;scroll-behavior:smooth;">
            🔔 Notifications
            <?php if ($nb_notifs > 0): ?>
                <span style="position:absolute;top:-4px;right:-4px;background:red;color:white;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:bold;"><?= $nb_notifs ?></span>
            <?php endif; ?>
        </a>
    </li>
        <li><a href="#users">👥 Utilisateurs</a></li>
        <li><a href="#assign">🔗 Affectation</a></li>
        <li><a href="manager.php">🏠 Propriétés</a></li>
    </ul>

    <!-- Déconnexion en bas -->
    <div class="logout-btn">
        <a href="index.php" style="color: #f87171; text-decoration: none; font-weight: bold;">
            🚪 Se déconnecter
        </a>
    </div>
</div>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <div class="main">

    <?php if ($message && strpos($message, 'retirée') === false): ?>
    <div class="alert"><?= $message ?></div>
<?php endif; ?>

        <!-- ===== TABLEAU DE BORD ===== -->
        <section id="notifications" style="<?= $nb_notifs === 0 ? 'display:none;' : '' ?>">
    <div class="section" style="border-left: 4px solid red; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2>🔔 Notifications <span style="color:red;">(<?= $nb_notifs ?>)</span></h2>
            <?php if ($nb_notifs > 0): ?>
                <a href="?lire_notif=1" class="btn btn-primary" style="font-size:12px;">Tout marquer lu</a>
            <?php endif; ?>
        </div>
        <?php if (empty($notifs)): ?>
            <p style="color:gray;">Aucune nouvelle notification.</p>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
            <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <span style="font-size:0.85rem;">🏠 <?= htmlspecialchars($n['message']) ?></span><br>
                    <small style="color:gray;"><?= date('d/m/Y H:i', strtotime($n['date_creation'])) ?> — expire dans <?= 7 - (int)floor((time() - strtotime($n['date_creation'])) / 86400) ?> jour(s)</small>
                </div>
                <?php if ($n['id_propriete']): ?>
                    <a href="manager.php?statut=publiee" onclick="localStorage.setItem('highlight_prop','<?= $n['id_propriete'] ?>');" class="btn btn-primary" style="font-size:11px;white-space:nowrap;margin-left:10px;">Voir</a>
<?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
        <section id="dashboard">
            <h1>📊 Tableau de bord</h1>
            <div class="cards">
                <div class="card">
                    <h2><?= (int)$nb_proprietes ?></h2>
                    <p>Propriétés</p>
                </div>
                <div class="card">
    <h2><?= (int)$nb_retirees ?></h2>
    <p>Retirées</p>
</div>

                <div class="card">
                    <h2><?= (int)$nb_visites ?></h2>
                    <p>Demandes de visite</p>
                </div>
                <div class="card">
                    <h2><?= (int)$nb_en_attente ?></h2>
                    <p>En attente</p>
                </div>
                <div class="card">
                    <h2><?= (int)$nb_users_actifs ?></h2>
                    <p>Utilisateurs actifs</p>
                </div>
            </div>

            <div class="chart-container">
    <div class="chart-box">
        <canvas id="barChart"></canvas>
    </div>
    <div class="chart-box">
        <canvas id="donutChart"></canvas>
    </div>
</div>

            <div class="section">
                <h2>Propriétés en attente</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Bailleur</th>
                    </tr>
                    <?php if ($proprietes_attente): ?>
                        <?php foreach ($proprietes_attente as $p): ?>
                        <tr>
                            <td><?= (int)$p['id_propriete'] ?></td>
                            <td><?= htmlspecialchars($p['titre']) ?></td>
                            <td><?= htmlspecialchars($p['proprietaire']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Aucune propriété en attente.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </section>

        <!-- ===== GESTION UTILISATEURS ===== -->
        <section id="users" class="section">
            <h2>👥 Gestion des utilisateurs</h2>
            <form method="GET" action="manager.php" class="search-box">
                <input type="text" name="recherche" placeholder="Recherche nom / email"
                       value="<?= htmlspecialchars($recherche) ?>">
                <select name="role">
                    <?php foreach (['Tous','client','bailleur','agent','manager'] as $r): ?>
                        <option value="<?= $r ?>" <?= $filtre_role === $r ? 'selected' : '' ?>>
                            <?= ucfirst($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <button type="button" class="btn btn-primary" onclick="openModal()">+ Ajouter</button>
            </form>

            <table>
                <tr>
                    <th>Nom & Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
                <?php if ($utilisateurs): ?>
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>
                        <td><?= ucfirst(htmlspecialchars($u['role'])) ?></td>
                        <td>
                            <button class="btn btn-primary"
                                onclick="ouvrirModifierUser(
                                    <?= $u['id_utilisateur'] ?>,
                                    '<?= htmlspecialchars($u['nom'],      ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($u['prenom'],   ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($u['email'],    ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($u['role'],     ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($u['telephone'] ?? '', ENT_QUOTES) ?>'
                                )">Modifier</button>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                <input type="hidden" name="action" value="supprimer_utilisateur">
                                <input type="hidden" name="id" value="<?= $u['id_utilisateur'] ?>">
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">Aucun utilisateur trouvé.</td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section id="assign" class="section">
    <h2>🔗 Affectation Clients - Agents</h2>
    <form method="POST" action="manager.php">
        <input type="hidden" name="action" value="affecter">
        <div class="assign">
            <div class="list-box">
                <h3>Clients</h3>
                <select name="client_id" size="6" required>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id_utilisateur'] ?>"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="list-box">
                <h3>Agents</h3>
                <select name="agent_id" size="6" required>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id_utilisateur'] ?>"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
    <h3>Clients affectés</h3>
    <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:15px;">
        <?php 
        $affs = $db->query("SELECT c.prenom as cp, c.nom as cn, a.prenom as ap, a.nom as an FROM utilisateurs c JOIN utilisateurs a ON c.id_agent = a.id_utilisateur WHERE c.role = 'client' AND c.id_agent IS NOT NULL")->fetchAll();
        foreach ($affs as $aff): ?>
            <div style="text-align:center; padding:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                <div style="font-size:0.9rem; font-weight:bold;"><?= htmlspecialchars($aff['cp'].' '.$aff['cn']) ?></div>
                <div style="height:25px; width:2px; background:#2563eb; margin:5px auto;"></div>
                <div style="font-size:0.9rem;"><?= htmlspecialchars($aff['ap'].' '.$aff['an']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
        </div>
        <button type="submit" class="btn btn-success">Affecter Client</button>
    </form>
</section>

<section id="assign-props" class="section">
    <h2>🏠 Affectation Propriétés - Agents</h2>
    <form method="POST" action="manager.php">
        <input type="hidden" name="action" value="affecter_propriete">
        <div class="assign">
            <div class="list-box">
                <h3>Propriétés</h3>
                <select name="id_propriete" size="6" required>
                    <?php foreach ($toutes_proprietes as $p): ?>
                        <option value="<?= $p['id_propriete'] ?>"><?= htmlspecialchars($p['titre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="list-box">
                <h3>Agents</h3>
                <select name="agent_id" size="6" required>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id_utilisateur'] ?>"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
    <h3>Propriétés affectées</h3>
    <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:15px;">
        <?php 
        $aff_props = $db->query("SELECT p.titre, a.prenom as ap, a.nom as an FROM proprietes p JOIN utilisateurs a ON p.id_agent = a.id_utilisateur WHERE p.id_agent IS NOT NULL AND p.statut = 'affectee'")->fetchAll();
        foreach ($aff_props as $ap): ?>
            <div style="text-align:center; padding:10px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px;">
                <div style="font-size:0.9rem; font-weight:bold;"><?= htmlspecialchars($ap['titre']) ?></div>
                <div style="height:25px; width:2px; background:#0284c7; margin:5px auto;"></div>
                <div style="font-size:0.9rem; color:#0369a1;"><?= htmlspecialchars($ap['ap'].' '.$ap['an']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
        </div>
        <button type="submit" class="btn btn-primary">Affecter Propriété</button>
    </form>
</section>

        <!-- ===== GESTION PROPRIÉTÉS ===== -->
        <section id="ads" class="section">
            <h2>🏠 Gestion des propriétés</h2>
            <form method="GET" action="manager.php" style="margin-bottom:10px">
                <select name="statut" onchange="this.form.submit()">
                    <?php foreach (['Toutes','attente','affectee','publiee','refusee','retiree'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filtre_statut === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="table">
            <thead>
        <tr>
            <th>Titre</th>
            <th>Type</th>
            <th>Modèle</th>
            <th>Zone</th>
            <th>Prix</th>
            <th>Bailleur</th>
            <th>Document</th>
            <th>Statut</th>
            <th class="col-actions">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($proprietes)): ?>
            <?php foreach ($proprietes as $p): ?>
                <tr id="prop-<?= $p['id_propriete'] ?>" style="transition:background 1s;">
                    <td><?= htmlspecialchars($p['titre']) ?></td>
                    <td><?= htmlspecialchars($p['type_bien']) ?></td>
                    <td><?= htmlspecialchars($p['modele']) ?></td>
                    <td><?= htmlspecialchars($p['zone']) ?></td>
                    <td><?= number_format($p['prix'], 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($p['nom_bailleur']) ?></td>
                    
                    <td class="col-doc">
                        <?php if (!empty($p['chemin_document'])): ?>
                            <a href="<?= htmlspecialchars($p['chemin_document']) ?>" target="_blank" class="btn btn-primary" style="font-size:10px; display:inline-block;">
                                👁️ <?= htmlspecialchars($p['nom_original'] ?? 'Ouvrir') ?>
                            </a>
                        <?php else: ?>
                            <span style="color:gray; font-size:12px;">Aucun</span>
                        <?php endif; ?>
                    </td>

                    <td style="font-weight:bold; color: <?= $p['statut'] == 'affectee' ? 'blue' : 'green' ?>;">
    <?= strtoupper($p['statut']) ?> </td>
                    
                        <td class="col-actions">
                        <?php if ($p['statut'] === 'attente'): ?>
                            <form method="POST" action="manager.php">
                                <input type="hidden" name="action" value="gerer_propriete">
                                <input type="hidden" name="id" value="<?= $p['id_propriete'] ?>">
                                
                                <select name="agent_id" style="width:100%; font-size:10px; margin-bottom:4px;">
                                    <option value="">Agent ?</option>
                                    <?php foreach ($agents as $a): ?>
                                        <option value="<?= $a['id_utilisateur'] ?>"><?= $a['prenom'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="choix" value="affecter" class="btn btn-primary" style="width:100%; font-size:10px; margin-bottom:4px;">Affecter</button>
                                <button type="submit" name="choix" value="publier" class="btn btn-success" style="width:100%; font-size:10px; margin-bottom:4px;">Publier</button>
                                <input type="text" name="commentaire" placeholder="Motif refus" style="width:100%; font-size:10px; margin-bottom:4px;">
                                <button type="submit" name="choix" value="refuser" class="btn btn-danger" style="width:100%; font-size:10px;">Refuser</button>
                            </form>
                        <?php elseif ($p['statut'] === 'publiee' || $p['statut'] === 'affectee'): ?>
                            <form method="POST" action="manager.php" onsubmit="return confirm('Retirer cette propriété ?')">
                                <input type="hidden" name="action" value="retirer_propriete">
                                <input type="hidden" name="id" value="<?= $p['id_propriete'] ?>">
                                <button type="submit" class="btn btn-danger" style="width:100%; font-size:10px;">Retirer</button>
                            </form>
                        <?php else: ?>
                            <small>-</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9">Aucune propriété trouvée.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
        </section>

    </div><!-- /main -->
</div><!-- /container -->

<!-- ========== MODAL AJOUTER UTILISATEUR ========== -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h2>Nouveau compte</h2>
        <form method="POST" action="manager.php">
            <input type="hidden" name="action" value="ajouter_utilisateur">
            <input type="text"     name="nom"          placeholder="Nom *"          required>
            <input type="text"     name="prenom"        placeholder="Prénom *"       required>
            <input type="email"    name="email"         placeholder="Email *"        required>
           <div style="position:relative; margin-bottom:10px;">
    <input type="password" name="mot_de_passe" id="mdp_input"
           placeholder="Mot de passe *" required
           style="width:100%; padding:10px; padding-right:40px; border:1px solid #ccc; border-radius:5px;">
    <span onclick="toggleMdp()" 
          style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1rem; user-select:none;">
        👁️
    </span>
</div>
            <input type="text"     name="telephone"     placeholder="Téléphone">
            <select name="role">
                <option value="client">Client</option>
                <option value="bailleur">Bailleur</option>
                <option value="agent">Agent</option>
                <option value="manager">Manager</option>
            </select>
            <button type="submit" class="btn btn-success">Créer</button>
            <button type="button" class="btn btn-danger" onclick="closeModal()">Fermer</button>
        </form>
    </div>
</div>

<!-- ========== MODAL MODIFIER UTILISATEUR ========== -->
<div class="modal" id="modalModifier">
    <div class="modal-content">
        <h2>Modifier l'utilisateur</h2>
        <form method="POST" action="manager.php">
            <input type="hidden" name="action" value="modifier_utilisateur">
            <input type="hidden" name="id"          id="modifier_id">
            <input type="text"   name="nom"          id="modifier_nom"    placeholder="Nom *"    required>
            <input type="text"   name="prenom"       id="modifier_prenom" placeholder="Prénom *" required>
            <input type="email"  name="email"        id="modifier_email"  placeholder="Email *"  required>
            <input type="text"   name="telephone"    id="modifier_tel"    placeholder="Téléphone">
            <select name="role"  id="modifier_role">
                <option value="client">Client</option>
                <option value="bailleur">Bailleur</option>
                <option value="agent">Agent</option>
                <option value="manager">Manager</option>
            </select>
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <button type="button" class="btn btn-danger" onclick="fermerModifier()">Fermer</button>
        </form>
    </div>
</div>

<!-- ========== SCRIPTS ========== -->
<script>
    // --- Fonctions de gestion des fenêtres (Modales) ---
    function openModal()  { document.getElementById("modal").style.display = "block"; }
    function closeModal() { document.getElementById("modal").style.display = "none";  }

    function ouvrirModifierUser(id, nom, prenom, email, role, tel) {
        document.getElementById("modifier_id").value     = id;
        document.getElementById("modifier_nom").value    = nom;
        document.getElementById("modifier_prenom").value = prenom;
        document.getElementById("modifier_email").value  = email;
        document.getElementById("modifier_role").value   = role;
        document.getElementById("modifier_tel").value    = tel;
        document.getElementById("modalModifier").style.display = "block";
    }

    function fermerModifier() { 
        document.getElementById("modalModifier").style.display = "none"; 
    }

    // --- Graphique barres — Statistiques globales ---
   new Chart(document.getElementById("barChart"), {
    type: 'bar',
    data: {
        labels: ['Total', 'Visites', 'Attente', 'Retirées', 'Refusées'],
        datasets: [{
            label: 'Données globales',
            data: [
                <?= (int)$nb_proprietes ?>, 
                <?= (int)$nb_visites ?>, 
                <?= (int)$nb_en_attente ?>, 
                <?= (int)$nb_retirees ?>,
                <?= (int)$nb_refusees ?>
            ],
            backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed']
        }]
    }
});

    // --- Graphique donut — types de biens ---
    new Chart(document.getElementById("donutChart"), {
        type: 'doughnut',
        data: {
            labels: <?= $labels_donut ?>,
            datasets: [{
                data: <?= $data_donut ?>,
                backgroundColor: ['#2563eb','#16a34a','#f59e0b','#dc2626','#7c3aed','#0891b2','#be123c','#15803d']
            }]
        }
    });
</script>

<script>
window.addEventListener('load', function() {
    const id = localStorage.getItem('highlight_prop');
    if (id) {
        localStorage.removeItem('highlight_prop');
        const el = document.getElementById('prop-' + id);
        if (el) {
            el.style.background = '#fef3c7';
            el.style.transition = 'background 2s';
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => { el.style.background = ''; }, 3000);
        }
    }
});
</script>
<script>
function toggleMdp() {
    const input = document.getElementById('mdp_input');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>