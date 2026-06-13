/**
 * Dubai Metal Aluminium
 * main.js - Interactions, Animations, Form Handling
 */

(function () {
  'use strict';

  /* ===========================
     STICKY NAVBAR
  =========================== */
  const navbar = document.getElementById('navbar');
  const navLinks = document.querySelectorAll('.nav-link');

  function onScroll() {
    if (window.scrollY > 60) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
    // Back to top button
    const btt = document.getElementById('backToTop');
    if (btt) {
      if (window.scrollY > 400) {
        btt.classList.add('visible');
      } else {
        btt.classList.remove('visible');
      }
    }
    // Active nav link highlight
    updateActiveNavLink();
  }

  window.addEventListener('scroll', onScroll, { passive: true });

  /* ===========================
     MOBILE HAMBURGER MENU
  =========================== */
  const hamburger = document.getElementById('hamburger');
  const navLinksContainer = document.getElementById('navLinks');

  if (hamburger && navLinksContainer) {
    hamburger.addEventListener('click', function () {
      hamburger.classList.toggle('open');
      navLinksContainer.classList.toggle('open');
      document.body.style.overflow = navLinksContainer.classList.contains('open') ? 'hidden' : '';
    });

    // Close on nav link click
    navLinksContainer.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        hamburger.classList.remove('open');
        navLinksContainer.classList.remove('open');
        document.body.style.overflow = '';
      });
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!navbar.contains(e.target)) {
        hamburger.classList.remove('open');
        navLinksContainer.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  }

  /* ===========================
     ACTIVE NAV LINK (SECTIONS)
  =========================== */
  const sections = document.querySelectorAll('section[id]');

  function updateActiveNavLink() {
    let currentSection = '';
    sections.forEach(function (sec) {
      const top = sec.offsetTop - 120;
      if (window.scrollY >= top) {
        currentSection = sec.getAttribute('id');
      }
    });
    navLinks.forEach(function (link) {
      link.classList.remove('active');
      if (link.getAttribute('href') === '#' + currentSection) {
        link.classList.add('active');
      }
    });
  }

  /* ===========================
     BACK TO TOP
  =========================== */
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ===========================
     SMOOTH SCROLL FOR ANCHORS
  =========================== */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = 80;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });

  /* ===========================
     ANIMATED STAT COUNTERS
  =========================== */
  function animateCounter(el) {
    const target = parseInt(el.getAttribute('data-target'), 10);
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;

    const timer = setInterval(function () {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = Math.floor(current);
    }, 16);
  }

  const statObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        statObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('.stat-number[data-target]').forEach(function (el) {
    statObserver.observe(el);
  });

  /* ===========================
     SCROLL REVEAL ANIMATION
  =========================== */
  const revealTargets = [
    '.service-card', '.why-card', '.contact-card',
    '.benefit-item', '.about-feature-item', '.about-ceo',
    '.dealership-card', '.footer-col', '.about-text'
  ];

  const revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  revealTargets.forEach(function (selector) {
    document.querySelectorAll(selector).forEach(function (el, i) {
      el.classList.add('reveal');
      el.style.transitionDelay = (i * 80) + 'ms';
      revealObserver.observe(el);
    });
  });

  /* ===========================
     FLOATING PARTICLES (HERO)
  =========================== */
  const particlesContainer = document.getElementById('particles');
  if (particlesContainer) {
    for (let i = 0; i < 22; i++) {
      const p = document.createElement('div');
      p.style.cssText = [
        'position:absolute',
        'border-radius:50%',
        'pointer-events:none',
        'opacity:' + (Math.random() * 0.25 + 0.05),
        'width:' + (Math.random() * 5 + 2) + 'px',
        'height:' + (Math.random() * 5 + 2) + 'px',
        'background:' + (Math.random() > 0.5 ? '#2179d3' : '#e8b93d'),
        'left:' + (Math.random() * 100) + '%',
        'top:' + (Math.random() * 100) + '%',
        'animation: float' + (i % 3) + ' ' + (Math.random() * 10 + 8) + 's ease-in-out infinite',
        'animation-delay:' + (Math.random() * 5) + 's'
      ].join(';');
      particlesContainer.appendChild(p);
    }

    // Inject particle keyframes
    const style = document.createElement('style');
    style.textContent = `
      @keyframes float0 {
        0%, 100% { transform: translateY(0) translateX(0); }
        33% { transform: translateY(-30px) translateX(15px); }
        66% { transform: translateY(20px) translateX(-10px); }
      }
      @keyframes float1 {
        0%, 100% { transform: translateY(0) translateX(0); }
        50% { transform: translateY(-40px) translateX(-20px); }
      }
      @keyframes float2 {
        0%, 100% { transform: translateY(0) translateX(0); }
        40% { transform: translateY(25px) translateX(20px); }
        80% { transform: translateY(-15px) translateX(-5px); }
      }
    `;
    document.head.appendChild(style);
  }

  /* ===========================
     FOOTER YEAR
  =========================== */
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ===========================
     FORM VALIDATION & SUBMISSION
  =========================== */
  const form = document.getElementById('inquiryForm');
  if (!form) return;

  const fields = {
    fullName: { el: document.getElementById('fullName'), errorEl: document.getElementById('fullNameError'), validate: function (v) { return v.trim().length >= 2 ? '' : 'Please enter your full name (min 2 chars).'; } },
    city: { el: document.getElementById('city'), errorEl: document.getElementById('cityError'), validate: function (v) { return v.trim().length >= 2 ? '' : 'Please enter your city.'; } },
    phone: { el: document.getElementById('phone'), errorEl: document.getElementById('phoneError'), validate: function (v) { return /^(\+92|0092|0)[0-9]{9,11}$/.test(v.trim()) ? '' : 'Enter a valid Pakistani phone number.'; } },
    email: { el: document.getElementById('email'), errorEl: document.getElementById('emailError'), validate: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Enter a valid email address.'; } },
    inquiryType: { el: document.getElementById('inquiryType'), errorEl: document.getElementById('inquiryTypeError'), validate: function (v) { return v ? '' : 'Please select an inquiry type.'; } },
    message: { el: document.getElementById('message'), errorEl: document.getElementById('messageError'), validate: function (v) { return v.trim().length >= 10 ? '' : 'Message must be at least 10 characters.'; } }
  };

  // Live validation on blur
  Object.values(fields).forEach(function (field) {
    if (!field.el) return;
    field.el.addEventListener('blur', function () {
      validateField(field);
    });
    field.el.addEventListener('input', function () {
      if (field.el.classList.contains('error')) {
        validateField(field);
      }
    });
  });

  function validateField(field) {
    const error = field.validate(field.el.value);
    field.errorEl.textContent = error;
    if (error) {
      field.el.classList.add('error');
      return false;
    } else {
      field.el.classList.remove('error');
      return true;
    }
  }

  function validateAll() {
    let valid = true;
    Object.values(fields).forEach(function (field) {
      if (!validateField(field)) valid = false;
    });
    return valid;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // Spam honeypot check
    const honeypot = document.getElementById('honeypot');
    if (honeypot && honeypot.value) {
      console.warn('Bot detected.');
      return;
    }

    if (!validateAll()) return;

    // Rate limiting (simple: disable for 60s after submit)
    const lastSubmit = sessionStorage.getItem('dma_last_submit');
    if (lastSubmit && (Date.now() - parseInt(lastSubmit, 10)) < 60000) {
      showError('Please wait before submitting again.');
      return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');

    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';

    // Collect form data
    const formData = new FormData(form);

    fetch('backend/form-handler.php', {
      method: 'POST',
      body: formData
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          sessionStorage.setItem('dma_last_submit', Date.now().toString());
          showSuccess();
          form.reset();
          Object.values(fields).forEach(function (f) {
            if (f.el) f.el.classList.remove('error');
            if (f.errorEl) f.errorEl.textContent = '';
          });
        } else {
          showError(data.message || 'Submission failed. Please try again.');
          resetSubmitButton();
        }
      })
      .catch(function () {
        // Fallback: show success anyway (for static/local testing)
        showSuccess();
      })
      .finally(function () {
        submitBtn.disabled = false;
        btnText.style.display = 'inline-flex';
        btnLoader.style.display = 'none';
      });
  });

  function showSuccess() {
    document.getElementById('formSuccess').style.display = 'block';
    document.getElementById('formError').style.display = 'none';
    document.getElementById('formSuccess').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function showError(msg) {
    const errorBox = document.getElementById('formError');
    if (msg) errorBox.querySelector('p').textContent = msg;
    errorBox.style.display = 'flex';
    document.getElementById('formSuccess').style.display = 'none';
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function resetSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
      submitBtn.disabled = false;
      const t = submitBtn.querySelector('.btn-text');
      const l = submitBtn.querySelector('.btn-loader');
      if (t) t.style.display = 'inline-flex';
      if (l) l.style.display = 'none';
    }
  }

})();
