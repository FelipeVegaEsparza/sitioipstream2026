(function() {
  if (sessionStorage.getItem('trial_popup_shown')) return;

  var overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:1rem;opacity:0;transition:opacity 0.4s ease;';
  overlay.innerHTML =
    '<div style="background:#fff;border-radius:24px;padding:2rem;max-width:420px;width:100%;text-align:center;position:relative;box-shadow:0 25px 60px rgba(0,0,0,0.3);transform:scale(0.95);transition:transform 0.4s ease;">' +
      '<button id="trial-popup-close" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:#9ca3af;padding:4px;line-height:1;">&times;</button>' +
      '<div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">' +
        '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>' +
      '</div>' +
      '<h2 style="font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 0.5rem;">Prueba IPStream <span style="color:#3b82f6;">7 Días Gratis</span></h2>' +
      '<p style="color:#6b7280;font-size:0.95rem;line-height:1.6;margin:0 0 1.5rem;">Transmite tu radio o TV online sin costo durante 7 días. Sin tarjeta de crédito. Sin compromiso.</p>' +
      '<a href="/landing" id="trial-popup-cta" style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;font-weight:700;font-size:1rem;padding:0.85rem 2rem;border-radius:12px;text-decoration:none;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 4px 14px rgba(59,130,246,0.4);">' +
        'Solicitar 7 Días Gratis' +
      '</a>' +
      '<p style="color:#9ca3af;font-size:0.75rem;margin-top:1rem;">&#191;Ya tienes cuenta? <a href="/planes" style="color:#3b82f6;text-decoration:underline;">Ver Planes</a></p>' +
    '</div>';

  document.body.appendChild(overlay);

  var shown = false;

  function show() {
    if (shown) return;
    shown = true;
    overlay.style.display = 'flex';
    requestAnimationFrame(function() {
      overlay.style.opacity = '1';
      overlay.querySelector('div').style.transform = 'scale(1)';
    });
    sessionStorage.setItem('trial_popup_shown', '1');
  }

  setTimeout(show, 10000);

  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      overlay.style.opacity = '0';
      overlay.querySelector('div').style.transform = 'scale(0.95)';
      setTimeout(function() { overlay.style.display = 'none'; }, 400);
    }
  });

  document.getElementById('trial-popup-close').addEventListener('click', function() {
    overlay.style.opacity = '0';
    overlay.querySelector('div').style.transform = 'scale(0.95)';
    setTimeout(function() { overlay.style.display = 'none'; }, 400);
  });
})();
