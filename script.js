/* ══════════════════════════════════════════════
   JASMINE DOM PIED — script.js
   ══════════════════════════════════════════════

   Ce fichier gère :
   1. Navigation scroll (active link highlight + shrink navbar)
   2. Menu burger mobile (toggle)
   3. Smooth scroll pour les liens internes
   4. Animations fade-in via IntersectionObserver
   5. Compteurs animés pour la section stats
   6. Validation du formulaire de contact (HTML + PHP fallback)
   ══════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── 1. NAVBAR SCROLL SHRINK ── */
  const navbar = document.getElementById('navbar');
  const onScroll = () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
    highlightNav();
  };
  window.addEventListener('scroll', onScroll, { passive: true });


  /* ── 2. BURGER MENU MOBILE ── */
  const burger   = document.getElementById('burgerBtn');
  const navLinks = document.getElementById('navLinks');

  burger.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    burger.classList.toggle('open', open);
    burger.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });

  // Ferme le menu quand on clique sur un lien
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      burger.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  });

  // Ferme en cliquant hors du menu
  document.addEventListener('click', (e) => {
    if (navLinks.classList.contains('open') &&
        !navLinks.contains(e.target) &&
        !burger.contains(e.target)) {
      navLinks.classList.remove('open');
      burger.classList.remove('open');
      document.body.style.overflow = '';
    }
  });


  /* ── 3. SMOOTH SCROLL (liens href="#...") ── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      const navH = navbar.offsetHeight;
      const top  = target.getBoundingClientRect().top + window.scrollY - navH;
      window.scrollTo({ top, behavior: 'smooth' });
    });
  });


  /* ── 4. ACTIVE NAV HIGHLIGHT ── */
  const sections = document.querySelectorAll('section[id], footer[id]');
  function highlightNav() {
    let current = '';
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - navbar.offsetHeight - 60) {
        current = sec.id;
      }
    });
    navLinks.querySelectorAll('a').forEach(link => {
      link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
    });
  }


  /* ── 5. FADE-IN avec IntersectionObserver ── */
  // Observe chaque élément .fade-in ; quand il entre dans le viewport
  // à 15%, on ajoute la classe .visible qui déclenche la transition CSS.
  const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        // Décalage progressif pour les éléments frères
        const siblings = [...entry.target.parentElement.querySelectorAll('.fade-in')];
        const idx = siblings.indexOf(entry.target);
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, Math.min(idx * 80, 400));
        fadeObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.fade-in').forEach(el => fadeObserver.observe(el));

  // Hero : visible directement
  const heroContent = document.querySelector('#hero .fade-in');
  if (heroContent) {
    setTimeout(() => heroContent.classList.add('visible'), 200);
  }


  /* ── 6. COMPTEURS ANIMÉS (stats) ── */
  // Quand la section #stats est visible, on anime les chiffres de 0 → target.
  const statNumbers = document.querySelectorAll('.stat-number[data-target]');

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const target = parseInt(el.dataset.target, 10);
      const dur    = 1800; // ms
      const step   = 16;   // ~60fps
      const inc    = target / (dur / step);
      let current  = 0;

      const timer = setInterval(() => {
        current += inc;
        if (current >= target) {
          el.textContent = target.toLocaleString('fr-FR');
          clearInterval(timer);
        } else {
          el.textContent = Math.floor(current).toLocaleString('fr-FR');
        }
      }, step);

      counterObserver.unobserve(el);
    });
  }, { threshold: 0.5 });

  statNumbers.forEach(el => counterObserver.observe(el));


  /* ── 7. VALIDATION FORMULAIRE ── */
  const form        = document.getElementById('contactForm');
  const submitBtn   = document.getElementById('submitBtn');
  const btnText     = document.getElementById('btnText');
  const btnLoading  = document.getElementById('btnLoading');
  const formSuccess = document.getElementById('formSuccess');
  const formErrBox  = document.getElementById('formError');

  if (!form) return;

  // Validation inline en temps réel
  const fields = {
    nom:     { el: form.querySelector('#nom'),     err: form.querySelector('#nomError'),     msg: 'Ton prénom est requis.' },
    email:   { el: form.querySelector('#email'),   err: form.querySelector('#emailError'),   msg: 'Une adresse email valide est requise.' },
    message: { el: form.querySelector('#message'), err: form.querySelector('#messageError'), msg: 'Ton message ne peut pas être vide.' },
  };

  Object.values(fields).forEach(({ el, err, msg }) => {
    el.addEventListener('blur', () => validateField(el, err, msg));
    el.addEventListener('input', () => { if (err.textContent) validateField(el, err, msg); });
  });

  function validateField(el, err, msg) {
    const valid = el.type === 'email'
      ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim())
      : el.value.trim().length > 0;

    err.textContent = valid ? '' : msg;
    el.style.borderColor = valid ? '' : 'var(--crimson-l)';
    return valid;
  }

  // Soumission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Valider tous les champs
    let ok = true;
    Object.values(fields).forEach(({ el, err, msg }) => {
      if (!validateField(el, err, msg)) ok = false;
    });

    // Vérifier checkbox
    const cbox = form.querySelector('#conditions');
    const cErr = form.querySelector('#conditionsError');
    if (!cbox.checked) {
      cErr.textContent = 'Tu dois accepter les conditions.';
      ok = false;
    } else {
      cErr.textContent = '';
    }

    if (!ok) return;

    // UI loading
    submitBtn.disabled = true;
    btnText.style.display    = 'none';
    btnLoading.style.display = 'inline';
    formSuccess.style.display = 'none';
    formErrBox.style.display  = 'none';

    try {
      const data = new FormData(form);
      const res  = await fetch('send_contact.php', { method: 'POST', body: data });

      if (res.ok) {
        const json = await res.json().catch(() => ({ success: true }));
        if (json.success !== false) {
          formSuccess.style.display = 'block';
          form.reset();
        } else {
          throw new Error(json.message || 'Erreur serveur');
        }
      } else {
        throw new Error(`HTTP ${res.status}`);
      }
    } catch (err) {
      console.error('Form error:', err);
      formErrBox.style.display = 'block';
    } finally {
      submitBtn.disabled       = false;
      btnText.style.display    = 'inline';
      btnLoading.style.display = 'none';
    }
  });

}); // fin DOMContentLoaded
