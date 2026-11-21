
(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  const menuBtn = $('#menuBtn'), sidebar = $('#sidebar');
  if (menuBtn && sidebar) menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));

  document.addEventListener('click', e => { $$('details.user-menu[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); }); });

  $$('[data-dialog]').forEach(btn => btn.addEventListener('click', e => {
    e.preventDefault();
    $$('details.user-menu[open]').forEach(m => m.removeAttribute('open'));
    const d = document.getElementById(btn.dataset.dialog); if (d) d.showModal();
  }));
  $$('dialog [data-close]').forEach(btn => btn.addEventListener('click', () => btn.closest('dialog').close()));
  $$('dialog').forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));
  $$('[data-reject]').forEach(btn => btn.addEventListener('click', () => {
    $('#rejectPaymentId').value = btn.dataset.reject; $('#dlgReject').showModal();
  }));

  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
  $$('[data-confirm-btn]').forEach(b => b.addEventListener('click', e => { if (!confirm(b.dataset.confirmBtn)) e.preventDefault(); }));


  $$('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));

  $$('input[data-filter-table]').forEach(inp => inp.addEventListener('input', () => {
    const table = document.getElementById(inp.dataset.filterTable); if (!table) return;
    const term = inp.value.trim().toLowerCase();
    $$('tbody tr', table).forEach(tr => { tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none'; });
  }));

  function showError(el, msg) {
    el.classList.add('invalid');
    let m = el.parentElement.querySelector('.field-error');
    if (!m) { m = document.createElement('span'); m.className = 'field-error'; el.parentElement.appendChild(m); }
    m.textContent = msg;
  }
  function clearError(el) { el.classList.remove('invalid'); const m = el.parentElement.querySelector('.field-error'); if (m) m.remove(); }

  $$('input[type=file][data-max]:not([data-images])').forEach(inp => inp.addEventListener('change', () => {
    clearError(inp);
    const files = Array.from(inp.files); if (!files.length) return;
    const imagesOnly = inp.hasAttribute('data-images');
    const extOk = imagesOnly ? /\.(jpe?g|png|webp)$/i : /\.(jpe?g|png|webp|pdf)$/i;
    const limit = Number(inp.dataset.limit || 0);
    if (files.some(f => !extOk.test(f.name))) { showError(inp, imagesOnly ? 'Only JPG, PNG or WEBP images are allowed.' : 'Only JPG, PNG, WEBP or PDF files are allowed.'); inp.value = ''; }
    else if (files.some(f => f.size > Number(inp.dataset.max))) { showError(inp, 'A file is too large (max ' + (inp.dataset.max / 1048576) + ' MB each).'); inp.value = ''; }
    else if (limit && files.length > limit) { showError(inp, 'Choose at most ' + limit + ' images.'); inp.value = ''; }
  }));


  // ---- event images: manual 1:1 crop + automatic resize, done in the browser before upload ----
  const EXT_OK = /\.(jpe?g|png|webp)$/i;
  let cropDlg = null;

  function buildCropDialog() {
    const d = document.createElement('dialog');
    d.className = 'crop-dlg';
    d.innerHTML =
      '<h2>Crop image <small data-count></small></h2>' +
      '<p class="muted small">Drag the picture to reposition it and use the slider (or mouse wheel) to zoom. ' +
      'The square is exactly what will be saved.</p>' +
      '<div class="crop-stage"><canvas></canvas></div>' +
      '<label class="crop-zoom">Zoom <input type="range" min="1" max="4" step="0.01" value="1"></label>' +
      '<div class="dlg-actions"><button type="button" class="btn" data-skip>Skip this image</button>' +
      '<button type="button" class="btn btn-primary" data-ok>Crop &amp; use</button></div>';
    document.body.appendChild(d);
    return d;
  }

  /** Opens the crop dialog for one file. Resolves with the cropped File, or null if skipped / cancelled / unreadable. */
  function cropImage(file, outMax, maxBytes, pos, total) {
    return new Promise(resolve => {
      if (!cropDlg) cropDlg = buildCropDialog();
      const dlg = cropDlg, cv = $('canvas', dlg), zoom = $('input[type=range]', dlg), ctx = cv.getContext('2d');
      $('[data-count]', dlg).textContent = total > 1 ? pos + ' of ' + total : '';
      const url = URL.createObjectURL(file), img = new Image();
      let done = false, S = 0, scale = 1, base = 1, x = 0, y = 0, drag = null;

      const finish = val => {
        if (done) return; done = true;
        URL.revokeObjectURL(url);
        zoom.oninput = cv.onpointerdown = cv.onpointermove = cv.onpointerup = cv.onpointercancel = cv.onwheel = null;
        $('[data-ok]', dlg).onclick = $('[data-skip]', dlg).onclick = null;
        dlg.onclose = null;
        if (dlg.open) dlg.close();
        resolve(val);
      };
      const clamp = () => {
        const dw = img.naturalWidth * scale, dh = img.naturalHeight * scale;
        x = Math.min(0, Math.max(S - dw, x)); y = Math.min(0, Math.max(S - dh, y));
      };
      const draw = () => {
        ctx.clearRect(0, 0, S, S);
        ctx.drawImage(img, x, y, img.naturalWidth * scale, img.naturalHeight * scale);
        ctx.strokeStyle = 'rgba(255,255,255,.65)'; ctx.lineWidth = 1; ctx.beginPath();
        for (let i = 1; i < 3; i++) { ctx.moveTo(S * i / 3, 0); ctx.lineTo(S * i / 3, S); ctx.moveTo(0, S * i / 3); ctx.lineTo(S, S * i / 3); }
        ctx.stroke();
      };
      const setZoom = z => {
        z = Math.min(4, Math.max(1, z));
        const cx = (S / 2 - x) / scale, cy = (S / 2 - y) / scale;   // keep the view centre fixed while zooming
        scale = base * z; x = S / 2 - cx * scale; y = S / 2 - cy * scale;
        zoom.value = z; clamp(); draw();
      };

      img.onerror = () => { finish(null); alert('Could not read "' + file.name + '". Try a JPG, PNG or WEBP image.'); };
      img.onload = () => {
        S = Math.round(Math.min(420, window.innerWidth * 0.8));
        const dpr = window.devicePixelRatio || 1;
        cv.style.width = cv.style.height = S + 'px'; cv.width = cv.height = Math.round(S * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.imageSmoothingQuality = 'high';
        base = S / Math.min(img.naturalWidth, img.naturalHeight);   // smallest side exactly fills the square
        scale = base; x = (S - img.naturalWidth * scale) / 2; y = (S - img.naturalHeight * scale) / 2;
        zoom.value = 1; draw();
      };

      zoom.oninput = () => setZoom(parseFloat(zoom.value));
      cv.onwheel = e => { e.preventDefault(); setZoom(parseFloat(zoom.value) * (e.deltaY < 0 ? 1.08 : 1 / 1.08)); };
      cv.onpointerdown = e => { drag = { px: e.clientX, py: e.clientY, x, y }; cv.setPointerCapture(e.pointerId); cv.classList.add('dragging'); };
      cv.onpointermove = e => { if (!drag) return; x = drag.x + e.clientX - drag.px; y = drag.y + e.clientY - drag.py; clamp(); draw(); };
      cv.onpointerup = cv.onpointercancel = () => { drag = null; cv.classList.remove('dragging'); };

      $('[data-skip]', dlg).onclick = () => finish(null);
      dlg.onclose = () => finish(null);                      // Esc key
      $('[data-ok]', dlg).onclick = () => {
        const sSize = S / scale;                              // side of the chosen square in original pixels
        const out = Math.max(1, Math.min(outMax, Math.round(sSize)));   // resize down, never blow small pictures up
        const c = document.createElement('canvas'); c.width = c.height = out;
        const g = c.getContext('2d'); g.imageSmoothingQuality = 'high';
        g.fillStyle = '#fff'; g.fillRect(0, 0, out, out);
        g.drawImage(img, -x / scale, -y / scale, sSize, sSize, 0, 0, out, out);
        const name = (file.name.replace(/\.[^.]+$/, '').replace(/[^A-Za-z0-9_\- ]/g, '_').slice(0, 60) || 'event') + '.jpg';
        const attempt = q => c.toBlob(b => {
          if (!b) return finish(null);
          if (b.size > maxBytes && q > 0.5) return attempt(q - 0.15);   // squeeze further if still too big
          finish(new File([b], name, { type: 'image/jpeg' }));
        }, 'image/jpeg', q);
        attempt(0.86);
      };
      img.src = url;
      dlg.showModal();
    });
  }

  function initImageCropper(inp) {
    const outMax = Number(inp.dataset.size || 800), rawMax = Number(inp.dataset.rawMax || 15 * 1048576);
    const maxBytes = Number(inp.dataset.max || 3 * 1048576), limit = Number(inp.dataset.limit || 0);
    const labelEl = inp.closest('label') || inp;
    const strip = document.createElement('div'); strip.className = 'crop-strip';
    labelEl.after(strip);
    let kept = [];                                            // cropped File objects that will be uploaded

    const sync = () => {
      const dt = new DataTransfer(); kept.forEach(f => dt.items.add(f)); inp.files = dt.files;
      strip.innerHTML = '';
      kept.forEach((f, i) => {
        const fig = document.createElement('figure'), im = document.createElement('img'), b = document.createElement('button');
        im.src = URL.createObjectURL(f); im.alt = ''; im.onload = () => URL.revokeObjectURL(im.src);
        b.type = 'button'; b.textContent = '×'; b.title = 'Remove'; b.setAttribute('aria-label', 'Remove image');
        b.onclick = () => { kept.splice(i, 1); sync(); clearError(inp); };
        fig.append(im, b); strip.append(fig);
      });
    };

    inp.addEventListener('change', async () => {
      clearError(inp);
      const picked = Array.from(inp.files);
      if (!picked.length) return;
      if (picked.some(f => !EXT_OK.test(f.name))) { showError(inp, 'Only JPG, PNG or WEBP images are allowed.'); return sync(); }
      if (picked.some(f => f.size > rawMax)) { showError(inp, 'An image is too large (max ' + Math.round(rawMax / 1048576) + ' MB).'); return sync(); }
      if (limit && kept.length + picked.length > limit) { showError(inp, 'Choose at most ' + limit + ' images (you already have ' + kept.length + ' ready).'); return sync(); }
      inp.disabled = true;
      for (let i = 0; i < picked.length; i++) {
        const f = await cropImage(picked[i], outMax, maxBytes, i + 1, picked.length);
        if (f) kept.push(f);
      }
      inp.disabled = false;
      sync();
    });
  }

  if (typeof DataTransfer !== 'undefined') $$('input[type=file][data-images]').forEach(initImageCropper);
  else $$('input[type=file][data-images]').forEach(inp => inp.addEventListener('change', () => clearError(inp)));   // old browser: the server crops + resizes instead

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


  // ---- student dashboard: toast alerts, "Pay" shortcuts, open a pop-up from the URL (#dlgNotifications …) ----
  if ($('[data-single-page]')) {
    $$('.content > .alert').forEach(a => {
      a.classList.add('toast');
      a.addEventListener('click', () => a.remove());
      setTimeout(() => a.classList.add('hide'), 6000);
      setTimeout(() => a.remove(), 6600);
    });
    $$('[data-pay-for]').forEach(b => b.addEventListener('click', e => {
      const sel = $('[data-pay-select]');
      if (!sel) return;
      e.preventDefault();
      sel.value = b.dataset.payFor;
      sel.dispatchEvent(new Event('change'));
      const card = document.getElementById('pay');
      if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    const d0 = location.hash.length > 1 ? document.getElementById(location.hash.slice(1)) : null;
    if (d0 && d0.tagName === 'DIALOG') d0.showModal();
  }

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