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
      --muted: #666666;
      --border: #bfc8d2;
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
      --muted: #b9c4d2;
      --border: #4b5663;
      --accent: #d38f39;
      --menu: #0f141a;
      --menu-text: #dce8f6;
      --danger: #ff7d7d;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Trebuchet MS", Verdana, sans-serif; color: var(--text); background: radial-gradient(circle at top right, var(--bg-alt), var(--bg)); }
    .layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    .menu { background: var(--menu); color: var(--menu-text); padding: 16px; position: sticky; top: 0; height: 100vh; }
    .menu-brand {
      display: flex;
      align-items: center;
      min-height: 40px;
      padding-left: 54px;
      margin-bottom: 8px;
    }
    #menuToggle {
      position: fixed;
      top: 10px;
      left: 10px;
      z-index: 1100;
      background: #314f72;
      color: #fff;
      border: 0;
      border-radius: 8px;
      width: 40px;
      height: 40px;
      cursor: pointer;
      font-size: 20px;
      line-height: 1;
    }
    body.menu-collapsed .layout { grid-template-columns: 0 1fr; }
    body.menu-collapsed .menu { width: 0; padding: 0; overflow: hidden; }
    .menu h2 { margin: 0; }
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
      .menu-brand { padding-left: 54px; }
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
    body.font-fun { font-family: "Comic Sans MS", "Trebuchet MS", cursive; }
    body.font-cool-person { font-family: "Papyrus", "Brush Script MT", fantasy; letter-spacing: 0.02em; }
    .announcement-card {
      border: 1px solid #c9d1db;
      border-radius: 12px;
      padding: 10px;
      margin-bottom: 10px;
      background: linear-gradient(135deg, var(--panel), var(--bg-alt));
    }
    .announcement-author { font-weight: 700; font-size: 13px; }
    .announcement-body { margin-top: 6px; white-space: pre-wrap; line-height: 1.5; }
    .zoom-control {
      position: absolute;
      right: 10px;
      top: 48px;
      z-index: 2;
      background: rgba(255,255,255,0.85);
      border-radius: 8px;
      padding: 6px;
      border: 1px solid #bfc8d2;
      width: 42px;
      display: flex;
      justify-content: center;
    }
    .zoom-control input { writing-mode: vertical-lr; direction: rtl; width: 24px; height: 160px; }
    .notify-panel { border: 1px solid #c9d1db; border-radius: 10px; padding: 10px; background: linear-gradient(180deg, var(--panel), var(--bg-alt)); }
    .notify-item { border: 1px solid #d7dee7; border-radius: 10px; padding: 10px; margin-bottom: 8px; background: var(--panel); }
    .notify-head { display:flex; justify-content:space-between; gap:8px; align-items:flex-start; }
    .notify-type { font-size:11px; background:#314f72; color:#fff; border-radius:999px; padding:2px 8px; }
    .setting-group { border: 1px solid #c9d1db; border-radius: 10px; padding: 10px; margin-top: 10px; }
    .map-shell { display:grid; grid-template-columns: 1fr 300px; gap:12px; }
    .map-stage-wrap { position:relative; border:1px solid #c9d1db; border-radius:12px; background:#0f1520; height:72vh; min-height:480px; overflow:hidden; }
    .map-stage-controls { position:absolute; left:12px; right:12px; top:10px; display:flex; justify-content:space-between; align-items:flex-start; z-index:4; pointer-events:none; }
    .map-stage-controls > * { pointer-events:auto; }
    .map-top-tools { display:flex; gap:8px; align-items:center; }
    .map-canvas { width:100%; height:100%; display:block; cursor:grab; }
    .map-canvas.dragging { cursor:grabbing; }
    .map-floating { position:absolute; z-index:5; background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:10px; padding:8px; backdrop-filter: blur(2px); }
    .map-info-box { left:12px; top:56px; width:260px; }
    .map-bottom-center { position:absolute; left:50%; bottom:10px; transform:translateX(-50%); z-index:5; background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:10px; padding:8px 10px; min-width:240px; }
    .map-bottom-right { position:absolute; right:10px; bottom:10px; z-index:5; display:flex; gap:8px; align-items:flex-end; }
    .map-bottom-left { position:absolute; left:10px; bottom:10px; z-index:5; min-width:260px; }
    .map-scroll-list { max-height:200px; overflow:auto; border:1px solid #c9d1db; border-radius:8px; padding:8px; background:var(--panel); }
    .map-type-item { display:block; width:100%; text-align:left; margin-bottom:6px; }
    .map-type-item.active { outline:2px solid var(--accent); }
    .map-side-panel { border:1px solid #c9d1db; border-radius:10px; padding:10px; background:var(--panel); }
    .map-side-panel h3 { margin:0 0 8px 0; }
    .map-admin-actions { display:flex; gap:8px; justify-content:flex-end; margin-bottom:8px; }
    .map-right-external { display:flex; justify-content:flex-end; gap:8px; margin-bottom:8px; }
    .terrain-color-pop { width:280px; max-height:280px; overflow:auto; }
    .terrain-color-row { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:4px; }
    .terrain-color-row input[type="color"] { width:48px; min-width:48px; height:30px; padding:0; border:1px solid #bfc8d2; border-radius:6px; background:transparent; }
    .map-editor-toolbar { display:flex; gap:8px; align-items:center; background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:10px; padding:8px; }
    .map-editor-header { display:flex; justify-content:flex-start; gap:10px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px; }
    .map-small-label { font-size:12px; color:var(--muted); }
    @media (max-width: 1100px) {
      .map-shell { grid-template-columns: 1fr; }
      .map-stage-wrap { height:62vh; min-height:420px; }
      .map-right-external { justify-content:flex-start; }
      .map-editor-header { flex-direction:column; }
    }
  </style>
</head>
<body>
<button id="menuToggle" title="Toggle menu">&#9776;</button>
<div class="layout">
  <aside class="menu">
    <div class="menu-brand"><h2>Azveria</h2></div>
    <div id="nav"></div>
    <select id="helpSelect" class="help-select">
      <option value="">Help</option>
      <option value="about">About</option>
      <option value="docs">Documentation</option>
      <option value="report-issue">Report Issue</option>
      <option value="reset-password">Reset Password</option>
      <option value="logout">Logout</option>
    </select>
  </aside>
  <main class="main">
    <div class="topbar" id="resourcesBar"></div>
    <section id="view"></section>
  </main>
</div>

<script>
const token = localStorage.getItem('azveria_token');
const user = JSON.parse(localStorage.getItem('azveria_user') || 'null');
if (!token || !user) window.location.href = '/';

const api = async (url, opts = {}) => {
  const controller = new AbortController();
  const headers = { 'Authorization': `Bearer ${token}`, ...(opts.headers || {}) };
  if (!(opts.body instanceof FormData) && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }
  const timeout = window.setTimeout(() => controller.abort(), opts.timeout ?? 20000);

  try {
    const res = await fetch(url, {
      ...opts,
      headers,
      signal: opts.signal || controller.signal,
    });
    if (res.status === 401) {
      localStorage.removeItem('azveria_token');
      localStorage.removeItem('azveria_user');
      window.location.href = '/';
      return null;
    }
    return res;
  } catch (error) {
    if (error?.name === 'AbortError') {
      throw new Error('The server took too long to respond. Please try again.');
    }
    throw new Error('The server could not be reached. Check the deployment and try again.');
  } finally {
    window.clearTimeout(timeout);
  }
};

let settings = { dog_bark_enabled: 0, theme: 'light', color_blind_mode: 'none', font_mode: 'normal', alliance_color_overrides: {} };
let ws = null;
let wsAuthToken = null;
let wsAuthTokenExpiresAt = 0;
let activeSectionName = '';
const view = document.getElementById('view');
const nav = document.getElementById('nav');
const resourcesBar = document.getElementById('resourcesBar');

const playerMenu = ['Player', 'Announcements', 'Map', 'Combat', 'Chat', 'Other Nations', 'Shop', 'Settings'];
const adminMenu = ['Announcements', 'All Nations', 'Notifications', 'Game Information and Rules', 'New Accounts', 'Time Tracker', 'Map', 'Combat', 'Chat', 'Shop', 'Settings'];

const goofyAudio = new Audio('https://actions.google.com/sounds/v1/cartoon/boing.ogg');
goofyAudio.preload = 'auto';

function barkIfEnabled() {
  if (!settings.dog_bark_enabled) return;
  try {
    goofyAudio.currentTime = 0;
    goofyAudio.play().catch(() => {});
  } catch {}
}

function setTheme(theme) {
  document.body.classList.toggle('dark', theme === 'dark');
}

function setFontMode(mode) {
  document.body.classList.remove('font-fun', 'font-cool-person');
  if (mode === 'fun') {
    document.body.classList.add('font-fun');
  }
  if (mode === 'cool_person') {
    document.body.classList.add('font-cool-person');
  }
}

function applyColorBlindMode(mode) {
  document.body.style.filter = mode === 'none' ? '' : 'contrast(1.05) saturate(0.9)';
}

function extractList(payload) {
  if (Array.isArray(payload)) return payload;
  if (payload && Array.isArray(payload.data)) return payload.data;
  return [];
}

function safeJsonParse(value, fallback = null) {
  if (value === null || value === undefined) return fallback;
  if (typeof value !== 'string') return value;
  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
}

function toFiniteNumber(value, fallback = 0) {
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : fallback;
  }
  if (typeof value === 'string') {
    const cleaned = value.trim().replace(/^"+|"+$/g, '');
    const parsed = Number(cleaned);
    return Number.isFinite(parsed) ? parsed : fallback;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function normalizeTerrainSquareMiles(raw) {
  const parsed = safeJsonParse(raw, raw) || {};
  const asObject = typeof parsed === 'string' ? (safeJsonParse(parsed, {}) || {}) : parsed;
  const source = (asObject && typeof asObject === 'object') ? asObject : {};
  const sea = source.seafront ?? source.sea_front ?? source.seaFront ?? 0;
  return {
    grassland: toFiniteNumber(source.grassland, 0),
    mountain: toFiniteNumber(source.mountain, 0),
    freshwater: toFiniteNumber(source.freshwater, 0),
    hills: toFiniteNumber(source.hills, 0),
    desert: toFiniteNumber(source.desert, 0),
    seafront: toFiniteNumber(sea, 0),
  };
}

function labelTerrainKey(key) {
  const map = {
    grassland: 'Grassland',
    mountain: 'Mountain',
    freshwater: 'Freshwater',
    hills: 'Hills',
    desert: 'Desert',
    seafront: 'Sea Front',
  };
  return map[key] || key.replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
}

function renderLoadingState(title) {
  view.innerHTML = `<div class="card"><h2>${title}</h2><p class="muted">Loading…</p></div>`;
}

function renderSectionError(title, error) {
  console.error(error);
  view.innerHTML = `
    <div class="card">
      <h2>${title}</h2>
      <p class="danger">This section could not be loaded.</p>
      <p class="muted">${error?.message || 'An unexpected error occurred.'}</p>
      <div class="row"><button class="primary" id="retrySectionBtn">Try Again</button></div>
    </div>
  `;
  document.getElementById('retrySectionBtn')?.addEventListener('click', () => loadSection(title));
}

async function readErrorMessage(res, fallback) {
  if (!res) return fallback;
  try {
    const raw = await res.text();
    const payload = safeJsonParse(raw, null);
    if (payload?.errors) {
      return Object.values(payload.errors).flat().join(' ');
    }
    if (payload?.message) {
      return payload.message;
    }
    const plain = raw
      .replace(/<[^>]+>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    if (plain) {
      return `${fallback} (${plain.slice(0, 160)})`;
    }
    return res.status ? `${fallback} (HTTP ${res.status})` : fallback;
  } catch {
    return fallback;
  }
}

async function parseJsonResponse(res, fallback = null) {
  if (!res) return fallback;
  const raw = await res.text();
  const parsed = safeJsonParse(raw, undefined);
  if (parsed !== undefined) {
    return parsed;
  }
  const snippet = raw
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 160);
  throw new Error(snippet ? `The server returned an invalid response: ${snippet}` : 'The server returned an invalid response.');
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
  refreshChatBadge();
  nav.querySelector('button')?.click();
}

async function refreshChatBadge() {
  const chatButton = nav.querySelector('button[data-item="Chat"]');
  if (!chatButton) return;

  const showBadge = settings?.show_unread_chat_badge !== false;
  if (!showBadge) {
    chatButton.textContent = 'Chat';
    return;
  }

  try {
    const res = await api('/api/chats');
    if (!res || !res.ok) {
      chatButton.textContent = 'Chat';
      return;
    }
    const chats = extractList(await res.json());
    const unreadChats = chats.filter(chat => Number(chat.unread_messages || 0) > 0 && !chat.is_archived).length;
    chatButton.textContent = unreadChats > 0 ? `Chat (${unreadChats})` : 'Chat';
  } catch {
    chatButton.textContent = 'Chat';
  }
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

function renderKVList(map, data, opts = {}) {
  const showZero = opts.showZero !== false;
  return Object.entries(map)
    .filter(([k]) => showZero || toFiniteNumber(data[k] || 0, 0) !== 0)
    .map(([k,label]) => `<div class="res-kv"><span>${label}</span><span>${toFiniteNumber(data[k]||0, 0).toLocaleString()}</span></div>`)
    .join('');
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
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
  const socket = ws;
  if (!socket) return false;

  const authToken = await getWsToken();
  if (!authToken) return false;

  if (!ws || socket !== ws) {
    return false;
  }

  if (socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ type: 'subscribe', channel, token: authToken }));
    return true;
  }

  if (socket.readyState === WebSocket.CONNECTING) {
    socket.addEventListener('open', () => {
      if (ws === socket) {
        socket.send(JSON.stringify({ type: 'subscribe', channel, token: authToken }));
      }
    }, { once: true });
    return true;
  }

  return false;
}

async function loadSection(name) {
  activeSectionName = name;
  renderLoadingState(name);
  try {
    if (name === 'Player') return await loadPlayer();
    if (name === 'Announcements') return await loadAnnouncements();
    if (name === 'New Accounts') return await loadNewAccounts();
    if (name === 'Time Tracker') return await loadTimeTracker();
    if (name === 'Map') return await loadMap();
    if (name === 'Combat') return await loadCombat();
    if (name === 'Chat') return await loadChat();
    if (name === 'Other Nations') return await loadOtherNations();
    if (name === 'Shop') return await loadShop();
    if (name === 'Settings') return await loadSettings();
    if (name === 'All Nations') return await loadAllNations();
    if (name === 'Notifications') return await loadNotifications();
    if (name === 'Game Information and Rules') return await loadGameInformationRules();
    if (name === 'About') return await loadAboutPage();
  } catch (error) {
    if (activeSectionName === name) {
      renderSectionError(name, error);
    }
  }
}

async function loadPlayer() {
  const [dashRes, sqMilesRes] = await Promise.all([
    api('/api/me/dashboard'),
    api('/api/me/terrain-square-miles'),
  ]);
  const data = await dashRes.json();
  const sqMiles = await sqMilesRes.json();
  const normalizedSqMiles = normalizeTerrainSquareMiles(sqMiles);
  const terrainRows = Object.entries(normalizedSqMiles).length
    ? Object.entries(normalizedSqMiles).map(([k, v]) => `<span>${labelTerrainKey(k)}: <strong>${toFiniteNumber(v, 0)} sq mi</strong></span>`).join(' &nbsp;|&nbsp; ')
    : 'No terrain data';

  const res = data.resources || {};
  const base = res.base || {};
  const refined = res.refined || {};
  const currencies = res.currencies || {};
  const yearly = data.yearly_projection || { income: { base: {}, refined: {}, currencies: {} }, maintenance: { base: {}, refined: {}, currencies: {} }, net: { base: {}, refined: {}, currencies: {} }, maintenance_breakdown: [] };

  const ownedUnits = data.units.owned || [];
  const trainingUnits = data.units.training || [];
  const builtBuildings = data.buildings.built || [];
  const progressBuildings = data.buildings.in_progress || [];

  view.innerHTML = `
    <div class="card">
      <h2>Player Dashboard</h2>
      <div class="twocol">
        <div>
          <p><strong>Nation:</strong> ${data.nation.name}</p>
          <p><strong>Leader:</strong> ${data.nation.leader_name || '-'}</p>
          <label>Alliance</label>
          <input id="allianceField" value="${data.nation.alliance_name || ''}">
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
              <details open><summary style="font-size:12px;color:#666;">⛏ Ore-derived</summary>${renderKVList(ORE_REFS, refined, { showZero: false }) || '<div class="muted" style="padding:4px 6px;">None</div>'}</details>
              <details open style="margin-top:4px;"><summary style="font-size:12px;color:#666;">🌲 Wood-derived</summary>${renderKVList(WOOD_REFS, refined, { showZero: false }) || '<div class="muted" style="padding:4px 6px;">None</div>'}</details>
              <details open style="margin-top:4px;"><summary style="font-size:12px;color:#666;">🍞 Food-derived</summary>${renderKVList(FOOD_REFS, refined, { showZero: false }) || '<div class="muted" style="padding:4px 6px;">None</div>'}</details>
            </div>
          </details>

          <details style="margin-top:8px;" open>
            <summary>Expected Yearly Income / Maintenance</summary>
            <div style="border:1px solid #c9d1db;border-radius:8px;padding:8px;margin-top:4px;">
              <div style="font-size:12px;color:#666;margin-bottom:4px;">Net (Income - Maintenance)</div>
              <div>${renderKVList({cow:'Cow',wood:'Wood',ore:'Ore',food:'Food'}, yearly.net?.base || {}, { showZero: false }) || '<div class="muted" style="padding:4px 6px;">No yearly net base changes.</div>'}</div>
              <div style="margin-top:8px;font-size:12px;color:#666;">Maintenance causes</div>
              <div>${(yearly.maintenance_breakdown || []).map(m => `<div class="res-kv"><span>${m.asset} (${m.key})</span><span>- ${toFiniteNumber(m.amount, 0)}</span></div>`).join('') || '<div class="muted" style="padding:4px 6px;">No maintenance assets.</div>'}</div>
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
          <div class="list">${ownedUnits.map(u => `<div><button class="primary playerUnitDetail" data-json='${JSON.stringify(u).replace(/'/g, '&#39;')}' title="Click for details" style="width:100%;margin-bottom:6px;background:#314f72;">${u.display_name || u.custom_name || 'Unit'} x${u.qty}</button></div>`).join('') || '<div class="muted">None</div>'}
          <hr>${trainingUnits.map(u => `<div><button class="primary playerUnitDetail" data-json='${JSON.stringify(u).replace(/'/g, '&#39;')}' title="Click for details" style="width:100%;margin-bottom:6px;background:#4f5f72;">${u.display_name || u.custom_name || 'Unit'} x${u.qty} (training)</button></div>`).join('') || '<div class="muted">No training units</div>'}
          <div id="playerUnitDetailPanel" class="muted" style="margin-top:8px;">Hover/click a unit to see details.</div></div>
          <h3>Buildings</h3>
          <div class="list">${builtBuildings.map(b => `<div><button class="primary playerBuildingDetail" data-json='${JSON.stringify(b).replace(/'/g, '&#39;')}' title="Click for details" style="width:100%;margin-bottom:6px;background:#314f72;">${b.display_name} L${b.level}</button></div>`).join('') || '<div class="muted">None</div>'}
          <hr>${progressBuildings.map(b => `<div><button class="primary playerBuildingDetail" data-json='${JSON.stringify(b).replace(/'/g, '&#39;')}' title="Click for details" style="width:100%;margin-bottom:6px;background:#4f5f72;">${b.display_name} (${b.status})</button></div>`).join('') || '<div class="muted">No construction</div>'}
          <div id="playerBuildingDetailPanel" class="muted" style="margin-top:8px;">Hover/click a building to see details.</div></div>
        </div>
      </div>
    </div>
  `;

  document.querySelectorAll('.playerUnitDetail').forEach(button => {
    const showDetails = () => {
      const payload = safeJsonParse(button.dataset.json, {});
      document.getElementById('playerUnitDetailPanel').innerHTML = `<strong>${payload.display_name || payload.custom_name || 'Unit'}</strong><br>Qty: ${payload.qty || 0}<br>Status: ${payload.status || 'owned'}<br>Class: ${payload.class_name || '-'}<br>ID: ${payload.id || '-'}`;
    };
    button.addEventListener('mouseenter', showDetails);
    button.addEventListener('click', showDetails);
  });

  document.querySelectorAll('.playerBuildingDetail').forEach(button => {
    const showDetails = () => {
      const payload = safeJsonParse(button.dataset.json, {});
      document.getElementById('playerBuildingDetailPanel').innerHTML = `<strong>${payload.display_name || 'Building'}</strong><br>Level: ${payload.level || 1}<br>Status: ${payload.status || 'built'}<br>Code: ${payload.code || '-'}<br>ID: ${payload.id || '-'}`;
    };
    button.addEventListener('mouseenter', showDetails);
    button.addEventListener('click', showDetails);
  });
  document.getElementById('saveAbout').onclick = async () => {
    const aboutText = document.getElementById('aboutField').value;
    const allianceName = document.getElementById('allianceField').value;
    const save = await api('/api/me/about', { method: 'PATCH', body: JSON.stringify({ about_text: aboutText, alliance_name: allianceName }) });
    document.getElementById('aboutMsg').textContent = save?.ok ? 'Saved' : await readErrorMessage(save, 'The about text could not be saved.');
    barkIfEnabled();
  };
}

async function loadCombat() {
  view.innerHTML = `
    <div class="card">
      <h2>Combat</h2>
      <p class="muted">Combat tools and battle resolution are coming soon.</p>
    </div>
  `;
}

async function loadAboutPage() {
  const res = await api('/api/meta/about');
  const about = await res.json();
  const sections = Array.isArray(about.sections) ? about.sections : [];

  view.innerHTML = `
    <div class="card">
      <h2>${about.title || 'About Azveria Online'}</h2>
      <p class="muted">${about.subtitle || ''}</p>
      <div class="twocol">
        <div>
          ${sections.map(section => `<div class="card"><h3 style="margin-top:0;">${section.heading}</h3><p style="margin-bottom:0;">${section.body}</p></div>`).join('')}
        </div>
        <div>
          <div class="card">
            <h3 style="margin-top:0;">Build Info</h3>
            <div><strong>Website:</strong> ${about.website_version || '-'}</div>
            <div><strong>Game:</strong> ${about.game_version || '-'}</div>
            <div><strong>Admin:</strong> ${about.admin || '-'}</div>
            <div><strong>Developer:</strong> ${about.developer || '-'}</div>
          </div>
          <div class="card">
            <h3 style="margin-top:0;">Documentation</h3>
            <div><a href="/docs/player" target="_blank" rel="noreferrer">Player Guide</a></div>
            <div><a href="/docs/admin" target="_blank" rel="noreferrer">Admin Guide</a></div>
            <div><a href="/docs/developer" target="_blank" rel="noreferrer">Developer Guide</a></div>
          </div>
        </div>
      </div>
    </div>
  `;
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
      <div class="list" id="annList">${list.map(a => `<div class="announcement-card"><div class="announcement-author">${a.author_name}</div><div class="muted" style="font-size:12px;">${a.created_at || ''}</div><div class="announcement-body">${a.body}</div></div>`).join('')}</div>
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
  const [layersRes, terrainRes, nationsRes, editorStateRes] = await Promise.all([
    api('/api/maps/layers'),
    api('/api/me/terrain-square-miles'),
    api('/api/nations?per_page=400'),
    api('/api/maps/editor-state'),
  ]);

  const layers = layersRes && layersRes.ok ? await parseJsonResponse(layersRes, []) : [];
  const myTerrainSqMiles = terrainRes && terrainRes.ok ? await parseJsonResponse(terrainRes, {}) : {};
  const nations = extractList(await parseJsonResponse(nationsRes, { data: [] }));
  const editorState = editorStateRes && editorStateRes.ok ? await parseJsonResponse(editorStateRes, {}) : {};

  const layerByType = Object.fromEntries((layers || []).map(l => [l.layer_type, l.image_path]));
  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
  const esc = (s) => String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
  const TERRAIN_KEYS = ['grassland', 'forest', 'mountain', 'desert', 'tundra', 'magic_grassland', 'water'];
  const TERRAIN_CODES = Object.fromEntries(TERRAIN_KEYS.map((k, i) => [k, i]));
  const CODE_TO_TERRAIN = Object.fromEntries(TERRAIN_KEYS.map((k, i) => [i, k]));
  const baseTerrainPalette = {
    none: {
      grassland: '#8fe388',
      forest: '#2e7a3d',
      mountain: '#4d3269',
      desert: '#d8c55a',
      tundra: 'rgba(0,0,0,0)',
      magic_grassland: '#7d2323',
      water: '#357ec7',
    },
    protanopia: {
      grassland: '#8fc67e',
      forest: '#3e7d4f',
      mountain: '#57508b',
      desert: '#d7be7a',
      tundra: 'rgba(0,0,0,0)',
      magic_grassland: '#6d4b28',
      water: '#2f7dc9',
    },
    deuteranopia: {
      grassland: '#b1c76b',
      forest: '#637d39',
      mountain: '#5b4f86',
      desert: '#d8be74',
      tundra: 'rgba(0,0,0,0)',
      magic_grassland: '#8b4631',
      water: '#2c7dc1',
    },
    tritanopia: {
      grassland: '#88b65f',
      forest: '#376a40',
      mountain: '#7e5f4f',
      desert: '#bfa26b',
      tundra: 'rgba(0,0,0,0)',
      magic_grassland: '#7d3e2e',
      water: '#4a84b8',
    },
  };

  let mapWidth = clamp(toFiniteNumber(editorState.width, 1200), 100, 5000);
  let mapHeight = clamp(toFiniteNumber(editorState.height, 700), 100, 5000);
  let terrainStrokes = Array.isArray(editorState.terrain_strokes) ? editorState.terrain_strokes.slice() : [];
  let politicalStrokes = Array.isArray(editorState.political_strokes) ? editorState.political_strokes.slice() : [];
  let politicalNationMeta = Array.isArray(editorState.political_nations) ? editorState.political_nations.slice() : [];
  let editorBackgroundPath = editorState.editor_background_path || null;
  let editorBackgroundOpacity = clamp(toFiniteNumber(editorState.editor_background_opacity, 100), 0, 100) / 100;
  let terrainGrid = new Uint8Array(mapWidth * mapHeight);
  let ownerGrid = new Int32Array(mapWidth * mapHeight);

  const politicalNationMap = new Map();
  nations.forEach(n => {
    politicalNationMap.set(Number(n.id), {
      id: Number(n.id),
      name: n.name,
      alliance_name: n.alliance_name || '',
      races: [],
      dirty: false,
    });
  });
  (politicalNationMeta || []).forEach(n => {
    const id = Number(n.id || 0);
    if (!id) return;
    const existing = politicalNationMap.get(id) || { id, name: n.name || `Nation ${id}`, alliance_name: '', races: [], dirty: false };
    existing.name = n.name || existing.name;
    existing.alliance_name = n.alliance_name || existing.alliance_name || '';
    existing.races = Array.isArray(n.races) ? n.races : [];
    politicalNationMap.set(id, existing);
  });

  const politicalNationsArray = () => Array.from(politicalNationMap.values()).sort((a, b) => a.name.localeCompare(b.name));

  view.innerHTML = `
    <div class="card">
      <h2>Map</h2>
      <div class="map-right-external" id="mapAdminButtons">
        ${user.role === 'admin' ? '<button class="primary" id="openTerrainEditorBtn">Terrain Editor</button><button class="primary" id="openPoliticalEditorBtn">Political Editor</button><button class="primary" id="recalcTerrainStatsBtn" style="background:#2f6a41;">Recalculate Terrain Stats</button><button class="primary" id="resetMapBtn" style="background:#8a1a1a;">Reset Map</button>' : ''}
      </div>
      <div class="map-shell">
        <div>
          <div class="map-editor-header" id="mapTopControls"></div>
          <div class="map-stage-wrap" id="mapStageWrap">
            <canvas id="mapCanvas" class="map-canvas"></canvas>
            <button class="primary map-floating" id="mapFullscreenBtn" style="right:10px;top:10px;">Fullscreen</button>
            <div class="map-floating map-info-box" id="mapNationInfo" style="display:none;"></div>
            <div class="map-bottom-left" id="mapBottomLeftTools" style="display:none;"></div>
            <div class="map-bottom-center">
              <label class="map-small-label" for="mapZoomPercent">Zoom</label>
              <input id="mapZoomPercent" type="range" min="-25" max="100" step="1" value="0">
            </div>
            <div class="map-bottom-right">
              <div class="map-floating" style="position:relative;right:auto;bottom:auto;display:flex;gap:8px;align-items:center;">
                <label class="map-small-label" for="terrainOpacity">Terrain Opacity</label>
                <input id="terrainOpacity" type="range" min="0" max="100" value="55">
              </div>
            </div>
          </div>
          <div class="row" style="margin-top:10px;justify-content:space-between;flex-wrap:wrap;">
            <div id="mapSaveArea"></div>
            <span class="muted" id="mapStatusMsg"></span>
          </div>
        </div>
        <div>
          <div class="map-side-panel" id="mapSidePanel"></div>
        </div>
      </div>
    </div>
  `;

  const stage = document.getElementById('mapStageWrap');
  const canvas = document.getElementById('mapCanvas');
  const ctx = canvas.getContext('2d');
  const mapNationInfo = document.getElementById('mapNationInfo');
  const mapFullscreenBtn = document.getElementById('mapFullscreenBtn');
  const mapSidePanel = document.getElementById('mapSidePanel');
  const mapTopControls = document.getElementById('mapTopControls');
  const mapBottomLeftTools = document.getElementById('mapBottomLeftTools');
  const mapBottomRight = stage.querySelector('.map-bottom-right');
  const mapSaveArea = document.getElementById('mapSaveArea');
  const mapStatusMsg = document.getElementById('mapStatusMsg');

  let mode = 'view';
  const minZoomPct = -25;
  const maxZoomPct = 100;
  const terrainDefaultBrushSize = 50;
  const politicalDefaultBrushSize = 8;
  let mapType = 'political';
  let terrainFilterEnabled = false;
  let terrainOpacity = 0.55;
  let selectedTerrainType = 'grassland';
  let selectedTool = 'brush';
  let brushSize = terrainDefaultBrushSize;
  let politicalEditNationId = 0;
  let politicalRemoveMode = false;
  let territoryEditing = false;
  let zoomPct = 0;
  let zoomTargetPct = 0;
  let zoomAnimFrame = 0;
  let panX = 0;
  let panY = 0;
  let dragging = false;
  let downPoint = null;
  let transform = { scale: 1, originX: 0, originY: 0 };
  let unsavedChanges = false;
  let renderScheduled = false;
  const scheduleRender = () => {
    if (renderScheduled) return;
    renderScheduled = true;
    requestAnimationFrame(() => { renderScheduled = false; render(); });
  };
  let politicalNeedsPostPaintBorderUpdate = false;
  let colorOverrides = (editorState.terrain_color_overrides && typeof editorState.terrain_color_overrides === 'object')
    ? { ...editorState.terrain_color_overrides }
    : {};
  let selectedNationId = 0;
  let labelCache = [];
  let lastPaintPoint = null;
  let outlinePoints = [];
  let outlineClosed = false;
  let lastOutlinePoint = null;
  let dragAction = 'none';

  const terrainColorControlsHtml = () => {
    const palette = getPalette();
    return `
      <details open>
        <summary>Terrain Colors</summary>
        ${TERRAIN_KEYS.map(key => `
          <div class="terrain-color-row">
            <label style="font-size:12px;">${labelTerrainKey(key)}</label>
            <input type="color" class="terrainColorInput" data-key="${key}" value="${palette[key].startsWith('#') ? palette[key] : '#cccccc'}">
          </div>
        `).join('')}
      </details>
    `;
  };

  const bindTerrainColorInputs = (root = document) => {
    root.querySelectorAll('.terrainColorInput').forEach(input => {
      input.addEventListener('input', () => {
        colorOverrides[input.dataset.key] = input.value;
        terrainLayerDirty = true;
        waterLayerDirty = true;
        render();
      });
    });
  };

  const imageCache = new Map();
  const normalizeStoragePath = (path) => String(path || '').replace(/^\/?storage\//, '').trim();
  const loadImage = (path) => {
    if (!path) return Promise.resolve(null);
    if (imageCache.has(path)) return imageCache.get(path);
    const p = new Promise((resolve) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => resolve(null);
      img.src = path.startsWith('/storage/') ? path : `/storage/${path}`;
    });
    imageCache.set(path, p);
    return p;
  };

  const layerImages = {
    main: await loadImage(layerByType.main || ''),
    terrain: await loadImage(layerByType.terrain || ''),
    political: await loadImage(layerByType.political || ''),
  };
  const mainLayerPathNormalized = normalizeStoragePath(layerByType.main || '');
  let editorBgImage = editorBackgroundPath
    ? await loadImage(String(editorBackgroundPath).replace(/^\/storage\//, ''))
    : null;

  const getPalette = () => {
    const modeKey = settings.color_blind_mode || 'none';
    const base = baseTerrainPalette[modeKey] || baseTerrainPalette.none;
    return { ...base, ...colorOverrides };
  };

  const idx = (x, y) => (y * mapWidth) + x;
  const inBounds = (x, y) => x >= 0 && y >= 0 && x < mapWidth && y < mapHeight;
  const pointDistance = (a, b) => Math.hypot((a?.x || 0) - (b?.x || 0), (a?.y || 0) - (b?.y || 0));
  const brushRadiusFromSize = (size) => clamp(Math.ceil(toFiniteNumber(size, 1) / 2), 1, 200);
  const brushOffsetCache = new Map();
  const getBrushOffsets = (radius) => {
    const r = clamp(Math.floor(radius), 1, 200);
    if (brushOffsetCache.has(r)) return brushOffsetCache.get(r);
    const offsets = [];
    const rr = r * r;
    for (let dy = -r; dy <= r; dy++) {
      for (let dx = -r; dx <= r; dx++) {
        if ((dx * dx) + (dy * dy) > rr) continue;
        offsets.push(dx, dy);
      }
    }
    brushOffsetCache.set(r, offsets);
    return offsets;
  };

  const terrainLayerCanvas = document.createElement('canvas');
  const terrainLayerCtx = terrainLayerCanvas.getContext('2d');
  const waterLayerCanvas = document.createElement('canvas');
  const waterLayerCtx = waterLayerCanvas.getContext('2d');
  const politicalLayerCanvas = document.createElement('canvas');
  const politicalLayerCtx = politicalLayerCanvas.getContext('2d');

  let terrainLayerDirty = true;
  let waterLayerDirty = true;
  let politicalLayerDirty = true;
  let politicalNeedsFullRebuild = false;
  let lastPaletteSignature = '';
  let lastPoliticalVisualKey = '';

  const resizeLayerCanvases = () => {
    terrainLayerCanvas.width = mapWidth;
    terrainLayerCanvas.height = mapHeight;
    waterLayerCanvas.width = mapWidth;
    waterLayerCanvas.height = mapHeight;
    politicalLayerCanvas.width = mapWidth;
    politicalLayerCanvas.height = mapHeight;
    terrainLayerDirty = true;
    waterLayerDirty = true;
    politicalLayerDirty = true;
  };

  const parseColorToRgb = (color) => {
    if (!color) return { r: 255, g: 255, b: 255 };
    const hex = /^#([0-9a-f]{6})$/i.exec(color);
    if (hex) {
      return {
        r: parseInt(hex[1].slice(0, 2), 16),
        g: parseInt(hex[1].slice(2, 4), 16),
        b: parseInt(hex[1].slice(4, 6), 16),
      };
    }
    const hsl = /^hsl\((\d+)\s+(\d+)%\s+(\d+)%\)$/i.exec(String(color).trim());
    if (!hsl) return { r: 255, g: 255, b: 255 };
    const h = (Number(hsl[1]) % 360) / 360;
    const s = Number(hsl[2]) / 100;
    const l = Number(hsl[3]) / 100;
    const hue2rgb = (p, q, t) => {
      let tt = t;
      if (tt < 0) tt += 1;
      if (tt > 1) tt -= 1;
      if (tt < 1 / 6) return p + (q - p) * 6 * tt;
      if (tt < 1 / 2) return q;
      if (tt < 2 / 3) return p + (q - p) * (2 / 3 - tt) * 6;
      return p;
    };
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    return {
      r: Math.round(hue2rgb(p, q, h + 1 / 3) * 255),
      g: Math.round(hue2rgb(p, q, h) * 255),
      b: Math.round(hue2rgb(p, q, h - 1 / 3) * 255),
    };
  };

  const pointInPolygon = (x, y, points) => {
    let inside = false;
    for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
      const xi = points[i].x;
      const yi = points[i].y;
      const xj = points[j].x;
      const yj = points[j].y;
      const intersects = ((yi > y) !== (yj > y))
        && (x < (xj - xi) * (y - yi) / ((yj - yi) || 0.00001) + xi);
      if (intersects) inside = !inside;
    }
    return inside;
  };

  const getScaleForZoom = (zoomValue) => {
    const viewW = canvas.width || Math.max(200, Math.floor(stage.getBoundingClientRect().width));
    const viewH = canvas.height || Math.max(200, Math.floor(stage.getBoundingClientRect().height));
    const fitScale = Math.min(viewW / mapWidth, viewH / mapHeight);
    const factor = zoomValue >= 0
      ? 1 + (zoomValue / 100) * 79
      : Math.max(0.55, 1 + (zoomValue / 100));
    return fitScale * factor;
  };

  const setZoom = (nextZoom, pivot = null) => {
    const clamped = clamp(nextZoom, minZoomPct, maxZoomPct);
    if (Math.abs(clamped - zoomPct) < 0.0001) return;

    const prevScale = transform.scale || getScaleForZoom(zoomPct);
    const prevOriginX = transform.originX;
    const prevOriginY = transform.originY;
    const viewW = canvas.width || Math.max(200, Math.floor(stage.getBoundingClientRect().width));
    const viewH = canvas.height || Math.max(200, Math.floor(stage.getBoundingClientRect().height));
    const newScale = getScaleForZoom(clamped);

    zoomPct = clamped;

    if (pivot && Number.isFinite(pivot.sx) && Number.isFinite(pivot.sy) && prevScale > 0 && newScale > 0) {
      const worldX = (pivot.sx - prevOriginX) / prevScale;
      const worldY = (pivot.sy - prevOriginY) / prevScale;
      const nextCenterX = (viewW - mapWidth * newScale) / 2;
      const nextCenterY = (viewH - mapHeight * newScale) / 2;
      panX = pivot.sx - nextCenterX - (worldX * newScale);
      panY = pivot.sy - nextCenterY - (worldY * newScale);
    }

    if (zoomPct <= 0) {
      panX = 0;
      panY = 0;
    }

    scheduleRender();
  };

  const animateZoomToTarget = (pivot = null) => {
    if (zoomAnimFrame) {
      cancelAnimationFrame(zoomAnimFrame);
      zoomAnimFrame = 0;
    }
    const step = () => {
      const delta = zoomTargetPct - zoomPct;
      if (Math.abs(delta) < 0.1) {
        setZoom(zoomTargetPct, pivot);
        zoomAnimFrame = 0;
        return;
      }
      setZoom(zoomPct + (delta * 0.22), pivot);
      zoomAnimFrame = requestAnimationFrame(step);
    };
    zoomAnimFrame = requestAnimationFrame(step);
  };

  const getNationById = (id) => politicalNationMap.get(Number(id)) || null;
  const firstLetterName = (name) => {
    const s = (name || '').trim();
    return s ? `${s.charAt(0).toUpperCase()}.` : 'N.';
  };

  const applyTerrainOperationToGrid = (op, targetGrid = terrainGrid) => {
    const code = TERRAIN_CODES[op.terrain] ?? TERRAIN_CODES.grassland;
    const x = Math.floor(op.x);
    const y = Math.floor(op.y);
    if (!inBounds(x, y)) return;

    if (op.tool === 'fill') {
      const start = idx(x, y);
      const oldCode = targetGrid[start];
      if (oldCode === code) return;
      const q = [start];
      targetGrid[start] = code;
      while (q.length) {
        const p = q.pop();
        const px = p % mapWidth;
        const py = Math.floor(p / mapWidth);
        const nbs = [[px - 1, py], [px + 1, py], [px, py - 1], [px, py + 1]];
        for (const [nx, ny] of nbs) {
          if (!inBounds(nx, ny)) continue;
          const ni = idx(nx, ny);
          if (targetGrid[ni] !== oldCode) continue;
          targetGrid[ni] = code;
          q.push(ni);
        }
      }
      return;
    }

    const r = brushRadiusFromSize(op.size || 50);
    const offsets = getBrushOffsets(r);
    for (let i = 0; i < offsets.length; i += 2) {
      const xx = x + offsets[i];
      const yy = y + offsets[i + 1];
      if (!inBounds(xx, yy)) continue;
      targetGrid[idx(xx, yy)] = code;
    }
  };

  const rebuildTerrainFromStrokes = () => {
    terrainGrid = new Uint8Array(mapWidth * mapHeight);
    terrainStrokes.forEach(op => applyTerrainOperationToGrid(op, terrainGrid));
    terrainLayerDirty = true;
    waterLayerDirty = true;
  };

  const applyPoliticalOperationToGrid = (op, targetGrid = ownerGrid) => {
    const x = Math.floor(op.x);
    const y = Math.floor(op.y);
    if (!inBounds(x, y)) return;
    const newId = op.remove ? 0 : Number(op.nation_id || 0);

    if (op.tool === 'fill') {
      const start = idx(x, y);
      const oldId = targetGrid[start];
      if (op.remove && oldId === 0) return;
      if (!op.remove && oldId === newId) return;
      if (!op.remove && terrainGrid[start] === TERRAIN_CODES.water) return;
      const q = [start];
      targetGrid[start] = newId;
      while (q.length) {
        const p = q.pop();
        const px = p % mapWidth;
        const py = Math.floor(p / mapWidth);
        const nbs = [[px - 1, py], [px + 1, py], [px, py - 1], [px, py + 1]];
        for (const [nx, ny] of nbs) {
          if (!inBounds(nx, ny)) continue;
          const ni = idx(nx, ny);
          if (targetGrid[ni] !== oldId) continue;
          if (!op.remove && terrainGrid[ni] === TERRAIN_CODES.water) continue;
          targetGrid[ni] = newId;
          q.push(ni);
        }
      }
      return;
    }

    const r = brushRadiusFromSize(op.size || 50);
    const offsets = getBrushOffsets(r);
    for (let i = 0; i < offsets.length; i += 2) {
      const xx = x + offsets[i];
      const yy = y + offsets[i + 1];
      if (!inBounds(xx, yy)) continue;
      const ii = idx(xx, yy);
      if (!op.remove && terrainGrid[ii] === TERRAIN_CODES.water) continue;
      if (!op.remove && targetGrid[ii] && targetGrid[ii] !== newId) continue;
      targetGrid[ii] = newId;
    }
  };

  const rebuildPoliticalFromStrokes = () => {
    ownerGrid = new Int32Array(mapWidth * mapHeight);
    politicalStrokes.forEach(op => applyPoliticalOperationToGrid(op, ownerGrid));
    politicalLayerDirty = true;
    politicalNeedsFullRebuild = false;
  };

  const rebuildRasterLayers = (palette) => {
    const paletteSignature = JSON.stringify(palette);
    if (paletteSignature !== lastPaletteSignature) {
      terrainLayerDirty = true;
      waterLayerDirty = true;
      lastPaletteSignature = paletteSignature;
    }

    if (terrainLayerDirty) {
      const rgbByCode = new Array(TERRAIN_KEYS.length);
      for (let code = 0; code < TERRAIN_KEYS.length; code++) {
        const key = CODE_TO_TERRAIN[code];
        rgbByCode[code] = (key === 'tundra' || key === 'water') ? null : parseColorToRgb(palette[key] || '#ffffff');
      }
      const img = terrainLayerCtx.createImageData(mapWidth, mapHeight);
      for (let i = 0; i < terrainGrid.length; i++) {
        const rgb = rgbByCode[terrainGrid[i]];
        if (!rgb) continue;
        const p = i * 4;
        img.data[p] = rgb.r;
        img.data[p + 1] = rgb.g;
        img.data[p + 2] = rgb.b;
        img.data[p + 3] = 255;
      }
      terrainLayerCtx.putImageData(img, 0, 0);
      terrainLayerDirty = false;
    }

    if (waterLayerDirty) {
      const img = waterLayerCtx.createImageData(mapWidth, mapHeight);
      const rgb = parseColorToRgb(palette.water || '#357ec7');
      for (let i = 0; i < terrainGrid.length; i++) {
        if (terrainGrid[i] !== TERRAIN_CODES.water) continue;
        const p = i * 4;
        img.data[p] = rgb.r;
        img.data[p + 1] = rgb.g;
        img.data[p + 2] = rgb.b;
        img.data[p + 3] = 255;
      }
      waterLayerCtx.putImageData(img, 0, 0);
      waterLayerDirty = false;
    }

    const politicalVisualKey = `${mapType}|${mode}`;
    if (politicalVisualKey !== lastPoliticalVisualKey) {
      politicalLayerDirty = true;
      lastPoliticalVisualKey = politicalVisualKey;
    }

    if (politicalLayerDirty) {
      politicalLayerCtx.clearRect(0, 0, mapWidth, mapHeight);
      const img = politicalLayerCtx.createImageData(mapWidth, mapHeight);
      const nationRgbCache = new Map();
      const getNationRgb = (ownerId) => {
        if (nationRgbCache.has(ownerId)) return nationRgbCache.get(ownerId);
        let color = '#ffffff';
        if (mapType === 'alliance' || mode === 'political-editor') {
          const nation = getNationById(ownerId);
          color = nation?.alliance_name ? mapAllianceColor(nation.alliance_name) : '#7d7d7d';
          if (mapType === 'alliance' && !nation?.alliance_name) color = '#7d7d7d';
        }
        const rgb = parseColorToRgb(color);
        nationRgbCache.set(ownerId, rgb);
        return rgb;
      };
      for (let i = 0; i < ownerGrid.length; i++) {
        const owner = ownerGrid[i];
        if (!owner) continue;
        const rgb = getNationRgb(owner);
        const p = i * 4;
        img.data[p] = rgb.r;
        img.data[p + 1] = rgb.g;
        img.data[p + 2] = rgb.b;
        img.data[p + 3] = 210;
      }
      politicalLayerCtx.putImageData(img, 0, 0);

      const borderPrimary = mode === 'political-editor' ? 'rgba(0,0,0,0.98)' : 'rgba(0,0,0,0.9)';
      const borderHighlight = mode === 'political-editor' ? 'rgba(255,255,255,0.95)' : 'rgba(255,255,255,0.78)';
      politicalLayerCtx.fillStyle = borderPrimary;
      for (let y = 1; y < mapHeight - 1; y++) {
        for (let x = 1; x < mapWidth - 1; x++) {
          const c = ownerGrid[idx(x, y)];
          if (!c) continue;
          if (ownerGrid[idx(x + 1, y)] !== c || ownerGrid[idx(x - 1, y)] !== c || ownerGrid[idx(x, y + 1)] !== c || ownerGrid[idx(x, y - 1)] !== c) {
            politicalLayerCtx.fillRect(x, y, 1, 1);
            politicalLayerCtx.fillStyle = borderHighlight;
            politicalLayerCtx.fillRect(x + 0.22, y + 0.22, 0.56, 0.56);
            politicalLayerCtx.fillStyle = borderPrimary;
          }
        }
      }
      politicalLayerDirty = false;
    }
  };

  const nationPixelCount = (nationId) => {
    const idNum = Number(nationId);
    let c = 0;
    for (let i = 0; i < ownerGrid.length; i++) if (ownerGrid[i] === idNum) c++;
    return c;
  };

  const terrainPixelBreakdownForNation = (nationId) => {
    const idNum = Number(nationId);
    const out = Object.fromEntries(TERRAIN_KEYS.map(k => [k, 0]));
    const waterCode = TERRAIN_CODES.water;
    for (let i = 0; i < ownerGrid.length; i++) {
      if (ownerGrid[i] !== idNum) continue;
      const key = CODE_TO_TERRAIN[terrainGrid[i]] || 'grassland';
      if (key === 'water') continue; // nations don't own water tiles
      out[key] += 1;
    }
    // Water stat = land pixels of this nation that border a water tile (coastline pixels).
    const waterSet = new Set();
    for (let i = 0; i < ownerGrid.length; i++) {
      if (ownerGrid[i] !== idNum) continue;
      if (terrainGrid[i] === waterCode) continue; // skip water tiles owned by nation (shouldn't happen)
      const x = i % mapWidth;
      const y = Math.floor(i / mapWidth);
      if (
        (x > 0           && terrainGrid[i - 1] === waterCode) ||
        (x < mapWidth-1  && terrainGrid[i + 1] === waterCode) ||
        (y > 0           && terrainGrid[i - mapWidth] === waterCode) ||
        (y < mapHeight-1 && terrainGrid[i + mapWidth] === waterCode)
      ) {
        waterSet.add(i);
      }
    }
    out.water = waterSet.size;
    return out;
  };

  const normalizeTerrainColorStats = (raw) => {
    const parsed = safeJsonParse(raw, raw) || {};
    const source = (typeof parsed === 'string') ? (safeJsonParse(parsed, {}) || {}) : parsed;
    const out = Object.fromEntries(TERRAIN_KEYS.map(k => [k, 0]));
    const hasForest = Object.prototype.hasOwnProperty.call(source, 'forest');
    const hasWater = Object.prototype.hasOwnProperty.call(source, 'water');
    const legacySea = toFiniteNumber(source.seafront ?? source.sea_front ?? source.seaFront, 0);

    out.grassland = toFiniteNumber(source.grassland, 0);
    out.forest = hasForest ? toFiniteNumber(source.forest, 0) : toFiniteNumber(source.hills, 0);
    out.mountain = toFiniteNumber(source.mountain, 0);
    out.desert = toFiniteNumber(source.desert, 0);
    out.tundra = toFiniteNumber(source.tundra, 0);
    out.magic_grassland = toFiniteNumber(source.magic_grassland, 0);
    out.water = hasWater
      ? toFiniteNumber(source.water, 0)
      : toFiniteNumber(source.freshwater, 0) + legacySea;

    return out;
  };

  const normalizeAllianceColorOverrides = (raw) => {
    if (!raw || typeof raw !== 'object') return {};
    const out = {};
    Object.entries(raw).forEach(([k, v]) => {
      const key = String(k || '').trim().toLowerCase();
      const color = String(v || '').trim();
      if (!key) return;
      if (/^#[0-9A-Fa-f]{6}$/.test(color)) out[key] = color;
    });
    return out;
  };

  let allianceColorOverrides = normalizeAllianceColorOverrides(settings.alliance_color_overrides || {});

  const mapAllianceColor = (name) => {
    const s = String(name || '').trim().toLowerCase();
    if (!s) return '#808080';
    if (allianceColorOverrides[s]) return allianceColorOverrides[s];
    let hash = 0;
    for (let i = 0; i < s.length; i++) hash = ((hash << 5) - hash + s.charCodeAt(i)) | 0;
    const hue = Math.abs(hash) % 360;
    return `hsl(${hue} 68% 54%)`;
  };

  const toWorld = (clientX, clientY) => {
    const rect = canvas.getBoundingClientRect();
    const sx = clientX - rect.left;
    const sy = clientY - rect.top;
    const wx = Math.floor((sx - transform.originX) / transform.scale);
    const wy = Math.floor((sy - transform.originY) / transform.scale);
    return { wx, wy, sx, sy };
  };

  const computeLabels = () => {
    const visited = new Uint8Array(ownerGrid.length);
    const labels = [];
    for (let i = 0; i < ownerGrid.length; i++) {
      const owner = ownerGrid[i];
      if (!owner || visited[i]) continue;
      const queue = [i];
      visited[i] = 1;
      let sumX = 0;
      let sumY = 0;
      let count = 0;
      while (queue.length) {
        const p = queue.pop();
        const x = p % mapWidth;
        const y = Math.floor(p / mapWidth);
        sumX += x;
        sumY += y;
        count++;
        const nbs = [[x - 1, y], [x + 1, y], [x, y - 1], [x, y + 1]];
        for (const [nx, ny] of nbs) {
          if (!inBounds(nx, ny)) continue;
          const ni = idx(nx, ny);
          if (visited[ni] || ownerGrid[ni] !== owner) continue;
          visited[ni] = 1;
          queue.push(ni);
        }
      }
      const meta = getNationById(owner);
      const full = meta?.name || `Nation ${owner}`;
      const name = count < full.length * 70 ? firstLetterName(full) : full;
      labels.push({ owner, x: sumX / count, y: sumY / count, size: count, name });
    }
    labelCache = labels;
  };

  const resizeCanvas = () => {
    const rect = stage.getBoundingClientRect();
    canvas.width = Math.max(200, Math.floor(rect.width));
    canvas.height = Math.max(200, Math.floor(rect.height));
  };

  const render = () => {
    if (!canvas.width || !canvas.height) resizeCanvas();
    const viewW = canvas.width;
    const viewH = canvas.height;
    const fitScale = Math.min(viewW / mapWidth, viewH / mapHeight);
    const factor = zoomPct >= 0
      ? 1 + (zoomPct / 100) * 79
      : Math.max(0.55, 1 + (zoomPct / 100));
    const scale = fitScale * factor;
    transform.scale = scale;
    transform.originX = ((viewW - mapWidth * scale) / 2) + panX;
    transform.originY = ((viewH - mapHeight * scale) / 2) + panY;

    ctx.clearRect(0, 0, viewW, viewH);
    ctx.fillStyle = '#0f1520';
    ctx.fillRect(0, 0, viewW, viewH);

    ctx.save();
    ctx.translate(transform.originX, transform.originY);
    ctx.scale(scale, scale);

    if ((mode === 'terrain-editor' || mode === 'political-editor') && editorBgImage) {
      ctx.globalAlpha = editorBackgroundOpacity;
      ctx.drawImage(editorBgImage, 0, 0, mapWidth, mapHeight);
      ctx.globalAlpha = 1;
    } else if (layerImages.main && mainLayerPathNormalized !== normalizeStoragePath(editorBackgroundPath || '')) {
      ctx.drawImage(layerImages.main, 0, 0, mapWidth, mapHeight);
    }

    const palette = getPalette();
    rebuildRasterLayers(palette);

    if (mapType === 'political' || mapType === 'alliance' || mode === 'political-editor') {
      ctx.drawImage(politicalLayerCanvas, 0, 0);
    }

    if (terrainFilterEnabled || mode === 'terrain-editor' || mode === 'political-editor') {
      ctx.globalAlpha = terrainOpacity;
      ctx.drawImage(terrainLayerCanvas, 0, 0);
      ctx.globalAlpha = 1;
    }

    ctx.drawImage(waterLayerCanvas, 0, 0);

    if (mapType === 'political' || mapType === 'alliance' || mode === 'political-editor') {
      if (!labelCache.length) computeLabels();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      labelCache.forEach(label => {
        const size = clamp(Math.floor(Math.sqrt(label.size) / 3), 10, 28);
        ctx.font = `${size}px Trebuchet MS`;
        ctx.lineWidth = Math.max(2, size / 6);
        ctx.strokeStyle = 'rgba(0,0,0,0.7)';
        ctx.fillStyle = '#f8f8f8';
        ctx.strokeText(label.name, label.x, label.y);
        ctx.fillText(label.name, label.x, label.y);
      });
    }

    if (mode === 'political-editor' && outlinePoints.length > 1) {
      ctx.beginPath();
      ctx.moveTo(outlinePoints[0].x, outlinePoints[0].y);
      for (let i = 1; i < outlinePoints.length; i++) {
        ctx.lineTo(outlinePoints[i].x, outlinePoints[i].y);
      }
      if (outlineClosed) {
        ctx.closePath();
      }
      ctx.lineWidth = 2;
      ctx.strokeStyle = outlineClosed ? 'rgba(60,220,120,0.95)' : 'rgba(255,215,0,0.95)';
      ctx.setLineDash(outlineClosed ? [] : [4, 3]);
      ctx.stroke();
      ctx.setLineDash([]);
    }

    ctx.restore();
  };

  const setNationInfo = (nationId) => {
    selectedNationId = Number(nationId || 0);
    if (!selectedNationId) {
      mapNationInfo.style.display = 'none';
      mapNationInfo.innerHTML = '';
      return;
    }
    const n = getNationById(selectedNationId);
    const pixels = nationPixelCount(selectedNationId);
    const races = (n?.races || []).join(', ') || '-';
    mapNationInfo.style.display = 'block';
    mapNationInfo.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
        <strong>${esc(n?.name || `Nation ${selectedNationId}`)}</strong>
        <button class="primary" id="closeNationInfoBtn" style="padding:2px 8px;">X</button>
      </div>
      <div class="map-small-label" style="margin-top:4px;">Alliance: ${esc(n?.alliance_name || '-')}</div>
      <div class="map-small-label">Races: ${esc(races)}</div>
      <div class="map-small-label">Owned Terrain (pixels): ${pixels.toLocaleString()}</div>
    `;
    document.getElementById('closeNationInfoBtn').onclick = () => setNationInfo(0);
  };

  const setMode = (nextMode) => {
    if (mode === nextMode) return;
    if (unsavedChanges && (mode === 'terrain-editor' || mode === 'political-editor')) {
      const proceed = window.confirm('Discard unsaved map editor changes?');
      if (!proceed) return;
    }
    mode = nextMode;
    territoryEditing = false;
    lastPaintPoint = null;
    lastOutlinePoint = null;
    outlinePoints = [];
    outlineClosed = false;
    if (mode === 'terrain-editor') {
      selectedTool = 'brush';
      brushSize = terrainDefaultBrushSize;
    }
    if (mode === 'political-editor') {
      selectedTool = 'brush';
      brushSize = politicalDefaultBrushSize;
    }
    if (mode !== 'terrain-editor' && politicalNeedsFullRebuild) {
      rebuildPoliticalFromStrokes();
      labelCache = [];
    }
    politicalLayerDirty = true;
    mapStatusMsg.textContent = '';
    renderSidebar();
    renderTopEditorControls();
    renderBottomTools();
    mapFullscreenBtn.style.display = mode === 'view' ? 'inline-block' : 'none';
    render();
  };

  const renderTopEditorControls = () => {
    if (mode === 'view') {
      mapTopControls.innerHTML = '';
      return;
    }
    mapTopControls.innerHTML = `
      <div class="map-editor-toolbar">
        <span class="map-small-label">Grid</span>
        <input id="mapGridHeight" type="number" min="100" max="5000" value="${mapHeight}" style="width:90px;">
        <input id="mapGridWidth" type="number" min="100" max="5000" value="${mapWidth}" style="width:90px;">
        <button class="primary" id="applyGridBtn">Apply</button>
      </div>
      <div class="map-editor-toolbar">
        <label class="map-small-label">Reference Image</label>
        <input id="editorBgUpload" type="file" accept="image/*" style="max-width:180px;">
        <label class="map-small-label" for="editorBgOpacity">Reference Opacity</label>
        <input id="editorBgOpacity" type="range" min="0" max="100" value="${Math.round(editorBackgroundOpacity * 100)}" style="width:120px;">
        <span class="map-small-label" id="editorBgOpacityLabel">${Math.round(editorBackgroundOpacity * 100)}%</span>
      </div>
    `;

    document.getElementById('applyGridBtn').onclick = () => {
      const nh = clamp(toFiniteNumber(document.getElementById('mapGridHeight').value, mapHeight), 100, 5000);
      const nw = clamp(toFiniteNumber(document.getElementById('mapGridWidth').value, mapWidth), 100, 5000);
      mapWidth = nw;
      mapHeight = nh;
      resizeLayerCanvases();
      terrainGrid = new Uint8Array(mapWidth * mapHeight);
      terrainGrid.fill(TERRAIN_CODES.water);
      ownerGrid = new Int32Array(mapWidth * mapHeight);
      terrainStrokes = [{ tool: 'fill', terrain: 'water', x: 0, y: 0 }];
      politicalStrokes = [];
      politicalNeedsFullRebuild = false;
      terrainLayerDirty = true;
      waterLayerDirty = true;
      politicalLayerDirty = true;
      labelCache = [];
      unsavedChanges = true;
      lastOutlinePoint = null;
      resizeCanvas();
      render();
      mapStatusMsg.textContent = 'Grid resized and reset to all water.';
    };

    document.getElementById('editorBgUpload').onchange = async (e) => {
      if (!e.target.files || !e.target.files.length) return;
      const fd = new FormData();
      fd.append('image_file', e.target.files[0]);
      const response = await fetch('/api/admin/maps/editor-reference', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: fd,
      });
      if (!response.ok) {
        mapStatusMsg.textContent = 'Reference upload failed.';
        return;
      }
      const payload = await response.json();
      editorBackgroundPath = payload.image_path || null;
      editorBgImage = editorBackgroundPath ? await loadImage(editorBackgroundPath) : null;
      render();
      mapStatusMsg.textContent = 'Reference image uploaded.';
    };

    const bgOpacityInput = document.getElementById('editorBgOpacity');
    const bgOpacityLabel = document.getElementById('editorBgOpacityLabel');
    bgOpacityInput.oninput = (e) => {
      const pct = clamp(toFiniteNumber(e.target.value, 100), 0, 100);
      editorBackgroundOpacity = pct / 100;
      bgOpacityLabel.textContent = `${pct}%`;
      render();
    };
  };

  const renderBottomTools = () => {
    mapBottomLeftTools.style.display = (mode === 'terrain-editor' || mode === 'political-editor') ? 'block' : 'none';
    mapBottomRight.innerHTML = `
      <div class="map-floating" style="position:relative;right:auto;bottom:auto;display:flex;gap:8px;align-items:center;">
        <label class="map-small-label" for="terrainOpacity">Terrain Opacity</label>
        <input id="terrainOpacity" type="range" min="0" max="100" value="${Math.round(terrainOpacity * 100)}">
      </div>
    `;
    const opacityInput = document.getElementById('terrainOpacity');
    opacityInput.oninput = (e) => {
      terrainOpacity = clamp(toFiniteNumber(e.target.value, 55), 0, 100) / 100;
      render();
    };
    if (mode === 'view') {
      mapBottomLeftTools.innerHTML = '';
      return;
    }
    mapBottomLeftTools.innerHTML = `
      <div class="map-floating" style="position:relative;left:auto;bottom:auto;display:flex;gap:8px;align-items:center;">
        <label class="map-small-label">Tool</label>
        <select id="mapToolSelect" style="width:115px;"><option value="move">Move</option><option value="brush">Brush</option><option value="fill">Bucket</option><option value="outline">Outline</option></select>
        <label class="map-small-label">Size</label>
        <input id="mapBrushSize" type="range" min="1" max="200" value="${brushSize}">
        <span class="map-small-label" id="mapBrushSizeLabel">${brushSize}px</span>
      </div>
    `;
    document.getElementById('mapToolSelect').value = selectedTool;
    document.getElementById('mapToolSelect').onchange = (e) => {
      selectedTool = e.target.value;
      if (selectedTool === 'outline') {
        outlinePoints = [];
        outlineClosed = false;
      }
      canvas.style.cursor = selectedTool === 'move' ? 'grab' : 'crosshair';
      renderSidebar();
      render();
    };
    document.getElementById('mapBrushSize').oninput = (e) => {
      brushSize = clamp(toFiniteNumber(e.target.value, brushSize), 1, 200);
      document.getElementById('mapBrushSizeLabel').textContent = `${brushSize}px`;
    };

    if (mode === 'terrain-editor') {
      mapBottomRight.innerHTML = `
        <div class="map-floating" style="position:relative;right:auto;bottom:auto;max-width:280px;">
          <div class="map-small-label" style="margin-bottom:6px;">Terrain Types</div>
          <div class="map-scroll-list" style="max-height:160px;">
            ${TERRAIN_KEYS.map(k => `<button class="primary mapTerrainSelectBR" data-key="${k}" style="display:block;width:100%;margin-bottom:6px;${selectedTerrainType === k ? 'outline:2px solid var(--accent);' : ''}">${labelTerrainKey(k)}</button>`).join('')}
          </div>
        </div>
      `;
      mapBottomRight.querySelectorAll('.mapTerrainSelectBR').forEach(btn => {
        btn.onclick = () => {
          selectedTerrainType = btn.dataset.key;
          renderBottomTools();
        };
      });
    } else if (mode === 'political-editor') {
      mapBottomRight.innerHTML = `
        <div class="map-floating" style="position:relative;right:auto;bottom:auto;display:flex;gap:8px;align-items:center;">
          <label class="map-small-label" for="terrainOpacity">Terrain Opacity</label>
          <input id="terrainOpacity" type="range" min="0" max="100" value="${Math.round(terrainOpacity * 100)}">
        </div>
      `;
      const pOpacityInput = document.getElementById('terrainOpacity');
      pOpacityInput.oninput = (e) => {
        terrainOpacity = clamp(toFiniteNumber(e.target.value, 55), 0, 100) / 100;
        render();
      };
    }

    canvas.style.cursor = selectedTool === 'move' ? 'grab' : 'crosshair';
  };

  const assignOutlinedLandToNation = () => {
    if (mode !== 'political-editor' || !territoryEditing || !politicalEditNationId) {
      mapStatusMsg.textContent = 'Enable Political territory editing first.';
      return;
    }
    if (outlinePoints.length < 3 || !outlineClosed) {
      mapStatusMsg.textContent = 'Cannot assign: outline is not fully encapsulated (closed).';
      return;
    }

    const minX = Math.max(0, Math.floor(Math.min(...outlinePoints.map(p => p.x))));
    const maxX = Math.min(mapWidth - 1, Math.ceil(Math.max(...outlinePoints.map(p => p.x))));
    const minY = Math.max(0, Math.floor(Math.min(...outlinePoints.map(p => p.y))));
    const maxY = Math.min(mapHeight - 1, Math.ceil(Math.max(...outlinePoints.map(p => p.y))));

    let seed = null;
    for (let y = minY; y <= maxY && !seed; y++) {
      for (let x = minX; x <= maxX && !seed; x++) {
        if (!pointInPolygon(x + 0.5, y + 0.5, outlinePoints)) continue;
        if (terrainGrid[idx(x, y)] === TERRAIN_CODES.water) continue;
        if (ownerGrid[idx(x, y)] !== 0) continue;
        seed = { x, y };
      }
    }

    if (!seed) {
      mapStatusMsg.textContent = 'No unowned enclosed land found inside outline.';
      return;
    }

    const q = [seed];
    const visited = new Uint8Array(mapWidth * mapHeight);
    visited[idx(seed.x, seed.y)] = 1;
    let leaksToEdge = false;

    while (q.length) {
      const p = q.pop();
      if (p.x <= 0 || p.y <= 0 || p.x >= mapWidth - 1 || p.y >= mapHeight - 1) {
        leaksToEdge = true;
        break;
      }
      const neighbors = [[p.x - 1, p.y], [p.x + 1, p.y], [p.x, p.y - 1], [p.x, p.y + 1]];
      for (const [nx, ny] of neighbors) {
        if (!inBounds(nx, ny)) continue;
        const nIndex = idx(nx, ny);
        if (visited[nIndex]) continue;
        if (ownerGrid[nIndex] !== 0) continue;
        if (terrainGrid[nIndex] === TERRAIN_CODES.water) continue;
        visited[nIndex] = 1;
        q.push({ x: nx, y: ny });
      }
    }

    if (leaksToEdge) {
      mapStatusMsg.textContent = 'Cannot assign: outline does not fully encapsulate a region.';
      return;
    }

    commitPaintOperation({
      tool: 'fill',
      nation_id: politicalEditNationId,
      remove: false,
      x: seed.x,
      y: seed.y,
      size: brushSize,
    });

    outlinePoints = [];
    outlineClosed = false;
    mapStatusMsg.textContent = 'Encapsulated region assigned to selected nation.';
    renderSidebar();
    render();
  };

  const syncNationTerrainStats = async () => {
    const nationPayload = politicalNationsArray().map(n => ({
      id: n.id,
      name: n.name,
      alliance_name: n.alliance_name || '',
      races: n.races || [],
      pixels: nationPixelCount(n.id),
    }));

    let failedNationUpdates = 0;
    for (const nation of nationPayload) {
      const breakdown = terrainPixelBreakdownForNation(nation.id);
      const terrainPayload = {
        grassland: breakdown.grassland,
        mountain: breakdown.mountain,
        hills: breakdown.forest,
        freshwater: 0,
        seafront: breakdown.water,
        desert: breakdown.desert,
        forest: breakdown.forest,
        water: breakdown.water,
        tundra: breakdown.tundra,
        magic_grassland: breakdown.magic_grassland,
      };
      const nationSaveRes = await api('/api/admin/nations/' + nation.id, {
        method: 'PUT',
        body: JSON.stringify({
          name: nation.name,
          alliance_name: nation.alliance_name,
          terrain_square_miles: terrainPayload,
        }),
      });
      if (!nationSaveRes || !nationSaveRes.ok) {
        failedNationUpdates++;
      }
    }

    return {
      ok: failedNationUpdates === 0,
      failedNationUpdates,
      updatedCount: nationPayload.length,
    };
  };

  const renderSidebar = () => {
    if (mode === 'terrain-editor') {
      mapSidePanel.innerHTML = `
        <h3>Terrain Editor</h3>
        <div class="map-scroll-list">
          ${TERRAIN_KEYS.map(k => `<button class="primary mapTerrainSelect" data-key="${k}" style="display:block;width:100%;margin-bottom:6px;${selectedTerrainType === k ? 'outline:2px solid var(--accent);' : ''}">${labelTerrainKey(k)}</button>`).join('')}
        </div>
        <div class="setting-group" style="margin-top:8px;">
          ${terrainColorControlsHtml()}
        </div>
        <div class="row"><button class="primary" id="exitTerrainEditorBtn">Exit Editor</button></div>
      `;
      mapSidePanel.querySelectorAll('.mapTerrainSelect').forEach(btn => {
        btn.onclick = () => {
          selectedTerrainType = btn.dataset.key;
          renderSidebar();
        };
      });
      bindTerrainColorInputs(mapSidePanel);
      document.getElementById('exitTerrainEditorBtn').onclick = () => setMode('view');
    } else if (mode === 'political-editor') {
      const rows = politicalNationsArray();
      const selected = getNationById(politicalEditNationId);
      mapSidePanel.innerHTML = `
        <h3>Political Editor</h3>
        <div class="row" style="margin-top:0;">
          <button class="primary" id="addPoliticalNationBtn">Add Nation</button>
          <button class="primary" id="removePoliticalNationBtn" ${!politicalEditNationId ? 'disabled' : ''}>Remove From Map</button>
        </div>
        <div class="map-scroll-list" style="margin-top:8px;">
          ${rows.map(n => `<button class="primary politicalNationPick" data-id="${n.id}" style="display:block;width:100%;margin-bottom:6px;${Number(n.id) === Number(politicalEditNationId) ? 'outline:2px solid var(--accent);' : ''}">${esc(n.name)}</button>`).join('') || '<div class="muted">No nations available.</div>'}
        </div>
        <div class="setting-group" style="margin-top:8px;">
          <label>Name</label>
          <input id="politicalNationName" value="${esc(selected?.name || '')}" ${!selected ? 'disabled' : ''}>
          <label>Alliance</label>
          <input id="politicalNationAlliance" value="${esc(selected?.alliance_name || '')}" ${!selected ? 'disabled' : ''}>
          <label>Races (comma-separated)</label>
          <input id="politicalNationRaces" value="${esc((selected?.races || []).join(', '))}" ${!selected ? 'disabled' : ''}>
          <div class="row">
            <label><input id="politicalRemoveToggle" type="checkbox" ${politicalRemoveMode ? 'checked' : ''}> Remove territory</label>
          </div>
          <div class="row">
            <button class="primary" id="editTerritoryBtn" ${!selected ? 'disabled' : ''}>${territoryEditing ? 'Stop Territory Edit' : 'Edit Territory'}</button>
          </div>
          <div class="row">
            <button class="primary" id="assignOutlineBtn" ${(!selected || !territoryEditing) ? 'disabled' : ''}>Assign Encapsulated Land</button>
            <button class="primary" id="clearOutlineBtn" ${outlinePoints.length ? '' : 'disabled'}>Clear Outline</button>
          </div>
          <div class="map-small-label">Outline: ${outlinePoints.length < 3 ? 'Not started' : (outlineClosed ? 'Closed' : 'Open')}</div>
          <div class="row">
            <button class="primary" id="politicalDoneBtn" ${!territoryEditing ? 'disabled' : ''}>Done</button>
          </div>
        </div>
        <div class="row"><button class="primary" id="exitPoliticalEditorBtn">Exit Editor</button></div>
        <div class="setting-group" style="margin-top:8px;">
          ${terrainColorControlsHtml()}
        </div>
      `;

      mapSidePanel.querySelectorAll('.politicalNationPick').forEach(btn => {
        btn.onclick = () => {
          politicalEditNationId = Number(btn.dataset.id);
          territoryEditing = false;
          renderSidebar();
        };
      });

      const syncMeta = () => {
        const cur = getNationById(politicalEditNationId);
        if (!cur) return;
        cur.name = document.getElementById('politicalNationName').value.trim() || cur.name;
        cur.alliance_name = document.getElementById('politicalNationAlliance').value.trim();
        cur.races = document.getElementById('politicalNationRaces').value
          .split(',')
          .map(v => v.trim())
          .filter(Boolean);
        cur.dirty = true;
        unsavedChanges = true;
        labelCache = [];
      };

      const nName = document.getElementById('politicalNationName');
      if (nName) nName.onchange = syncMeta;
      const nAlliance = document.getElementById('politicalNationAlliance');
      if (nAlliance) nAlliance.onchange = syncMeta;
      const nRaces = document.getElementById('politicalNationRaces');
      if (nRaces) nRaces.onchange = syncMeta;

      document.getElementById('politicalRemoveToggle').onchange = (e) => {
        politicalRemoveMode = !!e.target.checked;
      };

      document.getElementById('editTerritoryBtn').onclick = () => {
        territoryEditing = !territoryEditing;
        if (territoryEditing) {
          selectedTool = 'brush';
          brushSize = politicalDefaultBrushSize;
          outlinePoints = [];
          outlineClosed = false;
        }
        renderSidebar();
        renderBottomTools();
      };

      document.getElementById('assignOutlineBtn').onclick = assignOutlinedLandToNation;
      document.getElementById('clearOutlineBtn').onclick = () => {
        outlinePoints = [];
        outlineClosed = false;
        mapStatusMsg.textContent = 'Outline cleared.';
        renderSidebar();
        render();
      };

      document.getElementById('politicalDoneBtn').onclick = () => {
        territoryEditing = false;
        const pixels = nationPixelCount(politicalEditNationId);
        mapStatusMsg.textContent = `Nation territory committed: ${pixels.toLocaleString()} pixels.`;
        renderSidebar();
      };

      document.getElementById('addPoliticalNationBtn').onclick = async () => {
        const name = window.prompt('Nation name for new territory:', 'New Nation');
        if (!name) return;
        const response = await api('/api/admin/nations', { method: 'POST', body: JSON.stringify({ name }) });
        if (!response || !response.ok) {
          mapStatusMsg.textContent = 'Failed to create nation record.';
          return;
        }
        const payload = await response.json();
        const newId = Number(payload.id || 0);
        if (!newId) return;
        politicalNationMap.set(newId, { id: newId, name, alliance_name: '', races: [], dirty: true });
        politicalEditNationId = newId;
        territoryEditing = true;
        selectedTool = 'brush';
        brushSize = politicalDefaultBrushSize;
        outlinePoints = [];
        outlineClosed = false;
        unsavedChanges = true;
        renderSidebar();
        renderBottomTools();
      };

      document.getElementById('removePoliticalNationBtn').onclick = () => {
        if (!politicalEditNationId) return;
        const ok = window.confirm('Remove all territory for this nation from the political map?');
        if (!ok) return;
        const targetNationId = Number(politicalEditNationId);
        politicalStrokes = politicalStrokes.filter(op => Number(op.nation_id || 0) !== targetNationId);
        rebuildPoliticalFromStrokes();
        territoryEditing = false;
        unsavedChanges = true;
        labelCache = [];
        outlinePoints = [];
        outlineClosed = false;
        mapStatusMsg.textContent = 'Nation territory removed from map.';
        renderSidebar();
        renderBottomTools();
        render();
      };

      document.getElementById('exitPoliticalEditorBtn').onclick = () => setMode('view');
      bindTerrainColorInputs(mapSidePanel);
    } else {
      const allianceNames = Array.from(new Set(
        nations
          .map(n => String(n?.alliance_name || '').trim())
          .filter(Boolean)
      )).sort((a, b) => a.localeCompare(b));

      mapSidePanel.innerHTML = `
        <h3>Map Type</h3>
        <div class="map-scroll-list">
          <button class="primary mapTypeBtn map-type-item ${mapType === 'political' ? 'active' : ''}" data-type="political">Political Map</button>
          <button class="primary mapTypeBtn map-type-item ${mapType === 'alliance' ? 'active' : ''}" data-type="alliance">Alliance Map</button>
        </div>
        <div class="setting-group" style="margin-top:8px;">
          <label><input type="checkbox" id="terrainFilterToggle" ${terrainFilterEnabled ? 'checked' : ''}> Terrain filter overlay</label>
        </div>
        <h3 style="margin-top:10px;">Terrain Sq Miles</h3>
        <label style="font-size:13px;">View Nation</label>
        <select id="mapNationSelectView" style="margin-bottom:8px;">
          <option value="me">My Nation</option>
          ${nations.map(n => `<option value="${n.id}">${esc(n.name)}</option>`).join('')}
        </select>
        <div class="list" id="mapTerrainStats"></div>
        <div class="setting-group" style="margin-top:8px;">
          ${terrainColorControlsHtml()}
        </div>
        ${mapType === 'alliance' ? `
          <div class="setting-group" style="margin-top:8px;">
            <h3 style="margin-top:0;">Alliance Colors (Your View)</h3>
            <div class="map-small-label">These colors are saved per player.</div>
            <div class="map-scroll-list" style="max-height:180px;margin-top:6px;">
              ${allianceNames.map(name => {
                const key = String(name || '').trim().toLowerCase();
                const value = allianceColorOverrides[key] || '#7d7d7d';
                return `
                  <div class="terrain-color-row">
                    <label style="font-size:12px;">${esc(name)}</label>
                    <input type="color" class="allianceColorInput" data-key="${esc(key)}" value="${value}">
                  </div>
                `;
              }).join('') || '<div class="muted">No alliances found.</div>'}
            </div>
            <div class="row" style="margin-top:8px;">
              <button class="primary" id="saveAllianceColorsBtn">Save Alliance Colors</button>
              <button class="primary" id="resetAllianceColorsBtn">Reset</button>
            </div>
          </div>
        ` : ''}
      `;
      mapSidePanel.querySelectorAll('.mapTypeBtn').forEach(btn => {
        btn.onclick = () => {
          mapType = btn.dataset.type;
          politicalLayerDirty = true;
          setNationInfo(0);
          renderSidebar();
          render();
        };
      });
      document.getElementById('terrainFilterToggle').onchange = (e) => {
        terrainFilterEnabled = !!e.target.checked;
        render();
      };
      bindTerrainColorInputs(mapSidePanel);

      if (mapType === 'alliance') {
        mapSidePanel.querySelectorAll('.allianceColorInput').forEach(input => {
          input.addEventListener('input', () => {
            const key = String(input.dataset.key || '').trim().toLowerCase();
            if (!key) return;
            if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) {
              allianceColorOverrides[key] = input.value;
              politicalLayerDirty = true;
              render();
            }
          });
        });

        const saveAllianceColorsBtn = document.getElementById('saveAllianceColorsBtn');
        if (saveAllianceColorsBtn) {
          saveAllianceColorsBtn.onclick = async () => {
            const payload = normalizeAllianceColorOverrides(allianceColorOverrides);
            const saveRes = await api('/api/me/settings', {
              method: 'PATCH',
              body: JSON.stringify({ alliance_color_overrides: payload }),
            });
            if (!saveRes || !saveRes.ok) {
              mapStatusMsg.textContent = 'Failed to save alliance colors.';
              return;
            }
            settings.alliance_color_overrides = payload;
            mapStatusMsg.textContent = 'Alliance colors saved for your account.';
          };
        }

        const resetAllianceColorsBtn = document.getElementById('resetAllianceColorsBtn');
        if (resetAllianceColorsBtn) {
          resetAllianceColorsBtn.onclick = () => {
            allianceColorOverrides = {};
            settings.alliance_color_overrides = {};
            politicalLayerDirty = true;
            renderSidebar();
            render();
          };
        }
      }
      const renderTerrainStats = (sqMiles) => {
        const normalized = normalizeTerrainColorStats(sqMiles);
        const total = Math.max(1, Object.values(normalized).reduce((sum, val) => sum + toFiniteNumber(val, 0), 0));
        document.getElementById('mapTerrainStats').innerHTML = TERRAIN_KEYS.map((k) => {
          const v = normalized[k] || 0;
          const value = toFiniteNumber(v, 0);
          const pct = ((value / total) * 100).toFixed(1);
          return `<div>${labelTerrainKey(k)}: ${value} (${pct}%)</div>`;
        }).join('') || '<div class="muted">No data</div>';
      };
      renderTerrainStats(myTerrainSqMiles);
      document.getElementById('mapNationSelectView').onchange = async (e) => {
        if (e.target.value === 'me') {
          renderTerrainStats(myTerrainSqMiles);
          return;
        }
        const detailRes = await api('/api/nations/' + e.target.value);
        if (!detailRes || !detailRes.ok) return;
        const detail = await detailRes.json();
        renderTerrainStats(detail.terrain?.square_miles_json || {});
      };
    }

    mapSaveArea.innerHTML = (mode === 'terrain-editor' || mode === 'political-editor')
      ? '<button class="primary" id="saveMapEditorBtn">Save</button>'
      : '';
    const saveBtn = document.getElementById('saveMapEditorBtn');
    if (saveBtn) {
      saveBtn.onclick = async () => {
        const nationPayload = politicalNationsArray().map(n => ({
          id: n.id,
          name: n.name,
          alliance_name: n.alliance_name || '',
          races: n.races || [],
          pixels: nationPixelCount(n.id),
        }));
        const payload = {
          width: mapWidth,
          height: mapHeight,
          terrain_color_overrides: colorOverrides,
          terrain_strokes: terrainStrokes,
          political_strokes: politicalStrokes,
          political_nations: nationPayload,
          editor_background_path: editorBackgroundPath,
          editor_background_opacity: editorBackgroundOpacity,
        };
        const saveStateRes = await api('/api/admin/maps/editor-state', { method: 'POST', body: JSON.stringify(payload) });
        if (!saveStateRes || !saveStateRes.ok) {
          mapStatusMsg.textContent = 'Failed to save map editor state.';
          return;
        }

        const syncResult = await syncNationTerrainStats();
        if (!syncResult.ok) {
          mapStatusMsg.textContent = `Map saved, but ${syncResult.failedNationUpdates} nation terrain updates failed.`;
          return;
        }

        unsavedChanges = false;
        mapStatusMsg.textContent = 'Map saved.';
      };
    }
  };

  // Directly updates terrainLayerCanvas + waterLayerCanvas for a single brush circle,
  // avoiding a full O(W*H) rebuild during live painting.
  let brushPaletteSignature = '';
  let brushTerrainRgbByCode = [];
  let brushWaterRgb = { r: 53, g: 126, b: 199 };
  const syncBrushPaletteCache = () => {
    const palette = getPalette();
    const signature = JSON.stringify(palette);
    if (signature === brushPaletteSignature) return;
    const rgbByCode = new Array(TERRAIN_KEYS.length);
    for (let code = 0; code < TERRAIN_KEYS.length; code++) {
      const key = CODE_TO_TERRAIN[code];
      rgbByCode[code] = (key === 'tundra' || key === 'water') ? null : parseColorToRgb(palette[key] || '#ffffff');
    }
    brushPaletteSignature = signature;
    brushTerrainRgbByCode = rgbByCode;
    brushWaterRgb = parseColorToRgb(palette.water || '#357ec7');
  };

  const applyBrushToTerrainCanvas = (op) => {
    syncBrushPaletteCache();
    const cx = Math.floor(op.x);
    const cy = Math.floor(op.y);
    const r = brushRadiusFromSize(op.size);
    const bx = Math.max(0, cx - r);
    const by = Math.max(0, cy - r);
    const bw = Math.min(mapWidth, cx + r + 1) - bx;
    const bh = Math.min(mapHeight, cy + r + 1) - by;
    if (bw <= 0 || bh <= 0) return;
    const offsets = getBrushOffsets(r);
    const tImg = terrainLayerCtx.getImageData(bx, by, bw, bh);
    const wImg = waterLayerCtx.getImageData(bx, by, bw, bh);
    for (let i = 0; i < offsets.length; i += 2) {
      const gx = cx + offsets[i];
      const gy = cy + offsets[i + 1];
      if (!inBounds(gx, gy)) continue;
      const col = gx - bx;
      const row = gy - by;
      if (col < 0 || row < 0 || col >= bw || row >= bh) continue;
      const gi = gy * mapWidth + gx;
      const code = terrainGrid[gi];
      const p = (row * bw + col) * 4;
      if (code === TERRAIN_CODES.water) {
        tImg.data[p + 3] = 0;
        wImg.data[p] = brushWaterRgb.r; wImg.data[p + 1] = brushWaterRgb.g; wImg.data[p + 2] = brushWaterRgb.b; wImg.data[p + 3] = 255;
      } else if (code === TERRAIN_CODES.tundra) {
        tImg.data[p + 3] = 0;
        wImg.data[p + 3] = 0;
      } else {
        const rgb = brushTerrainRgbByCode[code];
        tImg.data[p] = rgb.r; tImg.data[p + 1] = rgb.g; tImg.data[p + 2] = rgb.b; tImg.data[p + 3] = 255;
        wImg.data[p + 3] = 0;
      }
    }
    terrainLayerCtx.putImageData(tImg, bx, by);
    waterLayerCtx.putImageData(wImg, bx, by);
  };

  // Directly updates politicalLayerCanvas for a single brush circle (fill pass only;
  // border rendering is deferred to a full rebuild on pointerup via politicalNeedsPostPaintBorderUpdate).
  const applyBrushToPoliticalCanvas = (op) => {
    const cx = Math.floor(op.x);
    const cy = Math.floor(op.y);
    const r = brushRadiusFromSize(op.size);
    const bx = Math.max(0, cx - r);
    const by = Math.max(0, cy - r);
    const bw = Math.min(mapWidth, cx + r + 1) - bx;
    const bh = Math.min(mapHeight, cy + r + 1) - by;
    if (bw <= 0 || bh <= 0) return;
    const offsets = getBrushOffsets(r);
    const colorCache = new Map();
    const cachedRgb = (c) => {
      if (!colorCache.has(c)) colorCache.set(c, parseColorToRgb(c));
      return colorCache.get(c);
    };
    const img = politicalLayerCtx.getImageData(bx, by, bw, bh);
    for (let i = 0; i < offsets.length; i += 2) {
      const gx = cx + offsets[i];
      const gy = cy + offsets[i + 1];
      if (!inBounds(gx, gy)) continue;
      const col = gx - bx;
      const row = gy - by;
      if (col < 0 || row < 0 || col >= bw || row >= bh) continue;
      const gi = gy * mapWidth + gx;
      const owner = ownerGrid[gi];
      const p = (row * bw + col) * 4;
      if (!owner) {
        img.data[p] = 0; img.data[p + 1] = 0; img.data[p + 2] = 0; img.data[p + 3] = 0;
      } else {
        let color = '#ffffff';
        if (mapType === 'alliance' || mode === 'political-editor') {
          const nation = getNationById(owner);
          color = nation?.alliance_name ? mapAllianceColor(nation.alliance_name) : '#7d7d7d';
        }
        const rgb = cachedRgb(color);
        img.data[p] = rgb.r; img.data[p + 1] = rgb.g; img.data[p + 2] = rgb.b; img.data[p + 3] = 210;
      }
    }
    politicalLayerCtx.putImageData(img, bx, by);
    politicalNeedsPostPaintBorderUpdate = true;
  };

  const commitPaintOperation = (op) => {
    if (mode === 'terrain-editor') {
      terrainStrokes.push(op);
      applyTerrainOperationToGrid(op, terrainGrid);
      if (op.tool === 'brush') {
        applyBrushToTerrainCanvas(op);
      } else {
        terrainLayerDirty = true;
        waterLayerDirty = true;
      }
      politicalNeedsFullRebuild = true;
    } else if (mode === 'political-editor') {
      politicalStrokes.push(op);
      if (politicalNeedsFullRebuild) {
        rebuildPoliticalFromStrokes();
      }
      applyPoliticalOperationToGrid(op, ownerGrid);
      if (op.tool === 'brush') {
        applyBrushToPoliticalCanvas(op);
      } else {
        politicalLayerDirty = true;
      }
    }
    if (mode === 'political-editor') {
      labelCache = [];
    }
    unsavedChanges = true;
  };

  const applyPaint = (wx, wy) => {
    if (!inBounds(wx, wy)) return;
    if (mode === 'terrain-editor') {
      if (selectedTool !== 'brush' && selectedTool !== 'fill') return;
      commitPaintOperation({
        tool: selectedTool === 'fill' ? 'fill' : 'brush',
        terrain: selectedTerrainType,
        x: wx,
        y: wy,
        size: brushSize,
      });
      scheduleRender();
      return;
    }

    if (mode === 'political-editor' && territoryEditing && politicalEditNationId) {
      if (selectedTool !== 'brush' && selectedTool !== 'fill' && selectedTool !== 'outline') return;
      if (selectedTool === 'outline') return;
      commitPaintOperation({
        tool: selectedTool === 'fill' ? 'fill' : 'brush',
        nation_id: politicalEditNationId,
        remove: politicalRemoveMode,
        x: wx,
        y: wy,
        size: brushSize,
      });
      scheduleRender();
      return;
    }
  };

  const applyBrushStrokeSegment = (fromPoint, toPoint) => {
    if (!fromPoint || !toPoint) {
      applyPaint(toPoint?.x, toPoint?.y);
      return;
    }
    const dx = toPoint.x - fromPoint.x;
    const dy = toPoint.y - fromPoint.y;
    const steps = Math.max(Math.abs(dx), Math.abs(dy), 1);
    const maxSamples = 120;
    const dynamicStride = Math.max(1, Math.floor(Math.max(1, brushSize * 0.6)));
    const stride = Math.max(dynamicStride, Math.ceil(steps / maxSamples));
    for (let step = stride; step <= steps; step += stride) {
      const x = Math.round(fromPoint.x + (dx * step) / steps);
      const y = Math.round(fromPoint.y + (dy * step) / steps);
      if (!inBounds(x, y)) continue;
      if (mode === 'terrain-editor') {
        commitPaintOperation({
          tool: 'brush',
          terrain: selectedTerrainType,
          x,
          y,
          size: brushSize,
        });
      } else if (mode === 'political-editor' && territoryEditing && politicalEditNationId) {
        commitPaintOperation({
          tool: 'brush',
          nation_id: politicalEditNationId,
          remove: politicalRemoveMode,
          x,
          y,
          size: brushSize,
        });
      }
    }
    const endX = Math.round(toPoint.x);
    const endY = Math.round(toPoint.y);
    if (inBounds(endX, endY)) {
      if (mode === 'terrain-editor') {
        commitPaintOperation({ tool: 'brush', terrain: selectedTerrainType, x: endX, y: endY, size: brushSize });
      } else if (mode === 'political-editor' && territoryEditing && politicalEditNationId) {
        commitPaintOperation({ tool: 'brush', nation_id: politicalEditNationId, remove: politicalRemoveMode, x: endX, y: endY, size: brushSize });
      }
    }
    scheduleRender();
  };

  document.getElementById('mapZoomPercent').oninput = (e) => {
    zoomTargetPct = clamp(toFiniteNumber(e.target.value, 0), minZoomPct, maxZoomPct);
    animateZoomToTarget();
  };

  stage.addEventListener('wheel', (e) => {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    zoomTargetPct = clamp(zoomTargetPct + (e.deltaY < 0 ? 3 : -3), minZoomPct, maxZoomPct);
    document.getElementById('mapZoomPercent').value = String(Math.round(zoomTargetPct));
    animateZoomToTarget({ sx: e.clientX - rect.left, sy: e.clientY - rect.top });
  }, { passive: false });

  canvas.addEventListener('pointerdown', (e) => {
    dragging = true;
    canvas.setPointerCapture(e.pointerId);
    downPoint = { x: e.clientX, y: e.clientY, panX, panY };
    canvas.classList.add('dragging');

    const inEditorMode = (mode === 'terrain-editor' || mode === 'political-editor');
    const canPaint = (mode === 'terrain-editor' || (mode === 'political-editor' && territoryEditing));
    const shiftMoveOverride = inEditorMode && e.shiftKey;
    if (selectedTool === 'move' || shiftMoveOverride) {
      dragAction = 'move';
      canvas.style.cursor = 'grabbing';
      return;
    }
    dragAction = canPaint ? 'paint' : 'none';

    if (canPaint) {
      const { wx, wy } = toWorld(e.clientX, e.clientY);
      lastPaintPoint = { x: wx, y: wy };
      if (mode === 'political-editor' && selectedTool === 'outline') {
        if (outlineClosed) {
          outlinePoints = [];
          outlineClosed = false;
        }
        if (!outlinePoints.length) {
          outlinePoints.push({ x: wx, y: wy });
          lastOutlinePoint = { x: wx, y: wy };
          scheduleRender();
        }
      }
      applyPaint(wx, wy);
    }
  });

  canvas.addEventListener('pointermove', (e) => {
    if (!dragging || !downPoint) return;

    if (dragAction === 'move') {
      panX = downPoint.panX + (e.clientX - downPoint.x);
      panY = downPoint.panY + (e.clientY - downPoint.y);
      scheduleRender();
      return;
    }

    if (dragAction === 'paint') {
      if (selectedTool === 'brush') {
        const { wx, wy } = toWorld(e.clientX, e.clientY);
        applyBrushStrokeSegment(lastPaintPoint, { x: wx, y: wy });
        lastPaintPoint = { x: wx, y: wy };
      } else if (selectedTool === 'outline' && mode === 'political-editor') {
        const { wx, wy } = toWorld(e.clientX, e.clientY);
        const next = { x: wx, y: wy };
        if (!lastOutlinePoint || pointDistance(lastOutlinePoint, next) >= 2) {
          outlinePoints.push(next);
          lastOutlinePoint = next;
          scheduleRender();
        }
      }
      return;
    }
    if (zoomPct <= 0) return;
    panX = downPoint.panX + (e.clientX - downPoint.x);
    panY = downPoint.panY + (e.clientY - downPoint.y);
    scheduleRender();
  });

  const releasePointer = (e) => {
    if (!dragging) return;
    const wasClick = downPoint && Math.hypot(e.clientX - downPoint.x, e.clientY - downPoint.y) < 4;
    dragging = false;
    dragAction = 'none';
    lastPaintPoint = null;
    lastOutlinePoint = null;
    // After brush painting on the political layer, trigger a full rebuild to restore border lines.
    if (politicalNeedsPostPaintBorderUpdate) {
      politicalLayerDirty = true;
      politicalNeedsPostPaintBorderUpdate = false;
      scheduleRender();
    }
    if (mode === 'political-editor' && territoryEditing && selectedTool === 'outline' && outlinePoints.length >= 3) {
      const closeThreshold = Math.max(5, brushSize * 1.5);
      if (pointDistance(outlinePoints[0], outlinePoints[outlinePoints.length - 1]) <= closeThreshold) {
        outlinePoints[outlinePoints.length - 1] = { ...outlinePoints[0] };
        outlineClosed = true;
      } else {
        outlineClosed = false;
      }
      renderSidebar();
      scheduleRender();
    }
    if (canvas.hasPointerCapture(e.pointerId)) {
      canvas.releasePointerCapture(e.pointerId);
    }
    canvas.classList.remove('dragging');
    canvas.style.cursor = selectedTool === 'move' ? 'grab' : ((mode === 'terrain-editor' || mode === 'political-editor') ? 'crosshair' : 'grab');
    if (wasClick && mode === 'view') {
      const { wx, wy } = toWorld(e.clientX, e.clientY);
      if (inBounds(wx, wy)) {
        const nationId = ownerGrid[idx(wx, wy)];
        setNationInfo(nationId || 0);
      }
    }
  };
  canvas.addEventListener('pointerup', releasePointer);
  canvas.addEventListener('pointercancel', releasePointer);

  mapFullscreenBtn.onclick = async () => {
    try {
      if (!document.fullscreenElement) {
        await stage.requestFullscreen();
      } else {
        await document.exitFullscreen();
      }
    } catch {}
  };

  if (user.role === 'admin') {
    document.getElementById('openTerrainEditorBtn').onclick = () => setMode('terrain-editor');
    document.getElementById('openPoliticalEditorBtn').onclick = () => setMode('political-editor');
    document.getElementById('recalcTerrainStatsBtn').onclick = async () => {
      const ok = window.confirm('Recalculate terrain stats for all nations from the current map pixels?');
      if (!ok) return;
      const syncResult = await syncNationTerrainStats();
      if (!syncResult.ok) {
        mapStatusMsg.textContent = `Recalculation failed for ${syncResult.failedNationUpdates} nation(s).`;
        return;
      }
      mapStatusMsg.textContent = `Terrain stats recalculated for ${syncResult.updatedCount} nation(s).`;
      renderSidebar();
    };
    document.getElementById('resetMapBtn').onclick = async () => {
      const firstWarning = window.confirm('This will permanently reset the entire map, clear all map layers, and reset all nation terrain map values. Continue?');
      if (!firstWarning) return;

      const phrase = window.prompt('Type exactly: confirm reset of map');
      if ((phrase || '').trim() !== 'confirm reset of map') {
        mapStatusMsg.textContent = 'Map reset cancelled: confirmation text did not match.';
        return;
      }

      const resetRes = await api('/api/admin/maps/reset', { method: 'POST' });
      if (!resetRes || !resetRes.ok) {
        mapStatusMsg.textContent = 'Failed to reset map.';
        return;
      }

      await loadMap();
    };
  }

  resizeLayerCanvases();
  rebuildTerrainFromStrokes();
  rebuildPoliticalFromStrokes();
  computeLabels();
  renderSidebar();
  renderTopEditorControls();
  renderBottomTools();
  resizeCanvas();
  render();
  window.addEventListener('resize', () => {
    resizeCanvas();
    render();
  });
}

async function loadChat(preferredChatId = null) {
  const [, chatsRes, playersRes] = await Promise.all([
    ensureWs(),
    api('/api/chats'),
    api('/api/players'),
  ]);
  if (!chatsRes?.ok) {
    throw new Error(await readErrorMessage(chatsRes, 'The chat list could not be loaded.'));
  }
  if (!playersRes?.ok) {
    throw new Error(await readErrorMessage(playersRes, 'The player list could not be loaded.'));
  }
  const chats = extractList(await parseJsonResponse(chatsRes, []));
  const players = await parseJsonResponse(playersRes, []);
  const activeChats = chats.filter(chat => !chat.is_archived);
  const archivedChats = chats.filter(chat => chat.is_archived);
  const chatsById = new Map(chats.map(chat => [Number(chat.id), chat]));
  const firstChat = chatsById.get(Number(preferredChatId)) || activeChats[0] || archivedChats[0] || null;

  const playerCheckboxes = players
    .filter(player => player.id !== user.id)
    .map(player => `<label style="display:flex;align-items:center;gap:6px;padding:4px 0;"><input type="checkbox" class="memberCheck" value="${player.id}"> ${player.name}</label>`)
    .join('');

  view.innerHTML = `
    <div class="card">
      <div class="twocol">
        <div>
          <h2 id="chatHeader" style="margin-top:0;">Chat</h2>
          <div id="chatView" class="list" style="min-height:220px;">Select a chat.</div>
          <div class="row" style="margin-top:8px;"><input id="chatMsg" placeholder="Message…"><button class="primary" id="sendMsg">Send</button></div>
          <div class="row" style="margin-top:10px;flex-wrap:wrap;">
            <span class="muted" id="chatUnreadStatus">No chat selected.</span>
            <button class="primary" id="markChatReadBtn" style="background:#2f6a41;">Read</button>
            <button class="primary" id="markChatUnreadBtn" style="background:#7a5b1f;">Unread</button>
          </div>
          <div class="row" style="margin-top:10px;flex-wrap:wrap;">
            <button class="primary" id="archiveChatBtn" style="background:#314f72;">Archive</button>
            <button class="primary" id="unarchiveChatBtn" style="display:none;background:#314f72;">Unarchive</button>
            <button class="primary" id="deleteChatForMeBtn" style="background:#8a1a1a;">Delete</button>
            <span class="muted" id="chatActionMsg"></span>
          </div>
        </div>
        <div>
          <div class="row"><input id="chatName" placeholder="New chat name"></div>
          ${user.role === 'admin' ? '<label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="chatAutoIncludeAll"> Auto include all current and future players</label>' : ''}
          <label style="font-size:13px;margin-top:8px;display:block;">Add players:</label>
          <div id="playerPickerList" style="max-height:160px;overflow:auto;border:1px solid #bfc8d2;border-radius:8px;padding:6px;margin-bottom:6px;">${playerCheckboxes || '<span class="muted">No other players</span>'}</div>
          <div class="row"><button class="primary" id="newChat">Create Chat</button><span class="muted" id="chatCreateMsg"></span></div>
          <h3>Chats</h3>
          <div class="list" id="chatList">${activeChats.map(chat => `<div><button class="primary selectChat" data-id="${chat.id}" data-name="${chat.name.replace(/"/g, '&quot;')}" data-archived="0" style="width:100%; margin-bottom:8px;">${chat.name}${chat.type === 'global' ? ' 🌐' : ''}${Number(chat.unread_messages || 0) > 0 ? ` (${Number(chat.unread_messages)})` : ''}</button></div>`).join('') || '<div class="muted">No active chats</div>'}</div>
          <h3 style="margin-top:12px;">Archived</h3>
          <div class="list" id="archivedChatList" style="max-height:140px;">${archivedChats.map(chat => `<div><button class="primary selectChat" data-id="${chat.id}" data-name="${chat.name.replace(/"/g, '&quot;')}" data-archived="1" style="width:100%; margin-bottom:8px; opacity:0.7;">${chat.name}</button></div>`).join('') || '<div class="muted">No archived chats</div>'}</div>
        </div>
      </div>
    </div>
  `;

  let activeChatId = firstChat ? Number(firstChat.id) : null;
  let activeChatArchived = !!firstChat?.is_archived;

  const setChatActions = () => {
    const activeChat = chatsById.get(Number(activeChatId)) || null;
    const unreadMessages = Number(activeChat?.unread_messages || 0);
    document.getElementById('chatUnreadStatus').textContent = activeChatId
      ? `Unread messages: ${unreadMessages}`
      : 'No chat selected.';
    document.getElementById('markChatReadBtn').disabled = !activeChatId || unreadMessages === 0;
    document.getElementById('markChatUnreadBtn').disabled = !activeChatId || unreadMessages > 0;
    document.getElementById('archiveChatBtn').style.display = activeChatId && !activeChatArchived ? 'inline-block' : 'none';
    document.getElementById('unarchiveChatBtn').style.display = activeChatId && activeChatArchived ? 'inline-block' : 'none';
    document.getElementById('deleteChatForMeBtn').style.display = activeChatId ? 'inline-block' : 'none';
  };

  async function loadMessages(chatId, chatName, isArchived = false) {
    if (!chatId) return;
    activeChatId = Number(chatId);
    activeChatArchived = !!isArchived;
    setChatActions();
    document.getElementById('chatActionMsg').textContent = '';
    if (chatName) document.getElementById('chatHeader').textContent = chatName;
    await subscribeChannel(`chat.${chatId}`);
    const res = await api(`/api/chats/${chatId}/messages`);
    if (!res?.ok) {
      throw new Error(await readErrorMessage(res, 'The chat messages could not be loaded.'));
    }
    const messages = extractList(await parseJsonResponse(res, []));
    document.getElementById('chatView').innerHTML = messages.map(message => {
      const isOwn = Number(message.sender_user_id) === Number(user.id);
      return `<div class="msg-wrap ${isOwn ? 'own' : 'other'}">
        <div class="msg-sender">${message.sender_name}</div>
        <div class="msg-bubble">${escapeHtml(message.message)}</div>
      </div>`;
    }).join('') || '<div class="muted">No messages</div>';
    const chatView = document.getElementById('chatView');
    chatView.scrollTop = chatView.scrollHeight;
    setChatActions();
  }

  document.querySelectorAll('.selectChat').forEach(btn => {
    btn.onclick = () => loadMessages(btn.dataset.id, btn.dataset.name, btn.dataset.archived === '1');
  });
  if (firstChat) {
    await loadMessages(firstChat.id, firstChat.name, firstChat.is_archived);
  } else {
    setChatActions();
  }

  document.getElementById('sendMsg').onclick = async () => {
    if (!activeChatId) return;
    const message = document.getElementById('chatMsg').value.trim();
    if (!message) return;
    const res = await api(`/api/chats/${activeChatId}/messages`, { method: 'POST', body: JSON.stringify({ message }) });
    if (!res?.ok) {
      document.getElementById('chatActionMsg').textContent = await readErrorMessage(res, 'The message could not be sent.');
      return;
    }
    document.getElementById('chatMsg').value = '';
    const activeBtn = document.querySelector(`.selectChat[data-id="${activeChatId}"]`);
    await loadMessages(activeChatId, activeBtn ? activeBtn.dataset.name : null, activeChatArchived);
    refreshChatBadge();
    barkIfEnabled();
  };

  document.getElementById('chatMsg').addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      document.getElementById('sendMsg').click();
    }
  });

  document.getElementById('markChatReadBtn').onclick = async () => {
    if (!activeChatId) return;
    const res = await api(`/api/chats/${activeChatId}/read`, { method: 'PATCH' });
    document.getElementById('chatActionMsg').textContent = res?.ok ? 'Chat marked as read.' : await readErrorMessage(res, 'The chat could not be marked as read.');
    if (res?.ok) {
      await loadChat(activeChatId);
      refreshChatBadge();
    }
  };

  document.getElementById('markChatUnreadBtn').onclick = async () => {
    if (!activeChatId) return;
    const res = await api(`/api/chats/${activeChatId}/unread`, { method: 'PATCH' });
    document.getElementById('chatActionMsg').textContent = res?.ok ? 'Chat marked as unread.' : await readErrorMessage(res, 'The chat could not be marked as unread.');
    if (res?.ok) {
      await loadChat(activeChatId);
      refreshChatBadge();
    }
  };

  document.getElementById('archiveChatBtn').onclick = async () => {
    if (!activeChatId) return;
    const res = await api(`/api/chats/${activeChatId}/archive`, { method: 'PATCH' });
    document.getElementById('chatActionMsg').textContent = res?.ok ? 'Chat archived.' : await readErrorMessage(res, 'The chat could not be archived.');
    if (res?.ok) await loadChat(activeChatId);
  };

  document.getElementById('unarchiveChatBtn').onclick = async () => {
    if (!activeChatId) return;
    const res = await api(`/api/chats/${activeChatId}/unarchive`, { method: 'PATCH' });
    document.getElementById('chatActionMsg').textContent = res?.ok ? 'Chat restored.' : await readErrorMessage(res, 'The chat could not be restored.');
    if (res?.ok) await loadChat(activeChatId);
  };

  document.getElementById('deleteChatForMeBtn').onclick = async () => {
    if (!activeChatId) return;
    const chatButton = document.querySelector(`.selectChat[data-id="${activeChatId}"]`);
    const chatName = chatButton?.dataset.name || 'this chat';
    if (!window.confirm(`Remove ${chatName} from your list? This cannot be undone from the UI.`)) {
      return;
    }
    const res = await api(`/api/chats/${activeChatId}`, { method: 'DELETE' });
    document.getElementById('chatActionMsg').textContent = res?.ok ? 'Chat deleted from your list.' : await readErrorMessage(res, 'The chat could not be deleted.');
    if (res?.ok) await loadChat();
  };

  document.getElementById('newChat').onclick = async () => {
    const name = document.getElementById('chatName').value.trim();
    const memberIds = Array.from(document.querySelectorAll('.memberCheck:checked')).map(el => Number(el.value));
    const autoIncludeAll = user.role === 'admin' && document.getElementById('chatAutoIncludeAll')?.checked;

    if (!name) {
      document.getElementById('chatCreateMsg').textContent = 'Enter a chat name before creating the chat.';
      return;
    }
    if (!autoIncludeAll && memberIds.length === 0) {
      document.getElementById('chatCreateMsg').textContent = 'Select at least one player for a normal group chat.';
      return;
    }

    const res = await api('/api/chats', {
      method: 'POST',
      body: JSON.stringify({ name, type: autoIncludeAll ? 'global' : 'group', member_ids: memberIds })
    });
    document.getElementById('chatCreateMsg').textContent = res?.ok
      ? (autoIncludeAll ? 'Everyone chat created.' : 'Chat created.')
      : await readErrorMessage(res, 'The chat could not be created.');
    if (res?.ok) {
      await loadChat();
      refreshChatBadge();
      barkIfEnabled();
    }
  };

  refreshChatBadge();
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
    const payload = await parseJsonResponse(res, []);
    const list = extractList(payload);
    const box = document.getElementById('nationList');
    box.innerHTML = list.map(n => `<button class="primary nationBtn" data-id="${n.id}" style="display:block; width:100%; margin-bottom:8px;">${escapeHtml(n.name)} (${escapeHtml(n.player_name || 'Unassigned')})</button>`).join('');
    document.querySelectorAll('.nationBtn').forEach(btn => btn.onclick = async () => {
      const detailRes = await api('/api/nations/' + btn.dataset.id);
      if (!detailRes?.ok) {
        document.getElementById('nationDetail').innerHTML = `<div class="danger">${escapeHtml(await readErrorMessage(detailRes, 'The nation details could not be loaded.'))}</div>`;
        return;
      }
      const d = await parseJsonResponse(detailRes, {});
      const visibility = d.visibility || {};
      const resourceExtra = safeJsonParse(d.resources?.extra_json, {}) || {};
      const refined = resourceExtra.refined || {};
      const currencies = resourceExtra.currencies || {};
      const terrainSqMiles = normalizeTerrainSquareMiles(d.terrain?.square_miles_json || {});
      const terrainTotal = Math.max(1, Object.values(terrainSqMiles).reduce((sum, value) => sum + toFiniteNumber(value, 0), 0));
      const sections = [];

      const renderSection = (title, body) => `
        <details open style="margin-top:10px;">
          <summary>${escapeHtml(title)}</summary>
          <div style="border:1px solid #c9d1db;border-radius:8px;padding:8px;margin-top:6px;">${body}</div>
        </details>
      `;

      const overviewRows = [];
      if (visibility.leader_name && d.nation?.leader_name) {
        overviewRows.push(`<div class="res-kv"><span>Leader</span><span>${escapeHtml(d.nation.leader_name)}</span></div>`);
      }
      if (visibility.alliance_name && d.nation?.alliance_name) {
        overviewRows.push(`<div class="res-kv"><span>Alliance</span><span>${escapeHtml(d.nation.alliance_name)}</span></div>`);
      }
      if (visibility.about_text && d.nation?.about_text) {
        overviewRows.push(`<div style="margin-top:8px;white-space:pre-wrap;">${escapeHtml(d.nation.about_text)}</div>`);
      }
      if (overviewRows.length > 0) {
        sections.push(renderSection('Overview', overviewRows.join('')));
      }

      if (visibility.resources_base && d.resources) {
        sections.push(renderSection(
          'Base Resources',
          renderKVList({ cow: 'Cow', wood: 'Wood', ore: 'Ore', food: 'Food' }, d.resources, { showZero: false }) || '<div class="muted">No base resource data visible.</div>'
        ));
      }

      if (visibility.resources_refined) {
        sections.push(renderSection(
          'Refined Resources',
          [
            `<div style="font-size:12px;color:#666;margin-bottom:4px;">Ore-derived</div>${renderKVList(ORE_REFS, refined, { showZero: false }) || '<div class="muted" style="margin-bottom:8px;">None</div>'}`,
            `<div style="font-size:12px;color:#666;margin:8px 0 4px;">Wood-derived</div>${renderKVList(WOOD_REFS, refined, { showZero: false }) || '<div class="muted" style="margin-bottom:8px;">None</div>'}`,
            `<div style="font-size:12px;color:#666;margin:8px 0 4px;">Food-derived</div>${renderKVList(FOOD_REFS, refined, { showZero: false }) || '<div class="muted">None</div>'}`,
          ].join('')
        ));
      }

      if (visibility.resources_currencies) {
        sections.push(renderSection('Currencies', renderKVList(CURRENCIES, currencies, { showZero: false }) || '<div class="muted">No currency data visible.</div>'));
      }

      if (visibility.terrain && d.terrain) {
        const terrainHtml = Object.entries(terrainSqMiles)
          .filter(([, value]) => toFiniteNumber(value, 0) > 0)
          .map(([key, value]) => `<div class="res-kv"><span>${escapeHtml(labelTerrainKey(key))}</span><span>${toFiniteNumber(value, 0)} sq mi (${((toFiniteNumber(value, 0) / terrainTotal) * 100).toFixed(1)}%)</span></div>`)
          .join('') || '<div class="muted">No terrain data visible.</div>';
        sections.push(renderSection('Terrain', terrainHtml));
      }

      if (visibility.units) {
        const units = Array.isArray(d.units) ? d.units : extractList(d.units);
        const unitsHtml = units.length > 0
          ? units.map(unit => `<div class="res-kv"><span>${escapeHtml(unit.display_name || unit.custom_name || 'Unit')} x${Number(unit.qty || 0)}</span><span>${escapeHtml(unit.class_name || unit.status || 'unit')}</span></div>`).join('')
          : '<div class="muted">No unit data visible.</div>';
        sections.push(renderSection('Units', unitsHtml));
      }

      if (visibility.buildings) {
        const buildings = Array.isArray(d.buildings) ? d.buildings : extractList(d.buildings);
        const buildingsHtml = buildings.length > 0
          ? buildings.map(building => `<div class="res-kv"><span>${escapeHtml(building.display_name || building.code || 'Building')}</span><span>L${Number(building.level || 1)}${building.status ? ` (${escapeHtml(building.status)})` : ''}</span></div>`).join('')
          : '<div class="muted">No building data visible.</div>';
        sections.push(renderSection('Buildings', buildingsHtml));
      }

      document.getElementById('nationDetail').innerHTML = `
        <div><strong>${escapeHtml(d.nation?.name || 'Unknown Nation')}</strong> (${escapeHtml(d.nation?.player_name || 'Unassigned')})</div>
        <div class="muted" style="margin-top:4px;">Visible data is controlled by the player visibility matrix.</div>
        ${sections.join('') || '<div class="muted" style="margin-top:10px;">No other nation details are currently visible.</div>'}
      `;
      barkIfEnabled();
    });
  };

  document.getElementById('nationSearch').addEventListener('input', e => loadList(e.target.value));
  loadList();
}

async function loadShop() {
  const isAdmin = user.role === 'admin';
  const calls = [api('/api/shop/categories'), api('/api/shop/items?per_page=300'), api('/api/me/buildings?status=built')];
  if (isAdmin) calls.push(api('/api/players'));
  if (isAdmin) calls.push(api('/api/admin/shop/item-templates'));
  const [catRes, itemRes, buildingsRes, playersRes, templateRes] = await Promise.all(calls);
  const cats = await catRes.json();
  const itemsPayload = await itemRes.json();
  const allItems = extractList(itemsPayload);
  const myBuildings = await buildingsRes.json();
  const allPlayers = playersRes ? await playersRes.json() : [];
  const itemTemplates = templateRes && templateRes.ok ? await templateRes.json() : [];

  const buildingCounts = {};
  (Array.isArray(myBuildings) ? myBuildings : []).forEach(b => {
    const family = String(b.code || '').toLowerCase();
    const level = Number(b.level || 1);
    const key = `${family}:l${level}`;
    buildingCounts[key] = (buildingCounts[key] || 0) + Number(b.qty || 1);
  });

  const parseStructCode = (code) => {
    const match = String(code || '').match(/^struct_([a-z0-9_]+)_l([0-9]+)$/);
    if (!match) return null;
    return { family: match[1], level: Number(match[2]) };
  };

  const canBuyUpgrade = (item) => {
    const effectObj = (() => { try { return JSON.parse(item.effect_json || '{}'); } catch { return {}; } })();
    if (effectObj.requires_building_code) {
      const reqLevel = Number(effectObj.requires_building_level || 1);
      const reqKey = `${String(effectObj.requires_building_code).toLowerCase()}:l${reqLevel}`;
      if ((buildingCounts[reqKey] || 0) <= 0) {
        return false;
      }
      if (effectObj.requires_building_code_2) {
        const reqLevel2 = Number(effectObj.requires_building_level_2 || 1);
        const reqKey2 = `${String(effectObj.requires_building_code_2).toLowerCase()}:l${reqLevel2}`;
        if ((buildingCounts[reqKey2] || 0) <= 0) {
          return false;
        }
      }
    }

    const meta = parseStructCode(item.code);
    if (!meta || meta.level <= 1 || item.category_code !== 'upgrades') return true;
    const requiredKey = `${meta.family}:l${meta.level - 1}`;
    return (buildingCounts[requiredKey] || 0) > 0;
  };

  view.innerHTML = `
    <div class="card">
      <h2>Shop</h2>
      <div id="shopSelectedCategory" class="chip" style="margin-bottom:10px;display:inline-flex;">No category selected</div>
      <div id="shopPurchaseMsg" class="muted" style="margin:6px 0 10px 0;"></div>
      <div class="twocol">
        <div id="shopItems" class="list">Select a category.</div>
        <div id="shopCats" class="list">${cats.map(c => `<button class="primary catBtn" data-code="${c.code}" style="display:block; width:100%; margin-bottom:8px; opacity:0.7;">${c.display_name}</button>`).join('')}
          ${isAdmin ? `<hr><h3 style="margin:8px 0;">Add Item</h3>
            <label style="font-size:12px;">Category</label>
            <select id="newShopCategory">${cats.map(c => `<option value="${c.id}">${c.display_name}</option>`).join('')}</select>
            <label style="font-size:12px;">Template (search by name)</label>
            <input id="newShopTemplateSearch" list="newShopTemplateList" placeholder="Type to search units/structures/items">
            <datalist id="newShopTemplateList">
              ${itemTemplates.map((t, i) => `<option value="${t.name}" data-idx="${i}"></option>`).join('')}
            </datalist>
            <label style="font-size:12px;">Name</label><input id="newShopName" placeholder="Display name">
            <label style="font-size:12px;">Description / Effects</label><textarea id="newShopDescription" rows="4"></textarea>
            <label style="font-size:12px;">Product (Purchase Effect JSON)</label><textarea id="newShopProduct" rows="4">{}</textarea>
            <div class="row"><button class="primary" id="createShopItemBtn" style="width:100%;">Create Item</button></div>
            <span class="muted" id="createShopItemMsg"></span>` : ''}
        </div>
      </div>
    </div>
  `;

  let activeCategory = null;

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
    activeCategory = category;
    const items = allItems.filter(i => i.category_code === category);
    if (category === 'upgrades') {
      items.sort((a, b) => Number(canBuyUpgrade(b)) - Number(canBuyUpgrade(a)));
    }
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
      const effectObj = (() => { try { return JSON.parse(i.effect_json || '{}'); } catch { return {}; } })();
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
                <label style="font-size:12px;margin-top:8px;display:block;">Product (Purchase Effect JSON)</label>
                <textarea id="effect-${i.id}" rows="4">${JSON.stringify(effectObj || {}, null, 2)}</textarea>
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
        const isUpgradeAvailable = canBuyUpgrade(i);
        return `
          <div class="card">
            <div><strong>${i.display_name}</strong></div>
            <div class="muted" style="font-size:12px;">${i.description_text || ''}</div>
            <div class="muted" style="font-size:13px;">Cost: ${formatCost(costObj)}</div>
            ${Object.keys(maintenanceObj).length ? `<div class="muted" style="font-size:12px;">Yearly maintenance: ${formatCost(maintenanceObj)}</div>` : ''}
            ${(!isUpgradeAvailable && i.category_code === 'upgrades') ? '<div class="muted" style="font-size:12px;margin-top:6px;">Need a lower-level structure to upgrade.</div>' : ''}
            <button class="primary buyItem" data-id="${i.id}" ${(!isUpgradeAvailable && i.category_code === 'upgrades') ? 'disabled style="margin-top:6px;opacity:0.45;cursor:not-allowed;"' : 'style="margin-top:6px;"'}>Buy</button>
          </div>`;
      }
    }).join('') || '<div class="muted">No items</div>';

    document.querySelectorAll('.buyItem').forEach(btn => btn.onclick = async () => {
      const r = await api('/api/shop/buy', { method: 'POST', body: JSON.stringify({ item_id: Number(btn.dataset.id), quantity: 1 }) });
      if (r.ok) {
        document.getElementById('shopPurchaseMsg').textContent = 'Purchase successful.';
        loadResources();
        const reload = await api('/api/shop/items?per_page=300');
        if (reload && reload.ok) {
          const refreshed = extractList(await reload.json());
          allItems.splice(0, allItems.length, ...refreshed);
        }
        renderItems(activeCategory || category);
      } else {
        let reason = 'Purchase failed.';
        try {
          const payload = await r.json();
          reason = payload.message ? `Purchase failed: ${payload.message}` : reason;
        } catch {}
        document.getElementById('shopPurchaseMsg').textContent = reason;
      }
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
      let effect_json = {};
      try {
        effect_json = safeJsonParse(document.getElementById(`effect-${id}`).value, {});
        if (!effect_json || typeof effect_json !== 'object') effect_json = {};
      } catch {
        effect_json = {};
      }
      const r = await api(`/api/admin/shop/items/${id}`, { method: 'PUT', body: JSON.stringify({ cost_json, maintenance_json, yearly_effect_json, effect_json, description_text, visibility_json }) });
      const msgEl = document.getElementById(`edit-msg-${id}`);
      if (msgEl) msgEl.textContent = r.ok ? 'Saved' : 'Failed';
      if (r.ok) {
        const reload = await api('/api/shop/items?per_page=300');
        const refreshed = extractList(await reload.json());
        allItems.splice(0, allItems.length, ...refreshed);
        renderItems(category);
      }
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

  document.getElementById('newShopTemplateSearch')?.addEventListener('input', (event) => {
    const term = (event.target.value || '').trim().toLowerCase();
    if (!term) return;
    const template = (itemTemplates || []).find(t => String(t.name || '').toLowerCase() === term)
      || (itemTemplates || []).find(t => String(t.name || '').toLowerCase().includes(term));
    if (!template) return;
    document.getElementById('newShopName').value = template.name || '';
    document.getElementById('newShopDescription').value = template.description_text || '';
    document.getElementById('newShopProduct').value = JSON.stringify(template.effect_json || {}, null, 2);
  });

  document.getElementById('createShopItemBtn')?.addEventListener('click', async () => {
    let effect_json = {};
    try {
      effect_json = safeJsonParse(document.getElementById('newShopProduct').value, {});
      if (!effect_json || typeof effect_json !== 'object') effect_json = {};
    } catch {
      effect_json = {};
    }
    const payload = {
      category_id: Number(document.getElementById('newShopCategory').value),
      display_name: document.getElementById('newShopName').value.trim(),
      description_text: document.getElementById('newShopDescription').value,
      effect_json,
      cost_json: {},
    };
    const r = await api('/api/admin/shop/items', { method: 'POST', body: JSON.stringify(payload) });
    document.getElementById('createShopItemMsg').textContent = r.ok ? 'Created' : 'Failed';
    if (r.ok) {
      const reload = await api('/api/shop/items?per_page=300');
      const refreshed = extractList(await reload.json());
      allItems.splice(0, allItems.length, ...refreshed);
      const categoryCode = cats.find(c => Number(c.id) === payload.category_id)?.code;
      if (categoryCode) {
        renderItems(categoryCode);
      } else if (activeCategory) {
        renderItems(activeCategory);
      }
    }
    barkIfEnabled();
  });
}

async function loadNewAccounts() {
  const [defaultsRes, usersRes] = await Promise.all([
    api('/api/admin/new-account-defaults'),
    api('/api/admin/users?role=player'),
  ]);
  const d = await defaultsRes.json();
  const players = await usersRes.json();
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
          <label id="na-sq-label-seafront">Sea Front</label><input id="na-sq-seafront" type="number" value="${Number(terrainSq.seafront || 0)}">
        </div>
      </details>

      <hr style="margin:12px 0;">
      <h3>Create Account</h3>
      <label>Username / Display Name</label>
      <input id="na-create-name" placeholder="New account name">
      <label>Email</label>
      <input id="na-create-email" type="email" placeholder="account@example.com">
      <label>Temporary Password</label>
      <input id="na-create-password" value="${d.default_temp_password || 'password123'}">
      <label>Role</label>
      <select id="na-create-role"><option value="player">Player</option><option value="admin">Admin</option></select>
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="na-create-nation" checked> Create nation for this account</label>
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="na-force-reset" checked> Require password reset on first login</label>
      <div class="row"><button class="primary" id="createManagedAccountBtn">Create Account</button><span class="muted" id="createManagedAccountMsg"></span></div>

      <hr style="margin:12px 0;">
      <h3>Delete Player Account</h3>
      <p class="danger" style="margin-top:0;">This permanently removes the player account and its owned nation data.</p>
      <label>Player Account</label>
      <select id="deletePlayerId">${players.map(player => `<option value="${player.id}" data-name="${player.name.replace(/"/g, '&quot;')}">${player.name} (${player.email})</option>`).join('')}</select>
      <label>Type the exact username to confirm deletion</label>
      <input id="deletePlayerConfirmName" placeholder="Exact username required">
      <div class="row"><button class="primary" id="deletePlayerBtn" style="background:#8a1a1a;">Delete Player Permanently</button><span class="muted" id="deletePlayerMsg"></span></div>

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
      seafront: Number(document.getElementById('na-sq-seafront').value || 0),
    };
    const total = Math.max(1, Object.values(sq).reduce((sum, value) => sum + value, 0));
    Object.entries(sq).forEach(([key, value]) => {
      const pct = ((value / total) * 100).toFixed(1);
      document.getElementById(`na-sq-label-${key}`).textContent = `${labelTerrainKey(key)} (${pct}%)`;
    });
  };
  ['grassland', 'mountain', 'freshwater', 'hills', 'desert', 'seafront'].forEach(key => {
    document.getElementById(`na-sq-${key}`).addEventListener('input', updateSqLabels);
  });
  updateSqLabels();

  document.getElementById('na-create-role').addEventListener('change', (e) => {
    const createNationToggle = document.getElementById('na-create-nation');
    if (e.target.value === 'admin') {
      createNationToggle.checked = false;
    } else {
      createNationToggle.checked = true;
    }
  });

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
        seafront: Number(document.getElementById('na-sq-seafront').value),
      },
    };

    const save = await api('/api/admin/new-account-defaults', { method: 'PATCH', body: JSON.stringify(payload) });
    document.getElementById('saveNewAccountDefaultsMsg').textContent = save?.ok ? 'Saved' : await readErrorMessage(save, 'The defaults could not be saved.');
    barkIfEnabled();
  };

  document.getElementById('createManagedAccountBtn').onclick = async () => {
    const payload = {
      name: document.getElementById('na-create-name').value.trim(),
      email: document.getElementById('na-create-email').value.trim(),
      password: document.getElementById('na-create-password').value,
      role: document.getElementById('na-create-role').value,
      create_nation: document.getElementById('na-create-nation').checked,
      force_password_reset: document.getElementById('na-force-reset').checked,
    };
    const create = await api('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
    document.getElementById('createManagedAccountMsg').textContent = create?.ok ? 'Account created.' : await readErrorMessage(create, 'The account could not be created.');
    if (create?.ok) {
      await loadNewAccounts();
    }
    barkIfEnabled();
  };

  document.getElementById('deletePlayerBtn').onclick = async () => {
    const userId = Number(document.getElementById('deletePlayerId').value);
    const confirmName = document.getElementById('deletePlayerConfirmName').value.trim();
    const selected = document.getElementById('deletePlayerId').selectedOptions[0];
    const expectedName = selected?.dataset.name || '';

    if (!userId) {
      document.getElementById('deletePlayerMsg').textContent = 'Select a player account first.';
      return;
    }
    if (confirmName !== expectedName) {
      document.getElementById('deletePlayerMsg').textContent = 'The confirmation username does not match the selected player.';
      return;
    }
    if (!window.confirm(`Delete ${expectedName} forever? This also removes the player\'s nation data.`)) {
      return;
    }

    const res = await api(`/api/admin/users/${userId}`, {
      method: 'DELETE',
      body: JSON.stringify({ confirmation_name: confirmName })
    });
    document.getElementById('deletePlayerMsg').textContent = res?.ok ? 'Player deleted permanently.' : await readErrorMessage(res, 'The player could not be deleted.');
    if (res?.ok) {
      await loadNewAccounts();
    }
    barkIfEnabled();
  };
}

