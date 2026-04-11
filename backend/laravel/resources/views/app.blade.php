<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Azveria App</title>
  <style>
    :root {
      --bg: #f6f1e5;
      --bg-alt: #e2ebf6;
      --panel: #ffffff;
      --text: #1f252c;
      --accent: #9b5a1e;
      --menu: #20354d;
      --menu-text: #f0f5fb;
      --danger: #8a1a1a;
    }
    body.dark {
      --bg: #121418;
      --bg-alt: #1e2229;
      --panel: #242a33;
      --text: #e9eef5;
      --accent: #d38f39;
      --menu: #0f141a;
      --menu-text: #dce8f6;
      --danger: #ff7d7d;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Trebuchet MS", Verdana, sans-serif; color: var(--text); background: radial-gradient(circle at top right, var(--bg-alt), var(--bg)); }
    .layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    .menu { background: var(--menu); color: var(--menu-text); padding: 16px; position: sticky; top: 0; height: 100vh; }
    .menu h2 { margin-top: 0; }
    .menu button, .menu .help-select { width: 100%; margin-top: 8px; padding: 10px; border-radius: 8px; border: 0; text-align: left; cursor: pointer; }
    .menu button { background: #314f72; color: #f8fbff; }
    .menu button.active { background: var(--accent); }
    .help-select { background: #314f72; color: #fff; }
    .main { padding: 18px; }
    .topbar { display: flex; justify-content: flex-end; gap: 8px; }
    .chip { background: var(--panel); padding: 8px 12px; border-radius: 999px; border: 1px solid #c8d0da; }
    .card { background: var(--panel); border-radius: 12px; padding: 14px; border: 1px solid #c9d1db; margin-top: 12px; }
    .twocol { display: grid; grid-template-columns: 1fr 300px; gap: 12px; }
    .list { max-height: 420px; overflow: auto; border: 1px solid #c9d1db; border-radius: 8px; padding: 8px; }
    .muted { color: #777; }
    .row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
    input, textarea, select, button { font: inherit; }
    input, textarea, select { width: 100%; padding: 8px; border: 1px solid #bfc8d2; border-radius: 8px; background: var(--panel); color: var(--text); }
    button.primary { background: var(--accent); color: #fff; border: 0; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
    .danger { color: var(--danger); }
    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .menu { height: auto; position: relative; }
      .twocol { grid-template-columns: 1fr; }
    }
    details summary { cursor:pointer; font-weight:bold; padding:4px 0; user-select:none; }
    details[open] summary { margin-bottom:6px; }
    .msg-wrap { display:flex; flex-direction:column; margin-bottom:8px; }
    .msg-wrap.own { align-items:flex-end; }
    .msg-wrap.other { align-items:flex-start; }
    .msg-bubble { max-width:80%; padding:8px 12px; border-radius:12px; line-height:1.4; word-break:break-word; }
    .msg-wrap.own  .msg-bubble { background:var(--accent); color:#fff; border-bottom-right-radius:3px; }
    .msg-wrap.other .msg-bubble { background:var(--bg-alt); border-bottom-left-radius:3px; }
    .msg-sender { font-size:11px; margin-bottom:3px; font-weight:bold; }
    .msg-wrap.own  .msg-sender { color:var(--accent); }
    .msg-wrap.other .msg-sender { color:#3a72b5; }
    .res-kv { display:flex; justify-content:space-between; padding:2px 6px; font-size:13px; }
    .res-kv:nth-child(even) { background:var(--bg-alt); border-radius:4px; }
  </style>
</head>
<body>
<div class="layout">
  <aside class="menu">
    <h2>Azveria</h2>
    <div id="nav"></div>
    <select id="helpSelect" class="help-select">
      <option value="">Help</option>
      <option value="about">About</option>
      <option value="docs">Documentation</option>
      <option value="reset-password">Reset Password</option>
      <option value="logout">Logout</option>
    </select>
  </aside>
  <main class="main">
    <div class="topbar" id="resourcesBar"></div>
    <section id="view"></section>
  </main>
</div>

<audio id="barkAudio" preload="auto" src="https://www.soundjay.com/animal/dog-bark-1.mp3"></audio>

<script>
const token = localStorage.getItem('azveria_token');
const user = JSON.parse(localStorage.getItem('azveria_user') || 'null');
if (!token || !user) window.location.href = '/';

const api = async (url, opts = {}) => {
  const res = await fetch(url, {
    ...opts,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      ...(opts.headers || {})
    }
  });
  if (res.status === 401) {
    localStorage.removeItem('azveria_token');
    localStorage.removeItem('azveria_user');
    window.location.href = '/';
    return;
  }
  return res;
};

let settings = { dog_bark_enabled: 0, theme: 'light', color_blind_mode: 'none' };
let ws = null;
let wsAuthToken = null;
let wsAuthTokenExpiresAt = 0;
const view = document.getElementById('view');
const nav = document.getElementById('nav');
const resourcesBar = document.getElementById('resourcesBar');

const playerMenu = ['Player', 'Announcements', 'Map', 'Chat', 'Other Nations', 'Shop', 'Settings'];
const adminMenu = ['Announcements', 'All Nations', 'New Accounts', 'Time Tracker', 'Map', 'Chat', 'Shop', 'Settings'];

function barkIfEnabled() {
  if (settings.dog_bark_enabled) {
    document.getElementById('barkAudio').play().catch(() => {});
  }
}

function setTheme(theme) {
  document.body.classList.toggle('dark', theme === 'dark');
}

function applyColorBlindMode(mode) {
  document.body.style.filter = mode === 'none' ? '' : 'contrast(1.05) saturate(0.9)';
}

function extractList(payload) {
  if (Array.isArray(payload)) return payload;
  if (payload && Array.isArray(payload.data)) return payload.data;
  return [];
}

async function getWsToken() {
  const now = Math.floor(Date.now() / 1000);
  if (wsAuthToken && wsAuthTokenExpiresAt - now > 30) {
    return wsAuthToken;
  }

  const res = await api('/api/ws/token', { method: 'POST' });
  if (!res || !res.ok) {
    return null;
  }

  const payload = await res.json();
  wsAuthToken = payload.token || null;
  wsAuthTokenExpiresAt = now + Number(payload.expires_in || 0);
  return wsAuthToken;
}

function renderNav() {
  const menu = user.role === 'admin' ? adminMenu : playerMenu;
  nav.innerHTML = menu.map(item => `<button data-item="${item}">${item}</button>`).join('');
  nav.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', () => {
      nav.querySelectorAll('button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      loadSection(btn.dataset.item);
      barkIfEnabled();
    });
  });
  nav.querySelector('button')?.click();
}

async function loadResources() {
  const res = await api('/api/me/resources');
  if (!res || !res.ok) return;
  const r = await res.json();
  const base = r.base || {};
  resourcesBar.innerHTML = `
    <div class="chip">Cow: ${Number(base.cow || 0).toFixed(0)}</div>
    <div class="chip">Wood: ${Number(base.wood || 0).toFixed(0)}</div>
    <div class="chip">Ore: ${Number(base.ore || 0).toFixed(0)}</div>
    <div class="chip">Food: ${Number(base.food || 0).toFixed(0)}</div>
  `;
}

// Human-readable names for all resource/currency cost keys
const RESOURCE_LABELS = {
  cow:'Cow', wood:'Wood', ore:'Ore', food:'Food',
  ref_M:'Metal', ref_RM:'Radioactive Metal', ref_FS:'Fovium Steel', ref_URM:'Uranium',
  ref_AD:'Aderite', ref_AM:'Antimatter', ref_DM:'Dark Matter', ref_DE:'Dark Energy',
  ref_H:'Hardwood', ref_TW:'Toxic Waste', ref_CB:'Carbon Battery', ref_MYC:'Mycelium',
  ref_SM:'Shroomium', ref_CFB:'Carbon Fiber', ref_BST:'Bulistium', ref_CGM:'Chaos Gem',
  ref_GBR:'Granola Bars', ref_CHB:'Chocolate Bar', ref_SR:'Sushi Rolls', ref_ZZ:'Zaza',
  ref_PZA:'Pizza', ref_IC:'Ice Cream', ref_WSH:'Whale Sushi', ref_SD:'StarDust', ref_NS:'Neutron StarDust',
  ref_K:'K', ref_RK:'RK', ref_DP:'DP',
  cur_GB:'Gobbo Bucks', cur_P:'Psycoin', cur_G:'Gold', cur_S:'Silver', cur_B:'Bronze',
  cur_X:'codebuX', cur_CD:'Credits', cur_FD:'Fairy Dust', cur_cheese:'Cheese',
  cur_SP:'SPores', cur_R:'Rupees', cur_MK:'MarKs',
};
function labelKey(k) { return RESOURCE_LABELS[k] || k; }
function formatCost(costJson) {
  try {
    const obj = typeof costJson === 'string' ? JSON.parse(costJson) : costJson;
    return Object.entries(obj || {}).map(([k,v]) => `${labelKey(k)}: <strong>${v}</strong>`).join(' &nbsp;+&nbsp; ') || 'Free';
  } catch { return costJson || 'Free'; }
}

// Ore / wood / food refined resource groups for display
const ORE_REFS   = {M:'Metal', RM:'Radioactive Metal', FS:'Fovium Steel', URM:'Uranium', AD:'Aderite', AM:'Antimatter', DM:'Dark Matter', DE:'Dark Energy'};
const WOOD_REFS  = {H:'Hardwood', TW:'Toxic Waste', CB:'Carbon Battery', MYC:'Mycelium', SM:'Shroomium', CFB:'Carbon Fiber', BST:'Bulistium', CGM:'Chaos Gem'};
const FOOD_REFS  = {GBR:'Granola Bars', CHB:'Chocolate Bar', SR:'Sushi Rolls', ZZ:'Zaza', PZA:'Pizza', IC:'Ice Cream', WSH:'Whale Sushi', SD:'StarDust', NS:'Neutron StarDust'};
const CURRENCIES = {GB:'Gobbo Bucks', P:'Psycoin', G:'Gold', S:'Silver', B:'Bronze', X:'codebuX', CD:'Credits', FD:'Fairy Dust', cheese:'Cheese', SP:'SPores', R:'Rupees', MK:'MarKs'};

function renderKVList(map, data) {
  return Object.entries(map).map(([k,label]) => `<div class="res-kv"><span>${label}</span><span>${Number(data[k]||0).toLocaleString()}</span></div>`).join('');
}

async function ensureWs() {
  if (ws || !window.WebSocket) return;
  ws = new WebSocket('ws://localhost:18081');
  ws.onclose = () => {
    ws = null;
  };
  ws.onerror = () => {
    ws = null;
  };
}

async function subscribeChannel(channel) {
  await ensureWs();
  if (!ws) return false;

  const authToken = await getWsToken();
  if (!authToken) return false;

  if (ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ type: 'subscribe', channel, token: authToken }));
    return true;
  }

  if (ws.readyState === WebSocket.CONNECTING) {
    ws.addEventListener('open', () => {
      ws.send(JSON.stringify({ type: 'subscribe', channel, token: authToken }));
    }, { once: true });
    return true;
  }

  return false;
}

async function loadSection(name) {
  view.innerHTML = '<div class="muted" style="padding:32px 16px;">Loading…</div>';
  if (name === 'Player') return loadPlayer();
  if (name === 'Announcements') return loadAnnouncements();
  if (name === 'New Accounts') return loadNewAccounts();
  if (name === 'Time Tracker') return loadTimeTracker();
  if (name === 'Map') return loadMap();
  if (name === 'Chat') return loadChat();
  if (name === 'Other Nations') return loadOtherNations();
  if (name === 'Shop') return loadShop();
  if (name === 'Settings') return loadSettings();
  if (name === 'All Nations') return loadAllNations();
}

async function loadPlayer() {
  const [dashRes, sqMilesRes] = await Promise.all([
    api('/api/me/dashboard'),
    api('/api/me/terrain-square-miles'),
  ]);
  const data = await dashRes.json();
  const sqMiles = await sqMilesRes.json();
  const terrainRows = Object.entries(sqMiles).length
    ? Object.entries(sqMiles).map(([k, v]) => `<span>${k.charAt(0).toUpperCase() + k.slice(1)}: <strong>${v} sq mi</strong></span>`).join(' &nbsp;|&nbsp; ')
    : 'No terrain data';

  const res = data.resources || {};
  const base = res.base || {};
  const refined = res.refined || {};
  const currencies = res.currencies || {};

  view.innerHTML = `
    <div class="card">
      <h2>Player Dashboard</h2>
      <div class="twocol">
        <div>
          <p><strong>Nation:</strong> ${data.nation.name}</p>
          <p><strong>Leader:</strong> ${data.nation.leader_name || '-'}</p>
          <p><strong>Alliance:</strong> ${data.nation.alliance_name || '-'}</p>
          <p><strong>Terrain (sq miles):</strong> ${terrainRows}</p>

          <details style="margin-top:12px;">
            <summary>Base Resources</summary>
            <div style="border:1px solid #c9d1db;border-radius:8px;padding:6px;margin-top:4px;">
              <div class="res-kv"><span>Cow</span><span>${Number(base.cow||0).toLocaleString()}</span></div>
              <div class="res-kv"><span>Wood</span><span>${Number(base.wood||0).toLocaleString()}</span></div>
              <div class="res-kv"><span>Ore</span><span>${Number(base.ore||0).toLocaleString()}</span></div>
              <div class="res-kv"><span>Food</span><span>${Number(base.food||0).toLocaleString()}</span></div>
            </div>
          </details>

          <details style="margin-top:8px;">
            <summary>Refined Resources</summary>
            <div style="border:1px solid #c9d1db;border-radius:8px;padding:6px;margin-top:4px;">
              <details open><summary style="font-size:12px;color:#666;">⛏ Ore-derived</summary>${renderKVList(ORE_REFS, refined)}</details>
              <details open style="margin-top:4px;"><summary style="font-size:12px;color:#666;">🌲 Wood-derived</summary>${renderKVList(WOOD_REFS, refined)}</details>
              <details open style="margin-top:4px;"><summary style="font-size:12px;color:#666;">🍞 Food-derived</summary>${renderKVList(FOOD_REFS, refined)}</details>
            </div>
          </details>

          <details style="margin-top:8px;">
            <summary>Currency</summary>
            <div style="border:1px solid #c9d1db;border-radius:8px;padding:6px;margin-top:4px;">
              ${renderKVList(CURRENCIES, currencies)}
            </div>
          </details>

          <label style="margin-top:12px;display:block;">About</label>
          <textarea id="aboutField" rows="4">${data.nation.about_text || ''}</textarea>
          <div class="row"><button class="primary" id="saveAbout">Save About</button><span id="aboutMsg" class="muted"></span></div>
        </div>
        <div>
          <h3>Units</h3>
          <div class="list">${data.units.owned.map(u => `<div>${u.display_name || u.custom_name || 'Unit'} x${u.qty}</div>`).join('') || '<div class="muted">None</div>'}
          <hr>${data.units.training.map(u => `<div>${u.display_name || u.custom_name || 'Unit'} x${u.qty} (training)</div>`).join('') || '<div class="muted">No training units</div>'}</div>
          <h3>Buildings</h3>
          <div class="list">${data.buildings.built.map(b => `<div>${b.display_name} L${b.level}</div>`).join('') || '<div class="muted">None</div>'}
          <hr>${data.buildings.in_progress.map(b => `<div>${b.display_name} (${b.status})</div>`).join('') || '<div class="muted">No construction</div>'}</div>
        </div>
      </div>
    </div>
  `;
  document.getElementById('saveAbout').onclick = async () => {
    const aboutText = document.getElementById('aboutField').value;
    const save = await api('/api/me/about', { method: 'PATCH', body: JSON.stringify({ about_text: aboutText }) });
    document.getElementById('aboutMsg').textContent = save.ok ? 'Saved' : 'Failed';
    barkIfEnabled();
  };
}

async function loadAnnouncements() {
  await subscribeChannel('announcements.global');
  const res = await api('/api/announcements');
  const payload = await res.json();
  const list = extractList(payload);
  const canPost = user.role === 'admin';
  view.innerHTML = `
    <div class="card">
      <h2>Announcements</h2>
      ${canPost ? '<textarea id="annText" rows="3" placeholder="Write announcement..."></textarea><div class="row"><button class="primary" id="sendAnn">Send</button></div>' : '<p class="muted">Read-only for players.</p>'}
      <div class="list" id="annList">${list.map(a => `<div><strong>${a.author_name}</strong>: ${a.body}</div>`).join('')}</div>
    </div>
  `;
  if (canPost) {
    document.getElementById('sendAnn').onclick = async () => {
      const body = document.getElementById('annText').value;
      await api('/api/announcements', { method: 'POST', body: JSON.stringify({ body }) });
      loadAnnouncements();
      barkIfEnabled();
    };
  }
}

async function loadMap() {
  const [layersRes, terrainRes, nationsRes] = await Promise.all([
    api('/api/maps/layers'),
    api('/api/me/terrain-square-miles'),
    api('/api/nations?per_page=200')
  ]);
  const layers = await layersRes.json();
  const terrain = await terrainRes.json();
  const nations = extractList(await nationsRes.json());

  const initial = layers.find(l => l.layer_type === 'main') || layers[0] || { image_path: '' };
  view.innerHTML = `
    <div class="card">
      <h2>Map</h2>
      <div class="twocol">
        <div>
          <div id="mapViewport" style="position:relative;overflow:hidden;border:1px solid #ccc;border-radius:8px;max-height:70vh;height:70vh;background:#111;">
            <img id="mapImage" src="/storage/${initial.image_path}" alt="Map" style="position:absolute;left:0;top:0;width:100%;height:100%;object-fit:contain;transform-origin:center center;user-select:none;cursor:grab;" />
            <button id="resetMapBtn" class="primary" style="display:none;position:absolute;right:8px;top:8px;z-index:2;">Reset View</button>
          </div>
        </div>
        <div>
          <h3>Layers</h3>
          ${layers.map(l => `<button class="primary layerBtn" data-path="${l.image_path}" style="display:block; width:100%; margin-bottom:8px;">${l.layer_type}</button>`).join('')}
          <h3>Terrain Sq Miles</h3>
          <label style="font-size:13px;">View Nation</label>
          <select id="mapNationSelect" style="margin-bottom:8px;">
            <option value="me">My Nation</option>
            ${nations.map(n => `<option value="${n.id}">${n.name}</option>`).join('')}
          </select>
          <div class="list" id="mapTerrainStats"></div>
          ${user.role === 'admin' ? `<h3>Admin Upload</h3>
            <label style="font-size:13px;">Layer Type</label>
            <select id="layerType" style="margin-bottom:6px;"><option value="main">Main</option><option value="terrain">Terrain</option><option value="political">Political</option></select>
            <label style="font-size:13px;">Image File</label>
            <input type="file" id="layerFile" accept="image/*" style="margin-bottom:6px;">
            <button class="primary" id="saveLayer">Upload Layer</button>
            <span id="uploadMsg" class="muted" style="display:block;margin-top:4px;"></span>` : ''}
        </div>
      </div>
    </div>
  `;

  const renderTerrainStats = (sqMiles) => {
    const total = Math.max(1, Object.values(sqMiles || {}).reduce((sum, val) => sum + Number(val || 0), 0));
    document.getElementById('mapTerrainStats').innerHTML = Object.entries(sqMiles || {}).map(([k, v]) => {
      const pct = ((Number(v || 0) / total) * 100).toFixed(1);
      return `<div>${k}: ${v} (${pct}%)</div>`;
    }).join('') || '<div class="muted">No data</div>';
  };
  renderTerrainStats(terrain);

  document.querySelectorAll('.layerBtn').forEach(btn => btn.onclick = () => {
    document.getElementById('mapImage').src = '/storage/' + btn.dataset.path;
    resetMapView();
    barkIfEnabled();
  });

  document.getElementById('mapNationSelect').onchange = async (e) => {
    if (e.target.value === 'me') {
      renderTerrainStats(terrain);
      return;
    }
    const detailRes = await api('/api/nations/' + e.target.value);
    const detail = await detailRes.json();
    const sqMiles = detail.terrain?.square_miles_json ? JSON.parse(detail.terrain.square_miles_json) : {};
    renderTerrainStats(sqMiles);
  };

  const mapViewport = document.getElementById('mapViewport');
  const mapImage = document.getElementById('mapImage');
  const resetMapBtn = document.getElementById('resetMapBtn');
  let mapScale = 1;
  let mapX = 0;
  let mapY = 0;
  let dragging = false;
  let pointerId = null;
  let startX = 0;
  let startY = 0;

  const applyMapTransform = () => {
    mapImage.style.transform = `translate(${mapX}px, ${mapY}px) scale(${mapScale})`;
    resetMapBtn.style.display = (mapScale !== 1 || mapX !== 0 || mapY !== 0) ? 'inline-block' : 'none';
  };

  const resetMapView = () => {
    mapScale = 1;
    mapX = 0;
    mapY = 0;
    applyMapTransform();
  };

  mapImage.addEventListener('dragstart', (e) => e.preventDefault());

  mapViewport.addEventListener('wheel', (e) => {
    e.preventDefault();
    const delta = e.deltaY < 0 ? 0.1 : -0.1;
    mapScale = Math.max(1, Math.min(6, mapScale + delta));
    if (mapScale === 1) {
      mapX = 0;
      mapY = 0;
    }
    applyMapTransform();
  }, { passive: false });

  mapViewport.addEventListener('pointerdown', (e) => {
    if (mapScale <= 1) {
      return;
    }
    dragging = true;
    pointerId = e.pointerId;
    mapViewport.setPointerCapture(pointerId);
    startX = e.clientX - mapX;
    startY = e.clientY - mapY;
    mapImage.style.cursor = 'grabbing';
    e.preventDefault();
  });

  mapViewport.addEventListener('pointermove', (e) => {
    if (!dragging || mapScale <= 1) {
      return;
    }
    mapX = e.clientX - startX;
    mapY = e.clientY - startY;
    applyMapTransform();
  });

  const releaseMapPointer = () => {
    dragging = false;
    if (pointerId !== null) {
      try { mapViewport.releasePointerCapture(pointerId); } catch {}
      pointerId = null;
    }
    mapImage.style.cursor = 'grab';
  };

  mapViewport.addEventListener('pointerup', releaseMapPointer);
  mapViewport.addEventListener('pointercancel', releaseMapPointer);
  mapViewport.addEventListener('pointerleave', () => {
    if (dragging && pointerId === null) {
      releaseMapPointer();
    }
  });

  resetMapBtn.onclick = resetMapView;

  if (user.role === 'admin') {
    document.getElementById('saveLayer').onclick = async () => {
      const layerType = document.getElementById('layerType').value;
      const fileInput = document.getElementById('layerFile');
      const msgEl = document.getElementById('uploadMsg');
      if (!fileInput.files.length) {
        msgEl.textContent = 'Please select a file first.';
        return;
      }
      msgEl.textContent = 'Uploading…';
      const formData = new FormData();
      formData.append('image_file', fileInput.files[0]);
      const res = await fetch(`/api/admin/maps/layers/${layerType}`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: formData,
      });
      msgEl.textContent = res.ok ? 'Uploaded!' : 'Upload failed.';
      if (res.ok) setTimeout(loadMap, 800);
      barkIfEnabled();
    };
  }
}

async function loadChat() {
  const [, chatsRes, playersRes] = await Promise.all([
    ensureWs(),
    api('/api/chats'),
    api('/api/players'),
  ]);
  const chatsPayload = await chatsRes.json();
  const chats = extractList(chatsPayload);
  const players = await playersRes.json();
  const firstChat = chats[0];

  const playerCheckboxes = players
    .filter(p => p.id !== user.id)
    .map(p => `<label style="display:flex;align-items:center;gap:6px;padding:4px 0;"><input type="checkbox" class="memberCheck" value="${p.id}"> ${p.name}</label>`)
    .join('');

  view.innerHTML = `
    <div class="card">
      <div class="twocol">
        <div>
          <h2 id="chatHeader" style="margin-top:0;">Chat</h2>
          <div id="chatView" class="list" style="min-height:200px;">Select a chat.</div>
          <div class="row" style="margin-top:8px;"><input id="chatMsg" placeholder="Message…"><button class="primary" id="sendMsg">Send</button></div>
        </div>
        <div>
          <div class="row"><input id="chatName" placeholder="New chat name"></div>
          <label style="font-size:13px;margin-top:8px;">Add players:</label>
          <div id="playerPickerList" style="max-height:160px;overflow:auto;border:1px solid #bfc8d2;border-radius:8px;padding:6px;margin-bottom:6px;">${playerCheckboxes || '<span class="muted">No other players</span>'}</div>
          <div class="row"><button class="primary" id="newChat">Create Chat</button></div>
          <h3>Chats</h3>
          <div class="list" id="chatList">${chats.map(c => `<div><button class="primary selectChat" data-id="${c.id}" data-name="${c.name.replace(/"/g,'&quot;')}" style="width:100%; margin-bottom:8px;">${c.name}${c.type === 'global' ? ' 🌐' : ''}</button></div>`).join('')}</div>
        </div>
      </div>
    </div>
  `;

  let activeChatId = firstChat ? firstChat.id : null;

  async function loadMessages(chatId, chatName) {
    if (!chatId) return;
    activeChatId = chatId;
    if (chatName) document.getElementById('chatHeader').textContent = chatName;
    await subscribeChannel(`chat.${chatId}`);
    const res = await api(`/api/chats/${chatId}/messages`);
    const messagesPayload = await res.json();
    const messages = extractList(messagesPayload);
    document.getElementById('chatView').innerHTML = messages.map(m => {
      const isOwn = m.sender_id === user.id;
      return `<div class="msg-wrap ${isOwn ? 'own' : 'other'}">
        <div class="msg-sender">${m.sender_name}</div>
        <div class="msg-bubble">${m.message.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
      </div>`;
    }).join('') || '<div class="muted">No messages</div>';
    const cv = document.getElementById('chatView');
    cv.scrollTop = cv.scrollHeight;
  }

  document.querySelectorAll('.selectChat').forEach(btn => btn.onclick = () => loadMessages(btn.dataset.id, btn.dataset.name));
  if (firstChat) loadMessages(firstChat.id, firstChat.name);

  document.getElementById('sendMsg').onclick = async () => {
    if (!activeChatId) return;
    const message = document.getElementById('chatMsg').value;
    await api(`/api/chats/${activeChatId}/messages`, { method: 'POST', body: JSON.stringify({ message }) });
    document.getElementById('chatMsg').value = '';
    const activeBtn = document.querySelector(`.selectChat[data-id="${activeChatId}"]`);
    loadMessages(activeChatId, activeBtn ? activeBtn.dataset.name : null);
    barkIfEnabled();
  };

  document.getElementById('newChat').onclick = async () => {
    const name = document.getElementById('chatName').value.trim();
    if (!name) return;
    const memberIds = Array.from(document.querySelectorAll('.memberCheck:checked')).map(el => Number(el.value));
    await api('/api/chats', { method: 'POST', body: JSON.stringify({ name, type: 'group', member_ids: memberIds }) });
    loadChat();
    barkIfEnabled();
  };
}

async function loadOtherNations() {
  view.innerHTML = `
    <div class="card">
      <h2>Other Nations</h2>
      <div class="twocol">
        <div id="nationDetail" class="list">Select a nation from the right.</div>
        <div>
          <input id="nationSearch" placeholder="Search nation or player">
          <div id="nationList" class="list" style="margin-top:8px;"></div>
        </div>
      </div>
    </div>
  `;

  const loadList = async (q = '') => {
    const res = await api('/api/nations?search=' + encodeURIComponent(q));
    const payload = await res.json();
    const list = extractList(payload);
    const box = document.getElementById('nationList');
    box.innerHTML = list.map(n => `<button class="primary nationBtn" data-id="${n.id}" style="display:block; width:100%; margin-bottom:8px;">${n.name} (${n.player_name || 'Unassigned'})</button>`).join('');
    document.querySelectorAll('.nationBtn').forEach(btn => btn.onclick = async () => {
      const detailRes = await api('/api/nations/' + btn.dataset.id);
      const d = await detailRes.json();
      document.getElementById('nationDetail').innerHTML = `
        <div><strong>${d.nation.name}</strong> (${d.nation.player_name || 'Unassigned'})</div>
        <div>Leader: ${d.nation.leader_name || '-'}</div>
        <div>Alliance: ${d.nation.alliance_name || '-'}</div>
        <div>About: ${d.nation.about_text || '-'}</div>
      `;
      barkIfEnabled();
    });
  };

  document.getElementById('nationSearch').addEventListener('input', e => loadList(e.target.value));
  loadList();
}

async function loadShop() {
  const isAdmin = user.role === 'admin';
  const calls = [api('/api/shop/categories'), api('/api/shop/items?per_page=300')];
  if (isAdmin) calls.push(api('/api/players'));
  const [catRes, itemRes, playersRes] = await Promise.all(calls);
  const cats = await catRes.json();
  const itemsPayload = await itemRes.json();
  const allItems = extractList(itemsPayload);
  const allPlayers = playersRes ? await playersRes.json() : [];

  view.innerHTML = `
    <div class="card">
      <h2>Shop</h2>
      <div id="shopSelectedCategory" class="chip" style="margin-bottom:10px;display:inline-flex;">No category selected</div>
      <div class="twocol">
        <div id="shopItems" class="list">Select a category.</div>
        <div id="shopCats" class="list">${cats.map(c => `<button class="primary catBtn" data-code="${c.code}" style="display:block; width:100%; margin-bottom:8px; opacity:0.7;">${c.display_name}</button>`).join('')}
          ${isAdmin ? `<hr><h3 style="margin:8px 0;">Add Item</h3>
            <label style="font-size:12px;">Category</label>
            <select id="newShopCategory">${cats.map(c => `<option value="${c.id}">${c.display_name}</option>`).join('')}</select>
            <label style="font-size:12px;">Code</label><input id="newShopCode" placeholder="unique_code">
            <label style="font-size:12px;">Name</label><input id="newShopName" placeholder="Display name">
            <label style="font-size:12px;">Description / Effects</label><textarea id="newShopDescription" rows="4"></textarea>
            <div class="row"><button class="primary" id="createShopItemBtn" style="width:100%;">Create Item</button></div>
            <span class="muted" id="createShopItemMsg"></span>` : ''}
        </div>
      </div>
    </div>
  `;

  // All valid cost key options for the dropdown
  const ALL_COST_KEYS = ['cow','wood','ore','food',
    'ref_M','ref_RM','ref_FS','ref_URM','ref_AD','ref_AM','ref_DM','ref_DE',
    'ref_H','ref_TW','ref_CB','ref_MYC','ref_SM','ref_CFB','ref_BST','ref_CGM',
    'ref_GBR','ref_CHB','ref_SR','ref_ZZ','ref_PZA','ref_IC','ref_WSH','ref_SD','ref_NS',
    'ref_K','ref_RK','ref_DP',
    'cur_GB','cur_P','cur_G','cur_S','cur_B','cur_X','cur_CD','cur_FD','cur_cheese','cur_SP','cur_R','cur_MK'];

  function costEditorRows(costObj, itemId) {
    const rows = Object.entries(costObj).map(([k,v]) => `
      <div class="row cost-row" style="align-items:center;gap:4px;">
        <select class="cost-key" style="flex:1;padding:4px;">
          ${ALL_COST_KEYS.map(ck => `<option value="${ck}" ${ck===k?'selected':''}>${labelKey(ck)}</option>`).join('')}
        </select>
        <input type="number" class="cost-val" value="${v}" style="width:80px;padding:4px;">
        <button type="button" class="danger" onclick="this.closest('.cost-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>
      </div>`).join('');
    return `<div id="cost-rows-${itemId}">${rows}</div>
      <button type="button" onclick="addCostRow(${itemId})" style="font-size:12px;margin-top:4px;background:none;border:1px solid #aaa;border-radius:6px;padding:3px 8px;cursor:pointer;">+ Add</button>`;
  }

  function jsonEditorRows(obj, itemId, editorIdPrefix) {
    const rows = Object.entries(obj || {}).map(([k, v]) => `
      <div class="row dyn-row" style="align-items:center;gap:4px;">
        <select class="dyn-key" style="flex:1;padding:4px;">
          ${ALL_COST_KEYS.map(ck => `<option value="${ck}" ${ck === k ? 'selected' : ''}>${labelKey(ck)}</option>`).join('')}
        </select>
        <input type="number" class="dyn-val" value="${v}" style="width:80px;padding:4px;">
        <button type="button" class="danger" onclick="this.closest('.dyn-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>
      </div>`).join('');
    return `<div id="${editorIdPrefix}-${itemId}">${rows}</div>
      <button type="button" onclick="addDynRow('${editorIdPrefix}', ${itemId})" style="font-size:12px;margin-top:4px;background:none;border:1px solid #aaa;border-radius:6px;padding:3px 8px;cursor:pointer;">+ Add</button>`;
  }

  window.addCostRow = (itemId) => {
    const container = document.getElementById('cost-rows-' + itemId);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'row cost-row';
    div.style.cssText = 'align-items:center;gap:4px;';
    div.innerHTML = `
      <select class="cost-key" style="flex:1;padding:4px;">
        ${ALL_COST_KEYS.map(ck => `<option value="${ck}">${labelKey(ck)}</option>`).join('')}
      </select>
      <input type="number" class="cost-val" value="1" style="width:80px;padding:4px;">
      <button type="button" class="danger" onclick="this.closest('.cost-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>`;
    container.appendChild(div);
  };

  window.addDynRow = (prefix, itemId) => {
    const container = document.getElementById(`${prefix}-${itemId}`);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'row dyn-row';
    div.style.cssText = 'align-items:center;gap:4px;';
    div.innerHTML = `
      <select class="dyn-key" style="flex:1;padding:4px;">
        ${ALL_COST_KEYS.map(ck => `<option value="${ck}">${labelKey(ck)}</option>`).join('')}
      </select>
      <input type="number" class="dyn-val" value="1" style="width:80px;padding:4px;">
      <button type="button" class="danger" onclick="this.closest('.dyn-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>`;
    container.appendChild(div);
  };

  function readCostRows(itemId) {
    const rows = document.querySelectorAll(`#cost-rows-${itemId} .cost-row`);
    const obj = {};
    rows.forEach(row => {
      const k = row.querySelector('.cost-key').value;
      const v = Number(row.querySelector('.cost-val').value);
      if (k && v) obj[k] = v;
    });
    return obj;
  }

  function readDynRows(prefix, itemId) {
    const rows = document.querySelectorAll(`#${prefix}-${itemId} .dyn-row`);
    const obj = {};
    rows.forEach(row => {
      const k = row.querySelector('.dyn-key').value;
      const v = Number(row.querySelector('.dyn-val').value);
      if (k && v) obj[k] = v;
    });
    return obj;
  }

  const renderItems = (category) => {
    const items = allItems.filter(i => i.category_code === category);
    const selectedCategory = cats.find(c => c.code === category);
    document.getElementById('shopSelectedCategory').textContent = selectedCategory ? `Selected: ${selectedCategory.display_name}` : 'No category selected';
    document.querySelectorAll('.catBtn').forEach(btn => {
      btn.style.opacity = btn.dataset.code === category ? '1' : '0.65';
      btn.style.boxShadow = btn.dataset.code === category ? '0 0 0 3px rgba(155,90,30,0.2)' : 'none';
    });
    document.getElementById('shopItems').innerHTML = items.map(i => {
      const costObj = (() => { try { return JSON.parse(i.cost_json || '{}'); } catch { return {}; } })();
      const maintenanceObj = (() => { try { return JSON.parse(i.maintenance_json || '{}'); } catch { return {}; } })();
      const yearlyObj = (() => { try { return JSON.parse(i.yearly_effect_json || '{}'); } catch { return {}; } })();
      const visArr  = (() => { try { return i.visibility_json ? JSON.parse(i.visibility_json) : null; } catch { return null; } })();

      if (isAdmin) {
        const playerCheckboxes = allPlayers.map(p =>
          `<label style="display:flex;align-items:center;gap:4px;font-size:12px;"><input type="checkbox" class="vis-check-${i.id}" value="${p.id}" ${visArr && visArr.includes(p.id) ? 'checked' : ''}> ${p.name}</label>`
        ).join('');
        return `
          <div class="card">
            <div style="display:flex;align-items:center;gap:8px;">
              <strong>${i.display_name}</strong>
              <span class="muted" style="font-size:12px;">${i.category_code}</span>
              <span class="muted" style="font-size:11px;">#${i.id}</span>
            </div>
            <div class="muted" style="font-size:12px;margin-top:4px;">${i.description_text || 'No description/effects text.'}</div>
            <div style="font-size:13px;margin:4px 0;">Cost: ${formatCost(costObj)}</div>
            <div style="font-size:13px;margin:4px 0;">Maintenance Cost: ${Object.keys(maintenanceObj).length ? formatCost(maintenanceObj) : 'None'}</div>
            <div style="font-size:13px;margin:4px 0;">Yearly Effect: ${Object.keys(yearlyObj).length ? formatCost(yearlyObj) : 'None'}</div>
            <details style="margin-top:6px;">
              <summary style="font-size:12px;">✏ Edit</summary>
              <div style="margin-top:8px;">
                <label style="font-size:12px;">Description / Effects</label>
                <textarea id="desc-${i.id}" rows="4">${i.description_text || ''}</textarea>
                <label style="font-size:12px;">Cost</label>
                ${costEditorRows(costObj, i.id)}
                <label style="font-size:12px;margin-top:8px;display:block;">Maintenance Cost</label>
                ${jsonEditorRows(maintenanceObj, i.id, 'maint-rows')}
                <label style="font-size:12px;margin-top:8px;display:block;">Yearly Effect</label>
                ${jsonEditorRows(yearlyObj, i.id, 'yearly-rows')}
                <label style="font-size:12px;margin-top:8px;display:block;">Visibility</label>
                <label style="font-size:12px;display:flex;align-items:center;gap:4px;margin-bottom:4px;">
                  <input type="checkbox" id="vis-global-${i.id}" ${visArr === null ? 'checked' : ''} onchange="document.getElementById('vis-players-${i.id}').style.display=this.checked?'none':'block'">
                  Global (all players)
                </label>
                <div id="vis-players-${i.id}" style="display:${visArr !== null ? 'block' : 'none'};max-height:120px;overflow:auto;border:1px solid #bbb;border-radius:6px;padding:6px;">
                  ${playerCheckboxes || '<span class="muted">No players</span>'}
                </div>
                <div class="row" style="margin-top:8px;">
                  <button class="primary editItem" data-id="${i.id}">Save</button>
                  <button class="primary deleteItem" data-id="${i.id}" style="background:#8a1a1a;">Delete</button>
                  <span class="muted" id="edit-msg-${i.id}"></span>
                </div>
              </div>
            </details>
          </div>`;
      } else {
        return `
          <div class="card">
            <div><strong>${i.display_name}</strong></div>
            <div class="muted" style="font-size:12px;">${i.description_text || ''}</div>
            <div class="muted" style="font-size:13px;">Cost: ${formatCost(costObj)}</div>
            ${Object.keys(maintenanceObj).length ? `<div class="muted" style="font-size:12px;">Yearly maintenance: ${formatCost(maintenanceObj)}</div>` : ''}
            <button class="primary buyItem" data-id="${i.id}" style="margin-top:6px;">Buy</button>
          </div>`;
      }
    }).join('') || '<div class="muted">No items</div>';

    document.querySelectorAll('.buyItem').forEach(btn => btn.onclick = async () => {
      const r = await api('/api/shop/buy', { method: 'POST', body: JSON.stringify({ item_id: Number(btn.dataset.id), quantity: 1 }) });
      if (r.ok) loadResources();
      barkIfEnabled();
    });

    document.querySelectorAll('.editItem').forEach(btn => btn.onclick = async () => {
      const id = Number(btn.dataset.id);
      const isGlobal = document.getElementById(`vis-global-${id}`)?.checked;
      let visibility_json = null;
      if (!isGlobal) {
        visibility_json = Array.from(document.querySelectorAll(`.vis-check-${id}:checked`)).map(el => Number(el.value));
      }
      const cost_json = readCostRows(id);
      const maintenance_json = readDynRows('maint-rows', id);
      const yearly_effect_json = readDynRows('yearly-rows', id);
      const description_text = document.getElementById(`desc-${id}`).value;
      const r = await api(`/api/admin/shop/items/${id}`, { method: 'PUT', body: JSON.stringify({ cost_json, maintenance_json, yearly_effect_json, description_text, visibility_json }) });
      const msgEl = document.getElementById(`edit-msg-${id}`);
      if (msgEl) msgEl.textContent = r.ok ? 'Saved' : 'Failed';
      barkIfEnabled();
    });

    document.querySelectorAll('.deleteItem').forEach(btn => btn.onclick = async () => {
      const id = Number(btn.dataset.id);
      const r = await api(`/api/admin/shop/items/${id}`, { method: 'DELETE' });
      if (r.ok) {
        const index = allItems.findIndex(item => item.id === id);
        if (index >= 0) allItems.splice(index, 1);
        renderItems(category);
      }
      barkIfEnabled();
    });
  };

  document.querySelectorAll('.catBtn').forEach(btn => btn.onclick = () => renderItems(btn.dataset.code));
  document.getElementById('createShopItemBtn')?.addEventListener('click', async () => {
    const payload = {
      category_id: Number(document.getElementById('newShopCategory').value),
      code: document.getElementById('newShopCode').value.trim(),
      display_name: document.getElementById('newShopName').value.trim(),
      description_text: document.getElementById('newShopDescription').value,
      cost_json: {},
    };
    const r = await api('/api/admin/shop/items', { method: 'POST', body: JSON.stringify(payload) });
    document.getElementById('createShopItemMsg').textContent = r.ok ? 'Created' : 'Failed';
    if (r.ok) {
      const reload = await api('/api/shop/items?per_page=300');
      const refreshed = extractList(await reload.json());
      allItems.splice(0, allItems.length, ...refreshed);
    }
    barkIfEnabled();
  });
}

