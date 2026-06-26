<?php
// footer.php — Pied de page commun à toutes les pages
?>

<style>
/* ===== FOOTER ===== */
.site-footer {
    background: #0A0A0A;
    color: #888;
    font-family: 'DM Sans', sans-serif;
    margin-top: 0;
}

/* ── BANDE DORÉE HAUT ── */
.footer-accent-bar {
    height: 3px;
    background: linear-gradient(90deg, transparent, #C9A84C 30%, #E8C96A 50%, #C9A84C 70%, transparent);
}

/* ── SECTION PRINCIPALE ── */
.footer-top {
    display: grid;
    grid-template-columns: 2.2fr 1fr 1fr 1.5fr;
    gap: 56px;
    padding: 64px 6% 56px;
    border-bottom: 1px solid #161616;
}

/* ── BRAND ── */
.footer-brand {}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.footer-logo-circle {
    width: 42px; height: 42px;
    border-radius: 50%;
    border: 2px solid #C9A84C;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.footer-logo-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #FAFAF8;
    line-height: 1.2;
}

.footer-logo-name span { color: #C9A84C; }

.footer-logo-sub {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #555;
    margin-top: 2px;
}

.footer-desc {
    font-size: 0.855rem;
    line-height: 1.8;
    color: #555;
    max-width: 300px;
    margin-bottom: 28px;
}

/* Badges confiance */
.footer-trust {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.trust-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #1e1e1e;
    border-radius: 20px;
    font-size: 0.72rem;
    color: #555;
    background: #111;
}

.trust-badge span { font-size: 0.9rem; }

/* ── COLONNES ── */
.footer-col h4 {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #FAFAF8;
    margin-bottom: 22px;
    position: relative;
    padding-bottom: 12px;
}

.footer-col h4::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0;
    width: 24px; height: 2px;
    background: #C9A84C;
    border-radius: 2px;
}

.footer-col ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.footer-col ul li {
    font-size: 0.855rem;
    color: #555;
    line-height: 1.5;
    transition: color 0.2s;
}

.footer-col ul li a {
    color: #555;
    text-decoration: none;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.footer-col ul li a:hover { color: #C9A84C; }

.footer-col ul li a::before {
    content: '›';
    color: #C9A84C;
    font-size: 1rem;
    line-height: 1;
}

/* ── CONTACT ── */
.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.855rem;
    color: #555;
    line-height: 1.5;
}

.contact-icon {
    width: 28px; height: 28px;
    background: #161616;
    border: 1px solid #1e1e1e;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.contact-list {
    display: flex;
    flex-direction: column;
    gap: 12px !important;
}

/* ── BANDE MILIEU : Horaires ── */
.footer-middle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40px;
    padding: 20px 6%;
    background: #0D0D0D;
    border-bottom: 1px solid #161616;
    flex-wrap: wrap;
}

.footer-hours-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #444;
}

.footer-hours-item strong { color: #777; }

.footer-hours-sep {
    width: 1px; height: 16px;
    background: #1e1e1e;
}

/* ── BOTTOM ── */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 6%;
    flex-wrap: wrap;
    gap: 12px;
}

.footer-bottom-left {
    font-size: 0.78rem;
    color: #333;
}

.footer-bottom-left strong { color: #C9A84C; }

.footer-bottom-right {
    display: flex;
    gap: 20px;
    font-size: 0.78rem;
}

.footer-bottom-right a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-bottom-right a:hover { color: #C9A84C; }

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
    .footer-top {
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    .footer-brand { grid-column: 1 / -1; }
    .footer-desc { max-width: 100%; }
}

@media (max-width: 600px) {
    .footer-top {
        grid-template-columns: 1fr;
        gap: 32px;
        padding: 40px 5% 32px;
    }
    .footer-brand { grid-column: auto; }
    .footer-middle { gap: 16px; padding: 16px 5%; }
    .footer-hours-sep { display: none; }
    .footer-bottom {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 16px 5%;
    }
    .footer-bottom-right { gap: 16px; }
}
</style>

<footer class="site-footer">

    <!-- Bande dorée -->
    <div class="footer-accent-bar"></div>

    <!-- Section principale -->
    <div class="footer-top">

        <!-- Brand -->
        <div class="footer-brand">
            <div class="footer-logo">
                <div class="footer-logo-circle">🏠</div>
                <div>
                    <div class="footer-logo-name">Habitat-<span>Horizon</span></div>
                    <div class="footer-logo-sub">Agence Immobilière</div>
                </div>
            </div>
            <p class="footer-desc">
                Votre partenaire de confiance pour la vente et la location de biens immobiliers au Burkina Faso.
                Terrains, villas, appartements, commerces — trouvez votre bien idéal en toute sérénité.
            </p>
            <div class="footer-trust">
                <div class="trust-badge"><span>✅</span> Biens vérifiés</div>
                <div class="trust-badge"><span>🔒</span> Transactions sécurisées</div>
                <div class="trust-badge"><span>🤝</span> Agents certifiés</div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="index.php#catalogue">Propriétés</a></li>
                <li><a href="connexion.php">Se connecter</a></li>
                <li><a href="inscription.php">S'inscrire</a></li>
            </ul>
        </div>

        <!-- Types de biens -->
        <div class="footer-col">
            <h4>Types de biens</h4>
            <ul>
                <li><a href="index.php?type=terrain">Terrains</a></li>
                <li><a href="index.php?type=appartement">Appartements</a></li>
                <li><a href="index.php?type=villa">Villas</a></li>
                <li><a href="index.php?type=batiment">Bâtiments</a></li>
                <li><a href="index.php?type=commerce">Locaux commerciaux</a></li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <h4>Contact</h4>
            <ul class="contact-list">
                <li>
                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <span>Ouagadougou,<br>Burkina Faso</span>
                    </div>
                </li>
                <li>
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <span>+226 75 90 97 11</span>
                    </div>
                </li>
                <li>
                    <div class="contact-item">
                        <div class="contact-icon">✉️</div>
                        <span>habitat.horizon@faso.bf</span>
                    </div>
                </li>
            </ul>
        </div>

    </div>

    <!-- Bande horaires -->
    <div class="footer-middle">
        <div class="footer-hours-item">
            🕗 <strong>Lun – Ven :</strong> 08h00 – 18h00
        </div>
        <div class="footer-hours-sep"></div>
        <div class="footer-hours-item">
            🕗 <strong>Samedi :</strong> 09h00 – 14h00
        </div>
        <div class="footer-hours-sep"></div>
        <div class="footer-hours-item">
            🔴 <strong>Dimanche :</strong> Fermé
        </div>
    </div>

    <!-- Barre bas -->
    <div class="footer-bottom">
        <div class="footer-bottom-left">
            &copy; <?= date('Y') ?> <strong>Habitat-Horizon</strong> — Tous droits réservés.
        </div>
        <div class="footer-bottom-right">
            <a href="#">Confidentialité</a>
            <a href="#">Conditions d'utilisation</a>
            <a href="#">Mentions légales</a>
        </div>
    </div>

</footer>

</body>
</html>