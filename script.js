/* BatchFund – vanilla JS: dialogs, confirmations, validation, live table search, charts */
(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  /* mobile sidebar */
  const menuBtn = $('#menuBtn'), sidebar = $('#sidebar');
  if (menuBtn && sidebar) menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));

  /* close the top-right account menu when clicking elsewhere */
  document.addEventListener('click', e => { $$('details.user-menu[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); }); });

  /* dialogs */
  $$('[data-dialog]').forEach(btn => btn.addEventListener('click', () => { const d = document.getElementById(btn.dataset.dialog); if (d) d.showModal(); }));
  $$('dialog [data-close]').forEach(btn => btn.addEventListener('click', () => btn.closest('dialog').close()));
  $$('dialog').forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));
  $$('[data-reject]').forEach(btn => btn.addEventListener('click', () => {
    $('#rejectPaymentId').value = btn.dataset.reject; $('#dlgReject').showModal();
  }));

  /* confirmation dialogs (forms with data-confirm, buttons with data-confirm-btn) */
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
  $$('[data-confirm-btn]').forEach(b => b.addEventListener('click', e => { if (!confirm(b.dataset.confirmBtn)) e.preventDefault(); }));

  /* print */
  $$('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));

  /* live table filter */
  $$('input[data-filter-table]').forEach(inp => inp.addEventListener('input', () => {
    const table = document.getElementById(inp.dataset.filterTable); if (!table) return;
    const term = inp.value.trim().toLowerCase();
    $$('tbody tr', table).forEach(tr => { tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none'; });
  }));

  /* client-side validation (server re-validates everything) */
  function showError(el, msg) {
    el.classList.add('invalid');
    let m = el.parentElement.querySelector('.field-error');
    if (!m) { m = document.createElement('span'); m.className = 'field-error'; el.parentElement.appendChild(m); }
    m.textContent = msg;
  }
  function clearError(el) { el.classList.remove('invalid'); const m = el.parentElement.querySelector('.field-error'); if (m) m.remove(); }

  $$('input[type=file][data-max]').forEach(inp => inp.addEventListener('change', () => {
    clearError(inp);
    const f = inp.files[0]; if (!f) return;
    const ok = /\.(jpe?g|png|webp|pdf)$/i.test(f.name);
    if (!ok) { showError(inp, 'Only JPG, PNG, WEBP or PDF files are allowed.'); inp.value = ''; }
    else if (f.size > Number(inp.dataset.max)) { showError(inp, 'File is too large (max ' + (inp.dataset.max / 1048576) + ' MB).'); inp.value = ''; }
  }));

  $$('form[data-validate]').forEach(form => {
    form.addEventListener('submit', e => {
      let bad = false;
      $$('input,select,textarea', form).forEach(el => { clearError(el); if (!el.checkValidity()) { showError(el, el.validationMessage); bad = true; } });
      const np = form.elements['new_password'], cp = form.elements['confirm_password'];
      if (np && cp && np.value !== cp.value) { showError(cp, 'Passwords do not match.'); bad = true; }
      const np2 = form.elements['new_password'];
      if (np2 && !(np2.value.length >= 8 && /[A-Za-z]/.test(np2.value) && /\d/.test(np2.value))) { showError(np2, 'Use 8+ characters with letters and numbers.'); bad = true; }
      if (bad) { e.preventDefault(); const first = $('.invalid', form); if (first) first.focus(); }
    });
  });

  /* payment form: fill the amount from the selected collection */
  $$('form[data-pay-form]').forEach(form => {
    const sel = $('[data-pay-select]', form), amt = $('[data-pay-amount]', form);
    const fill = () => { const o = sel.options[sel.selectedIndex]; amt.value = o && o.dataset.amount ? o.dataset.amount : ''; if (o && o.dataset.amount) amt.min = amt.max = o.dataset.amount; };
    sel.addEventListener('change', fill); fill();
    form.addEventListener('submit', e => {
      const o = sel.options[sel.selectedIndex];
      if (o && o.dataset.amount && Math.abs(parseFloat(amt.value) - parseFloat(o.dataset.amount)) > 0.001) {
        e.preventDefault(); showError(amt, 'Amount must equal the contribution amount.');
      }
    });
  });

  /* ---------- charts (canvas, no libraries) ---------- */
  const COLORS = ['#2f5bea', '#12b76a', '#f79009', '#7a5af8', '#0ba5ec', '#f04438', '#667085', '#d444f1'];
  function fmt(n) { return n >= 1e6 ? (n / 1e6).toFixed(1) + 'M' : n >= 1e3 ? (n / 1e3).toFixed(1) + 'k' : String(Math.round(n)); }

  function drawBar(ctx, w, h, cfg) {
    const pad = { l: 46, r: 10, t: 24, b: 40 }, cw = w - pad.l - pad.r, ch = h - pad.t - pad.b;
    const max = Math.max(1, ...cfg.series.flatMap(s => s.data)) * 1.1;
    ctx.font = '11px system-ui'; ctx.fillStyle = '#667085'; ctx.strokeStyle = '#e4e7ec'; ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) { const y = pad.t + ch - ch * i / 4; ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(w - pad.r, y); ctx.stroke(); ctx.fillText(fmt(max * i / 4), pad.l - 6, y + 4); }
    const groups = cfg.labels.length || 1, gw = cw / groups, bw = Math.min(34, gw / (cfg.series.length + 1));
    cfg.labels.forEach((lab, i) => {
      cfg.series.forEach((s, j) => {
        const v = s.data[i] || 0, bh = ch * v / max, x = pad.l + gw * i + (gw - bw * cfg.series.length) / 2 + bw * j;
        ctx.fillStyle = COLORS[j]; ctx.fillRect(x, pad.t + ch - bh, bw - 2, bh);
      });
      ctx.fillStyle = '#475467'; ctx.textAlign = 'center';
      ctx.fillText(lab.length > 14 ? lab.slice(0, 13) + '…' : lab, pad.l + gw * i + gw / 2, h - pad.b + 16);
    });
    cfg.series.forEach((s, j) => { ctx.fillStyle = COLORS[j]; ctx.fillRect(pad.l + j * 90, 6, 10, 10); ctx.fillStyle = '#475467'; ctx.textAlign = 'left'; ctx.fillText(s.name, pad.l + j * 90 + 14, 15); });
    if (!cfg.labels.length) { ctx.fillStyle = '#667085'; ctx.textAlign = 'center'; ctx.fillText('No data yet', w / 2, h / 2); }
  }
  function drawDoughnut(ctx, w, h, cfg) {
    const data = cfg.series[0].data, total = data.reduce((a, b) => a + b, 0);
    const r = Math.min(w * 0.3, h / 2 - 10), cx = r + 20, cy = h / 2;
    if (total <= 0) { ctx.fillStyle = '#667085'; ctx.font = '12px system-ui'; ctx.textAlign = 'center'; ctx.fillText('No data yet', w / 2, h / 2); return; }
    let a = -Math.PI / 2;
    data.forEach((v, i) => { const s = v / total * Math.PI * 2; ctx.beginPath(); ctx.moveTo(cx, cy); ctx.arc(cx, cy, r, a, a + s); ctx.closePath(); ctx.fillStyle = COLORS[i % COLORS.length]; ctx.fill(); a += s; });
    ctx.globalCompositeOperation = 'destination-out'; ctx.beginPath(); ctx.arc(cx, cy, r * 0.58, 0, Math.PI * 2); ctx.fill(); ctx.globalCompositeOperation = 'source-over';
    ctx.font = '12px system-ui'; ctx.textAlign = 'left';
    cfg.labels.forEach((l, i) => { const y = 30 + i * 22; ctx.fillStyle = COLORS[i % COLORS.length]; ctx.fillRect(cx + r + 24, y - 9, 10, 10); ctx.fillStyle = '#344054'; ctx.fillText(l + ' – ' + fmt(data[i]), cx + r + 40, y); });
  }
  function renderCharts() {
    $$('canvas[data-chart]').forEach(cv => {
      let cfg; try { cfg = JSON.parse(cv.dataset.chart); } catch (e) { return; }
      const dpr = window.devicePixelRatio || 1, w = cv.clientWidth, h = cv.clientHeight;
      cv.width = w * dpr; cv.height = h * dpr;
      const ctx = cv.getContext('2d'); ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, w, h);
      (cfg.type === 'doughnut' ? drawDoughnut : drawBar)(ctx, w, h, cfg);
    });
  }
  renderCharts();
  let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(renderCharts, 150); });
})();