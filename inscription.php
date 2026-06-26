<?php
// 1. Initialisation unique (session + connexion BDD)
// init.php s'occupe déjà de session_start() et de la variable $db
require_once 'init.php';

$message = "";
$status = "";

if (isset($_POST['register'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $role = $_POST['role'];
    $password = $_POST['mot_de_passe'];

    // Validation des données
    if ($role !== 'client' && $role !== 'bailleur') {
        $message = "Rôle invalide sélectionné.";
        $status = "error";
    } 
    elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
        $status = "error";
    } 
    elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $message = "Le mot de passe doit inclure au moins une majuscule, une minuscule et un chiffre.";
        $status = "error";
    } else {
        // Vérification si l'email existe (utilisation de $db)
        $stmt = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $message = "Cet email est déjà utilisé.";
            $status = "error";
        } else {
            // Hashage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertion (utilisation de $db)
            $insert = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($insert->execute([$nom, $prenom, $email, $hashed_password, $role, $telephone])) {
                $message = "Inscription réussie ! Vous pouvez vous connecter.";
                $status = "success";
            } else {
                $message = "Une erreur est survenue lors de l'inscription.";
                $status = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription — ImmoGest</title>
<style>
/* ════════════ Thème couleur Noir / Or Professionnel ════════════ */
:root {
    --or-vif:     #C9A84C;   
    --or-clair:   #E8CC7A;   
    --noir-pur:   #0A0A0A;   
    --noir-card:  #111111;   
    --noir-input: #1A1A1A;   
    --noir-bord:  #2A2A2A;   
    --texte-gris: #8A8A8A;
    --texte-blanc:#F5F0E8;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

html {
    background-color: var(--noir-pur);
    min-height: 100vh;
}

body {
    min-height: 100vh;
    display: flex;
    flex-direction: column; /* Aligne le logo au-dessus du conteneur */
    justify-content: center;
    align-items: center;
    gap: 24px; /* Espace harmonieux sous le logo */
    background: radial-gradient(circle at center, #151515 0%, var(--noir-pur) 100%) no-repeat fixed;
    padding: 40px 20px;
    color: var(--texte-blanc);
}

/* Le Logo maintenant centré au-dessus du bloc */
.global-logo {
    width: 90px;
    height: 90px;
    background: var(--or-vif);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 8px 30px rgba(0,0,0,0.7);
    overflow: hidden;
    border: 2px solid var(--or-vif);
    transition: transform 0.3s ease;
}

.global-logo:hover {
    transform: scale(1.05);
}

.global-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Ajustement du conteneur en mode "carte centrée" unique */
.container {
    width: 540px; /* Légèrement plus large pour accommoder les lignes doubles (.row) proprement */
    max-width: 100%;
    height: auto; /* Hauteur dynamique s'adaptant parfaitement aux champs */
    display: flex;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.9);
    border: 1px solid rgba(201, 168, 76, 0.18);
    background: var(--noir-card);
}

/* Disparition complète de la section image de gauche */
.left {
    display: none;
}

/* Alignements de la partie de droite sur 100% de la largeur disponible */
.right {
    width: 100%;
    background: var(--noir-card);
    padding: 45px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right h2 {
    color: var(--or-vif);
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 6px;
    text-align: center;
}

.right .subtitle {
    color: var(--texte-gris);
    font-size: 14px;
    margin-bottom: 25px;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.row {
    display: flex;
    gap: 15px;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.input-group label {
    color: #aaa;
    font-size: 13px;
    font-weight: 600;
}

input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid var(--noir-bord);
    border-radius: 8px;
    font-size: 14px;
    background: var(--noir-input);
    color: #fff;
    outline: none;
    transition: all 0.3s;
}

input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
    border-color: var(--or-vif);
    background: #181818;
    box-shadow: 0 0 8px rgba(201, 168, 76, 0.2);
}

.show-password-container {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
    color: var(--texte-gris);
    font-size: 13px;
    cursor: pointer;
    user-select: none;
}

.show-password-container input {
    accent-color: var(--or-vif);
    cursor: pointer;
    width: 15px;
    height: 15px;
}

.radio-group-label {
    color: #aaa;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 4px;
}

.radio-container {
    display: flex;
    gap: 25px;
    background: var(--noir-input);
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid var(--noir-bord);
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
}

.radio-option input[type="radio"] {
    accent-color: var(--or-vif);
    width: 17px;
    height: 17px;
    cursor: pointer;
}

button[type="submit"] {
    width: 100%;
    padding: 14px;
    background: var(--or-vif);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(201, 168, 76, 0.2);
    margin-top: 5px;
}

button[type="submit"]:hover {
    background: var(--or-clair);
    transform: translateY(-1px);
    box-shadow: 0 5px 20px rgba(201, 168, 76, 0.4);
}

.link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #aaa;
}

.link a {
    color: var(--or-vif);
    text-decoration: none;
    font-weight: 600;
}

.link a:hover {
    color: var(--or-clair);
    text-decoration: underline;
}

.msg {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
    text-align: center;
}

.msg.error {
    color: #ff4b4b;
    background: rgba(255, 75, 75, 0.1);
    border: 1px solid rgba(255, 75, 75, 0.3);
}

.msg.success {
    color: #2b8a3e;
    background: rgba(43, 138, 62, 0.1);
    border: 1px solid rgba(43, 138, 62, 0.3);
}

/* Adaptation réactive pour tablettes et smartphones */
@media screen and (max-width: 580px) {
    body {
        padding: 20px 16px;
    }
    .right {
        padding: 35px 20px;
    }
    .right h2 {
        font-size: 26px;
    }
    .row {
        flex-direction: column;
        gap: 16px;
    }
}
</style>
</head>
<body>

<div class="global-logo">
<img src="" alt="Logo" onerror="this.style.display='none'">🏠
</div>

<div class="container">

    <div class="left">
    </div>

    <div class="right">

        <h2>Inscription</h2>
        <p class="subtitle">Créez votre compte pour accéder à nos services immobiliers.</p>

        <?php if($message): ?>
            <div class="msg <?php echo $status; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="input-group">
                    <label>Nom</label>
                    <input type="text" name="nom" placeholder="Votre nom" required>
                </div>

                <div class="input-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" placeholder="Votre prénom" required>
                </div>
            </div>

            <div class="row">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Ex: nom@exemple.com" required>
                </div>

                <div class="input-group">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" placeholder="Ex: +22600000000" required>
                </div>
            </div>

            <div class="input-group">
                <p class="radio-group-label">Vous êtes ?</p>
                <div class="radio-container">
                    <label class="radio-option">
                        <input type="radio" name="role" value="client" checked required>
                        Client
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="role" value="bailleur">
                        Bailleur
                    </label>
                </div>
            </div>

            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" 
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$" 
                       title="Le mot de passe doit contenir au moins 6 caractères, dont une majuscule, une minuscule et un chiffre." 
                       placeholder="6 caractères min" required>
                
                <label class="show-password-container">
                    <input type="checkbox" id="togglePassword"> Afficher le mot de passe
                </label>
            </div>

            <button type="submit" name="register">S'inscrire</button>
        </form>

        <div class="link">
            Déjà un compte ? <a href="connexion.php">Se connecter</a>
        </div>

    </div>

</div>

<script>
document.getElementById('togglePassword').addEventListener('change', function() {
    const passwordField = document.getElementById('mot_de_passe');
    if (this.checked) {
        passwordField.type = 'text';
    } else {
        passwordField.type = 'password';
    }
});
</script>

</body>
</html>