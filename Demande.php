<?php
require_once 'init.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit;
}
$id_client = (int)$_SESSION['id_utilisateur'];

$id_propriete = (int)($_GET['id'] ?? 0);

if ($id_propriete <= 0) {
    die('Propriété invalide');
}

   //RÉCUPÉRATION DE LA PROPRIÉTÉ AVEC GESTION DES DEUX SOURCES D'IMAGE
$stmt = $db->prepare("
SELECT p.*, 
       COALESCE(ph.chemin_photo, p.image_url) AS photo
FROM proprietes p
LEFT JOIN photos ph 
       ON ph.id_propriete = p.id_propriete 
       AND ph.est_principale = 1
WHERE p.id_propriete = ?
");
$stmt->execute([$id_propriete]);

$propriete = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$propriete) {
    die('Propriété introuvable');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message_client = trim($_POST['message_client']);
    $date_souhaitee = $_POST['date_souhaitee'];

    // 1. Récupérer l'agent affecté au client
    $stmtAgent = $db->prepare("SELECT id_agent FROM utilisateurs WHERE id_utilisateur = ? AND role = 'client'");
$stmtAgent->execute([$id_client]);
$affectation = $stmtAgent->fetch(PDO::FETCH_ASSOC);

if (!$affectation || empty($affectation['id_agent'])) {
    die('Erreur : Aucun agent ne vous est affecté. Contactez le manager.');
}

$id_agent = $affectation['id_agent'];

    // 3. Insérer la demande de visite
    $insert = $db->prepare("
        INSERT INTO demandes_visite
        (id_client, id_propriete, id_agent, message_client, date_souhaitee, statut)
        VALUES (?, ?, ?, ?, ?, 'attente')
    ");
    
    $insert->execute([
        $id_client,
        $id_propriete,
        $id_agent,
        $message_client,
        $date_souhaitee
    ]);
    
    // 4. Redirection après succès
    header("Location: demande.php?id=" . $id_propriete . "&success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demande de visite</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:#f4f4f4;
min-height:100vh;
padding:40px 20px;
}

.container{
max-width:1000px;
margin:auto;
}

.header{
background:#000;
color:#fff;
padding:25px;
border-radius:15px 15px 0 0;
text-align:center;
}

.header h1{
color:#D4AF37;
font-size:32px;
margin-bottom:8px;
}

.header p{
color:#ddd;
}

.card{
background:#fff;
border-radius:0 0 15px 15px;
overflow:hidden;
box-shadow:0 8px 25px rgba(0,0,0,.12);
}

.image{
width:100%;
height:400px;
object-fit:cover;
display:block;
}

.content{
padding:30px;
}

.titre{
font-size:30px;
font-weight:700;
color:#000;
margin-bottom:20px;
}

.info-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:15px;
margin-bottom:25px;
}

.info-box{
background:#fafafa;
border-left:5px solid #D4AF37;
padding:15px;
border-radius:8px;
}

.info-box span{
display:block;
font-size:13px;
color:#777;
margin-bottom:5px;
}

.info-box strong{
font-size:18px;
color:#000;
}

.description{
background:#fafafa;
padding:20px;
border-radius:10px;
margin-bottom:30px;
line-height:1.8;
}

.description h3{
margin-bottom:10px;
color:#D4AF37;
}

.form-title{
font-size:24px;
font-weight:bold;
margin-bottom:20px;
color:#000;
}

.form-group{
margin-bottom:20px;
}

.form-group label{
display:block;
font-weight:600;
margin-bottom:8px;
color:#000;
}

.form-group input,
.form-group textarea{
width:100%;
padding:14px;
border:1px solid #ddd;
border-radius:8px;
font-size:15px;
outline:none;
transition:.3s;
}

.form-group input:focus,
.form-group textarea:focus{
border-color:#D4AF37;
box-shadow:0 0 0 3px rgba(212,175,55,.15);
}

.form-group textarea{
height:140px;
resize:none;
}

.btn{
width:100%;
padding:16px;
background:#D4AF37;
color:#000;
border:none;
border-radius:10px;
font-size:18px;
font-weight:bold;
cursor:pointer;
transition:.3s;
}

.btn:hover{
background:#be9827;
transform:translateY(-2px);
}

.back-btn{
display:inline-block;
margin-bottom:20px;
text-decoration:none;
background:#000;
color:#fff;
padding:10px 18px;
border-radius:8px;
}

.back-btn:hover{
background:#222;
}

@media(max-width:768px){

.image{
height:250px;
}

.titre{
font-size:24px;
}



}

</style>
</head>
<body>

<div class="container">

<?php if(isset($_GET['success'])): ?>
<div class="success-message">
    ✅ Votre demande a été envoyée avec succès à votre agent immobilier.
</div>
<?php endif; ?>

<a href="client.php" class="back-btn">← Retour</a>

<div class="header">
<h1>Demande de visite</h1>
<p>Planifiez votre visite avec votre agent immobilier</p>
</div>

<div class="card">

<?php if(!empty($propriete['photo'])): ?>
    <img src="<?= htmlspecialchars($propriete['photo']) ?>" class="image" alt="Photo de la propriété">
<?php else: ?>
    <img src="assets/img/default.jpg" class="image" alt="Image indisponible">
<?php endif; ?>

<div class="content">

<div class="titre">
<?= htmlspecialchars($propriete['titre']) ?>
</div>

<div class="info-grid">

<div class="info-box">
<span>Type de bien</span>
<strong><?= ucfirst(str_replace('_',' ',$propriete['type_bien'])) ?></strong>
</div>

<div class="info-box">
<span>Zone</span>
<strong><?= htmlspecialchars($propriete['zone']) ?></strong>
</div>

<div class="info-box">
<span>Prix</span>
<strong><?= number_format($propriete['prix'],0,',',' ') ?> FCFA</strong>
</div>

<div class="info-box">
<span>Mode</span>
<strong><?= ucfirst($propriete['modele']) ?></strong>
</div>

</div>

<div class="description">
<h3>Description</h3>
<?= nl2br(htmlspecialchars($propriete['description'])) ?>
</div>

<div class="form-title">
Remplissez votre demande
</div>

<form method="POST">

<div class="form-group">
<label>Date souhaitée</label>
<input
type="date"
name="date_souhaitee"
required
min="<?= date('Y-m-d') ?>"
>
</div>

<div class="form-group">
<label>Message client</label>
<textarea
name="message_client"
required
placeholder="Décrivez votre besoin, vos disponibilités ou toute information utile..."
></textarea>
</div>

<button type="submit" class="btn">
📅 Envoyer la demande de visite
</button>

</form>

</div>

</div>

</div>

</body>
</html>