async function loadSettings() {
  const res = await api('/api/me/settings');
  settings = await res.json();
  setTheme(settings.theme);
  applyColorBlindMode(settings.color_blind_mode);
  setFontMode(settings.font_mode || 'normal');

  view.innerHTML = `
    <div class="card">
      <h2>Settings</h2>
      <div class="setting-group">
        <h3 style="margin:0 0 8px 0;">Display</h3>
        <label>Theme</label>
        <select id="theme"><option value="light">Light</option><option value="dark">Dark</option></select>
        <label>Color Blind Mode</label>
        <select id="cb"><option value="none">None</option><option value="protanopia">Protanopia</option><option value="deuteranopia">Deuteranopia</option><option value="tritanopia">Tritanopia</option></select>
        <label>Font Mode</label>
        <select id="fontMode"><option value="normal">Normal Mode</option><option value="fun">Fun Mode</option><option value="cool_person">Cool Person Mode</option></select>
      </div>
      <div class="setting-group">
        <h3 style="margin:0 0 8px 0;">Sound</h3>
        <label><input type="checkbox" id="goofySound"> Goofy Sound on actions</label>
        <label><input type="checkbox" id="showUnreadChatBadge"> Show unread chat count on Chat tab</label>
      </div>
      <div class="row"><button class="primary" id="saveSettings">Save</button></div>
    </div>
  `;

  document.getElementById('theme').value = settings.theme;
  document.getElementById('cb').value = settings.color_blind_mode;
  document.getElementById('goofySound').checked = !!settings.dog_bark_enabled;
  document.getElementById('showUnreadChatBadge').checked = settings.show_unread_chat_badge !== false;
  document.getElementById('fontMode').value = settings.font_mode || 'normal';

  document.getElementById('saveSettings').onclick = async () => {
    const payload = {
      theme: document.getElementById('theme').value,
      color_blind_mode: document.getElementById('cb').value,
      dog_bark_enabled: document.getElementById('goofySound').checked,
      font_mode: document.getElementById('fontMode').value,
      show_unread_chat_badge: document.getElementById('showUnreadChatBadge').checked,
    };
    await api('/api/me/settings', { method: 'PATCH', body: JSON.stringify(payload) });
    settings = { ...settings, ...payload };
    setTheme(settings.theme);
    applyColorBlindMode(settings.color_blind_mode);
    setFontMode(settings.font_mode);
    refreshChatBadge();
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

async function loadResetPasswordPage() {
  const isAdmin = user.role === 'admin';
  const usersRes = isAdmin ? await api('/api/admin/users') : null;
  const users = usersRes ? await usersRes.json() : [];

  view.innerHTML = `
    <div class="card" style="max-width:720px;">
      <h2>Reset Password</h2>
      <p class="muted">Use this page to safely reset passwords with clear options.</p>
      <h3>My Password</h3>
      <label>Current Password</label>
      <input id="rpCurrent" type="password">
      <label>New Password</label>
      <input id="rpNew" type="password">
      <label>Confirm New Password</label>
      <input id="rpConfirm" type="password">
      <div class="row"><button class="primary" id="rpSelfBtn">Update My Password</button><span class="muted" id="rpSelfMsg"></span></div>
      ${isAdmin ? `
        <hr style="margin:12px 0;">
        <h3>Admin: Reset Another User</h3>
        <label>Account</label>
        <select id="rpUserId">${users.map(account => `<option value="${account.id}">${account.name} (${account.role})</option>`).join('')}</select>
        <label>Temporary Password</label>
        <input id="rpOtherNew" type="password" value="password123">
        <label><input id="rpShowTemp" type="checkbox"> Show temporary password</label>
        <label><input id="rpForce" type="checkbox" checked> Require reset on next login</label>
        <div class="row"><button class="primary" id="rpOtherBtn">Reset Selected User</button><span class="muted" id="rpOtherMsg"></span></div>
      ` : ''}
    </div>
  `;

  document.getElementById('rpSelfBtn').onclick = async () => {
    const current_password = document.getElementById('rpCurrent').value;
    const new_password = document.getElementById('rpNew').value;
    const confirm_password = document.getElementById('rpConfirm').value;
    if (new_password !== confirm_password) {
      document.getElementById('rpSelfMsg').textContent = 'New password and confirmation do not match.';
      return;
    }
    const r = await api('/api/auth/password', { method: 'PATCH', body: JSON.stringify({ current_password, new_password }) });
    if (r.ok) {
      document.getElementById('rpSelfMsg').textContent = 'Password updated';
    } else {
      let msg = 'Failed';
      try {
        const payload = await r.json();
        msg = payload.message || msg;
      } catch {}
      document.getElementById('rpSelfMsg').textContent = msg;
    }
    barkIfEnabled();
  };

  if (isAdmin) {
    document.getElementById('rpShowTemp').onchange = (e) => {
      document.getElementById('rpOtherNew').type = e.target.checked ? 'text' : 'password';
    };

    document.getElementById('rpOtherBtn').onclick = async () => {
      const userId = Number(document.getElementById('rpUserId').value);
      const new_password = document.getElementById('rpOtherNew').value;
      const force_password_reset = document.getElementById('rpForce').checked;
      const r = await api('/api/admin/users/' + userId + '/password', { method: 'PATCH', body: JSON.stringify({ new_password, force_password_reset }) });
      if (r.ok) {
        document.getElementById('rpOtherMsg').textContent = 'User password reset';
      } else {
        let msg = 'Failed';
        try {
          const payload = await r.json();
          msg = payload.message || msg;
        } catch {}
        document.getElementById('rpOtherMsg').textContent = msg;
      }
      barkIfEnabled();
    };
  }
}

async function loadAllNations() {
  const [res] = await Promise.all([api('/api/admin/nations')]);
  const payload = await res.json();
  const nations = extractList(payload);

  view.innerHTML = `
    <div class="card">
      <h2>All Nations (Admin)</h2>
      <div>
        <div class="card" style="margin-top:0;">
          <h3 style="margin-top:0;">Nation Stats Editor</h3>
          <div id="adminNationEditor" class="list" style="margin-bottom:12px;">Select nation to edit.</div>
        </div>
      </div>
      <div class="card">
        <h3 style="margin-top:0;">Nation Management</h3>
        <div class="twocol">
          <div>
            <input id="newPlaceholder" placeholder="New placeholder nation name">
            <button class="primary" id="createPlaceholder" style="margin-top:8px; width:100%;">Create Placeholder Nation</button>
          </div>
          <div>
            <div class="list" id="adminNationList">${nations.map(n => `<button class="primary editNationBtn" data-id="${n.id}" style="display:block; width:100%; margin-bottom:8px;">${n.name}</button>`).join('')}</div>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 style="margin-top:0;">Player Visibility Matrix</h3>
        <p class="muted" style="margin-top:0;">Control what one player can see about another player in Other Nations.</p>
        <div class="row" style="flex-wrap:wrap;">
          <div style="min-width:260px;flex:1;">
            <label style="font-size:12px;">Player View (viewer)</label>
            <select id="visViewer"></select>
          </div>
          <div style="min-width:260px;flex:1;">
            <label style="font-size:12px;">Player To Be Seen (subject)</label>
            <select id="visSubject"></select>
          </div>
          <button class="primary" id="loadVisibilityRulesBtn" style="align-self:flex-end;">Load Rules</button>
        </div>
        <div id="visRuleGrid" class="list" style="margin-top:8px;max-height:260px;">Select players to load rules.</div>
        <div class="row"><button class="primary" id="saveVisibilityRulesBtn">Save Visibility Rules</button><span class="muted" id="saveVisibilityMsg"></span></div>
      </div>
    </div>
  `;

  const playersRes = await api('/api/players');
  const visFieldsRes = await api('/api/admin/visibility/fields');
  const players = playersRes && playersRes.ok ? (await playersRes.json()) : [];
  const visFields = visFieldsRes && visFieldsRes.ok ? (await visFieldsRes.json()) : [];

  const visViewer = document.getElementById('visViewer');
  const visSubject = document.getElementById('visSubject');
  const playerOptions = (players || []).map(p => `<option value="${p.id}">${p.name}</option>`).join('');
  visViewer.innerHTML = `<option value="">Select viewer</option>${playerOptions}`;
  visSubject.innerHTML = `<option value="">Select subject</option>${playerOptions}`;

  const renderVisGrid = (ruleMap = {}) => {
    document.getElementById('visRuleGrid').innerHTML = visFields.map(field => `
      <label style="display:flex;align-items:center;justify-content:space-between;padding:6px 4px;border-bottom:1px solid #d7dee7;">
        <span>${field.label}</span>
        <input type="checkbox" class="vis-rule-box" data-key="${field.key}" ${ruleMap[field.key] !== false ? 'checked' : ''}>
      </label>
    `).join('') || '<div class="muted">No visibility fields configured.</div>';
  };
  renderVisGrid();

  const syncVisibilitySelectors = (changedField = '') => {
    const viewerValue = visViewer.value;
    const subjectValue = visSubject.value;

    Array.from(visViewer.options).forEach(option => {
      option.disabled = option.value !== '' && option.value === subjectValue;
    });
    Array.from(visSubject.options).forEach(option => {
      option.disabled = option.value !== '' && option.value === viewerValue;
    });

    if (viewerValue !== '' && viewerValue === subjectValue) {
      if (changedField === 'viewer') {
        visSubject.value = '';
      } else if (changedField === 'subject') {
        visViewer.value = '';
      }
      document.getElementById('saveVisibilityMsg').textContent = 'Viewer and subject must be different players.';
    }
  };

  visViewer.addEventListener('change', () => syncVisibilitySelectors('viewer'));
  visSubject.addEventListener('change', () => syncVisibilitySelectors('subject'));
  syncVisibilitySelectors();

  document.getElementById('loadVisibilityRulesBtn').onclick = async () => {
    const viewer = Number(visViewer.value || 0);
    const subject = Number(visSubject.value || 0);
    if (!viewer || !subject) {
      document.getElementById('saveVisibilityMsg').textContent = 'Select both viewer and subject first.';
      return;
    }
    const res = await api(`/api/admin/visibility/rules?viewer_user_id=${viewer}&subject_user_id=${subject}`);
    if (!res || !res.ok) {
      document.getElementById('saveVisibilityMsg').textContent = 'Failed to load rules.';
      return;
    }
    const rows = await res.json();
    const map = {};
    rows.forEach(rule => { map[rule.field_key] = !!rule.is_allowed; });
    renderVisGrid(map);
    document.getElementById('saveVisibilityMsg').textContent = 'Rules loaded.';
  };

  document.getElementById('saveVisibilityRulesBtn').onclick = async () => {
    const viewer = Number(visViewer.value || 0);
    const subject = Number(visSubject.value || 0);
    if (!viewer || !subject) {
      document.getElementById('saveVisibilityMsg').textContent = 'Select both viewer and subject first.';
      return;
    }
    if (viewer === subject) {
      document.getElementById('saveVisibilityMsg').textContent = 'Viewer and subject must be different players.';
      return;
    }
    const rules = Array.from(document.querySelectorAll('.vis-rule-box')).map(box => ({
      field_key: box.dataset.key,
      is_allowed: box.checked,
    }));
    const res = await api('/api/admin/visibility/rules', {
      method: 'PUT',
      body: JSON.stringify({ viewer_user_id: viewer, subject_user_id: subject, rules }),
    });
    document.getElementById('saveVisibilityMsg').textContent = res && res.ok ? 'Visibility rules saved.' : 'Failed to save rules.';
  };

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
        <label>Sea Front</label><input id="nSqSeafront" type="number" value="${sqMiles.seafront || 0}">
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
      <h3 style="margin:0 0 8px;">Owned Units / Buildings</h3>
      <details open style="margin-bottom:8px;">
        <summary>Units (${(d.units || []).length})</summary>
        <div class="list" style="max-height:160px;">${(d.units || []).map(u => `<div>${u.display_name || 'Unit'} x${u.qty} (${u.status})</div>`).join('') || '<div class="muted">No units</div>'}</div>
      </details>
      <details open style="margin-bottom:8px;">
        <summary>Buildings (${(d.buildings || []).length})</summary>
        <div class="list" style="max-height:160px;">${(d.buildings || []).map(b => `<div>${b.display_name} L${b.level} (${b.status})</div>`).join('') || '<div class="muted">No buildings</div>'}</div>
      </details>

      <hr style="margin:12px 0;">
      <h3 style="margin:0 0 8px;">Add Unit</h3>
      <div id="unitCatArea" class="muted">Loading units…</div>
      <div class="row" style="margin-top:6px;"><button class="primary" id="addUnitBtn">Add Unit</button><span class="muted" id="addUnitMsg"></span></div>
    `;

    const unitCatalogRes = await api('/api/admin/unit-catalog');
    const unitCatalog = unitCatalogRes && unitCatalogRes.ok ? await unitCatalogRes.json() : [];
    document.getElementById('unitCatArea').innerHTML = `
      <label style="font-size:13px;">Unit</label>
      <select id="unitCatId" style="margin-bottom:4px;">
        ${unitCatalog.map(u => `<option value="${u.id}">${u.display_name} [${u.class_name || 'unit'}]</option>`).join('')}
      </select>
      <label style="font-size:13px;">Quantity</label>
      <input id="unitQty" type="number" value="1" min="1">
      <label style="font-size:13px;">Status</label>
      <select id="unitStatus"><option value="owned">Owned</option><option value="training">Training</option></select>
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
          seafront: Number(document.getElementById('nSqSeafront').value),
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
      const status = document.getElementById('unitStatus').value;
      if (!unitCatalogId) { document.getElementById('addUnitMsg').textContent = 'Enter a unit catalog ID'; return; }
      const r = await api('/api/admin/nations/' + id + '/units', { method: 'POST', body: JSON.stringify({ unit_catalog_id: unitCatalogId, qty, status }) });
      document.getElementById('addUnitMsg').textContent = r.ok ? 'Added!' : 'Failed';
      if (r.ok) {
        openEditor(id);
      }
      barkIfEnabled();
    };
  };

  document.querySelectorAll('.editNationBtn').forEach(btn => btn.onclick = () => openEditor(btn.dataset.id));
  document.getElementById('createPlaceholder').onclick = async () => {
    const name = document.getElementById('newPlaceholder').value;
    await api('/api/admin/nations', { method: 'POST', body: JSON.stringify({ name }) });
    loadAllNations();
    barkIfEnabled();
  };
}