async function loadNewAccounts() {
  const res = await api('/api/admin/new-account-defaults');
  const d = await res.json();
  const resources = d.resources || {};
  const refined = d.refined_resources || {};
  const currencies = d.currencies || {};
  const terrainSq = d.terrain_square_miles || {};
  const income = d.income_defaults || {};

  const makeRefInputs = (groupName, map) => `
    <details style="margin:6px 0;">
      <summary style="font-size:13px;">${groupName}</summary>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:4px;">
        ${Object.entries(map).map(([k,label]) => `<label style="font-size:12px;">${label}</label><input id="na-ref-${k}" type="number" value="${Number(refined[k] || 0)}">`).join('')}
      </div>
    </details>`;

  view.innerHTML = `
    <div class="card">
      <h2>New Accounts (Admin)</h2>
      <p class="muted" style="margin-top:0;">These values are applied when a player creates a new account and nation.</p>

      <label>Nation Name Template</label>
      <input id="na-nation-template" value="${d.nation_name_template || "{name}'s Nation"}">
      <label>Leader Name Template</label>
      <input id="na-leader-template" value="${d.leader_name_template || '{name}'}">
      <label>Alliance Name</label>
      <input id="na-alliance" value="${d.alliance_name || ''}">
      <label>Default Temporary Password</label>
      <input id="na-temp-password" value="${d.default_temp_password || 'password123'}">
      <label>About Text</label>
      <textarea id="na-about" rows="3">${d.about_text || ''}</textarea>

      <details open style="margin-top:8px;">
        <summary>Base Resources</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;">
          <label>Cow</label><input id="na-cow" type="number" value="${Number(resources.cow || 0)}">
          <label>Wood</label><input id="na-wood" type="number" value="${Number(resources.wood || 0)}">
          <label>Ore</label><input id="na-ore" type="number" value="${Number(resources.ore || 0)}">
          <label>Food</label><input id="na-food" type="number" value="${Number(resources.food || 0)}">
        </div>
      </details>

      <details style="margin-top:8px;">
        <summary>Refined Resources</summary>
        ${makeRefInputs('Ore-derived', ORE_REFS)}
        ${makeRefInputs('Wood-derived', WOOD_REFS)}
        ${makeRefInputs('Food-derived', FOOD_REFS)}
        <details style="margin:6px 0;">
          <summary style="font-size:13px;">Special</summary>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:4px;">
            <label style="font-size:12px;">K</label><input id="na-ref-K" type="number" value="${Number(refined.K || 0)}">
            <label style="font-size:12px;">RK</label><input id="na-ref-RK" type="number" value="${Number(refined.RK || 0)}">
            <label style="font-size:12px;">DP</label><input id="na-ref-DP" type="number" value="${Number(refined.DP || 0)}">
          </div>
        </details>
      </details>

      <details style="margin-top:8px;">
        <summary>Currencies</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:4px;">
          ${Object.entries(CURRENCIES).map(([k,label]) => `<label style="font-size:12px;">${label}</label><input id="na-cur-${k}" type="number" value="${Number(currencies[k] || 0)}">`).join('')}
        </div>
      </details>

      <details style="margin-top:8px;">
        <summary>Base Income Per Game Year</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;">
          <label>Cow</label><input id="na-income-cow" type="number" value="${Number(income.cow || 30)}">
          <label>Wood</label><input id="na-income-wood" type="number" value="${Number(income.wood || 3)}">
          <label>Ore</label><input id="na-income-ore" type="number" value="${Number(income.ore || 3)}">
          <label>Food</label><input id="na-income-food" type="number" value="${Number(income.food || 3)}">
          <label><input type="checkbox" id="na-rand-res" ${d.income_randomize_resources ? 'checked' : ''}> Randomize resources</label><span></span>
          <label>Resource Min</label><input id="na-rand-res-min" type="number" value="${Number(d.income_resource_min || 1)}">
          <label>Resource Max</label><input id="na-rand-res-max" type="number" value="${Number(d.income_resource_max || 5)}">
          <label><input type="checkbox" id="na-rand-cow" ${d.income_randomize_cow ? 'checked' : ''}> Randomize Cow</label><span></span>
          <label>Cow Min</label><input id="na-rand-cow-min" type="number" value="${Number(d.income_cow_min || 30)}">
          <label>Cow Max</label><input id="na-rand-cow-max" type="number" value="${Number(d.income_cow_max || 30)}">
        </div>
      </details>

      <details style="margin-top:8px;">
        <summary>Terrain Square Miles</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;">
          <label id="na-sq-label-grassland">Grassland</label><input id="na-sq-grassland" type="number" value="${Number(terrainSq.grassland || 0)}">
          <label id="na-sq-label-mountain">Mountain</label><input id="na-sq-mountain" type="number" value="${Number(terrainSq.mountain || 0)}">
          <label id="na-sq-label-freshwater">Freshwater</label><input id="na-sq-freshwater" type="number" value="${Number(terrainSq.freshwater || 0)}">
          <label id="na-sq-label-hills">Hills</label><input id="na-sq-hills" type="number" value="${Number(terrainSq.hills || 0)}">
          <label id="na-sq-label-desert">Desert</label><input id="na-sq-desert" type="number" value="${Number(terrainSq.desert || 0)}">
        </div>
      </details>

      <hr style="margin:12px 0;">
      <h3>Create Account</h3>
      <label>Username / Display Name</label>
      <input id="na-create-name" placeholder="New player name">
      <label>Email</label>
      <input id="na-create-email" type="email" placeholder="player@example.com">
      <label>Temporary Password</label>
      <input id="na-create-password" value="${d.default_temp_password || 'password123'}">
      <div class="row"><button class="primary" id="createManagedAccountBtn">Create Account</button><span class="muted" id="createManagedAccountMsg"></span></div>

      <div class="row" style="margin-top:10px;">
        <button class="primary" id="saveNewAccountDefaults">Save Defaults</button>
        <span class="muted" id="saveNewAccountDefaultsMsg"></span>
      </div>
    </div>
  `;

  const updateSqLabels = () => {
    const sq = {
      grassland: Number(document.getElementById('na-sq-grassland').value || 0),
      mountain: Number(document.getElementById('na-sq-mountain').value || 0),
      freshwater: Number(document.getElementById('na-sq-freshwater').value || 0),
      hills: Number(document.getElementById('na-sq-hills').value || 0),
      desert: Number(document.getElementById('na-sq-desert').value || 0),
    };
    const total = Math.max(1, Object.values(sq).reduce((sum, value) => sum + value, 0));
    Object.entries(sq).forEach(([key, value]) => {
      const pct = ((value / total) * 100).toFixed(1);
      const label = key.charAt(0).toUpperCase() + key.slice(1);
      document.getElementById(`na-sq-label-${key}`).textContent = `${label} (${pct}%)`;
    });
  };
  ['grassland', 'mountain', 'freshwater', 'hills', 'desert'].forEach(key => {
    document.getElementById(`na-sq-${key}`).addEventListener('input', updateSqLabels);
  });
  updateSqLabels();

  document.getElementById('saveNewAccountDefaults').onclick = async () => {
    const refinedResources = {};
    Object.keys(ORE_REFS).concat(Object.keys(WOOD_REFS)).concat(Object.keys(FOOD_REFS)).concat(['K', 'RK', 'DP']).forEach(k => {
      const el = document.getElementById('na-ref-' + k);
      if (el) refinedResources[k] = Number(el.value);
    });

    const currencyPayload = {};
    Object.keys(CURRENCIES).forEach(k => {
      const el = document.getElementById('na-cur-' + k);
      if (el) currencyPayload[k] = Number(el.value);
    });

    const payload = {
      nation_name_template: document.getElementById('na-nation-template').value,
      leader_name_template: document.getElementById('na-leader-template').value,
      alliance_name: document.getElementById('na-alliance').value,
      default_temp_password: document.getElementById('na-temp-password').value,
      about_text: document.getElementById('na-about').value,
      resources: {
        cow: Number(document.getElementById('na-cow').value),
        wood: Number(document.getElementById('na-wood').value),
        ore: Number(document.getElementById('na-ore').value),
        food: Number(document.getElementById('na-food').value),
      },
      refined_resources: refinedResources,
      currencies: currencyPayload,
      income_defaults: {
        cow: Number(document.getElementById('na-income-cow').value),
        wood: Number(document.getElementById('na-income-wood').value),
        ore: Number(document.getElementById('na-income-ore').value),
        food: Number(document.getElementById('na-income-food').value),
      },
      income_randomize_resources: document.getElementById('na-rand-res').checked,
      income_resource_min: Number(document.getElementById('na-rand-res-min').value),
      income_resource_max: Number(document.getElementById('na-rand-res-max').value),
      income_randomize_cow: document.getElementById('na-rand-cow').checked,
      income_cow_min: Number(document.getElementById('na-rand-cow-min').value),
      income_cow_max: Number(document.getElementById('na-rand-cow-max').value),
      terrain_square_miles: {
        grassland: Number(document.getElementById('na-sq-grassland').value),
        mountain: Number(document.getElementById('na-sq-mountain').value),
        freshwater: Number(document.getElementById('na-sq-freshwater').value),
        hills: Number(document.getElementById('na-sq-hills').value),
        desert: Number(document.getElementById('na-sq-desert').value),
      },
    };

    const save = await api('/api/admin/new-account-defaults', { method: 'PATCH', body: JSON.stringify(payload) });
    document.getElementById('saveNewAccountDefaultsMsg').textContent = save.ok ? 'Saved' : 'Failed';
    barkIfEnabled();
  };

  document.getElementById('createManagedAccountBtn').onclick = async () => {
    const payload = {
      name: document.getElementById('na-create-name').value.trim(),
      email: document.getElementById('na-create-email').value.trim(),
      password: document.getElementById('na-create-password').value,
    };
    const create = await api('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
    document.getElementById('createManagedAccountMsg').textContent = create.ok ? 'Created' : 'Failed';
    barkIfEnabled();
  };
}

async function loadSettings() {
  const res = await api('/api/me/settings');
  settings = await res.json();
  setTheme(settings.theme);
  applyColorBlindMode(settings.color_blind_mode);

  view.innerHTML = `
    <div class="card">
      <h2>Settings</h2>
      <label>Theme</label>
      <select id="theme"><option value="light">Light</option><option value="dark">Dark</option></select>
      <label>Color Blind Mode</label>
      <select id="cb"><option value="none">None</option><option value="protanopia">Protanopia</option><option value="deuteranopia">Deuteranopia</option><option value="tritanopia">Tritanopia</option></select>
      <label><input type="checkbox" id="dog"> Dog Bark on actions</label>
      <div class="row"><button class="primary" id="saveSettings">Save</button></div>
    </div>
  `;

  document.getElementById('theme').value = settings.theme;
  document.getElementById('cb').value = settings.color_blind_mode;
  document.getElementById('dog').checked = !!settings.dog_bark_enabled;

  document.getElementById('saveSettings').onclick = async () => {
    const payload = {
      theme: document.getElementById('theme').value,
      color_blind_mode: document.getElementById('cb').value,
      dog_bark_enabled: document.getElementById('dog').checked,
    };
    await api('/api/me/settings', { method: 'PATCH', body: JSON.stringify(payload) });
    settings = payload;
    setTheme(settings.theme);
    applyColorBlindMode(settings.color_blind_mode);
    barkIfEnabled();
  };
}

function loadForcedPasswordReset() {
  nav.innerHTML = '';
  view.innerHTML = `
    <div class="card" style="max-width:560px;">
      <h2>Password Reset Required</h2>
      <p class="muted">This account was created or reset with a temporary password. You must choose a new password before accessing the game.</p>
      <label>Current Password</label>
      <input id="forcedCurrentPassword" type="password">
      <label>New Password</label>
      <input id="forcedNewPassword" type="password">
      <div class="row"><button class="primary" id="forcedResetBtn">Update Password</button><span class="muted" id="forcedResetMsg"></span></div>
    </div>
  `;

  document.getElementById('forcedResetBtn').onclick = async () => {
    const current_password = document.getElementById('forcedCurrentPassword').value;
    const new_password = document.getElementById('forcedNewPassword').value;
    const res = await api('/api/auth/password', { method: 'PATCH', body: JSON.stringify({ current_password, new_password }) });
    if (res.ok) {
      user.force_password_reset = false;
      localStorage.setItem('azveria_user', JSON.stringify(user));
      renderNav();
    } else {
      document.getElementById('forcedResetMsg').textContent = 'Failed';
    }
  };
}

async function loadAllNations() {
  const [res, notificationsRes] = await Promise.all([api('/api/admin/nations'), api('/api/admin/notifications')]);
  const payload = await res.json();
  const nations = extractList(payload);
  const notifications = await notificationsRes.json();

  view.innerHTML = `
    <div class="card">
      <h2>All Nations (Admin)</h2>
      <div class="twocol">
        <div>
          <div id="adminNationEditor" class="list" style="margin-bottom:12px;">Select nation to edit.</div>
          <div class="list" id="adminNotifications">
            <h3 style="margin-top:0;">Notifications</h3>
            ${notifications.map(n => `<div style="border-bottom:1px solid #ddd;padding:8px 0;">
              <div style="display:flex;justify-content:space-between;gap:8px;">
                <strong>${n.is_read ? 'Read' : 'Unread'}: ${n.title}</strong>
                <button class="primary deleteNotif" data-id="${n.id}" style="background:#8a1a1a;">Delete</button>
              </div>
              <div class="muted" style="font-size:12px;">${n.created_at || ''}</div>
              <div style="font-size:13px;white-space:pre-wrap;">${n.body}</div>
            </div>`).join('') || '<div class="muted">No notifications</div>'}
          </div>
        </div>
        <div>
          <input id="newPlaceholder" placeholder="New placeholder nation name">
          <button class="primary" id="createPlaceholder" style="margin-top:8px; width:100%;">Create Placeholder Nation</button>
          <div class="list" id="adminNationList" style="margin-top:8px;">${nations.map(n => `<button class="primary editNationBtn" data-id="${n.id}" style="display:block; width:100%; margin-bottom:8px;">${n.name}</button>`).join('')}</div>
        </div>
      </div>
    </div>
  `;

  const openEditor = async (id) => {
    const detailRes = await api('/api/nations/' + id);
    const d = await detailRes.json();

    // Parse extra_json for refined + currencies
    let extra = {};
    try { extra = JSON.parse(d.resources?.extra_json || '{}'); } catch {}
    const ref = extra.refined || {};
    const cur = extra.currencies || {};
    const income = extra.income || { cow: 30, wood: 3, ore: 3, food: 3 };
    let sqMiles = {};
    try { sqMiles = d.terrain?.square_miles_json ? JSON.parse(d.terrain.square_miles_json) : {}; } catch {}

    const makeRefInput = (group, map) => `
      <details style="margin:6px 0;">
        <summary style="font-size:13px;">${group}</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:4px;">
          ${Object.entries(map).map(([k,label]) => `<label style="font-size:12px;">${label}</label><input id="ref-${k}" type="number" value="${ref[k]||0}" style="padding:4px;">`).join('')}
        </div>
      </details>`;

    const makeCurInput = () => `
      <details style="margin:6px 0;">
        <summary style="font-size:13px;">Currency</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:4px;">
          ${Object.entries(CURRENCIES).map(([k,label]) => `<label style="font-size:12px;">${label}</label><input id="cur-${k}" type="number" value="${cur[k]||0}" style="padding:4px;">`).join('')}
        </div>
      </details>`;

    document.getElementById('adminNationEditor').innerHTML = `
      <label>Name</label><input id="nName" value="${d.nation.name}">
      <label>Leader</label><input id="nLeader" value="${d.nation.leader_name || ''}">
      <label>Alliance</label><input id="nAlliance" value="${d.nation.alliance_name || ''}">
      <label>About</label><textarea id="nAbout">${d.nation.about_text || ''}</textarea>
      <details style="margin-top:8px;" open>
        <summary>Base Resources</summary>
        <label>Cow</label><input id="nCow" type="number" value="${d.resources?.cow || 0}">
        <label>Wood</label><input id="nWood" type="number" value="${d.resources?.wood || 0}">
        <label>Ore</label><input id="nOre" type="number" value="${d.resources?.ore || 0}">
        <label>Food</label><input id="nFood" type="number" value="${d.resources?.food || 0}">
      </details>
      <details style="margin-top:8px;">
        <summary>Income Per Game Year</summary>
        <label>Cow</label><input id="nIncomeCow" type="number" value="${income.cow || 30}">
        <label>Wood</label><input id="nIncomeWood" type="number" value="${income.wood || 3}">
        <label>Ore</label><input id="nIncomeOre" type="number" value="${income.ore || 3}">
        <label>Food</label><input id="nIncomeFood" type="number" value="${income.food || 3}">
      </details>
      <details style="margin-top:8px;">
        <summary>Terrain Square Miles</summary>
        <label>Grassland</label><input id="nSqGrassland" type="number" value="${sqMiles.grassland || 0}">
        <label>Mountain</label><input id="nSqMountain" type="number" value="${sqMiles.mountain || 0}">
        <label>Freshwater</label><input id="nSqFreshwater" type="number" value="${sqMiles.freshwater || 0}">
        <label>Hills</label><input id="nSqHills" type="number" value="${sqMiles.hills || 0}">
        <label>Desert</label><input id="nSqDesert" type="number" value="${sqMiles.desert || 0}">
      </details>
      <details style="margin-top:8px;">
        <summary>Refined Resources</summary>
        ${makeRefInput('⛏ Ore-derived', ORE_REFS)}
        ${makeRefInput('🌲 Wood-derived', WOOD_REFS)}
        ${makeRefInput('🍞 Food-derived', FOOD_REFS)}
      </details>
      ${makeCurInput()}
      <div class="row"><button class="primary" id="saveNation">Save Nation</button><span class="muted" id="saveNationMsg"></span></div>

      <hr style="margin:12px 0;">
      <h3 style="margin:0 0 8px;">Add Unit</h3>
      <div id="unitCatArea" class="muted">Loading units…</div>
      <div class="row" style="margin-top:6px;"><button class="primary" id="addUnitBtn">Add Unit</button><span class="muted" id="addUnitMsg"></span></div>
    `;

    // Load unit catalog for the admin unit-add form
    document.getElementById('unitCatArea').innerHTML = `
      <label style="font-size:13px;">Unit Catalog ID</label>
      <input id="unitCatId" type="number" placeholder="e.g. 6" style="margin-bottom:4px;">
      <label style="font-size:13px;">Quantity</label>
      <input id="unitQty" type="number" value="1">
    `;

    document.getElementById('saveNation').onclick = async () => {
      const refined_resources = {};
      Object.keys(ORE_REFS).concat(Object.keys(WOOD_REFS)).concat(Object.keys(FOOD_REFS)).forEach(k => {
        const el = document.getElementById('ref-' + k);
        if (el) refined_resources[k] = Number(el.value);
      });
      const currencies = {};
      Object.keys(CURRENCIES).forEach(k => {
        const el = document.getElementById('cur-' + k);
        if (el) currencies[k] = Number(el.value);
      });
      const payload = {
        name: document.getElementById('nName').value,
        leader_name: document.getElementById('nLeader').value,
        alliance_name: document.getElementById('nAlliance').value,
        about_text: document.getElementById('nAbout').value,
        resources: {
          cow: Number(document.getElementById('nCow').value),
          wood: Number(document.getElementById('nWood').value),
          ore: Number(document.getElementById('nOre').value),
          food: Number(document.getElementById('nFood').value),
        },
        income: {
          cow: Number(document.getElementById('nIncomeCow').value),
          wood: Number(document.getElementById('nIncomeWood').value),
          ore: Number(document.getElementById('nIncomeOre').value),
          food: Number(document.getElementById('nIncomeFood').value),
        },
        terrain_square_miles: {
          grassland: Number(document.getElementById('nSqGrassland').value),
          mountain: Number(document.getElementById('nSqMountain').value),
          freshwater: Number(document.getElementById('nSqFreshwater').value),
          hills: Number(document.getElementById('nSqHills').value),
          desert: Number(document.getElementById('nSqDesert').value),
        },
        refined_resources,
        currencies,
      };
      const save = await api('/api/admin/nations/' + id, { method: 'PUT', body: JSON.stringify(payload) });
      document.getElementById('saveNationMsg').textContent = save.ok ? 'Saved' : 'Failed';
      barkIfEnabled();
    };

    document.getElementById('addUnitBtn').onclick = async () => {
      const unitCatalogId = Number(document.getElementById('unitCatId').value);
      const qty = Number(document.getElementById('unitQty').value);
      if (!unitCatalogId) { document.getElementById('addUnitMsg').textContent = 'Enter a unit catalog ID'; return; }
      const r = await api('/api/admin/nations/' + id + '/units', { method: 'POST', body: JSON.stringify({ unit_catalog_id: unitCatalogId, qty, status: 'owned' }) });
      document.getElementById('addUnitMsg').textContent = r.ok ? 'Added!' : 'Failed';
      barkIfEnabled();
    };
  };

  document.querySelectorAll('.editNationBtn').forEach(btn => btn.onclick = () => openEditor(btn.dataset.id));
  document.querySelectorAll('.deleteNotif').forEach(btn => btn.onclick = async () => {
    const del = await api('/api/admin/notifications/' + btn.dataset.id, { method: 'DELETE' });
    if (del.ok) loadAllNations();
  });
  document.getElementById('createPlaceholder').onclick = async () => {
    const name = document.getElementById('newPlaceholder').value;
    await api('/api/admin/nations', { method: 'POST', body: JSON.stringify({ name }) });
    loadAllNations();
    barkIfEnabled();
  };
}

async function loadTimeTracker() {
  const res = await api('/api/admin/time-tracker');
  const d = await res.json();
  view.innerHTML = `
    <div class="card">
      <h2>Time Tracker</h2>
      <div class="list">
        <div><strong>Started:</strong> ${d.started_at}</div>
        <div><strong>Seconds Per In-Game Year:</strong> ${d.seconds_per_year}</div>
        <div><strong>Elapsed Years:</strong> ${d.elapsed_years}</div>
        <div><strong>Processed Years:</strong> ${d.processed_years}</div>
        <div><strong>Current Game Year:</strong> ${d.current_game_year}</div>
        <div><strong>Processed This Load:</strong> ${d.processed_now}</div>
        <div class="muted" style="margin-top:8px;">Every real-world two weeks equals one in-game year. Opening this page processes any elapsed unprocessed years.</div>
      </div>
    </div>
  `;
}

async function init() {
  const [settingsRes] = await Promise.all([
    api('/api/me/settings'),
    loadResources(),
  ]);
  if (settingsRes && settingsRes.ok) {
    settings = await settingsRes.json();
    setTheme(settings.theme);
    applyColorBlindMode(settings.color_blind_mode);
  }
  if (user.force_password_reset) {
    loadForcedPasswordReset();
    return;
  }
  renderNav();
}

const helpSelect = document.getElementById('helpSelect');
helpSelect.addEventListener('change', async (e) => {
  if (e.target.value === 'about') {
    const res = await api('/api/meta/about');
    const d = await res.json();
    alert(`Website: ${d.website_version}\nGame: ${d.game_version}\nAdmin: ${d.admin}`);
  }
  if (e.target.value === 'docs') {
    window.open('https://github.com/TheBuilderHero/AzveriaOnline/blob/main/READMEPLAYER', '_blank');
  }
  if (e.target.value === 'reset-password') {
    if (user.role === 'admin') {
      const mode = window.prompt('Type "self" to reset your own password or enter another user ID to reset that user password.', 'self');
      if (mode) {
        if (mode === 'self') {
          const currentPassword = window.prompt('Current password');
          const newPassword = window.prompt('New password (min 8 characters)');
          if (currentPassword && newPassword) {
            await api('/api/auth/password', { method: 'PATCH', body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }) });
          }
        } else {
          const newPassword = window.prompt('New temporary password for user #' + mode);
          if (newPassword) {
            await api('/api/admin/users/' + mode + '/password', { method: 'PATCH', body: JSON.stringify({ new_password: newPassword, force_password_reset: true }) });
          }
        }
      }
    } else {
      const currentPassword = window.prompt('Current password');
      const newPassword = window.prompt('New password (min 8 characters)');
      if (currentPassword && newPassword) {
        await api('/api/auth/password', { method: 'PATCH', body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }) });
      }
    }
  }
  if (e.target.value === 'logout') {
    await api('/api/auth/logout', { method: 'POST' });
    localStorage.removeItem('azveria_token');
    localStorage.removeItem('azveria_user');
    window.location.href = '/';
  }
  helpSelect.value = '';
});

init();
</script>
</body>
</html>
