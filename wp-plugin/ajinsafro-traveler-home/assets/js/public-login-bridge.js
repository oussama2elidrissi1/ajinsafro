(function () {
  function qs(name) {
    const m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function showAlert(message) {
    if (!message) return;
    const form = document.querySelector('form');
    const host = form?.parentElement || document.body;
    const box = document.createElement('div');
    box.setAttribute('role', 'alert');
    box.style.cssText =
      'margin:12px 0;padding:12px 14px;border-radius:10px;border:1px solid rgba(220,38,38,.25);background:rgba(220,38,38,.08);color:#991b1b;font-weight:600;font-size:13px;line-height:1.4;';
    box.textContent = message;
    host.insertBefore(box, host.firstChild);
  }

  function findLoginForm() {
    const forms = Array.from(document.querySelectorAll('form'));
    return forms.find((f) => f.querySelector('input[type="password"]')) || null;
  }

  function first(form, selectors) {
    for (const sel of selectors) {
      const n = form.querySelector(sel);
      if (n) return n;
    }
    return null;
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (qs('login_error') === '1') {
      showAlert('Identifiants invalides. Veuillez réessayer.');
    }
    if (qs('session_expired') === '1') {
      showAlert('Votre session a expiré, veuillez vous reconnecter.');
    }

    const form = findLoginForm();
    if (!form) return;

    const loginPrefill = qs('login') || qs('email');
    if (loginPrefill) {
      const loginInput = first(form, [
        'input[type="email"]',
        'input[type="text"][name*="user"]',
        'input[name="login"]',
        'input[name="email"]',
        'input[name="username"]',
        'input[name="log"]',
      ]);
      if (loginInput && !loginInput.value) loginInput.value = loginPrefill;
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const loginInput = first(form, [
        'input[type="email"]',
        'input[type="text"][name*="user"]',
        'input[name="login"]',
        'input[name="email"]',
        'input[name="username"]',
        'input[name="log"]',
      ]);
      const passInput = first(form, [
        'input[type="password"]',
        'input[name="password"]',
        'input[name="pwd"]',
        'input[name="user_pass"]',
      ]);
      const rememberInput = first(form, ['input[name="remember"]', 'input[name="rememberme"]']);

      const login = (loginInput?.value || '').trim();
      const password = passInput?.value || '';
      const remember = !!rememberInput?.checked;

      const postForm = document.createElement('form');
      postForm.method = 'POST';
      postForm.action = 'https://booking.ajinsafro.net/auth/public-login';

      function add(name, value) {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = name;
        i.value = String(value ?? '');
        postForm.appendChild(i);
      }

      add('login', login);
      add('password', password);
      if (remember) add('remember', '1');
      add('source', 'wp_public_login_ui');

      document.body.appendChild(postForm);
      postForm.submit();
    });
  });
})();

