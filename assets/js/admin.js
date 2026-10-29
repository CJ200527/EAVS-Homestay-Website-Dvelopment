/* admin.js — toasts, bell/filter panels, skeleton arrival, cascade unveil. Offline, vanilla. */
function showToast(message, category) {
  var host = document.getElementById('toast-container');
  if (!host) { host = document.createElement('div'); host.id = 'toast-container'; host.className = 'toast-container'; document.body.insertBefore(host, document.body.firstChild); }
  var cat = category || 'info';
  var card = document.createElement('div');
  card.className = 'toast-card toast-' + cat;
  var msg = document.createElement('span'); msg.className = 'toast-message'; msg.textContent = message;
  var close = document.createElement('button'); close.type = 'button'; close.className = 'toast-close'; close.textContent = '✕';
  close.addEventListener('click', function () { if (card.parentNode) card.remove(); });
  card.appendChild(msg); card.appendChild(close); host.appendChild(card);
  setTimeout(function () {
    card.style.transition = 'opacity .4s ease,transform .4s ease'; card.style.opacity = '0'; card.style.transform = 'translateY(-20px) scale(.9)';
    setTimeout(function () { if (card.parentNode) card.remove(); }, 400);
  }, 4000);
  return card;
}
// Auto-dismiss server-rendered flash toasts
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('#toast-container .toast-card').forEach(function (t) {
    setTimeout(function () {
      t.style.transition = 'opacity .4s ease,transform .4s ease'; t.style.opacity = '0'; t.style.transform = 'translateY(-20px) scale(.9)';
      setTimeout(function () { if (t.parentNode) t.remove(); }, 400);
    }, 4000);
  });
});
// Bell + funnel panels: toggle, outside-click, Escape
(function () {
  function closePanels() {
    document.querySelectorAll('.notif-panel.open,.filter-panel.open').forEach(function (p) { p.classList.remove('open'); });
    document.querySelectorAll('.notif-bell.open,.filter-btn.open').forEach(function (b) { b.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); });
  }
  document.querySelectorAll('.notif-bell').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var pop = btn.parentNode.querySelector('.notif-panel'); if (!pop) return;
      var will = !pop.classList.contains('open'); closePanels();
      pop.classList.toggle('open', will); btn.classList.toggle('open', will); btn.setAttribute('aria-expanded', will ? 'true' : 'false');
    });
  });
  document.querySelectorAll('.filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var pop = btn.parentNode.querySelector('.filter-panel'); if (!pop) return;
      var will = !pop.classList.contains('open'); closePanels();
      pop.classList.toggle('open', will); btn.classList.toggle('open', will); btn.setAttribute('aria-expanded', will ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest && (e.target.closest('.notif-panel') || e.target.closest('.filter-panel'))) return;
    closePanels();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePanels(); });
})();
// Bootstrap modals live inside glass cards (backdrop-filter traps fixed
// positioning), so relocate each modal to <body> when shown.
document.addEventListener('show.bs.modal', function (e) {
  var m = e.target && e.target.closest ? e.target.closest('.modal') : null;
  if (m && m.parentNode !== document.body) document.body.appendChild(m);
});
var ADMIN_TOAST_DELAY = 700;
document.addEventListener('submit', function (e) {
  var f = e.target;
  if (!f || f.tagName !== 'FORM' || !f.hasAttribute('data-toast-form')) return;
  if ((f.getAttribute('method') || 'GET').toUpperCase() !== 'GET') return;
  if (f.hasAttribute('data-toast-shown')) return;
  e.preventDefault(); f.setAttribute('data-toast-shown', '1');
  showToast('Searching records.', 'info');
  try { sessionStorage.setItem('eavRowsAnim', '1'); } catch (err) {}
  setTimeout(function () { f.submit(); }, ADMIN_TOAST_DELAY);
}, true);
// Sidebar clicks plant one-shot flag; arrival handler runs cascade (VT skipped — cascade is the base)
document.addEventListener('click', function (e) {
  var a = e.target && e.target.closest ? e.target.closest('.ad-side .side-link') : null;
  if (!a || a.href.indexOf('logout.php') !== -1) return;
  try { sessionStorage.setItem('eavTabAnim', '1'); } catch (err) {}
});
function staggerUnveil() {
  if (window.__staggerDone) return; window.__staggerDone = true;
  try { if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; } catch (e) {}
  var STEP = 0.12, i = 0;
  function arm(el) {
    el.classList.add('cascade-unveil');
    el.style.animationDelay = (0.08 + (i++) * STEP).toFixed(2) + 's';
  }
  document.querySelectorAll('.ad-body > *').forEach(arm);
}
try {
  var arrival = false;
  try { arrival = sessionStorage.getItem('eavTabAnim') === '1' || sessionStorage.getItem('eavRowsAnim') === '1'; } catch (e) {}
  try { sessionStorage.removeItem('eavTabAnim'); sessionStorage.removeItem('eavRowsAnim'); } catch (e2) {}
  if (arrival) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', staggerUnveil);
    else staggerUnveil();
  }
} catch (e) {}
// Skeleton: shown only when landing from login (?welcome=1); fades, flag cleaned so refresh never replays
document.addEventListener('DOMContentLoaded', function () {
  var sk = document.getElementById('skeleton-overlay');
  if (!sk) return;
  try {
    if (window.history.replaceState) window.history.replaceState(null, '', window.location.pathname);
  } catch (e) {}
  setTimeout(function () {
    sk.style.transition = 'opacity .4s ease'; sk.style.opacity = '0';
    setTimeout(function () { sk.style.display = 'none'; }, 400);
  }, 1100);
});
