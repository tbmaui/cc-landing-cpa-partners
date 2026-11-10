(function () {
'use strict';
var ccCpaRoot = document.querySelector('.cc-cpa-page');
if (!ccCpaRoot || ccCpaRoot.dataset.ccCpaReady) return;
ccCpaRoot.dataset.ccCpaReady = 'true';

/* Program terms: the single place to update once Complete Controller approves them. */
const PROGRAM = {
  commissionRate: 0.10,   // ILLUSTRATIVE ONLY, pending approval
  commissionMonths: 12,   // ILLUSTRATIVE ONLY, pending approval
  termsApproved: false
};

(function () {
  const $ = (id) => ccCpaRoot.querySelector('#' + CSS.escape(id));
  const money = (n) => n.toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
  const pair = (range, num) => {
    const sync = (from, to) => {
      let v = Number(from.value);
      if (!Number.isFinite(v)) return;
      v = Math.min(Number(from.max), Math.max(Number(from.min), v));
      to.value = v; if (from === num && from.value !== '') from.value = v;
      calc();
    };
    range.addEventListener('input', () => sync(range, num));
    num.addEventListener('change', () => sync(num, range));
  };
  function calc() {
    const clients = Number($('clients-range').value);
    const fee = Number($('fee-range').value);
    const perMonth = fee * PROGRAM.commissionRate;
    const perClient = perMonth * PROGRAM.commissionMonths;
    $('out-month').textContent = money(perMonth);
    $('out-client').textContent = money(perClient);
    $('out-total').textContent = money(perClient * clients);
    $('out-client-desc').textContent = 'Over ' + PROGRAM.commissionMonths + ' months of service';
    $('out-total-desc').textContent = clients + ' referred client' + (clients === 1 ? '' : 's') + ' over ' + PROGRAM.commissionMonths + ' months';
    $('calc-terms').textContent = 'Based on a ' + Math.round(PROGRAM.commissionRate * 100) + '% commission on monthly bookkeeping fees for ' + PROGRAM.commissionMonths + ' months per referred client.' + (PROGRAM.termsApproved ? '' : ' Illustrative terms pending approval.');
  }
  pair($('clients-range'), $('clients-num'));
  pair($('fee-range'), $('fee-num'));
  calc();

  /* Application form: preview only. Production posts to a server route that writes to HubSpot. */
  const freeDomains = /@(gmail|yahoo|hotmail|outlook|aol|icloud)\./i;
  function wire(form, hp, done) {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (hp.value) return;
    let firstBad = null;
    form.querySelectorAll('.field-error').forEach((n) => n.remove());
    form.querySelectorAll('[required]').forEach((el) => {
      let msg = '';
      if (el.type === 'checkbox') { if (!el.checked) msg = 'Please confirm your client expects to hear from us.'; }
      else if (!el.value.trim()) msg = 'Please fill this in.';
      else if (el.type === 'email' && !el.checkValidity()) msg = 'Enter a valid email address, like name@company.com.';
      else if (el.matches('[data-work]') && freeDomains.test(el.value)) msg = 'Please use your firm email address.';
      el.setAttribute('aria-invalid', msg ? 'true' : 'false');
      if (msg) {
        const s = document.createElement('span'); s.className = 'field-error'; s.id = el.id + '-err'; s.textContent = msg;
        el.setAttribute('aria-describedby', s.id); (el.type === 'checkbox' ? el.closest('label') : el).after(s);
        firstBad = firstBad || el;
      }
    });
    if (firstBad) { firstBad.focus(); return; }
    form.hidden = true; done.hidden = false; done.focus();
  });
  }
  wire($('apply-form'), $('f-hp'), $('apply-done'));
  wire($('refer-form'), $('r-hp'), $('refer-done'));

  /* Refer a Client pop-up: every [data-refer] button opens it; reopening after a submission starts a fresh form. */
  const dlg = $('refer-dialog');
  let opener = null;
  const closeRefer = () => { if (dlg.close) dlg.close(); else { dlg.removeAttribute('open'); if (opener) opener.focus(); } };
  function openRefer(e) {
    if (e) { e.preventDefault(); opener = e.currentTarget; }
    const f = $('refer-form'), d = $('refer-done');
    if (f.hidden) {
      f.reset(); f.hidden = false; d.hidden = true;
      f.querySelectorAll('.field-error').forEach((n) => n.remove());
      f.querySelectorAll('[aria-invalid]').forEach((n) => { n.removeAttribute('aria-invalid'); n.removeAttribute('aria-describedby'); });
    }
    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); } else dlg.setAttribute('open', '');
    dlg.scrollTop = 0;
    $('r-name').focus();
  }
  ccCpaRoot.querySelectorAll('[data-refer]').forEach((b) => b.addEventListener('click', openRefer));
  ccCpaRoot.querySelectorAll('[data-close-refer]').forEach((b) => b.addEventListener('click', closeRefer));
  dlg.addEventListener('click', (e) => { if (e.target === dlg) closeRefer(); });
  dlg.addEventListener('close', () => { if (opener) opener.focus(); });
  if (location.hash === '#refer') openRefer();

  /* Reveal sections below the first screen only, so the page is complete at rest. */
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const targets = ccCpaRoot.querySelectorAll('.cc-heading-block, .cc-feature, .cc-tile, .cc-card, .cc-step, .cc-quote-card, .cc-quote-sm, .cc-callout');
    const io = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } }), { threshold: 0.12 });
    targets.forEach((el) => {
      if (el.getBoundingClientRect().top > window.innerHeight) {
        if (el.matches('.cc-feature, .cc-tile, .cc-card, .cc-step, .cc-quote-sm')) el.style.setProperty('--cc-rise', '60px');
        el.classList.add('js-reveal'); io.observe(el);
      }
    });
  }
})();

})();
