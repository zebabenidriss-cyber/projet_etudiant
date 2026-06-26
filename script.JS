/**
 * script.js — Gestion Immobilière (Bailleur)
 * Fonctions : confirmation suppression, aperçu photo,
 *             highlight ligne active, auto-dismiss messages,
 *             validation formulaire côté client
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── 1. AUTO-DISMISS des messages feedback ──────────────────
  const message = document.querySelector('.message');
  if (message) {
    setTimeout(() => {
      message.style.transition = 'opacity .5s';
      message.style.opacity = '0';
      setTimeout(() => message.remove(), 500);
    }, 4000);
  }

  // ── 2. CONFIRMATION SUPPRESSION stylisée ──────────────────
  document.querySelectorAll('.action-delete').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const href = link.getAttribute('href');
      showConfirm('Supprimer cette propriété ?', 'Cette action est irréversible.', () => {
        window.location.href = href;
      });
    });
  });

  // ── 3. APERÇU DE LA PHOTO avant upload ────────────────────
  const photoInput = document.querySelector('input[name="photo"]');
  if (photoInput) {
    const preview = document.createElement('img');
    preview.style.cssText = 'display:none;margin-top:10px;max-width:180px;max-height:130px;border-radius:8px;border:2px solid var(--border);object-fit:cover;';
    photoInput.parentNode.appendChild(preview);

    photoInput.addEventListener('change', () => {
      const file = photoInput.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    });
  }

  // ── 4. NOM DU FICHIER PDF sélectionné ─────────────────────
  const pdfInput = document.querySelector('input[name="titre_foncier"]');
  if (pdfInput) {
    const label = document.createElement('span');
    label.style.cssText = 'display:block;margin-top:6px;font-size:.82rem;color:var(--muted);';
    label.textContent = 'Aucun fichier sélectionné';
    pdfInput.parentNode.appendChild(label);

    pdfInput.addEventListener('change', () => {
      label.textContent = pdfInput.files[0]
        ? '✔ ' + pdfInput.files[0].name
        : 'Aucun fichier sélectionné';
    });
  }

  // ── 5. VALIDATION FORMULAIRE côté client ──────────────────
  const formAjout = document.querySelector('form[enctype]');
  if (formAjout) {
    formAjout.addEventListener('submit', e => {
      const prix = formAjout.querySelector('input[name="prix"]');
      if (prix && parseFloat(prix.value) <= 0) {
        e.preventDefault();
        showToast('Le prix doit être supérieur à 0.', 'error');
        prix.focus();
        return;
      }
      const titre = formAjout.querySelector('input[name="titre"]');
      if (titre && titre.value.trim().length < 3) {
        e.preventDefault();
        showToast('Le titre doit contenir au moins 3 caractères.', 'error');
        titre.focus();
        return;
      }
    });
  }

  // ── 6. HIGHLIGHT ligne du tableau survolée ─────────────────
  // (géré en CSS via tbody tr:hover — rien à faire en JS)

  // ── 7. LIGHTBOX image du tableau ──────────────────────────
  document.querySelectorAll('table td img').forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', () => openLightbox(img.src, img.alt));
  });

});

// ════════════════════════════════════════════
// UTILITAIRES
// ════════════════════════════════════════════

/**
 * Boîte de confirmation modale personnalisée
 */
function showConfirm(title, subtitle, onConfirm) {
  const overlay = document.createElement('div');
  overlay.style.cssText = `
    position:fixed;inset:0;background:rgba(15,41,66,.55);
    display:flex;align-items:center;justify-content:center;
    z-index:9999;animation:fadeIn .2s ease;
  `;

  overlay.innerHTML = `
    <div style="background:#fff;border-radius:12px;padding:32px 36px;
                max-width:380px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.2);
                text-align:center;font-family:'Inter',Arial,sans-serif;">
      <div style="font-size:2.2rem;margin-bottom:10px;">🗑️</div>
      <h3 style="font-family:'Playfair Display',serif;color:#0f2942;
                 font-size:1.15rem;margin-bottom:8px;">${title}</h3>
      <p style="color:#6b7d93;font-size:.9rem;margin-bottom:24px;">${subtitle}</p>
      <div style="display:flex;gap:12px;justify-content:center;">
        <button id="confirmNo"
          style="padding:9px 22px;border-radius:7px;border:1px solid #dde3ec;
                 background:#f0f4f8;color:#1e2b3a;font-weight:600;cursor:pointer;font-size:.92rem;">
          Annuler
        </button>
        <button id="confirmYes"
          style="padding:9px 22px;border-radius:7px;border:none;
                 background:#b91c1c;color:#fff;font-weight:600;cursor:pointer;font-size:.92rem;">
          Supprimer
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);

  overlay.querySelector('#confirmNo').addEventListener('click', () => overlay.remove());
  overlay.querySelector('#confirmYes').addEventListener('click', () => {
    overlay.remove();
    onConfirm();
  });
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
}

/**
 * Toast de notification (succès / erreur)
 */
function showToast(text, type = 'success') {
  const toast = document.createElement('div');
  const bg = type === 'error' ? '#b91c1c' : '#1a7a4a';
  toast.style.cssText = `
    position:fixed;bottom:28px;right:28px;z-index:9999;
    background:${bg};color:#fff;padding:12px 20px;
    border-radius:8px;font-size:.9rem;font-weight:600;
    box-shadow:0 4px 20px rgba(0,0,0,.2);
    animation:slideUp .3s ease;max-width:320px;
  `;
  toast.textContent = text;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .4s';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 400);
  }, 3500);
}

/**
 * Lightbox simple pour les photos du tableau
 */
function openLightbox(src, alt) {
  const overlay = document.createElement('div');
  overlay.style.cssText = `
    position:fixed;inset:0;background:rgba(0,0,0,.80);
    display:flex;align-items:center;justify-content:center;
    z-index:9999;cursor:zoom-out;animation:fadeIn .2s ease;
  `;
  const img = document.createElement('img');
  img.src = src;
  img.alt = alt || '';
  img.style.cssText = `
    max-width:90vw;max-height:85vh;border-radius:10px;
    box-shadow:0 8px 40px rgba(0,0,0,.4);
  `;
  overlay.appendChild(img);
  document.body.appendChild(overlay);
  overlay.addEventListener('click', () => overlay.remove());
  document.addEventListener('keydown', function esc(e) {
    if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', esc); }
  });
}

/* Keyframes injectés dynamiquement */
const style = document.createElement('style');
style.textContent = `
  @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
  @keyframes slideUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
`;
document.head.appendChild(style);