/* ==========================================================================
   StudentHub — front-end behaviour
   Sections: helpers · theme · navigation · session-aware menu · dashboard
             sidebar · notice · forms · home slider · events · modal · FAQ
             · profile
   Every block checks that its elements exist, so one file serves all pages.
   ========================================================================== */
(() => {
  'use strict';

  /* ---------- Helpers ---------- */
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  // localStorage can throw (private mode, blocked storage), so wrap it.
  const store = {
    get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } },
    set(key, value) { try { localStorage.setItem(key, value); return true; } catch (e) { return false; } },
    remove(key) { try { localStorage.removeItem(key); } catch (e) { /* ignore */ } },
  };

  const formatDate = (value) => {
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
      ? value
      : date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  };

  const fetchJson = async (url) => {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`Request failed: ${response.status}`);
    return response.json();
  };

  /* ---------- Theme (dark / light) ---------- */
  const themeButton = $('#themeButton');
  const syncThemeButton = () => {
    if (!themeButton) return;
    const light = document.documentElement.dataset.theme === 'light';
    themeButton.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
    themeButton.setAttribute('aria-pressed', String(light));
  };
  syncThemeButton();
  themeButton?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
    document.documentElement.dataset.theme = next;
    store.set('studenthub-theme', next);
    syncThemeButton();
  });

  /* ---------- Navigation: hamburger, Hub dropdown, rail flyout ---------- */
  const hamburger = $('#hamburger');
  const topLinks = $('#topLinks');
  hamburger?.addEventListener('click', () => {
    const open = topLinks.classList.toggle('open');
    hamburger.setAttribute('aria-expanded', String(open));
  });

  const hubButton = $('#hubButton');
  const hubMenu = $('#hubMenu');
  const closeHubMenu = () => {
    if (!hubMenu) return;
    hubMenu.hidden = true;
    hubButton?.setAttribute('aria-expanded', 'false');
  };
  hubButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    const willOpen = hubMenu.hidden;
    closeSideFlyout();
    hubMenu.hidden = !willOpen;
    hubButton.setAttribute('aria-expanded', String(willOpen));
  });

  const sideHubBtn = $('#sideHubBtn');
  const sideFlyout = $('#sideHubMenu');

  // Place the flyout beside its button, kept inside the viewport.
  const positionSideFlyout = () => {
    if (!sideHubBtn || !sideFlyout || !sideFlyout.classList.contains('open')) return;
    const rail = sideFlyout.offsetParent || document.body;
    const railTop = rail.getBoundingClientRect().top;
    const button = sideHubBtn.getBoundingClientRect();
    const height = sideFlyout.offsetHeight;
    let top = button.top - railTop + button.height / 2 - height / 2;
    const maxTop = window.innerHeight - railTop - height - 8;
    top = Math.max(8, Math.min(top, maxTop));
    sideFlyout.style.top = `${top}px`;
  };
  const closeSideFlyout = () => {
    if (!sideFlyout) return;
    sideFlyout.classList.remove('open');
    sideHubBtn?.setAttribute('aria-expanded', 'false');
  };
  sideHubBtn?.addEventListener('click', (event) => {
    event.stopPropagation();
    const willOpen = !sideFlyout.classList.contains('open');
    closeHubMenu();
    sideFlyout.classList.toggle('open', willOpen);
    sideHubBtn.setAttribute('aria-expanded', String(willOpen));
    positionSideFlyout();
  });
  window.addEventListener('resize', positionSideFlyout);

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.hub-menu-wrap')) closeHubMenu();
    if (!event.target.closest('#sideHubMenu') && event.target !== sideHubBtn) closeSideFlyout();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeHubMenu();
    closeSideFlyout();
  });

  /* ---------- Session-aware menu (static pages only) ----------
     Static .html pages cannot know who is signed in, so ask php/me.php.
     If PHP is not available the default menu simply stays as it is. */
  if (document.body.dataset.nav !== 'server') {
    fetch('php/me.php', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then((response) => (response.ok ? response.json() : null))
      .then((me) => {
        if (!me || !me.loggedIn) return;
        const login = $('#navLogin');
        if (login) { login.textContent = 'Logout'; login.setAttribute('href', 'php/logout.php'); login.classList.remove('active'); }
        $('#navRegister')?.remove();
        $$('[data-role-link]').forEach((link) => {
          if (link.dataset.roleLink !== me.role) link.remove();
        });

        const back = $('#backToDashboard');
        if (back) {
          const admin = me.role === 'admin';
          back.href = admin ? 'admin-dashboard.php' : 'student-dashboard.php';
          back.textContent = admin ? '← Back to admin dashboard' : '← Back to dashboard';
          back.hidden = false;
        }
      })
      .catch(() => { /* PHP not running: keep the default menu */ });
  }

  /* ---------- Dashboard sidebar (collapsible) ---------- */
  const dashboardShell = $('#dashboardShell');
  const dashboardToggle = $('#dashboardBrandToggle');
  if (dashboardShell && dashboardToggle) {
    const desktop = window.matchMedia('(min-width: 951px)');
    const apply = (collapsed) => {
      dashboardShell.classList.toggle('is-collapsed', collapsed);
      dashboardToggle.setAttribute('aria-expanded', String(!collapsed));
    };
    apply(desktop.matches && store.get('studenthub-dashboard-collapsed') === '1');
    dashboardToggle.addEventListener('click', () => {
      const collapsed = !dashboardShell.classList.contains('is-collapsed');
      apply(collapsed);
      if (desktop.matches) store.set('studenthub-dashboard-collapsed', collapsed ? '1' : '0');
    });
  }

  /* ---------- Notice banner ---------- */
  $('#closeNotice')?.addEventListener('click', () => $('#noticeBanner')?.remove());

  /* ---------- Forms: validation helpers ---------- */
  const emailOK = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  const nameOK = (v) => /^\p{L}[\p{L} .'-]{1,49}$/u.test(v);
  const mobileOK = (v) => /^[6-9]\d{9}$/.test(v);
  const usernameOK = (v) => /^[A-Za-z0-9_.-]{3,30}$/.test(v);
  const passwordOK = (v) => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/.test(v);

  const getValue = (form, id) => {
    if (id === 'gender') return $('input[name="gender"]:checked', form)?.value || '';
    const el = $('#' + id, form);
    if (!el) return '';
    return el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value.trim();
  };

  const showError = (form, id, message) => {
    const holder = $(`[data-error-for="${id}"]`, form);
    if (holder) holder.textContent = message;
    const el = id === 'gender' ? $('input[name="gender"]', form) : $('#' + id, form);
    el?.closest('.form-group, .check-row')?.classList.toggle('invalid', Boolean(message));
    el?.setAttribute('aria-invalid', message ? 'true' : 'false');
  };

  /* rules: { fieldId: (value, form) => 'error message' | '' } */
  const attachValidation = (form, rules) => {
    const check = (id) => {
      const message = rules[id](getValue(form, id), form) || '';
      showError(form, id, message);
      return message === '';
    };
    Object.keys(rules).forEach((id) => {
      const targets = id === 'gender' ? $$('input[name="gender"]', form) : [$('#' + id, form)];
      targets.forEach((el) => {
        if (!el) return;
        el.addEventListener('blur', () => { if (el.dataset.touched) check(id); });
        ['input', 'change'].forEach((type) => el.addEventListener(type, () => {
          el.dataset.touched = '1';
          check(id);
          // keep "confirm password" in step with the password field
          if (id === 'regPassword' && getValue(form, 'regConfirm')) check('regConfirm');
        }));
      });
    });
    form.addEventListener('submit', (event) => {
      let firstBad = null;
      Object.keys(rules).forEach((id) => {
        if (!check(id) && !firstBad) firstBad = id === 'gender' ? $('input[name="gender"]', form) : $('#' + id, form);
      });
      if (firstBad) { event.preventDefault(); firstBad.focus(); }
    });
  };

  const required = (label) => (v) => (v ? '' : `${label} is required.`);

  /* Password show / hide */
  $$('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = $('#' + button.dataset.passwordToggle);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.textContent = show ? 'Hide' : 'Show';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  /* Registration */
  const registrationForm = $('#registrationForm');
  if (registrationForm) {
    attachValidation(registrationForm, {
      regName: (v) => (nameOK(v) ? '' : 'Enter your full name (letters, spaces, . \' and - only).'),
      regUsername: (v) => (usernameOK(v) ? '' : 'Use 3–30 letters, numbers, dots, underscores or hyphens.'),
      regEmail: (v) => (emailOK(v) ? '' : 'Enter a valid email address.'),
      regMobile: (v) => (mobileOK(v) ? '' : 'Enter a valid 10-digit mobile number.'),
      regCourse: required('Branch'),
      regYear: required('Year'),
      gender: required('Gender'),
      regPassword: (v) => (passwordOK(v) ? '' : 'Use 8–64 characters with upper, lower, number and special character.'),
      regConfirm: (v, form) => (v === getValue(form, 'regPassword') ? '' : 'Passwords do not match.'),
      regTerms: (v) => (v ? '' : 'Please confirm the information is correct.'),
    });

    const password = $('#regPassword');
    const strength = $('#strengthMeter');
    const strengthBar = $('#strengthBar');
    const strengthText = $('#strengthText');
    password?.addEventListener('input', () => {
      const v = password.value;
      const score = [v.length >= 8, /[a-z]/.test(v), /[A-Z]/.test(v), /\d/.test(v), /[^A-Za-z\d]/.test(v)].filter(Boolean).length;
      strengthBar.style.width = `${score * 20}%`;
      strength.dataset.level = String(score);
      strengthText.textContent = v
        ? ['', 'Very weak', 'Weak', 'Medium', 'Strong', 'Very strong'][score]
        : 'Use 8+ characters with uppercase, lowercase, number and special character.';
    });
  }

  /* Login */
  const loginForm = $('#loginForm');
  if (loginForm) {
    attachValidation(loginForm, {
      loginEmail: (v) => (emailOK(v) ? '' : 'Enter a valid email address.'),
      loginPassword: required('Password'),
    });
  }

  /* Contact */
  const contactForm = $('#contactForm');
  if (contactForm) {
    attachValidation(contactForm, {
      contactName: (v) => (nameOK(v) ? '' : 'Enter your full name.'),
      contactEmail: (v) => (emailOK(v) ? '' : 'Enter a valid email address.'),
      contactMessage: (v) => (v.length >= 5 && v.length <= 1000 ? '' : 'Message must be 5 to 1000 characters.'),
    });
  }

  /* Feedback */
  const feedbackForm = $('#feedbackForm');
  if (feedbackForm) {
    attachValidation(feedbackForm, {
      feedbackName: (v) => (nameOK(v) ? '' : 'Enter your full name.'),
      feedbackEmail: (v) => (emailOK(v) ? '' : 'Enter a valid email address.'),
      feedbackType: required('Feedback type'),
      feedbackMessage: (v) => (v.length >= 5 && v.length <= 1000 ? '' : 'Feedback must be 5 to 1000 characters.'),
    });
  }

  /* ---------- Event modal (shared by Home and Events) ---------- */
  const modal = $('#eventModal');
  let modalTrigger = null;

  const openEventModal = (event, trigger) => {
    if (!modal || !event) return;
    modalTrigger = trigger || null;
    modal.dataset.eventId = event.id;
    $('#eventModalImage').src = event.image;
    $('#eventModalImage').alt = `${event.title} poster`;
    $('#eventModalCategory').textContent = event.category;
    $('#eventModalTitle').textContent = event.title;
    $('#eventModalMeta').textContent = `${formatDate(event.date)} / ${event.time} / ${event.venue}`;
    $('#eventModalDescription').textContent = event.description;
    const feedback = $('#eventModalFeedback');
    if (feedback) { feedback.textContent = ''; feedback.className = 'modal-feedback'; }
    modal.hidden = false;
    document.body.classList.add('modal-open');
    $('#modalClose').focus();
  };

  const closeEventModal = () => {
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    modalTrigger?.focus();
  };

  if (modal) {
    $('#modalClose').addEventListener('click', closeEventModal);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeEventModal(); });
    document.addEventListener('keydown', (event) => {
      if (modal.hidden) return;
      if (event.key === 'Escape') closeEventModal();
      if (event.key === 'Tab') { // keep focus inside the dialog
        const focusable = $$('a[href], button:not([disabled])', modal);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    });
  }

  // "Register for event": a real request to the server, not an alert.
  const registerButton = $('#eventRegisterBtn');
  registerButton?.addEventListener('click', async () => {
    const feedback = $('#eventModalFeedback');
    const say = (text, kind) => { feedback.textContent = text; feedback.className = `modal-feedback ${kind}`; };
    registerButton.disabled = true;
    say('Sending your registration…', '');
    try {
      const response = await fetch('php/event-register.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch', Accept: 'application/json' },
        body: JSON.stringify({ event_id: Number(modal.dataset.eventId) }),
      });
      if (response.status === 401) {
        say('Please log in with a student account to register. Redirecting…', 'err');
        setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        return;
      }
      const result = await response.json().catch(() => ({}));
      say(result.message || 'Something went wrong. Please try again.', response.ok && result.success ? 'ok' : 'err');
    } catch (error) {
      say('The server could not be reached. Make sure Apache and MySQL are running.', 'err');
    } finally {
      registerButton.disabled = false;
    }
  });

  /* ---------- Home: featured events slider ---------- */
  const homeSlider = $('#homeSlider');
  if (homeSlider) {
    const dots = $('#sliderDots');
    const widths = [[701, 1], [951, 2]]; // below 701px: 1 card, below 951px: 2 cards, else 3
    const perView = () => (window.innerWidth < widths[0][0] ? 1 : window.innerWidth < widths[1][0] ? 2 : 3);

    fetchJson('data/events.json').then((events) => {
      const featured = events.slice(0, 6);
      let start = 0;

      const render = () => {
        const count = perView();
        const visible = Array.from({ length: count }, (_, i) => featured[(start + i) % featured.length]);
        homeSlider.innerHTML = visible.map((e) => `
          <article class="slider-slide">
            <img src="${esc(e.image)}" alt="${esc(e.title)} poster">
            <span class="course-code">${esc(e.category)}</span>
            <h3>${esc(e.title)}</h3>
            <p class="event-meta">${esc(formatDate(e.date))} / ${esc(e.venue)}</p>
            <button class="btn btn-small event-detail" data-event-id="${esc(e.id)}" type="button">View Details</button>
          </article>`).join('');
        dots.innerHTML = featured.map((_, i) =>
          `<button class="slider-dot ${i === start ? 'active' : ''}" data-slide="${i}" type="button" aria-label="Show featured event ${i + 1}"></button>`).join('');
      };

      homeSlider.addEventListener('click', (event) => {
        const button = event.target.closest('.event-detail');
        if (button) openEventModal(events.find((e) => e.id === Number(button.dataset.eventId)), button);
      });
      dots.addEventListener('click', (event) => {
        const dot = event.target.closest('.slider-dot');
        if (dot) { start = Number(dot.dataset.slide); render(); }
      });
      $('#sliderPrev')?.addEventListener('click', () => { start = (start - 1 + featured.length) % featured.length; render(); });
      $('#sliderNext')?.addEventListener('click', () => { start = (start + 1) % featured.length; render(); });
      window.addEventListener('resize', render);
      render();
    }).catch(() => {
      homeSlider.innerHTML = '<div class="state-message">Events are currently unavailable.</div>';
    });
  }

  /* ---------- Events page: search, filter, sort, pagination ---------- */
  const eventGrid = $('#eventGrid');
  if (eventGrid) {
    const perPage = 6;
    let allEvents = [];
    let filtered = [];
    let page = 1;

    const render = () => {
      const items = filtered.slice((page - 1) * perPage, page * perPage);
      eventGrid.innerHTML = items.map((e) => `
        <article class="event-card">
          <span class="event-number">${String(e.id).padStart(2, '0')} / ${esc(e.category.toUpperCase())}</span>
          <div class="event-image"><img src="${esc(e.image)}" alt="${esc(e.title)} poster" loading="lazy"></div>
          <h3>${esc(e.title)}</h3>
          <p class="event-meta">${esc(formatDate(e.date))} / ${esc(e.venue)}</p>
          <p class="muted">${esc(e.description)}</p>
          <button class="btn btn-small event-detail" data-event-id="${esc(e.id)}" type="button">View Details</button>
        </article>`).join('');
      $('#eventEmpty').hidden = items.length !== 0;
      const pages = Math.ceil(filtered.length / perPage);
      $('#eventPagination').innerHTML = pages > 1
        ? Array.from({ length: pages }, (_, i) =>
          `<button class="${page === i + 1 ? 'active' : ''}" data-page="${i + 1}" type="button" aria-label="Page ${i + 1}" ${page === i + 1 ? 'aria-current="page"' : ''}>${i + 1}</button>`).join('')
        : '';
    };

    const apply = () => {
      const q = $('#eventSearch').value.toLowerCase().trim();
      const category = $('#eventCategory').value;
      const sort = $('#eventSort').value;
      filtered = allEvents.filter((e) =>
        (!q || `${e.title} ${e.category} ${e.venue} ${e.description}`.toLowerCase().includes(q)) &&
        (category === 'all' || e.category === category));
      filtered.sort((a, b) => (sort === 'title' ? a.title.localeCompare(b.title)
        : sort === 'date-desc' ? b.date.localeCompare(a.date)
          : a.date.localeCompare(b.date)));
      page = 1;
      render();
    };

    fetchJson('data/events.json').then((events) => {
      allEvents = events;
      $('#eventLoading').hidden = true;
      const categories = [...new Set(events.map((e) => e.category))].sort();
      $('#eventCategory').insertAdjacentHTML('beforeend', categories.map((c) => `<option>${esc(c)}</option>`).join(''));
      apply();
    }).catch(() => {
      $('#eventLoading').textContent = 'Events could not be loaded. Please check the JSON file and that the site is served over http.';
    });

    $('#eventSearch').addEventListener('input', apply);
    $('#eventCategory').addEventListener('change', apply);
    $('#eventSort').addEventListener('change', apply);
    $('#eventPagination').addEventListener('click', (event) => {
      const button = event.target.closest('button[data-page]');
      if (!button) return;
      page = Number(button.dataset.page);
      render();
      eventGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    eventGrid.addEventListener('click', (event) => {
      const button = event.target.closest('.event-detail');
      if (button) openEventModal(allEvents.find((e) => e.id === Number(button.dataset.eventId)), button);
    });
  }

  /* ---------- FAQ: load, search, collapse ---------- */
  const faqList = $('#faqList');
  if (faqList) {
    let faqs = [];
    const render = () => {
      const q = $('#faqSearch').value.toLowerCase().trim();
      const items = faqs.filter((f) => !q || `${f.question} ${f.answer}`.toLowerCase().includes(q));
      faqList.innerHTML = items.map((f, i) => `
        <article class="faq-item">
          <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-answer-${i}">
            <span>${esc(f.question)}</span><span class="faq-icon" aria-hidden="true">+</span>
          </button>
          <div class="faq-answer" id="faq-answer-${i}" hidden>${esc(f.answer)}</div>
        </article>`).join('');
      $('#faqEmpty').hidden = items.length !== 0;
    };

    faqList.addEventListener('click', (event) => {
      const button = event.target.closest('.faq-question');
      if (!button) return;
      const open = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!open));
      $('.faq-icon', button).textContent = open ? '+' : '−';
      button.nextElementSibling.hidden = open;
    });
    $('#faqSearch').addEventListener('input', render);

    fetchJson('data/faqs.json').then((data) => {
      faqs = data;
      $('#faqLoading').hidden = true;
      render();
    }).catch(() => { $('#faqLoading').textContent = 'FAQs could not be loaded. Please check the JSON file and that the site is served over http.'; });
  }

  /* ---------- Dashboard: show the profile photo saved from the Profile page ---------- */
  const avatarImg = $('#dashboardAvatarImg');
  if (avatarImg) {
    const photo = store.get('studenthub-profile-photo');
    if (photo) {
      avatarImg.src = photo;
      avatarImg.hidden = false;
      $('#dashboardAvatarInitial').hidden = true;
    }
  }

  /* ---------- Profile: details and photo, kept in this browser ---------- */
  const profileForm = $('#profileForm');
  if (profileForm) {
    const fieldIds = ['profileName', 'profileEmail', 'profileMobile', 'profileId', 'profileCourse'];

    // restore saved details
    try {
      const saved = JSON.parse(store.get('studenthub-profile') || '{}');
      fieldIds.forEach((id) => { if (saved[id] !== undefined && $('#' + id)) $('#' + id).value = saved[id]; });
    } catch (e) { /* ignore corrupt data */ }

    attachValidation(profileForm, {
      profileName: (v) => (!v || nameOK(v) ? '' : 'Enter a valid name.'),
      profileEmail: (v) => (!v || emailOK(v) ? '' : 'Enter a valid email address.'),
      profileMobile: (v) => (!v || mobileOK(v) ? '' : 'Enter a valid 10-digit mobile number.'),
    });

    profileForm.addEventListener('submit', (event) => {
      if (event.defaultPrevented) return; // validation failed
      event.preventDefault();
      const data = {};
      fieldIds.forEach((id) => { data[id] = $('#' + id).value.trim(); });
      const ok = store.set('studenthub-profile', JSON.stringify(data));
      const message = $('#profileSuccess');
      message.textContent = ok ? 'Profile saved on this device.' : 'Could not save: browser storage is blocked.';
      message.hidden = false;
    });

    // photo
    const photoInput = $('#profilePhotoInput');
    const image = $('#profileImage');
    const placeholder = $('#profilePlaceholder');
    const photoError = $('#photoError');

    const showPhoto = (src) => {
      image.src = src;
      image.hidden = false;
      placeholder.hidden = true;
    };
    const clearPhoto = () => {
      image.removeAttribute('src');
      image.hidden = true;
      placeholder.hidden = false;
    };

    const savedPhoto = store.get('studenthub-profile-photo');
    if (savedPhoto) showPhoto(savedPhoto);

    $('#choosePhoto').addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', () => {
      const file = photoInput.files[0];
      photoError.textContent = '';
      if (!file) return;
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        photoError.textContent = 'Choose a JPG, PNG or WebP image.';
        photoInput.value = '';
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        photoError.textContent = 'Image must be 2 MB or smaller.';
        photoInput.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        showPhoto(reader.result);
        if (!store.set('studenthub-profile-photo', reader.result)) {
          photoError.textContent = 'Photo shown, but it is too large to remember on this device.';
        }
      };
      reader.readAsDataURL(file);
    });
    $('#removePhoto').addEventListener('click', () => {
      store.remove('studenthub-profile-photo');
      photoInput.value = '';
      photoError.textContent = '';
      clearPhoto();
    });
  }
})();
