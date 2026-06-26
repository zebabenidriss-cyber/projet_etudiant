<?php
$page_actuelle = basename($_SERVER['PHP_SELF']);

// On vérifie l'état de la connexion (La session est déjà lancée par init.php)
$est_connecte = isset($_SESSION['id_utilisateur']);

// Liens conditionnels
$lien_demandes = $est_connecte ? 'Demande.php' : 'connexion.php';
$lien_favoris  = $est_connecte ? 'Favoris.php' : 'connexion.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Habitat Horizon</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --noir:  #0E0E0E;
      --or:    #C9A84C;
      --blanc: #FAFAF8;
      --gris:  #888;
      --nav-h: 80px; 
    }

    body {
      font-family: 'DM Sans', sans-serif;
      padding-top: var(--nav-h);
      background-color: var(--blanc);
    }

    /* ===== NAVBAR ===== */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      height: var(--nav-h);
      background: #fff;
      border-bottom: 1px solid #e8e8e8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 40px;
      z-index: 1000;
    }

    /* ===== LOGO ===== */
    .nav-logo {
      display: flex;
      align-items: center;
      gap: 14px; 
      text-decoration: none;
    }

    .global-logo {
      width: 50px;  
      height: 50px;
      background: var(--or);
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 4px 12px rgba(201, 168, 76, 0.25); 
      overflow: hidden;
      border: 2px solid var(--or);
      flex-shrink: 0; 
    }

    .global-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover; 
    }

    .logo-texte {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .logo-nom {
      font-family: 'Playfair Display', serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--noir);
      letter-spacing: 0.01em;
    }

    .logo-nom span { color: var(--or); }

    .logo-slogan {
      font-size: 0.65rem;
      color: #aaa;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
    }

    /* ===== LIENS CENTRE ===== */
    .nav-links {
      display: flex;
      align-items: center;
      gap: 36px;
      list-style: none;
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    .nav-links a {
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      color: #555;
      letter-spacing: 0.02em;
      padding-bottom: 3px;
      border-bottom: 2px solid transparent;
      transition: all 0.2s;
    }

    .nav-links a:hover, .nav-links a.active {
      color: var(--noir);
      border-bottom-color: var(--or);
    }

    .nav-links a.active { font-weight: 600; }

    /* ===== CONNEXION ===== */
    .nav-connexion {
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      color: #fff;
      background: var(--noir);
      padding: 10px 24px;
      border-radius: 4px;
      letter-spacing: 0.03em;
      transition: background 0.2s;
      white-space: nowrap;
    }

    .nav-connexion:hover { background: var(--or); }

    /* ===== HAMBURGER ===== */
    .hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
    }

    .hamburger span {
      display: block;
      width: 22px;
      height: 2px;
      background: var(--noir);
      border-radius: 2px;
      transition: all 0.3s;
    }

    /* ===== MENU MOBILE ===== */
    .mobile-menu {
      display: none;
      position: fixed;
      top: var(--nav-h);
      left: 0; right: 0;
      background: #fff;
      border-top: 1px solid #eee;
      flex-direction: column;
      padding: 16px 40px 24px;
      z-index: 999;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .mobile-menu.open { display: flex; }

    .mobile-menu a {
      text-decoration: none;
      color: #555;
      font-size: 0.9rem;
      font-weight: 500;
      padding: 13px 0;
      border-bottom: 1px solid #f0f0f0;
      transition: color 0.2s;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 860px) {
      .nav-links, .nav-connexion { display: none; }
      .hamburger { display: flex; }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="global-logo">
      <img src="" alt="Logo" onerror="this.style.display='none'">🏠
    </div>
    <div class="logo-texte">
      <span class="logo-nom">Habitat<span>-Horizon</span></span>
      <span class="logo-slogan">Agence immobilière</span>
    </div>
  </a>

  <ul class="nav-links">
    <li><a href="index.php" class="<?= $page_actuelle === 'index.php' ? 'active' : '' ?>">Accueil</a></li>
    <li><a href="<?= $lien_demandes ?>" class="<?= $page_actuelle === 'Demande.php' ? 'active' : '' ?>">Demandes</a></li>
    <li><a href="<?= $lien_favoris ?>" class="<?= $page_actuelle === 'Favoris.php' ? 'active' : '' ?>">Favoris</a></li>
  </ul>

  <a href="connexion.php" class="nav-connexion">Connexion</a>

  <button class="hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <a href="index.php">Accueil</a>
  <a href="<?= $lien_demandes ?>">Demandes</a>
  <a href="<?= $lien_favoris ?>">Favoris</a>
  <a href="connexion.php" class="mobile-connexion">Connexion</a>
</div>

<script>
  const btn  = document.getElementById('hamburger');
  const menu = document.getElementById('mobileMenu');
  btn.addEventListener('click', () => {
    btn.classList.toggle('open');
    menu.classList.toggle('open');
  });
</script>