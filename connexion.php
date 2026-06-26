<?php
// On utilise le fichier unique pour la session et la base de données
require_once 'init.php';

$message = "";

if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['mot_de_passe'];

    // CORRECTION : On utilise $db (défini dans init.php) au lieu de $pdo
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $userRow = $stmt->fetch();

    if ($userRow && password_verify($password, $userRow['mot_de_passe'])) {

        // 🛡️ TRÈS IMPORTANT : Régénérer l'ID de session à la connexion
        session_regenerate_id(true);

        // Stockage des informations
        $_SESSION['id_utilisateur'] = $userRow['id_utilisateur']; // Important : le nom doit correspondre à ce que tu attends dans les autres pages
        $_SESSION['nom']            = $userRow['nom'];
        $_SESSION['prenom']         = $userRow['prenom']; // AJOUTE CETTE LIGNE
        $_SESSION['role']           = $userRow['role'];

        // Redirection selon le rôle
        $role = $userRow['role'];
        if ($role === 'client') {
            header("Location: client.php");
        } elseif ($role === 'bailleur') {
            header("Location: bailleur.php");
        } elseif ($role === 'agent') {
            header("Location: agent.php");
        } elseif ($role === 'manager') {
            header("Location: manager.php");
        }
        exit; // Toujours mettre exit après un header()

    } else {
        $message = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — ImmoGest</title>
<style>
/* ════════════ Thème couleur Noir / Or Professionnel ════════════ */
:root {
    --or-vif:     #C9A84C;   /* Or satiné professionnel */
    --or-clair:   #E8CC7A;   /* Or lumineux pour les hovers */
    --noir-pur:   #0A0A0A;   /* Fond de page sombre */
    --noir-card:  #111111;   /* Fond du conteneur de droite */
    --noir-input: #1A1A1A;   /* Fond des champs de saisie */
    --noir-bord:  #2A2A2A;   /* Bordures discrètes */
    --texte-gris: #8A8A8A;
    --texte-blanc:#F5F0E8;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Évite le blanc lors du tirage/glissement (overscroll-bounce) */
html {
    background-color: var(--noir-pur);
    min-height: 100vh;
}

body {
    min-height: 100vh;
    display: flex;
    flex-direction: column; /* Aligne le logo au-dessus du formulaire */
    justify-content: center;
    align-items: center;
    gap: 24px; /* Espace équilibré entre le logo et le formulaire */
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

/* Redimensionnement et recentrage du conteneur principal */
.container {
    width: 460px;
    max-width: 100%;
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

/* Ajustement de la partie formulaire pour occuper tout l'espace de la carte */
.right {
    width: 100%;
    background: var(--noir-card);
    padding: 45px 35px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right h2 {
    color: var(--or-vif);
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 8px;
    text-align: center;
}

.right .subtitle {
    color: var(--texte-gris);
    font-size: 14px;
    margin-bottom: 30px;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.input-group label {
    color: #aaa;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.password-wrapper {
    position: relative;
    width: 100%;
}

input[type="email"], input[type="password"], input[type="text"] {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--noir-bord);
    border-radius: 8px;
    font-size: 15px;
    background: var(--noir-input);
    color: #fff;
    outline: none;
    transition: all 0.3s;
}

.password-wrapper input {
    padding-right: 46px;
}

input[type="email"]:focus, input[type="password"]:focus, input[type="text"]:focus {
    border-color: var(--or-vif);
    background: #181818;
    box-shadow: 0 0 8px rgba(201, 168, 76, 0.2);
}

.btn-oeil {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--texte-gris);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}

.btn-oeil:hover {
    color: var(--or-vif);
}

.options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    margin-top: 2px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #aaa;
}

.remember-me input {
    cursor: pointer;
    accent-color: var(--or-vif);
}

.forgot {
    color: var(--texte-gris);
    text-decoration: none;
    transition: color 0.2s;
}

.forgot:hover {
    color: var(--or-vif);
    text-decoration: underline;
}

button[type="submit"] {
    width: 100%;
    padding: 15px;
    background: var(--or-vif);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(201, 168, 76, 0.25);
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
    color: #ff4b4b;
    background: rgba(255, 75, 75, 0.1);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
    border: 1px solid rgba(255, 75, 75, 0.3);
}

/* Ajustements pour les mobiles */
@media screen and (max-width: 480px) {
    body {
        padding: 20px 16px;
    }
    .right {
        padding: 35px 20px;
    }
    .right h2 {
        font-size: 26px;
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
        <a href="index.php" class="back">⬅</a>
        <img class="prop-img" src="image.png" alt="Immobilier">
    </div>

    <div class="right">
        <h2>Connexion</h2>
        <p class="subtitle">Entrez vos identifiants pour accéder à votre espace personnel.</p>

        <?php if (!empty($message)): ?>
            <div class="msg"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Adresse e-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="exemple@domaine.com" 
                    value="<?= htmlspecialchars($email ?? '') ?>" 
                    required
                >
            </div>

            <div class="input-group">
                <label for="mot_de_passe">Mot de passe</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="mot_de_passe" 
                        name="mot_de_passe" 
                        placeholder="Votre mot de passe" 
                        required
                    >
                    <button type="button" class="btn-oeil" id="btnOeil" aria-label="Afficher le mot de passe">
                        <svg id="iconeOeil" width="18" height="18" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="options">
                <div class="remember-me">
                    <input type="checkbox" id="se_souvenir" name="se_souvenir">
                    <label for="se_souvenir">Se souvenir de moi</label>
                </div>
                <a href="mot_de_passe_oublie.php" class="forgot">Mot de passe oublié ?</a>
            </div>

            <button type="submit" name="login">Se connecter</button>

            <div class="link">
                Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
            </div>
        </form>
    </div>
</div>

<script>
const champMdp = document.getElementById('mot_de_passe');
const btnOeil = document.getElementById('btnOeil');
const iconeOeil = document.getElementById('iconeOeil');

const OEIL_OUVERT = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
const OEIL_FERME = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8 a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4 c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19 m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

btnOeil.addEventListener('click', () => {
    const estMasque = champMdp.type === 'password';
    champMdp.type = estMasque ? 'text' : 'password';
    iconeOeil.innerHTML = estMasque ? OEIL_FERME : OEIL_OUVERT;
});
</script>

</body>
</html>