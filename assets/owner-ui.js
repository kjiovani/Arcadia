
(function () {
  // floating toggle
  const btn = document.createElement('button');
  btn.textContent = '✦ Edit UI';
  btn.style.cssText = `
    position:fixed; right:16px; bottom:16px; z-index:9999;
    padding:.7rem 1rem; border-radius:14px; border:1px solid #8b5cf6;
    background:linear-gradient(135deg,var(--arc-p1,#c9b3ff),var(--arc-p2,#9a78ff) 55%,var(--arc-p3,#7a5cff));
    color:#0f0f16; font-weight:900; letter-spacing:.2px; cursor:pointer;
  `;
  document.body.appendChild(btn);

  // drawer panel
  const pane = document.createElement('div');
  pane.style.cssText = `
    position:fixed; right:16px; bottom:72px; width:340px; max-height:70vh; overflow:auto;
    border:1px solid rgba(255,255,255,.12); border-radius:16px; padding:14px;
    background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));
    color:#eee; display:none; z-index:9999;
  `;
  pane.innerHTML = `
    <div style="font-weight:900;margin-bottom:8px">Owner UI Editor</div>
    <div style="display:grid;gap:12px">
      <div>
        <div style="font-weight:800;margin-bottom:6px">Ganti Logo</div>
        <input type="file" id="upLogo" accept=".png,.jpg,.jpeg,.webp" />
        <small>PNG transparan disarankan.</small>
      </div>
      <div>
        <div style="font-weight:800;margin:6px 0">Warna Brand</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
          <input type="color" id="c1" value="#c9b3ff">
          <input type="color" id="c2" value="#9a78ff">
          <input type="color" id="c3" value="#7a5cff">
        </div>
        <button id="saveColors" class="ui-btn" style="margin-top:8px">Simpan Warna</button>
      </div>
      <div>
        <div style="font-weight:800;margin:6px 0">Adjust Cover (klik gambar)</div>
        <small>Pilih gambar dengan kelas <code>.cover-adjustable</code>.</small>
        <div id="coverBox" style="display:none;margin-top:8px;border:1px dashed rgba(255,255,255,.2);padding:10px;border-radius:12px">
          <div style="font-weight:700">Fokus Cover</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px">
            <label>X <input type="range" id="fx" min="0" max="100"></label>
            <label>Y <input type="range" id="fy" min="0" max="100"></label>
          </div>
          <button id="saveCover" class="ui-btn" style="margin-top:8px">Simpan</button>
        </div>
      </div>
    </div>
  `;
  const styleBtn = `
    display:inline-flex;align-items:center;justify-content:center;gap:.4rem;
    padding:.6rem .9rem;border-radius:14px;border:1px solid rgba(255,255,255,.18);
    background:linear-gradient(135deg,var(--arc-p1,#c9b3ff),var(--arc-p2,#9a78ff) 55%,var(--arc-p3,#7a5cff));
    color:#0f0f16;font-weight:900;cursor:pointer
  `;
  pane.querySelectorAll('.ui-btn').forEach(b => b.style.cssText = styleBtn);
  document.body.appendChild(pane);

  btn.onclick = () => pane.style.display = pane.style.display === 'none' ? 'block' : 'none';

  // upload logo
  const upLogo = pane.querySelector('#upLogo');
  upLogo.addEventListener('change', async () => {
    if (!upLogo.files[0]) return;
    const fd = new FormData(); fd.append('act', 'set_logo'); fd.append('logo', upLogo.files[0]);
    const res = await fetch('/arcadia/public/admin/ui_api.php', { method: 'POST', body: fd });
    const j = await res.json(); alert(j.ok ? 'Logo diperbarui.' : 'Gagal: ' + j.msg); if (j.ok) location.reload();
  });

  // brand colors
  pane.querySelector('#saveColors').onclick = async () => {
    const fd = new FormData();
    fd.append('act', 'set_colors');
    fd.append('p1', pane.querySelector('#c1').value);
    fd.append('p2', pane.querySelector('#c2').value);
    fd.append('p3', pane.querySelector('#c3').value);
    const r = await fetch('/arcadia/public/admin/ui_api.php', { method: 'POST', body: fd });
    const j = await r.json(); alert(j.ok ? 'Warna disimpan.' : 'Gagal: ' + j.msg);
    if (j.ok) {
      document.documentElement.style.setProperty('--arc-p1', pane.querySelector('#c1').value);
      document.documentElement.style.setProperty('--arc-p2', pane.querySelector('#c2').value);
      document.documentElement.style.setProperty('--arc-p3', pane.querySelector('#c3').value);
    }
  };

  // cover focus pick
  let coverTarget = null, meta = { table: 'walkthroughs', id: 0 };
  document.addEventListener('click', e => {
    const img = e.target.closest('.cover-adjustable');
    if (!img) return;
    e.preventDefault();
    coverTarget = img;
    meta.table = img.dataset.table || 'walkthroughs';
    meta.id = parseInt(img.dataset.id || '0', 10);
    const [xp, yp] = (getComputedStyle(img).objectPosition || '50% 50%').split(' ');
    const toNum = s => Math.max(0, Math.min(100, parseInt(String(s).replace('%', '') || '50', 10)));
    pane.querySelector('#fx').value = toNum(xp);
    pane.querySelector('#fy').value = toNum(yp);
    pane.querySelector('#coverBox').style.display = 'block';
  });
  pane.querySelector('#fx').oninput = pane.querySelector('#fy').oninput = () => {
    if (!coverTarget) return;
    coverTarget.style.objectPosition = pane.querySelector('#fx').value + '% ' + pane.querySelector('#fy').value + '%';
  };
  pane.querySelector('#saveCover').onclick = async () => {
    if (!coverTarget) return;
    const fd = new FormData();
    fd.append('act', 'cover_focus');
    fd.append('table', meta.table);
    fd.append('id', meta.id);
    fd.append('fx', pane.querySelector('#fx').value);
    fd.append('fy', pane.querySelector('#fy').value);
    const r = await fetch('/arcadia/public/admin/ui_api.php', { method: 'POST', body: fd });
    const j = await r.json(); alert(j.ok ? 'Cover disimpan.' : 'Gagal: ' + j.msg);
  };
})();

