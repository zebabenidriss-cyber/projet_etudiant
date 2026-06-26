<?php
require_once 'init.php';

$message = "";
$status  = "";
$etape   = 1;

if (isset($_POST['verifier'])) {
    $email     = $_POST['email'];
    $telephone = $_POST['telephone'];
    $stmt = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ? AND telephone = ?");
    $stmt->execute([$email, $telephone]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_reset_id'] = $user['id_utilisateur'];
        $etape = 2;
    } else {
        $message = "Email ou numéro de téléphone incorrect.";
        $status  = "error";
    }
}

if (isset($_POST['modifier_mdp'])) {
    $password = $_POST['mot_de_passe'];
    if (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
        $status  = "error";
        $etape   = 2;
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $message = "Le mot de passe doit inclure au moins une majuscule, une minuscule et un chiffre.";
        $status  = "error";
        $etape   = 2;
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
        if ($update->execute([$hashed, $_SESSION['user_reset_id']])) {
            $message = "Mot de passe modifié avec succès !";
            $status  = "success";
            unset($_SESSION['user_reset_id']);
            $etape = 3;
        } else {
            $message = "Une erreur est survenue.";
            $status  = "error";
            $etape   = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Récupération de compte — Habitat Horizon</title>
<style>
:root {
    --or:       #C9A84C;
    --or-clair: #E8CC7A;
    --noir-pur: #0A0A0A;
    --noir-card:#111111;
    --noir-inp: #1A1A1A;
    --noir-bord:#2A2A2A;
    --gris:     #8A8A8A;
    --blanc:    #F5F0E8;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
html { background: var(--noir-pur); min-height: 100vh; }
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 24px;
    background: radial-gradient(circle at center, #151515 0%, var(--noir-pur) 100%) no-repeat fixed;
    padding: 40px 20px;
    color: var(--blanc);
}

.logo-circle {
    width: 70px; height: 70px;
    background: var(--or);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.7);
    border: 2px solid var(--or);
}

.card {
    width: 460px;
    max-width: 100%;
    background: var(--noir-card);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.9);
    border: 1px solid rgba(201,168,76,0.18);
    padding: 45px 35px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.card h2 {
    color: var(--or);
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 6px;
    text-align: center;
}
.card .subtitle {
    color: var(--gris);
    font-size: 13px;
    margin-bottom: 28px;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.input-group { display: flex; flex-direction: column; gap: 6px; }
.input-group label { color: #aaa; font-size: 13px; font-weight: 600; letter-spacing: 0.03em; }

input[type="email"],
input[type="password"],
input[type="text"] {
    width: 100%;
    padding: 13px 15px;
    border: 1px solid var(--noir-bord);
    border-radius: 8px;
    font-size: 14px;
    background: var(--noir-inp);
    color: #fff;
    outline: none;
    transition: all 0.3s;
}
input:focus {
    border-color: var(--or);
    background: #181818;
    box-shadow: 0 0 8px rgba(201,168,76,0.2);
}

.show-pw {
    display: flex; align-items: center; gap: 8px;
    margin-top: 4px; color: #888; font-size: 12px;
    cursor: pointer; user-select: none;
}
.show-pw input { accent-color: var(--or); cursor: pointer; }

button[type="submit"] {
    width: 100%; padding: 14px;
    background: var(--or); color: #000;
    border: none; border-radius: 8px;
    font-size: 15px; font-weight: 700;
    cursor: pointer; margin-top: 4px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(201,168,76,0.25);
}
button[type="submit"]:hover {
    background: var(--or-clair);
    transform: translateY(-1px);
    box-shadow: 0 5px 20px rgba(201,168,76,0.4);
}

.msg {
    padding: 11px; border-radius: 8px;
    margin-bottom: 16px; font-size: 13px; text-align: center;
}
.msg.error   { color: #ff4b4b; background: rgba(255,75,75,0.1);   border: 1px solid rgba(255,75,75,0.3); }
.msg.success { color: #2b8a3e; background: rgba(43,138,62,0.1);   border: 1px solid rgba(43,138,62,0.3); }

.link { text-align: center; margin-top: 20px; font-size: 13px; color: #aaa; }
.link a { color: var(--or); text-decoration: none; font-weight: 600; }
.link a:hover { color: var(--or-clair); text-decoration: underline; }

.btn-retour {
    display: block; width: 100%; padding: 14px;
    background: var(--or); color: #000;
    border-radius: 8px; font-weight: 700;
    text-decoration: none; text-align: center;
    font-size: 15px; margin-top: 10px;
    transition: background 0.3s;
}
.btn-retour:hover { background: var(--or-clair); }

@media (max-width: 480px) {
    .card { padding: 35px 20px; }
    .card h2 { font-size: 24px; }
}
</style>
</head>
<body>

<div class="logo-circle">🏠</div>

<div class="card">
    <h2>Récupération</h2>
    <p class="subtitle">Réinitialisez le mot de passe de votre compte.</p>

    <?php if ($message): ?>
        <div class="msg <?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($etape === 1): ?>
        <form method="POST">
            <div class="input-group">
                <label>Adresse e-mail</label>
                <input type="email" name="email" placeholder="Ex: nom@exemple.com" required>
            </div>
            <div class="input-group">
                <label>Numéro de téléphone</label>
                <input type="text" name="telephone" placeholder="Ex: +22600000000" required>
            </div>
            <button type="submit" name="verifier">Vérifier mon identité</button>
        </form>

    <?php elseif ($etape === 2): ?>
        <form method="POST">
            <div class="input-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe"
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$"
                       title="6 caractères min, une majuscule, une minuscule, un chiffre."
                       placeholder="6 caractères min" required>
                <label class="show-pw">
                    <input type="checkbox" id="togglePw"> Afficher le mot de passe
                </label>
            </div>
            <button type="submit" name="modifier_mdp">Mettre à jour le mot de passe</button>
        </form>

    <?php elseif ($etape === 3): ?>
        <a href="connexion.php" class="btn-retour">✅ Retourner à la connexion</a>
    <?php endif; ?>

    <?php if ($etape !== 3): ?>
        <div class="link">Vous vous en rappelez ? <a href="connexion.php">Se connecter</a></div>
    <?php endif; ?>
</div>

<script>
const cb = document.getElementById('togglePw');
if (cb) {
    cb.addEventListener('change', function() {
        document.getElementById('mot_de_passe').type = this.checked ? 'text' : 'password';
    });
}
</script>
</body>
</html>