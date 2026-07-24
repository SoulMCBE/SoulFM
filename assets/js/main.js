/**
 * SoulFM - Main JavaScript
 */
(function () {
  'use strict';

  /* ===================== MOBILE NAV ===================== */
  const hamburger = document.getElementById('hamburger');
  const mainNav   = document.getElementById('main-nav');

  if (hamburger && mainNav) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mainNav.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', mainNav.classList.contains('open'));
    });

    // Close on outside click
    document.addEventListener('click', e => {
      if (!hamburger.contains(e.target) && !mainNav.contains(e.target)) {
        hamburger.classList.remove('open');
        mainNav.classList.remove('open');
      }
    });

    // Close on nav link click
    mainNav.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mainNav.classList.remove('open');
      });
    });
  }

  /* ===================== ACTIVE NAV LINK ===================== */
  const currentPath = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.main-nav a').forEach(a => {
    const href = a.getAttribute('href').split('/').pop() || 'index.php';
    if (href === currentPath) a.classList.add('active');
  });

  /* ===================== SCHEDULE TABS ===================== */
  const tabBtns     = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  if (tabBtns.length) {
    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        const content = document.getElementById('tab-' + target);
        if (content) content.classList.add('active');
      });
    });
  }

  /* ===================== REQUEST FORM AJAX ===================== */
  const requestForm = document.getElementById('request-form');

  if (requestForm) {
    requestForm.addEventListener('submit', async e => {
      e.preventDefault();
      clearErrors();

      const btn = requestForm.querySelector('[type="submit"]');
      const origText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Versturen...';

      const formData = new FormData(requestForm);

      try {
        const base = document.querySelector('meta[name="base-url"]')?.content || '';
        const res  = await fetch(base + '/api/request.php', {
          method: 'POST',
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          requestForm.reset();
          showToast('Jouw verzoekje is verstuurd! 🎵', 'success');
          showFormSuccess('Je verzoekje is ontvangen en wordt zo snel mogelijk gedraaid.');
        } else {
          if (data.errors) {
            Object.entries(data.errors).forEach(([field, msg]) => showFieldError(field, msg));
          }
          showToast(data.message || 'Er is iets misgegaan.', 'error');
        }
      } catch (err) {
        showToast('Verbindingsfout. Probeer het opnieuw.', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = origText;
      }
    });
  }

  function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => el.remove());
    const successMsg = document.getElementById('request-success');
    if (successMsg) successMsg.remove();
  }

  function showFieldError(field, message) {
    const input = requestForm.querySelector(`[name="${field}"]`);
    if (!input) return;
    const err = document.createElement('span');
    err.className = 'form-error';
    err.textContent = message;
    input.parentNode.insertBefore(err, input.nextSibling);
    input.classList.add('error');
  }

  function showFormSuccess(message) {
    const div = document.createElement('div');
    div.id = 'request-success';
    div.className = 'alert alert-success';
    div.innerHTML = `<svg viewBox="0 0 20 20" style="width:18px;height:18px;fill:currentColor;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg> ${message}`;
    requestForm.parentNode.insertBefore(div, requestForm);
    requestForm.style.display = 'none';
  }

  /* ===================== CONTACT FORM ===================== */
  const contactForm = document.getElementById('contact-form');

  if (contactForm) {
    contactForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = contactForm.querySelector('[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Versturen...';

      // Simulate form send (replace with actual endpoint)
      await new Promise(r => setTimeout(r, 800));

      contactForm.reset();
      showToast('Bericht verstuurd! We nemen snel contact op.', 'success');

      const div = document.createElement('div');
      div.className = 'alert alert-success';
      div.textContent = 'Je bericht is verzonden. We nemen zo spoedig mogelijk contact met je op!';
      contactForm.parentNode.insertBefore(div, contactForm);
      contactForm.style.display = 'none';

      btn.disabled = false;
      btn.textContent = 'Bericht versturen';
    });
  }

  /* ===================== TOAST NOTIFICATIONS ===================== */
  let toastContainer = document.querySelector('.toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
  }

  window.showToast = function (message, type = 'success', duration = 4000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'slideOut 0.3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  };

  /* ===================== SMOOTH SCROLL ===================== */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ===================== HEADER SCROLL EFFECT ===================== */
  const header = document.querySelector('.site-header');
  if (header) {
    const onScroll = () => {
      header.style.boxShadow = window.scrollY > 50
        ? '0 4px 30px rgba(0,0,0,0.4)'
        : 'none';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
  }

})();