async function loadNotifications() {
  const [notifRes, nationsRes] = await Promise.all([api('/api/admin/notifications'), api('/api/admin/nations')]);
  const notifications = await notifRes.json();
  const nations = extractList(await nationsRes.json());
  const nationOwnerById = Object.fromEntries(nations.map(n => [Number(n.id), Number(n.owner_user_id || 0)]));

  const parseMeta = (n) => {
    try { return typeof n.meta_json === 'string' ? JSON.parse(n.meta_json) : (n.meta_json || {}); } catch { return {}; }
  };
  const rows = (notifications || []).map(n => ({ ...n, _meta: parseMeta(n) }));
  const types = Array.from(new Set(rows.map(n => n.type).filter(Boolean))).sort();
  const players = Array.from(new Set(rows.map(n => Number(n._meta.actor_user_id || n._meta.target_user_id || nationOwnerById[Number(n._meta.nation_id)] || 0)).filter(v => v > 0))).sort((a, b) => a - b);

  const savedType = localStorage.getItem('azveria_notif_type') || '';
  const savedPlayer = localStorage.getItem('azveria_notif_player') || '';
  const savedText = localStorage.getItem('azveria_notif_text') || '';

  view.innerHTML = `
    <div class="card">
      <h2>Notifications</h2>
      <div class="notify-panel">
        <div class="row" style="flex-wrap:wrap;">
          <select id="notifTypeFilter" style="max-width:220px;">
            <option value="">All Types</option>
            ${types.map(t => `<option value="${t}" ${savedType === t ? 'selected' : ''}>${t}</option>`).join('')}
          </select>
          <select id="notifPlayerFilter" style="max-width:220px;">
            <option value="">All Players</option>
            ${players.map(id => `<option value="${id}" ${savedPlayer === String(id) ? 'selected' : ''}>Player #${id}</option>`).join('')}
          </select>
          <input id="notifTextFilter" placeholder="Search title/body" value="${savedText.replace(/"/g, '&quot;')}" style="max-width:260px;">
          <button class="primary" id="notifClearFilters" style="background:#314f72;">Clear Filters</button>
        </div>
        <div class="list" id="adminNotifications" style="margin-top:8px;"></div>
      </div>
    </div>
  `;

  const renderNotifications = () => {
    const typeFilter = document.getElementById('notifTypeFilter').value;
    const playerFilter = Number(document.getElementById('notifPlayerFilter').value || 0);
    const textFilter = (document.getElementById('notifTextFilter').value || '').trim().toLowerCase();

    localStorage.setItem('azveria_notif_type', typeFilter);
    localStorage.setItem('azveria_notif_player', document.getElementById('notifPlayerFilter').value || '');
    localStorage.setItem('azveria_notif_text', document.getElementById('notifTextFilter').value || '');

    const filtered = rows.filter(n => {
      if (typeFilter && n.type !== typeFilter) return false;
      if (playerFilter) {
        const actor = Number(n._meta.actor_user_id || 0);
        const target = Number(n._meta.target_user_id || 0);
        const nationOwner = Number(nationOwnerById[Number(n._meta.nation_id || 0)] || 0);
        if (actor !== playerFilter && target !== playerFilter && nationOwner !== playerFilter) return false;
      }
      if (textFilter) {
        const hay = `${n.title || ''} ${n.body || ''}`.toLowerCase();
        if (!hay.includes(textFilter)) return false;
      }
      return true;
    });

    document.getElementById('adminNotifications').innerHTML = filtered.map(n => `<details class="notify-item">
      <summary class="notify-head">
        <div>
          <div style="font-weight:700;">${n.title}</div>
          <div class="muted" style="font-size:12px;">${n.created_at || ''}</div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
          <span class="notify-type">${n.type}</span>
          <button class="primary deleteNotif" data-id="${n.id}" style="background:#8a1a1a;" type="button">Delete</button>
        </div>
      </summary>
      <div style="font-size:13px;white-space:pre-wrap;margin-top:8px;">${n.body}</div>
    </details>`).join('') || '<div class="muted">No notifications</div>';

    document.querySelectorAll('.deleteNotif').forEach(btn => btn.onclick = async (event) => {
      event.preventDefault();
      event.stopPropagation();
      const del = await api('/api/admin/notifications/' + btn.dataset.id, { method: 'DELETE' });
      if (del.ok) loadNotifications();
    });
  };

  document.getElementById('notifTypeFilter').onchange = renderNotifications;
  document.getElementById('notifPlayerFilter').onchange = renderNotifications;
  document.getElementById('notifTextFilter').oninput = renderNotifications;
  document.getElementById('notifClearFilters').onclick = () => {
    document.getElementById('notifTypeFilter').value = '';
    document.getElementById('notifPlayerFilter').value = '';
    document.getElementById('notifTextFilter').value = '';
    renderNotifications();
  };

  renderNotifications();
}

async function loadGameInformationRules() {
  const listRes = await api('/api/admin/game-documents');
  const docs = listRes && listRes.ok ? await listRes.json() : [];

  view.innerHTML = `
    <div class="card">
      <h2>Game Information and Rules</h2>
      <div class="row" style="gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <div style="min-width:240px;flex:1;">
          <label style="font-size:12px;">Document</label>
          <select id="gameDocSelect">
            <option value="">— Select a document —</option>
            ${docs.map(d => `<option value="${escapeHtml(d.code)}">${escapeHtml(d.title)}</option>`).join('')}
          </select>
        </div>
        <button class="primary" id="gameDocEditBtn" style="background:#314f72;" disabled>Edit</button>
        <button class="primary" id="gameDocSaveBtn" style="display:none;" disabled>Save</button>
        <button id="gameDocCancelBtn" style="display:none;">Cancel</button>
        <button id="gameDocDownloadAllBtn" style="background:#2a5934;">Download All</button>
        <span class="muted" id="gameDocMsg"></span>
      </div>
      <p id="gameDocHint" class="muted" style="margin:8px 0 0;">Select a document above to view its contents.</p>
      <div id="gameDocLoading" style="display:none;margin-top:8px;" class="muted">Loading…</div>
      <textarea id="gameDocText" rows="28" readonly
        style="display:none;margin-top:8px;font-family:Consolas,'Courier New',monospace;resize:vertical;"></textarea>
    </div>
  `;

  let currentCode = '';
  let originalContent = '';

  const select          = document.getElementById('gameDocSelect');
  const text            = document.getElementById('gameDocText');
  const editBtn         = document.getElementById('gameDocEditBtn');
  const saveBtn         = document.getElementById('gameDocSaveBtn');
  const cancelBtn       = document.getElementById('gameDocCancelBtn');
  const downloadAllBtn  = document.getElementById('gameDocDownloadAllBtn');
  const msg             = document.getElementById('gameDocMsg');

  downloadAllBtn.onclick = async () => {
    const res = await fetch('/api/admin/game-documents/download-all', {
      headers: { 'Authorization': `Bearer ${token}` },
    });
    if (!res.ok) {
      alert('Download failed.');
      return;
    }
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'game-information.zip';
    a.click();
    URL.revokeObjectURL(url);
  };
  const hint      = document.getElementById('gameDocHint');
  const loading   = document.getElementById('gameDocLoading');

  const showReadOnly = () => {
    text.readOnly = true;
    editBtn.style.display  = '';
    saveBtn.style.display  = 'none';
    cancelBtn.style.display = 'none';
    msg.textContent = '';
  };

  const showEditMode = () => {
    text.readOnly = false;
    editBtn.style.display  = 'none';
    saveBtn.style.display  = '';
    saveBtn.disabled = false;
    cancelBtn.style.display = '';
    text.focus();
  };

  select.onchange = async () => {
    currentCode = select.value;
    text.value = '';
    text.style.display = 'none';
    hint.style.display = 'none';
    msg.textContent = '';
    editBtn.disabled = true;
    showReadOnly();

    if (!currentCode) {
      hint.style.display = '';
      loading.style.display = 'none';
      return;
    }

    loading.style.display = 'block';
    const res = await api('/api/admin/game-documents/' + encodeURIComponent(currentCode));
    loading.style.display = 'none';

    if (!res || !res.ok) {
      msg.textContent = 'Failed to load document.';
      return;
    }

    const doc = await res.json();
    originalContent = doc.content_text || '';
    text.value = originalContent;
    text.style.display = 'block';
    editBtn.disabled = false;
  };

  editBtn.onclick = () => {
    if (!currentCode) return;
    showEditMode();
  };

  cancelBtn.onclick = () => {
    text.value = originalContent;
    showReadOnly();
  };

  saveBtn.onclick = async () => {
    if (!currentCode) return;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';
    const res = await api('/api/admin/game-documents/' + encodeURIComponent(currentCode), {
      method: 'PUT',
      body: JSON.stringify({ content_text: text.value }),
    });
    saveBtn.textContent = 'Save';
    if (res && res.ok) {
      originalContent = text.value;
      msg.textContent = 'Saved.';
      setTimeout(() => { if (msg.textContent === 'Saved.') msg.textContent = ''; }, 3000);
      showReadOnly();
    } else {
      saveBtn.disabled = false;
      msg.textContent = 'Save failed.';
    }
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
        <div><strong>Current Game Year:</strong> ${d.current_game_year}</div>
        <div><strong>Elapsed Hours This Year:</strong> ${d.elapsed_hours_in_year} / ${Number(d.hours_per_year || 0).toFixed(2)} hours</div>
        <div><strong>Seconds Per In-Game Year:</strong> ${d.seconds_per_year}</div>
        <div><strong>Processed Years:</strong> ${d.processed_years}</div>
        <div><strong>Processed This Load:</strong> ${d.processed_now}</div>
        <div class="muted" style="margin-top:8px;">Auto increment uses real time. Manual mode lets admins advance years explicitly.</div>
      </div>
      <div class="row">
        <label style="min-width:220px;">Auto Increment Time</label>
        <input id="ttAuto" type="checkbox" ${d.auto_increment_enabled ? 'checked' : ''}>
      </div>
      <div class="row">
        <label style="min-width:220px;">Hours Per In-Game Year</label>
        <input id="ttHoursPerYear" type="number" min="0.01" step="0.01" value="${Number(d.hours_per_year || 48).toFixed(2)}">
      </div>
      <div class="row">
        <label style="min-width:220px;">Elapsed Hours (Current Year)</label>
        <input id="ttElapsedHours" type="number" min="0" step="0.01" value="${Number(d.elapsed_hours_in_year || 0).toFixed(2)}">
      </div>
      <div class="row">
        <label style="min-width:220px;">Current Game Year (Display)</label>
        <input id="ttCurrentYear" type="number" min="1" step="1" value="${d.current_game_year}">
      </div>
      <div class="row">
        <label><input id="ttApplyYearEffects" type="checkbox" ${settings?.apply_year_change_effects ? 'checked' : ''}> Apply income/maintenance when changing year</label>
      </div>
      <div class="row">
        <button class="primary" id="ttSave">Save Time Settings</button>
        <button class="primary" id="ttNextYear" style="background:#314f72;">Next Year</button>
        <button class="primary" id="ttSkipYear" style="background:#676767;">Skip Year (No Effects)</button>
        <span class="muted" id="ttMsg"></span>
      </div>
    </div>
  `;

  document.getElementById('ttSave').onclick = async () => {
    const applyYearChangeEffects = document.getElementById('ttApplyYearEffects').checked;
    const payload = {
      auto_increment_enabled: document.getElementById('ttAuto').checked,
      hours_per_year: Number(document.getElementById('ttHoursPerYear').value || 48),
      elapsed_hours_in_year: Number(document.getElementById('ttElapsedHours').value || 0),
      current_game_year: Number(document.getElementById('ttCurrentYear').value || 1),
      apply_year_change_effects: applyYearChangeEffects,
    };
    const save = await api('/api/admin/time-tracker', { method: 'PATCH', body: JSON.stringify(payload) });
    document.getElementById('ttMsg').textContent = save.ok ? 'Saved' : 'Failed';
    settings.apply_year_change_effects = applyYearChangeEffects;
    await api('/api/me/settings', {
      method: 'PATCH',
      body: JSON.stringify({ apply_year_change_effects: applyYearChangeEffects }),
    });
    if (save.ok) loadTimeTracker();
    barkIfEnabled();
  };

  document.getElementById('ttNextYear').onclick = async () => {
    if (!window.confirm('Advance to the next year and apply income/maintenance once?')) return;
    const r = await api('/api/admin/time-tracker/next-year', { method: 'POST', body: JSON.stringify({ apply_effects: true }) });
    document.getElementById('ttMsg').textContent = r.ok ? 'Year advanced' : 'Failed';
    if (r.ok) loadTimeTracker();
    barkIfEnabled();
  };

  document.getElementById('ttSkipYear').onclick = async () => {
    if (!window.confirm('Skip to the next year without applying nation income/maintenance?')) return;
    const r = await api('/api/admin/time-tracker/next-year', { method: 'POST', body: JSON.stringify({ apply_effects: false }) });
    document.getElementById('ttMsg').textContent = r.ok ? 'Year skipped' : 'Failed';
    if (r.ok) loadTimeTracker();
    barkIfEnabled();
  };
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
    setFontMode(settings.font_mode || 'normal');
  }
  if (user.force_password_reset) {
    loadForcedPasswordReset();
    return;
  }
  initMenuToggle();
  renderNav();
}

function initMenuToggle() {
  const btn = document.getElementById('menuToggle');
  const savedCollapsed = localStorage.getItem('azveria_menu_collapsed') === '1';
  if (savedCollapsed) {
    document.body.classList.add('menu-collapsed');
  }
  btn.addEventListener('click', () => {
    document.body.classList.toggle('menu-collapsed');
    localStorage.setItem('azveria_menu_collapsed', document.body.classList.contains('menu-collapsed') ? '1' : '0');
  });
}

const helpSelect = document.getElementById('helpSelect');
helpSelect.addEventListener('change', async (e) => {
  if (e.target.value === 'about') {
    await loadAboutPage();
  }
  if (e.target.value === 'docs') {
    window.open(user.role === 'admin' ? '/docs/developer' : '/docs/player', '_blank');
  }
  if (e.target.value === 'report-issue') {
    window.open('https://github.com/TheBuilderHero/AzveriaOnline/issues', '_blank', 'noopener,noreferrer');
  }
  if (e.target.value === 'reset-password') {
    await loadResetPasswordPage();
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
