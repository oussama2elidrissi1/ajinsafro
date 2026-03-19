(function () {
  function qs(name) {
    const m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function showError(message) {
    if (!message) return;
    const host = document.querySelector('form')?.parentElement || document.body;
    const el = document.createElement('div');
    el.setAttribute('role', 'alert');
    el.style.cssText = 'margin:12px 0;padding:12px 14px;border-radius:10px;border:1px solid rgba(220,38,38,.25);background:rgba(220,38,38,.08);color:#991b1b;font-weight:600;font-size:13px;line-height:1.4;';
    el.textContent = message;
    host.insertBefore(el, host.firstChild);
  }

  function findLoginForm() {
    // Heuristics: first form containing a password input.
    const forms = Array.from(document.querySelectorAll('form'));
    return forms.find(f => f.querySelector('input[type="password"]')) || null;
  }

  function getInput(form, selectors) {
    for (const sel of selectors) {
      const el = form.querySelector(sel);
      if (el) return el;
    }
    return null;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Error display on same UI (from Laravel redirect back).
    if (qs('login_error') === '1') {
      showError('Identifiants invalides. Veuillez réessayer.');
    }
    if (qs('session_expired') === '1') {
      showError('Votre session a expiré, veuillez vous reconnecter.');
    }

    const form = findLoginForm();
    if (!form) return;

    // Pre-fill email when provided.
    const emailPrefill = qs('email');
    if (emailPrefill) {
      const emailInput = getInput(form, [
        'input[type="email"]',
        'input[name="email"]',
        'input[name="log"]',
        'input[name="username"]',
        'input[name="user_login"]',
      ]);
      if (emailInput && !emailInput.value) emailInput.value = emailPrefill;
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const emailInput = getInput(form, [
        'input[type="email"]',
        'input[name="email"]',
        'input[name="log"]',
        'input[name="username"]',
        'input[name="user_login"]',
      ]);
      const passInput = getInput(form, [
        'input[type="password"]',
        'input[name="password"]',
        'input[name="pwd"]',
        'input[name="user_pass"]',
      ]);
      const rememberInput = getInput(form, [
        'input[name="remember"]',
        'input[name="rememberme"]',
      ]);

      const email = (emailInput?.value || '').trim();
      const password = passInput?.value || '';
      const remember = !!rememberInput?.checked;

      const postForm = document.createElement('form');
      postForm.method = 'POST';
      postForm.action = 'https://booking.ajinsafro.net/auth/public-login';

      const add = (name, value) => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = name;
        i.value = String(value ?? '');
        postForm.appendChild(i);
      };

      add('email', email);
      add('password', password);
      if (remember) add('remember', '1');

      // Optional: allow Laravel to know origin (audit/logs).
      add('source', 'wp_public_login');

      document.body.appendChild(postForm);
      postForm.submit();
    });
  });
})();

