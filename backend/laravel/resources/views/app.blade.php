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
    .topbar { display: flex; justify-content: flex-end; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 4px; }
    .chip {
      background: var(--panel);
      padding: 5px 11px;
      border-radius: 999px;
      border: 1px solid var(--border);
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.01em;
      white-space: nowrap;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .chip-label { color: var(--muted); font-weight: 400; margin-right: 4px; }
    .card {
      background: var(--panel);
      border-radius: 14px;
      padding: 16px;
      border: 1px solid var(--border);
      margin-top: 12px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .card h2 { margin: 0 0 14px 0; font-size: 1.25rem; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
    .card h3 { margin: 12px 0 6px 0; font-size: 1rem; color: var(--text); }
    .twocol { display: grid; grid-template-columns: 1fr 300px; gap: 12px; }
    .list { max-height: 420px; overflow: auto; border: 1px solid var(--border); border-radius: 10px; padding: 8px; background: var(--bg); }
    .muted { color: var(--muted); }
    .row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
    input, textarea, select, button { font: inherit; }
    input, textarea, select { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--panel); color: var(--text); }
    button.primary { background: var(--accent); color: #fff; border: 0; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .danger { color: var(--danger); }
    .res-panel { border: 1px solid var(--border); border-radius: 10px; padding: 6px; margin-top: 6px; background: var(--bg); }
    .num-pos { color: #2e7d32; font-weight: 600; }
    .num-neg { color: #c62828; font-weight: 600; }
    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .menu { height: auto; position: relative; }
      .menu-brand { padding-left: 54px; }
      .twocol { grid-template-columns: 1fr; }
    }
    details summary { cursor:pointer; font-weight:600; padding:5px 2px; user-select:none; font-size:14px; color:var(--text); }
    details[open] summary { margin-bottom:6px; }
    details + details { margin-top:6px; }
    .msg-wrap { display:flex; flex-direction:column; margin-bottom:8px; }
    .msg-wrap.own { align-items:flex-end; }
    .msg-wrap.other { align-items:flex-start; }
    .msg-bubble { max-width:80%; padding:8px 12px; border-radius:12px; line-height:1.4; word-break:break-word; }
    .msg-wrap.own  .msg-bubble { background:var(--accent); color:#fff; border-bottom-right-radius:3px; }
    .msg-wrap.other .msg-bubble { background:var(--bg-alt); border-bottom-left-radius:3px; }
    .msg-sender { font-size:11px; margin-bottom:3px; font-weight:bold; }
    .msg-wrap.own  .msg-sender { color:var(--accent); }
    .msg-wrap.other .msg-sender { color:#3a72b5; }
    .res-kv { display:flex; justify-content:space-between; align-items:center; padding:4px 8px; font-size:13px; gap:8px; min-height:26px; }
    .res-kv:nth-child(even) { background:var(--bg-alt); border-radius:6px; }
    .res-kv span:last-child { font-weight:600; white-space:nowrap; text-align:right; }
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
    .doc-toolbar {
      display: grid;
      grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto;
      gap: 10px;
      align-items: end;
      padding: 10px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: linear-gradient(180deg, var(--panel), var(--bg));
    }
    .doc-toolbar-actions { display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
    .doc-read {
      margin-top: 10px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--panel);
      padding: 14px;
      line-height: 1.6;
      min-height: 280px;
      max-height: 62vh;
      overflow: auto;
      white-space: break-spaces;
      font-size: 14px;
    }
    .doc-editor {
      margin-top: 10px;
      min-height: 280px;
      max-height: 62vh;
      font-family: Consolas, 'Courier New', monospace;
      resize: vertical;
      background: var(--panel);
      color: var(--text);
      border: 1px solid var(--border);
    }
      .combat-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
        gap: 12px;
        align-items: start;
      }
      .combat-commanders {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px;
        background: linear-gradient(180deg, var(--panel), var(--bg));
        margin-bottom: 10px;
      }
      .combat-unit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .combat-unit-card {
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel);
        padding: 8px;
      }
      .combat-unit-card summary {
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: center;
        font-weight: 600;
      }
      .combat-orders {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px;
        background: var(--panel);
      }
      .combat-order-item {
        border: 1px solid #d7dee7;
        border-radius: 8px;
        padding: 8px;
        margin-top: 8px;
        background: var(--bg);
      }
    .admin-picker {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 8px;
      background: var(--bg);
      margin-top: 8px;
    }
    .admin-picker-list {
      width: 100%;
      min-height: 170px;
      max-height: 220px;
      overflow: auto;
      font-size: 13px;
    }
    .admin-asset-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 8px;
      align-items: center;
      padding: 6px 8px;
      border-bottom: 1px solid #d7dee7;
    }
    .admin-asset-row:last-child { border-bottom: 0; }
    .admin-asset-remove { background:#8a1a1a; }
    .topbar-admin-shell {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
      background: linear-gradient(180deg, var(--panel), var(--bg));
    }
    .topbar-admin-grid { display:grid; grid-template-columns:1fr; gap:10px; }
    .topbar-admin-block {
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px;
      background: var(--panel);
    }
    .topbar-admin-block-head {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      margin-bottom:8px;
      flex-wrap:wrap;
    }
    .topbar-admin-count {
      font-size:11px;
      font-weight:700;
      letter-spacing:0.02em;
      border:1px solid var(--border);
      border-radius:999px;
      padding:2px 8px;
      background:var(--bg-alt);
      color:var(--text);
    }
    .topbar-admin-groups {
      display:grid;
      gap:10px;
      grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
      max-height:260px;
      overflow:auto;
      border:1px solid var(--border);
      border-radius:8px;
      padding:8px;
      background:var(--bg);
    }
    .topbar-admin-group {
      border:1px solid var(--border);
      border-radius:8px;
      padding:8px;
      background:var(--panel);
    }
    .topbar-admin-group-title { font-size:12px; font-weight:700; margin:0 0 6px 0; color:var(--text); }
    .topbar-admin-items { display:grid; gap:6px; }
    .topbar-admin-item {
      display:flex;
      align-items:center;
      gap:8px;
      padding:6px 8px;
      border:1px solid #d7dee7;
      border-radius:7px;
      background:var(--bg);
      font-size:12px;
      line-height:1.2;
    }
    .topbar-admin-item input[type="checkbox"] {
      width:16px;
      height:16px;
      margin:0;
      flex:0 0 auto;
    }
    .topbar-admin-type {
      font-size:10px;
      font-weight:700;
      border-radius:999px;
      padding:2px 6px;
      color:#fff;
      background:#314f72;
      flex:0 0 auto;
    }
    .topbar-admin-item.advanced .topbar-admin-type { background:#8c4a22; }
    .topbar-admin-name { flex:1; min-width:0; }
    .defaults-admin-shell {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
      background: linear-gradient(180deg, var(--panel), var(--bg));
    }
    .defaults-admin-grid { display:grid; grid-template-columns:1fr; gap:10px; }
    .defaults-admin-block {
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px;
      background: var(--panel);
    }
    .defaults-admin-form {
      display:grid;
      grid-template-columns:minmax(220px,1fr) 130px auto;
      gap:8px;
      align-items:end;
    }
    .defaults-admin-row {
      display:flex;
      align-items:center;
      gap:8px;
      border:1px solid var(--border);
      border-radius:8px;
      padding:7px 8px;
      background:var(--bg);
    }
    .defaults-admin-row .type-pill {
      font-size:10px;
      font-weight:700;
      border-radius:999px;
      padding:2px 7px;
      color:#fff;
      background:#314f72;
      flex:0 0 auto;
    }
    .defaults-admin-row.advanced .type-pill { background:#8c4a22; }
    .defaults-admin-row .resource-name { flex:1; min-width:0; }
    .defaults-admin-row .resource-amount { max-width:130px; }
    .resource-def-card {
      border:1px solid var(--border);
      border-radius:10px;
      padding:10px;
      background:var(--panel);
      margin:6px 0;
    }
    .resource-def-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));
      gap:8px;
      align-items:end;
    }
    .resource-def-actions {
      display:flex;
      gap:8px;
      align-items:center;
      flex-wrap:wrap;
    }
    .nation-editor-shell {
      border:1px solid var(--border);
      border-radius:12px;
      padding:10px;
      background:linear-gradient(180deg, var(--panel), var(--bg));
      margin-bottom:12px;
    }
    .nation-editor-grid {
      display:grid;
      grid-template-columns:repeat(2, minmax(220px, 1fr));
      gap:8px;
      align-items:end;
    }
    .nation-editor-block {
      border:1px solid var(--border);
      border-radius:10px;
      padding:10px;
      background:var(--panel);
      margin-top:8px;
    }
    .nation-income-row {
      display:flex;
      align-items:center;
      gap:8px;
      border:1px solid var(--border);
      border-radius:8px;
      padding:6px 8px;
      background:var(--bg);
    }
    .nation-income-row .type-pill {
      font-size:10px;
      font-weight:700;
      border-radius:999px;
      padding:2px 7px;
      color:#fff;
      background:#314f72;
      flex:0 0 auto;
    }
    .nation-income-row.advanced .type-pill { background:#8c4a22; }
    .nation-income-row .name { flex:1; min-width:0; }
    .nation-income-row .amt { max-width:140px; }
    .alln-grid { display:grid; grid-template-columns:1fr 320px; gap:12px; }
    .alln-panel {
      border:1px solid var(--border);
      border-radius:12px;
      padding:10px;
      background:linear-gradient(180deg, var(--panel), var(--bg));
    }
    .alln-panel-title { margin:0 0 8px 0; font-size:15px; }
    .nation-select-btn {
      display:block;
      width:100%;
      margin-bottom:8px;
      text-align:left;
      border:1px solid color-mix(in srgb, var(--accent) 30%, var(--border));
      background:linear-gradient(180deg, color-mix(in srgb, var(--panel) 75%, var(--bg) 25%), var(--bg));
      color:var(--text);
      font-weight:600;
    }
    .nation-select-btn:hover { filter: brightness(0.98); }
    .vis-rule-row {
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:8px 10px;
      border:1px solid #d7dee7;
      border-radius:8px;
      background:var(--panel);
      margin-bottom:6px;
    }
    .vis-rule-row:last-child { margin-bottom:0; }
    .vis-controls-grid {
      display:grid;
      grid-template-columns:1fr 1fr auto;
      gap:8px;
      align-items:end;
    }
    @media (max-width: 1280px) {
      .twocol { grid-template-columns: 1fr; }
      .alln-grid { grid-template-columns: 1fr; }
      .nation-editor-grid { grid-template-columns:1fr; }
      .defaults-admin-form { grid-template-columns:minmax(180px,1fr) 120px auto; }
      .resource-def-grid { grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); }
      .topbar-admin-groups { grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); }
      .doc-toolbar { grid-template-columns:1fr 1fr; }
      .main { padding: 14px; }
    }
    @media (max-width: 900px) {
      .defaults-admin-form { grid-template-columns:1fr; }
      .defaults-admin-row { flex-wrap:wrap; }
        .combat-layout { grid-template-columns: 1fr; }
        .combat-unit-grid { grid-template-columns: 1fr; }
      .defaults-admin-row .resource-amount { max-width:100%; }
      .nation-editor-grid { grid-template-columns:1fr; }
      .nation-income-row { flex-wrap:wrap; }
      .nation-income-row .amt { max-width:100%; }
      .alln-grid { grid-template-columns:1fr; }
      .vis-controls-grid { grid-template-columns:1fr; }
      .topbar-admin-groups { grid-template-columns:1fr; }
      .resource-def-grid { grid-template-columns:1fr; }
      .admin-asset-row { grid-template-columns:1fr; }
      .doc-read, .doc-editor { max-height:none; }
      .main { padding: 12px; }
      .card { padding: 12px; }
      .list { max-height: 320px; }
      .chip { font-size: 14px; }
    }
    @media (max-width: 640px) {
      .main { padding: 10px; }
      .card { padding: 10px; }
      .doc-toolbar { grid-template-columns:1fr; }
      .doc-toolbar-actions { justify-content:flex-start; }
      .topbar-admin-block-head { align-items:flex-start; }
      .vis-rule-row { flex-direction:column; align-items:flex-start; gap:6px; }
      .nation-income-row { align-items:flex-start; }
    }
    .doc-create {
      margin-top: 12px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: linear-gradient(180deg, var(--bg), var(--panel));
      padding: 12px;
    }
    .doc-create-grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .doc-create h3 { margin: 0 0 8px 0; }
    .doc-visibility {
      margin-top: 12px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: linear-gradient(180deg, var(--bg), var(--panel));
      padding: 12px;
    }
    .doc-vis-panel {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px;
      background: var(--bg);
    }
    .doc-vis-label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--text);
    }
    .doc-vis-select {
      width: 100%;
      min-height: 220px;
      padding: 8px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: var(--panel);
      color: var(--text);
    }
    .doc-vis-help {
      font-size: 12px;
      margin-top: 6px;
      color: var(--muted);
    }
    @media (max-width: 900px) {
      .doc-toolbar { grid-template-columns: 1fr; }
      .doc-toolbar-actions { justify-content:flex-start; }
      .doc-create-grid { grid-template-columns: 1fr; }
    }
    .map-shell { display:grid; grid-template-columns: 1fr 300px; gap:12px; }
    .map-stage-wrap { position:relative; border:1px solid #c9d1db; border-radius:12px; background:#0f1520; height:72vh; min-height:480px; overflow:hidden; }
    .map-stage-controls { position:absolute; left:12px; right:12px; top:10px; display:flex; justify-content:space-between; align-items:flex-start; z-index:4; pointer-events:none; }
    .map-stage-controls > * { pointer-events:auto; }
    .map-top-tools { display:flex; gap:8px; align-items:center; }
    .map-canvas { width:100%; height:100%; display:block; cursor:grab; touch-action:none; -ms-touch-action:none; }
    .map-canvas.dragging { cursor:grabbing; }
    body.map-fullscreen-lock { overflow: hidden; }
    .map-stage-wrap.map-pseudo-fullscreen {
      position: fixed;
      inset: 0;
      width: 100vw;
      z-index: 1200;
      border-radius: 0;
      height: 100vh;
      height: 100dvh;
      min-height: 100vh;
      min-height: 100dvh;
    }
    .map-floating { position:absolute; z-index:5; background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:10px; padding:8px; backdrop-filter: blur(2px); }
    .map-info-box { left:12px; top:56px; width:260px; }
    .map-bottom-center { position:absolute; left:50%; bottom:10px; transform:translateX(-50%); z-index:5; background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:10px; padding:8px 10px; min-width:240px; }
    .map-bottom-right { position:absolute; right:10px; bottom:10px; z-index:5; display:flex; gap:8px; align-items:flex-end; }
    .map-bottom-left { position:absolute; left:10px; bottom:10px; z-index:5; min-width:260px; }
    .map-controls-dock {
      margin-top: 8px;
      display: grid;
      grid-template-columns: minmax(260px, 1fr) minmax(220px, 320px) minmax(220px, 1fr);
      gap: 8px;
      align-items: start;
    }
    .map-controls-dock .map-bottom-left,
    .map-controls-dock .map-bottom-center,
    .map-controls-dock .map-bottom-right {
      position: relative;
      left: auto;
      right: auto;
      bottom: auto;
      transform: none;
      z-index: auto;
      min-width: 0;
    }
    .map-controls-dock .map-bottom-center {
      background: var(--panel);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 10px;
    }
    .map-controls-dock .map-bottom-right {
      justify-content: flex-end;
      align-items: stretch;
    }
    .map-controls-dock .map-floating {
      position: relative;
      z-index: auto;
      backdrop-filter: none;
    }
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
    .map-status-message {
      color: var(--text);
      font-size: 12px;
      font-weight: 600;
      min-height: 18px;
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: color-mix(in srgb, var(--panel) 85%, transparent);
    }
    .map-status-message[data-state="success"] {
      color: #17683a;
      border-color: #2f6a41;
      background: #dff4e7;
    }
    .map-status-message[data-state="error"] {
      color: #8a1a1a;
      border-color: #8a1a1a;
      background: #fde8e8;
    }
    .map-busy-indicator {
      color: var(--text);
      font-size: 12px;
      font-weight: 600;
      min-height: 18px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 2px 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: color-mix(in srgb, var(--panel) 85%, transparent);
    }
    .map-spinner {
      width: 12px;
      height: 12px;
      border: 2px solid color-mix(in srgb, var(--border) 75%, transparent);
      border-top-color: #2f6a41;
      border-radius: 50%;
      animation: map-spin 0.85s linear infinite;
      flex: 0 0 auto;
    }
    @keyframes map-spin {
      to { transform: rotate(360deg); }
    }
    @media (max-width: 1100px) {
      .map-shell { grid-template-columns: 1fr; }
      .map-stage-wrap { height:62vh; min-height:420px; }
      .map-right-external { justify-content:flex-start; }
      .map-editor-header { flex-direction:column; }
    }
    @media (max-width: 1280px) {
      .map-controls-dock { grid-template-columns: 1fr 280px; }
      .map-bottom-right { grid-column: 1 / -1; justify-content: flex-start; }
    }
    @media (max-width: 900px) {
      .main { padding: 12px; }
      .card { padding: 12px; }
      .row { flex-wrap: wrap; }
      .map-stage-wrap { height:68vh; min-height:460px; }
      .map-controls-dock { grid-template-columns: 1fr; }
      .map-bottom-center { min-width: 0; width: 100%; }
      .map-controls-dock.mobile-map-ui .map-bottom-left .map-floating {
        width: 100%;
        max-height: 44vh;
        overflow: auto;
      }
      .map-editor-dock-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
        align-items: center;
      }
      .map-editor-dock-grid input[type="range"] { width: 100%; }
      .map-editor-dock-terrain-list .primary {
        width: 100%;
        margin-bottom: 6px;
        text-align: left;
      }
    }
    @media (max-width: 640px) {
      .topbar { justify-content: flex-start; }
      .menu button, .menu .help-select { padding: 12px; }
      input, textarea, select, button { font-size: 16px; }
      .map-info-box { width: min(86vw, 260px); left: 8px; top: 52px; }
      .map-editor-toolbar { flex-wrap: wrap; }
      .map-right-external { flex-wrap: wrap; }
      .map-bottom-center { width: auto; }
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
      <option value="developer-options">Developer Options</option>
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
    if (!opts.silentLog && !developerLogInternalPath(url)) {
      if (res.status >= 500) {
        captureDeveloperLog('error', `HTTP ${res.status} ${opts.method || 'GET'} ${url}`, {
          status: res.status,
          url,
          method: opts.method || 'GET',
        }, { source: 'api.http' });
      } else if (res.status >= 400) {
        captureDeveloperLog('warning', `HTTP ${res.status} ${opts.method || 'GET'} ${url}`, {
          status: res.status,
          url,
          method: opts.method || 'GET',
        }, { source: 'api.http' });
      }
    }
    return res;
  } catch (error) {
    if (error?.name === 'AbortError') {
      if (!opts.silentLog && !developerLogInternalPath(url)) {
        captureDeveloperLog('error', `Request timeout ${opts.method || 'GET'} ${url}`, {
          timeout_ms: opts.timeout ?? 20000,
          url,
          method: opts.method || 'GET',
        }, { source: 'api.network' });
      }
      throw new Error('The server took too long to respond. Please try again.');
    }
    if (!opts.silentLog && !developerLogInternalPath(url)) {
      captureDeveloperLog('error', `Network error ${opts.method || 'GET'} ${url}`, {
        url,
        method: opts.method || 'GET',
      }, { source: 'api.network' });
    }
    throw new Error('The server could not be reached. Check the deployment and try again.');
  } finally {
    window.clearTimeout(timeout);
  }
};

let settings = { dog_bark_enabled: 0, theme: 'light', color_blind_mode: 'none', font_mode: 'normal', map_zoom_sensitivity: 1, map_max_zoom_pct: 180, alliance_color_overrides: {}, political_nation_color_overrides: {} };
let ws = null;
let wsAuthToken = null;
let wsAuthTokenExpiresAt = 0;
let activeSectionName = '';
const view = document.getElementById('view');
const nav = document.getElementById('nav');
const resourcesBar = document.getElementById('resourcesBar');

const developerLogSettingsDefaults = {
  capture_error: true,
  capture_warning: true,
  capture_info: true,
  auto_capture_client: true,
  max_entries: 2000,
};
let developerLogSettings = { ...developerLogSettingsDefaults };
let developerErrorHandlersInstalled = false;

function shouldCaptureDeveloperLevel(level) {
  if (user.role !== 'admin') return false;
  const normalized = String(level || '').toLowerCase();
  if (normalized === 'error') return !!developerLogSettings.capture_error;
  if (normalized === 'warning') return !!developerLogSettings.capture_warning;
  if (normalized === 'info') return !!developerLogSettings.capture_info;
  return false;
}

function developerLogInternalPath(url) {
  return String(url || '').includes('/api/admin/developer/log');
}

async function loadDeveloperLogSettingsClient() {
  if (user.role !== 'admin') return;
  try {
    const res = await api('/api/admin/developer/log-settings', { timeout: 10000, silentLog: true });
    if (!res || !res.ok) return;
    const payload = await res.json();
    developerLogSettings = { ...developerLogSettingsDefaults, ...(payload || {}) };
  } catch {}
}

async function captureDeveloperLog(level, summary, context = {}, opts = {}) {
  const normalized = String(level || '').toLowerCase();
  if (!shouldCaptureDeveloperLevel(normalized)) return;
  if (developerLogSettings.auto_capture_client === false && opts.force !== true) return;

  const payload = {
    level: normalized,
    summary: String(summary || 'Unknown issue').slice(0, 300),
    source: String(opts.source || 'ui').slice(0, 120),
    section: String(opts.section || activeSectionName || '').slice(0, 120),
    url: String(window.location.pathname || '').slice(0, 500),
    context: (context && typeof context === 'object') ? context : { note: String(context || '') },
  };

  try {
    await api('/api/admin/developer/logs', {
      method: 'POST',
      timeout: 10000,
      silentLog: true,
      body: JSON.stringify(payload),
    });
  } catch {}
}

function installGlobalDeveloperErrorHandlers() {
  if (developerErrorHandlersInstalled || user.role !== 'admin') return;
  developerErrorHandlersInstalled = true;

  window.addEventListener('error', (event) => {
    captureDeveloperLog('error', 'Unhandled browser error', {
      message: event?.message || 'Unknown browser error',
      filename: event?.filename || '',
      line: event?.lineno || 0,
      column: event?.colno || 0,
      stack: event?.error?.stack || '',
    }, { source: 'window.error' });
  });

  window.addEventListener('unhandledrejection', (event) => {
    const reason = event?.reason;
    captureDeveloperLog('error', 'Unhandled promise rejection', {
      message: reason?.message || String(reason || 'Promise rejected without reason'),
      stack: reason?.stack || '',
    }, { source: 'window.unhandledrejection' });
  });
}

const playerMenu = ['Player', 'Announcements', 'Information', 'Map', 'Combat', 'Chat', 'Other Nations', 'Shop', 'Settings'];
const adminMenu = ['Announcements', 'All Nations', 'Notifications', 'Information', 'Resource Management', 'New Accounts', 'Time Tracker', 'Map', 'Combat', 'Chat', 'Shop', 'Settings'];

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

// Format a number cleanly. abbrev=true uses K/M/B suffixes for large values (topbar use).
function fmtNum(n, { abbrev = false, signed = false } = {}) {
  const v = toFiniteNumber(n, 0);
  let s;
  if (abbrev) {
    const abs = Math.abs(v);
    if (abs >= 1e9) s = (v / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
    else if (abs >= 1e6) s = (v / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    else if (abs >= 1e4) s = (v / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    else s = v.toLocaleString();
  } else {
    s = v.toLocaleString();
  }
  return signed && v > 0 ? '+' + s : s;
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
  if (user.role === 'admin') {
    resourcesBar.innerHTML = '';
    return;
  }
  if (!window.resourceDefs) {
    try {
      const defsRes = await api('/api/resources');
      if (defsRes && defsRes.ok) {
        const defs = await defsRes.json();
        window.resourceDefs = defs;
        setDynamicResourceLabels(defs);
      }
    } catch {}
  }
  const res = await api('/api/me/resources', { silentLog: true });
  if (!res || !res.ok) return;
  const r = await res.json();
  const base = r.base || {};
  const defaultChips = [
    { type: 'base', name: 'cow', icon: '🐄', label: 'Cow', val: base.cow },
    { type: 'base', name: 'wood', icon: '🌳', label: 'Wood', val: base.wood },
    { type: 'base', name: 'ore', icon: '⛏', label: 'Ore', val: base.ore },
    { type: 'base', name: 'food', icon: '🍞', label: 'Food', val: base.food },
  ];
  // Resolve icons from resource definitions, tolerating old payload name/label formats.
  const getResourceIcon = (type, name, label = '') => {
    const normalizedType = String(type || '').trim().toLowerCase();
    const normalizedName = String(name || '').trim().toLowerCase();
    const normalizedLabel = String(label || '').trim().toLowerCase();
    const canonical = canonicalResourceKey(name);
    const canonicalName = canonical.includes(':') ? canonical.split(':', 2)[1].trim().toLowerCase() : '';

    if (window.resourceDefs && window.resourceDefs[normalizedType]) {
      for (const groupArr of Object.values(window.resourceDefs[normalizedType])) {
        const def = (groupArr || []).find(d => {
          const defName = String(d?.name || '').trim().toLowerCase();
          const defDisplay = String(d?.display_name || d?.name || '').trim().toLowerCase();
          return !!defName && (
            defName === normalizedName ||
            defDisplay === normalizedName ||
            (canonicalName && (defName === canonicalName || defDisplay === canonicalName)) ||
            (normalizedLabel && (defName === normalizedLabel || defDisplay === normalizedLabel))
          );
        });
        if (def && def.meta && def.meta.icon) return def.meta.icon;
      }
    }

    if (normalizedType === 'advanced') return '⚙';
    return ({ cow: '🐄', wood: '🌳', ore: '⛏', food: '🍞' }[normalizedName] || '•');
  };
  const chips = Array.isArray(r.topbar_display) && r.topbar_display.length > 0
    ? r.topbar_display.map(item => ({
      type: item.type || 'base',
      name: item.name || '',
      icon: getResourceIcon(item.type || 'base', item.name || '', item.label || ''),
      label: item.label || labelKey(item.name || ''),
      val: toFiniteNumber(item.value, 0),
    }))
    : defaultChips;
  resourcesBar.innerHTML = chips.map(c =>
    `<div class="chip" title="${c.label}: ${fmtNum(c.val)}">${c.icon} <span class="chip-label">${c.label}</span>${fmtNum(c.val, { abbrev: true })}</div>`
  ).join('');
}

// Legacy fallback labels for older payloads. Dynamic definitions are the primary source.
const LEGACY_RESOURCE_LABELS = {
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
let DYNAMIC_RESOURCE_LABELS = {};

function toTitleCase(value) {
  const input = String(value || '').replace(/[_-]+/g, ' ').trim();
  if (!input) return '';
  return input.replace(/\b\w/g, c => c.toUpperCase());
}

function canonicalResourceKey(rawKey) {
  const key = String(rawKey || '').trim();
  if (!key) return '';

  if (key.includes(':')) {
    const [rawType, rawName] = key.split(':', 2);
    const type = String(rawType || '').trim().toLowerCase();
    const name = String(rawName || '').trim();
    if (!name) return '';
    if (type === 'base') return `base:${name}`;
    if (type === 'advanced' || type === 'refined') return `advanced:${name}`;
    if (type === 'currency' || type === 'curr' || type === 'currencies') return `currencies:${name}`;
    return '';
  }

  if (key.startsWith('ref_')) return `advanced:${key.substring(4)}`;
  if (key.startsWith('cur_')) return `currencies:${key.substring(4)}`;
  return `base:${key}`;
}

function setDynamicResourceLabels(defs) {
  const labels = {};
  ['base', 'advanced'].forEach(type => {
    const groups = defs?.[type] || {};
    Object.values(groups).forEach(arr => {
      (arr || []).forEach(def => {
        const name = String(def?.name || '').trim();
        if (!name) return;
        const display = String(def?.display_name || name).trim() || name;
        const canonical = `${type}:${name}`;
        labels[canonical] = display;
        if (type === 'base') {
          labels[name] = display;
        } else {
          labels[`ref_${name}`] = display;
        }
      });
    });
  });
  DYNAMIC_RESOURCE_LABELS = labels;
}

function labelKey(k) {
  const raw = String(k || '');
  const canonical = canonicalResourceKey(raw);
  if (canonical && DYNAMIC_RESOURCE_LABELS[canonical]) return DYNAMIC_RESOURCE_LABELS[canonical];
  if (DYNAMIC_RESOURCE_LABELS[raw]) return DYNAMIC_RESOURCE_LABELS[raw];
  if (LEGACY_RESOURCE_LABELS[raw]) return LEGACY_RESOURCE_LABELS[raw];

  if (canonical.startsWith('base:') || canonical.startsWith('advanced:') || canonical.startsWith('currencies:')) {
    return toTitleCase(canonical.split(':', 2)[1] || raw);
  }
  return toTitleCase(raw) || raw;
}

function formatCost(costJson) {
  try {
    const obj = typeof costJson === 'string' ? JSON.parse(costJson) : costJson;
    return Object.entries(obj || {}).map(([k,v]) => `${labelKey(k)}: <strong>${v}</strong>`).join(' &nbsp;+&nbsp; ') || 'Free';
  } catch { return costJson || 'Free'; }
}

function renderKVList(map, data, opts = {}) {
  const showZero = opts.showZero !== false;
  return Object.entries(map)
    .filter(([k]) => showZero || toFiniteNumber(data[k] || 0, 0) !== 0)
    .map(([k,label]) => `<div class="res-kv"><span>${label}</span><span>${fmtNum(data[k]||0)}</span></div>`)
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
    if (name === 'Information') return await loadGameInformationRules();
    if (name === 'Resource Management') return await loadResourceManagement();
    if (name === 'About') return await loadAboutPage();
    if (name === 'Developer Options') return await loadDeveloperOptionsPage();
  // Admin Resource Management UI
  async function loadResourceManagement() {
    view.innerHTML = `<div class="card"><h2>Resource Management</h2><div id="resourceMgmtPanel"><div class="muted">Loading…</div></div></div>`;
    try {
      const [res, defaultsRes, usersRes, topbarCfgRes] = await Promise.all([
        api('/api/admin/resources'),
        api('/api/admin/new-account-defaults'),
        api('/api/admin/users?role=player'),
        api('/api/admin/resource-topbar-config'),
      ]);
      if (!res || !res.ok) throw new Error('Failed to load resource definitions.');
      if (!defaultsRes || !defaultsRes.ok) throw new Error('Failed to load new account defaults.');
      if (!usersRes || !usersRes.ok) throw new Error('Failed to load players.');
      const defs = await res.json();
      const defaults = await defaultsRes.json();
      const players = await usersRes.json();
      const topbarCfg = (topbarCfgRes && topbarCfgRes.ok) ? await topbarCfgRes.json() : { global: [], overrides: [], available: { base: [], advanced: [] } };
      renderResourceMgmt(defs, defaults, players, topbarCfg);
    } catch (e) {
      document.getElementById('resourceMgmtPanel').innerHTML = `<div class="danger">${escapeHtml(e.message)}</div>`;
    }
  }

  function renderResourceMgmt(defs, defaults, players, topbarCfg) {
    // defs: { base: {group: [defs]}, advanced: {group: [defs]} }
    const panel = document.getElementById('resourceMgmtPanel');
    const topbarGlobal = Array.isArray(topbarCfg?.global) ? topbarCfg.global : [];
    const topbarOverrides = Array.isArray(topbarCfg?.overrides) ? topbarCfg.overrides : [];
    const collectTopbarDefs = (type) => {
      const groups = defs[type] || {};
      return Object.values(groups)
        .flat()
        .map(def => ({
          type,
          name: String(def?.name || ''),
          display_name: String(def?.display_name || def?.name || ''),
        }))
        .filter(item => item.name !== '');
    };
    const topbarResources = [
      ...collectTopbarDefs('base'),
      ...collectTopbarDefs('advanced'),
    ];
    const topbarSelectionValues = (selection) => {
      if (!Array.isArray(selection)) return [];
      return selection
        .map(item => `${item.type || ''}|${item.name || ''}`)
        .filter(v => v !== '|');
    };
    const topbarResourceCheckboxes = (prefix, selectedValues) => {
      if (!topbarResources.length) return '<div class="muted">No resource definitions found.</div>';
      const renderTypeGroup = (type, title) => {
        const items = topbarResources.filter(item => item.type === type);
        if (!items.length) return '';
        return `
          <div class="topbar-admin-group">
            <div class="topbar-admin-group-title">${title}</div>
            <div class="topbar-admin-items">
              ${items.map(item => {
                const val = `${item.type}|${item.name}`;
                const checked = selectedValues.includes(val) ? 'checked' : '';
                return `<label class="topbar-admin-item ${item.type}"><input type="checkbox" class="${prefix}" value="${val}" ${checked}><span class="topbar-admin-type">${item.type === 'advanced' ? 'ADV' : 'BASE'}</span><span class="topbar-admin-name">${escapeHtml(item.display_name || item.name)}</span></label>`;
              }).join('')}
            </div>
          </div>
        `;
      };

      return `${renderTypeGroup('base', 'Base Resources')}${renderTypeGroup('advanced', 'Advanced Resources')}`;
    };
    let html = '';
    html += `<div class="row" style="margin-bottom:10px;"><button class="primary" id="addResourceBtn">+ Add Resource</button></div>`;
    const sortGroupEntries = (groups) => {
      return Object.entries(groups || {}).sort((a, b) => {
        const aOrder = Number(Array.isArray(a[1]) && a[1][0] ? a[1][0].group_order : 0) || 0;
        const bOrder = Number(Array.isArray(b[1]) && b[1][0] ? b[1][0].group_order : 0) || 0;
        if (aOrder !== bOrder) return aOrder - bOrder;
        return String(a[0] || '').localeCompare(String(b[0] || ''));
      });
    };
    ['base','advanced'].forEach(type => {
      const groups = defs[type] || {};
      html += `<details style="margin-bottom:10px;"><summary style="font-size:16px;font-weight:600;">${type.charAt(0).toUpperCase()+type.slice(1)} Resources</summary>`;
      sortGroupEntries(groups).forEach(([group, arr]) => {
        html += `<details style="margin:6px 0 0 12px;"><summary style="font-size:15px;">Group: ${escapeHtml(group)}</summary>`;
        html += arr.length === 0 ? '<div class="muted">No resources in this group.</div>' : arr.map(def => `
          <div class="resource-def-card">
            <form class="resourceEditForm" data-id="${def.id}">
              <div class="resource-def-grid">
                <label>Name <input name="name" value="${escapeHtml(def.name)}" required></label>
                <label>Display <input name="display_name" value="${escapeHtml(def.display_name)}" required></label>
                <label>Type <select name="type"><option value="base"${def.type==='base'?' selected':''}>Base</option><option value="advanced"${def.type==='advanced'?' selected':''}>Advanced</option></select></label>
                <label>Group <input name="group" value="${escapeHtml(def.group)}" required></label>
                <label>Group Order <input name="group_order" type="number" min="0" value="${Number(def.group_order)||0}"></label>
                <label>Order <input name="order" type="number" value="${Number(def.order)||0}"></label>
                <label>Meta <input name="meta" value="${escapeHtml(JSON.stringify(def.meta||{}))}"></label>
              </div>
              <div class="resource-def-actions" style="margin-top:8px;">
                <button class="primary saveResourceBtn" type="submit">Save</button>
                <button class="primary deleteResourceBtn" type="button" style="background:#8a1a1a;">Delete</button>
                <span class="muted resourceMsg"></span>
              </div>
            </form>
          </div>
        `).join('');
        html += '</details>';
      });
      html += '</details>';
    });
    html += `
      <details style="margin-top:12px;">
        <summary style="font-size:16px;font-weight:600;">New Account Resource Defaults</summary>
        <div class="defaults-admin-shell" style="margin-top:8px;">
          <p class="muted" style="margin-top:0;">Configure starting resources and yearly income for newly created nations. Duplicates are not allowed.</p>

          <div class="defaults-admin-grid">
            <details style="margin-top:8px;">
              <summary>Starting Resources</summary>
              <div class="defaults-admin-block" style="margin-top:8px;">
                <div class="topbar-admin-block-head">
                  <span class="muted">Resources each new nation starts with.</span>
                  <span class="topbar-admin-count" id="rm-start-count">0 selected</span>
                </div>
                <div class="defaults-admin-form">
                  <div>
                    <label style="font-size:12px;">Resource</label>
                    <select id="rm-start-resource"></select>
                  </div>
                  <div>
                    <label style="font-size:12px;">Amount</label>
                    <input id="rm-start-amount" type="number" value="0">
                  </div>
                  <button class="primary" type="button" id="rm-start-add">Add</button>
                </div>
                <div id="rm-start-rows" style="display:grid;gap:6px;margin-top:8px;"></div>
              </div>
            </details>

            <details style="margin-top:8px;">
              <summary>Income Per Game Year</summary>
              <div class="defaults-admin-block" style="margin-top:8px;">
                <div class="topbar-admin-block-head">
                  <span class="muted">Passive yearly resources each new nation gains.</span>
                  <span class="topbar-admin-count" id="rm-income-count">0 selected</span>
                </div>
                <div class="defaults-admin-form">
                  <div>
                    <label style="font-size:12px;">Resource</label>
                    <select id="rm-income-resource"></select>
                  </div>
                  <div>
                    <label style="font-size:12px;">Amount</label>
                    <input id="rm-income-amount" type="number" value="0">
                  </div>
                  <button class="primary" type="button" id="rm-income-add">Add</button>
                </div>
                <div id="rm-income-rows" style="display:grid;gap:6px;margin-top:8px;"></div>
              </div>
            </details>

            <div class="row" style="margin-top:4px;">
              <button class="primary" type="button" id="rm-save-defaults">Save New Account Resource Defaults</button>
              <span class="muted" id="rm-defaults-msg"></span>
            </div>
          </div>
        </div>
      </details>
      <details style="margin-top:12px;">
        <summary style="font-size:16px;font-weight:600;">Topbar Resources</summary>
        <div class="topbar-admin-shell" style="margin-top:8px;">
          <p class="muted" style="margin-top:0;">Select which resources appear in the top-right resource bar. Set global defaults and optional per-player overrides.</p>

          <div class="topbar-admin-grid">
            <details style="margin-top:8px;">
              <summary>Global Topbar Resources</summary>
              <div class="topbar-admin-block" style="margin-top:8px;">
                <div class="topbar-admin-block-head">
                  <span class="muted">Visible to all players unless overridden.</span>
                  <span class="topbar-admin-count" id="rmTopbarGlobalCount">0 selected</span>
                </div>
                <div id="rmTopbarGlobalWrap" class="topbar-admin-groups">
                  ${topbarResourceCheckboxes('rm-topbar-global', topbarSelectionValues(topbarGlobal))}
                </div>
              </div>
            </details>

            <details style="margin-top:8px;">
              <summary>Per-Player Override</summary>
              <div class="topbar-admin-block" style="margin-top:8px;">
                <label>Player Account</label>
                <select id="rmTopbarPlayerId">${(players || []).map(player => `<option value="${player.id}">${escapeHtml(player.name)} (${escapeHtml(player.email)})</option>`).join('')}</select>
                <label style="margin-top:8px;display:flex;align-items:center;gap:6px;"><input type="checkbox" id="rmTopbarOverrideEnabled"> Enable override for this player</label>
                <label>Override Mode</label>
                <select id="rmTopbarOverrideMode">
                  <option value="replace">Replace global resources</option>
                  <option value="append">Add to global resources</option>
                </select>
                <div class="topbar-admin-block-head" style="margin-top:8px;">
                  <span class="muted">Choose resources for this player override.</span>
                  <span class="topbar-admin-count" id="rmTopbarPlayerCount">0 selected</span>
                </div>
                <div id="rmTopbarPlayerWrap" class="topbar-admin-groups">
                  ${topbarResourceCheckboxes('rm-topbar-player', [])}
                </div>
              </div>
            </details>

            <div class="row" style="margin-top:4px;">
              <button class="primary" id="rmSaveTopbarConfigBtn" type="button">Save Topbar Configuration</button>
              <span class="muted" id="rmSaveTopbarConfigMsg"></span>
            </div>
          </div>
        </div>
      </details>
    `;

    panel.innerHTML = html;

    bindNewAccountResourceDefaults(defs, defaults || {});
    bindTopbarConfigSection(players || [], topbarGlobal, topbarOverrides);

    // Add resource
    document.getElementById('addResourceBtn').onclick = () => {
      panel.insertAdjacentHTML('afterbegin', `
        <div class="resource-def-card">
          <form class="resourceEditForm" data-id="">
            <div class="resource-def-grid">
              <label>Name <input name="name" required></label>
              <label>Display <input name="display_name" required></label>
              <label>Type <select name="type"><option value="base">Base</option><option value="advanced">Advanced</option></select></label>
              <label>Group <input name="group" required></label>
              <label>Group Order <input name="group_order" type="number" min="0" value="0"></label>
              <label>Order <input name="order" type="number" value="0"></label>
              <label>Meta <input name="meta" value="{}"></label>
            </div>
            <div class="resource-def-actions" style="margin-top:8px;">
              <button class="primary saveResourceBtn" type="submit">Create</button>
              <button class="primary deleteResourceBtn" type="button" style="background:#8a1a1a;">Delete</button>
              <span class="muted resourceMsg"></span>
            </div>
          </form>
        </div>
      `);
      bindResourceMgmtEvents();
    };
    bindResourceMgmtEvents();
  }

  function bindNewAccountResourceDefaults(defs, defaults) {
    const optionGroups = (type) => {
      const groups = defs[type] || {};
      const orderedGroups = Object.entries(groups).sort((a, b) => {
        const aOrder = Number(Array.isArray(a[1]) && a[1][0] ? a[1][0].group_order : 0) || 0;
        const bOrder = Number(Array.isArray(b[1]) && b[1][0] ? b[1][0].group_order : 0) || 0;
        if (aOrder !== bOrder) return aOrder - bOrder;
        return String(a[0] || '').localeCompare(String(b[0] || ''));
      });
      return orderedGroups.map(([group, arr]) => {
        if (!arr.length) return '';
        const label = type === 'advanced' ? `Advanced Resources - ${group}` : `Base Resources - ${group}`;
        const options = arr.map(def => `<option value="${type}|${def.name}">${escapeHtml(def.display_name)} (${escapeHtml(group)})</option>`).join('');
        return `<optgroup label="${escapeHtml(label)}">${options}</optgroup>`;
      }).join('');
    };

    const allOptions = `${optionGroups('base')}${optionGroups('advanced')}`;
    const startSelect = document.getElementById('rm-start-resource');
    const incomeSelect = document.getElementById('rm-income-resource');
    if (startSelect) startSelect.innerHTML = allOptions;
    if (incomeSelect) incomeSelect.innerHTML = allOptions;

    const normalizeRows = (rows) => {
      if (!Array.isArray(rows)) return [];
      const seen = new Set();
      const out = [];
      rows.forEach(row => {
        const type = row?.type === 'advanced' ? 'advanced' : 'base';
        const name = String(row?.name || '').trim();
        if (!name) return;
        const key = `${type}|${name}`;
        if (seen.has(key)) return;
        seen.add(key);
        out.push({ type, name, amount: Number(row?.amount || 0) });
      });
      return out;
    };

    const oldResources = defaults.resources || {};
    const oldRefined = defaults.refined_resources || {};

    const hasCanonicalStarting = Array.isArray(defaults.starting_resources);

    const startRows = normalizeRows(
      hasCanonicalStarting
        ? defaults.starting_resources
        : [
            ...Object.entries(oldResources).map(([name, amount]) => ({ type: 'base', name, amount })),
            ...Object.entries(oldRefined).map(([name, amount]) => ({ type: 'advanced', name, amount })),
          ]
    );
    // Only use canonical dynamic resources for income
    const incomeRows = normalizeRows(Array.isArray(defaults.income_resources) ? defaults.income_resources : []);

    const displayName = (type, name) => {
      const groups = defs[type] || {};
      for (const arr of Object.values(groups)) {
        const found = (arr || []).find(def => def.name === name);
        if (found) return found.display_name;
      }
      return name;
    };

    const renderRows = (rows, containerId, removeClass, amountClass) => {
      const el = document.getElementById(containerId);
      if (!el) return;
      if (!rows.length) {
        el.innerHTML = '<div class="muted">No rows configured.</div>';
        return;
      }
      el.innerHTML = rows.map((row, idx) => `
        <div class="defaults-admin-row ${row.type === 'advanced' ? 'advanced' : 'base'}">
          <span class="type-pill">${row.type === 'advanced' ? 'ADV' : 'BASE'}</span>
          <div class="resource-name">${escapeHtml(displayName(row.type, row.name))} <span class="muted">(${escapeHtml(row.name)})</span></div>
          <input type="number" class="${amountClass} resource-amount" data-idx="${idx}" value="${Number(row.amount || 0)}">
          <button class="primary ${removeClass}" type="button" data-idx="${idx}" style="background:#8a1a1a;">Remove</button>
        </div>
      `).join('');
    };

    const updateDefaultsCounts = () => {
      const startCountEl = document.getElementById('rm-start-count');
      const incomeCountEl = document.getElementById('rm-income-count');
      if (startCountEl) startCountEl.textContent = `${startRows.length} selected`;
      if (incomeCountEl) incomeCountEl.textContent = `${incomeRows.length} selected`;
    };

    const rerender = () => {
      renderRows(startRows, 'rm-start-rows', 'rm-start-remove', 'rm-start-amount-input');
      renderRows(incomeRows, 'rm-income-rows', 'rm-income-remove', 'rm-income-amount-input');
      updateDefaultsCounts();

      document.querySelectorAll('.rm-start-amount-input').forEach(input => {
        input.addEventListener('input', () => {
          const idx = Number(input.dataset.idx);
          if (Number.isFinite(idx) && startRows[idx]) startRows[idx].amount = Number(input.value || 0);
        });
      });
      document.querySelectorAll('.rm-income-amount-input').forEach(input => {
        input.addEventListener('input', () => {
          const idx = Number(input.dataset.idx);
          if (Number.isFinite(idx) && incomeRows[idx]) incomeRows[idx].amount = Number(input.value || 0);
        });
      });

      document.querySelectorAll('.rm-start-remove').forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.dataset.idx);
          if (Number.isFinite(idx) && startRows[idx]) {
            startRows.splice(idx, 1);
            rerender();
          }
        });
      });
      document.querySelectorAll('.rm-income-remove').forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.dataset.idx);
          if (Number.isFinite(idx) && incomeRows[idx]) {
            incomeRows.splice(idx, 1);
            rerender();
          }
        });
      });
    };

    const addUniqueRow = (rows, rawValue, amount, msgEl) => {
      if (!rawValue || !rawValue.includes('|')) {
        if (msgEl) msgEl.textContent = 'Select a resource first.';
        return;
      }
      const [typeRaw, nameRaw] = rawValue.split('|', 2);
      const type = typeRaw === 'advanced' ? 'advanced' : 'base';
      const name = String(nameRaw || '').trim();
      if (!name) {
        if (msgEl) msgEl.textContent = 'Invalid resource selection.';
        return;
      }
      const duplicate = rows.some(row => row.type === type && row.name === name);
      if (duplicate) {
        if (msgEl) msgEl.textContent = 'Duplicate resources are not allowed.';
        return;
      }
      rows.push({ type, name, amount: Number(amount || 0) });
      if (msgEl) msgEl.textContent = '';
      rerender();
    };

    const msgEl = document.getElementById('rm-defaults-msg');
    document.getElementById('rm-start-add')?.addEventListener('click', () => {
      addUniqueRow(startRows, document.getElementById('rm-start-resource')?.value, document.getElementById('rm-start-amount')?.value, msgEl);
    });
    document.getElementById('rm-income-add')?.addEventListener('click', () => {
      addUniqueRow(incomeRows, document.getElementById('rm-income-resource')?.value, document.getElementById('rm-income-amount')?.value, msgEl);
    });

    document.getElementById('rm-save-defaults')?.addEventListener('click', async () => {
      const payload = {
        starting_resources: startRows,
        income_resources: incomeRows,
      };
      const save = await api('/api/admin/new-account-defaults', { method: 'PATCH', body: JSON.stringify(payload) });
      if (msgEl) msgEl.textContent = save?.ok ? 'Saved new account resource defaults.' : await readErrorMessage(save, 'Could not save defaults.');
      barkIfEnabled();
    });

    rerender();
  }

  function bindTopbarConfigSection(players, topbarGlobal, topbarOverrides) {
    const topbarSelectionValues = (selection) => {
      if (!Array.isArray(selection)) return [];
      return selection
        .map(item => `${item.type || ''}|${item.name || ''}`)
        .filter(v => v !== '|');
    };

    const topbarOverrideMap = new Map();
    let activeTopbarPlayerId = 0;
    (topbarOverrides || []).forEach(override => {
      const userId = Number(override.user_id || 0);
      if (!userId || !Array.isArray(override.resources) || override.resources.length === 0) return;
      topbarOverrideMap.set(userId, {
        user_id: userId,
        mode: override.mode === 'append' ? 'append' : 'replace',
        resources: override.resources,
      });
    });

    const getCheckedTopbarSelections = (className) => Array.from(document.querySelectorAll(`.${className}:checked`)).map(el => {
      const [type, name] = String(el.value || '').split('|');
      return { type, name };
    }).filter(item => (item.type === 'base' || item.type === 'advanced') && item.name);

    const setCheckedTopbarSelections = (className, values) => {
      const selected = new Set(values || []);
      document.querySelectorAll(`.${className}`).forEach(el => {
        el.checked = selected.has(el.value);
      });
    };

    const updateTopbarSelectionCount = (className, countId) => {
      const count = document.querySelectorAll(`.${className}:checked`).length;
      const el = document.getElementById(countId);
      if (el) el.textContent = `${count} selected`;
    };

    const bindTopbarSelectionCountWatcher = (className, countId) => {
      document.querySelectorAll(`.${className}`).forEach(el => {
        el.addEventListener('change', () => updateTopbarSelectionCount(className, countId));
      });
      updateTopbarSelectionCount(className, countId);
    };

    const applyPlayerTopbarEditorEnabledState = (enabled) => {
      const wrap = document.getElementById('rmTopbarPlayerWrap');
      const modeEl = document.getElementById('rmTopbarOverrideMode');
      if (wrap) wrap.style.opacity = enabled ? '1' : '0.55';
      document.querySelectorAll('.rm-topbar-player').forEach(el => {
        el.disabled = !enabled;
      });
      if (modeEl) modeEl.disabled = !enabled;
    };

    const persistCurrentTopbarPlayerEditor = (playerIdOverride = null) => {
      const playerId = Number(playerIdOverride || document.getElementById('rmTopbarPlayerId')?.value || 0);
      if (!playerId) return;
      const enabled = !!document.getElementById('rmTopbarOverrideEnabled')?.checked;
      if (!enabled) {
        topbarOverrideMap.delete(playerId);
        return;
      }
      const resourcesSelected = getCheckedTopbarSelections('rm-topbar-player');
      if (resourcesSelected.length === 0) {
        topbarOverrideMap.delete(playerId);
        return;
      }
      const mode = document.getElementById('rmTopbarOverrideMode')?.value === 'append' ? 'append' : 'replace';
      topbarOverrideMap.set(playerId, {
        user_id: playerId,
        mode,
        resources: resourcesSelected,
      });
    };

    const loadTopbarPlayerEditor = async () => {
      const playerId = Number(document.getElementById('rmTopbarPlayerId')?.value || 0);
      activeTopbarPlayerId = playerId;
      const wrap = document.getElementById('rmTopbarPlayerWrap');
      const enabledEl = document.getElementById('rmTopbarOverrideEnabled');
      const modeEl = document.getElementById('rmTopbarOverrideMode');
      if (!wrap || !enabledEl || !modeEl) return;

      // Always fetch latest override from backend
      let override = null;
      try {
        const res = await api(`/api/admin/resource-topbar-override/${playerId}`);
        if (res && res.ok) {
          override = await res.json();
          if (override && override.user_id) {
            topbarOverrideMap.set(Number(override.user_id), {
              user_id: Number(override.user_id),
              mode: override.mode === 'append' ? 'append' : 'replace',
              resources: Array.isArray(override.resources) ? override.resources : [],
            });
          } else {
            topbarOverrideMap.delete(playerId);
          }
        } else {
          override = topbarOverrideMap.get(playerId) || null;
        }
      } catch (e) {
        // fallback to in-memory if fetch fails
        override = topbarOverrideMap.get(playerId) || null;
      }
      enabledEl.checked = !!override;
      modeEl.value = override?.mode === 'append' ? 'append' : 'replace';
      setCheckedTopbarSelections('rm-topbar-player', topbarSelectionValues(override?.resources || []));
      updateTopbarSelectionCount('rm-topbar-player', 'rmTopbarPlayerCount');
      applyPlayerTopbarEditorEnabledState(enabledEl.checked);
    };

    setCheckedTopbarSelections('rm-topbar-global', topbarSelectionValues(topbarGlobal));
    bindTopbarSelectionCountWatcher('rm-topbar-global', 'rmTopbarGlobalCount');
    bindTopbarSelectionCountWatcher('rm-topbar-player', 'rmTopbarPlayerCount');
    if ((players || []).length > 0) {
      document.getElementById('rmTopbarPlayerId')?.addEventListener('change', async () => {
        persistCurrentTopbarPlayerEditor(activeTopbarPlayerId);
        await loadTopbarPlayerEditor();
      });
      document.getElementById('rmTopbarOverrideEnabled')?.addEventListener('change', () => {
        const enabled = !!document.getElementById('rmTopbarOverrideEnabled')?.checked;
        applyPlayerTopbarEditorEnabledState(enabled);
      });
      loadTopbarPlayerEditor();
    }

    document.getElementById('rmSaveTopbarConfigBtn')?.addEventListener('click', async () => {
      persistCurrentTopbarPlayerEditor();

      const msgEl = document.getElementById('rmSaveTopbarConfigMsg');
      const globalSelection = getCheckedTopbarSelections('rm-topbar-global');
      if (globalSelection.length === 0) {
        if (msgEl) msgEl.textContent = 'Select at least one global topbar resource.';
        return;
      }

      const payload = {
        global: globalSelection,
        overrides: Array.from(topbarOverrideMap.values()),
      };
      const save = await api('/api/admin/resource-topbar-config', { method: 'PUT', body: JSON.stringify(payload) });
      if (msgEl) msgEl.textContent = save?.ok ? 'Topbar configuration saved.' : await readErrorMessage(save, 'Topbar configuration could not be saved.');
      if (save?.ok) {
        await loadResources();
      }
      barkIfEnabled();
    });
  }

  function bindResourceMgmtEvents() {
    document.querySelectorAll('.resourceEditForm').forEach(form => {
      const id = form.dataset.id;
      const saveBtn = form.querySelector('.saveResourceBtn');
      const delBtn = form.querySelector('.deleteResourceBtn');
      const msg = form.querySelector('.resourceMsg');
      form.onsubmit = async (e) => {
        e.preventDefault();
        saveBtn.disabled = true;
        msg.textContent = 'Saving…';
        try {
          const fd = new FormData(form);
          const payload = {
            name: fd.get('name'),
            display_name: fd.get('display_name'),
            type: fd.get('type'),
            group: fd.get('group'),
            group_order: Number(fd.get('group_order')||0),
            order: Number(fd.get('order')||0),
            meta: JSON.parse(fd.get('meta')||'{}'),
          };
          let res;
          if (id) {
            res = await api(`/api/admin/resources/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
          } else {
            res = await api('/api/admin/resources', { method: 'POST', body: JSON.stringify(payload) });
          }
          if (!res || !res.ok) throw new Error(await readErrorMessage(res, 'Save failed'));
          msg.textContent = 'Saved';
          setTimeout(() => { msg.textContent = ''; }, 1200);
          await loadResourceManagement();
        } catch (err) {
          msg.textContent = err.message || 'Save failed';
        } finally {
          saveBtn.disabled = false;
        }
      };
      delBtn.onclick = async () => {
        if (!id) {
          form.parentElement.remove();
          return;
        }
        if (!window.confirm('Delete this resource?')) return;
        delBtn.disabled = true;
        msg.textContent = 'Deleting…';
        try {
          const res = await api(`/api/admin/resources/${id}`, { method: 'DELETE' });
          if (!res || !res.ok) throw new Error(await readErrorMessage(res, 'Delete failed'));
          msg.textContent = 'Deleted';
          setTimeout(() => { msg.textContent = ''; }, 1200);
          await loadResourceManagement();
        } catch (err) {
          msg.textContent = err.message || 'Delete failed';
        } finally {
          delBtn.disabled = false;
        }
      };
    });
  }
  } catch (error) {
    if (activeSectionName === name) {
      renderSectionError(name, error);
    }
  }
}

async function loadPlayer() {
  // Fetch dashboard, terrain, and resource definitions
  const [dashRes, sqMilesRes, resDefRes, combatSnapshotRes] = await Promise.all([
    api('/api/me/dashboard'),
    api('/api/me/terrain-square-miles'),
    api('/api/resources'),
    api('/api/me/combat/snapshot'),
  ]);
  const data = await dashRes.json();
  const sqMiles = await sqMilesRes.json();
  const resourceDefs = await resDefRes.json();
  window.resourceDefs = resourceDefs;
  setDynamicResourceLabels(resourceDefs);
  const normalizedSqMiles = normalizeTerrainSquareMiles(sqMiles);
  const combatSnapshot = combatSnapshotRes && combatSnapshotRes.ok ? await parseJsonResponse(combatSnapshotRes, {}) : {};
  const totalArmyRating = Number(combatSnapshot?.total_army_rating || 0);
  const terrainRows = Object.entries(normalizedSqMiles).length
    ? Object.entries(normalizedSqMiles).map(([k, v]) => `<span>${labelTerrainKey(k)}: <strong>${fmtNum(v)} sq mi</strong></span>`).join(' &nbsp;|&nbsp; ')
    : 'No terrain data';

  const res = data.resources || {};
  const yearly = data.yearly_projection || { income: {}, maintenance: {}, net: {}, maintenance_breakdown: [] };
  const ownedUnits = data.units.owned || [];
  const trainingUnits = data.units.training || [];
  const builtBuildings = data.buildings.built || [];
  const progressBuildings = data.buildings.in_progress || [];

  // Helper to render resource groups
  function renderResourceGroups(type, values) {
    const groups = resourceDefs[type] || {};
    return Object.entries(groups).map(([group, defs]) => {
      const openDefault = (
        (type === 'base' && (group === 'Currencies' || group === 'Common')) ||
        (type === 'advanced' && (group === 'Uncommon' || group === 'Rare'))
      );
      return `<details style="margin-top:6px;"${openDefault ? ' open' : ''}>
        <summary>${group}</summary>
        <div class="res-panel">
          ${defs.length === 0 ? '<div class="muted">none</div>' : defs.map(def => {
            const val = values?.[def.name] ?? 0;
            return `<div class="res-kv"><span>${escapeHtml(def.display_name)}</span><span>${fmtNum(val)}</span></div>`;
          }).join('')}
        </div>
      </details>`;
    }).join('');
  }

  // Helper to render income groups
  function renderIncomeGroups() {
    const groups = [
      { key: 'income', label: 'Income', open: true },
      { key: 'production', label: 'Production', open: false },
      { key: 'upkeep', label: 'Upkeep', open: false },
    ];
    return groups.map(g => {
      const vals = yearly[g.key] || {};
      const entries = [];
      if (vals && typeof vals === 'object') {
        const baseVals = (vals.base && typeof vals.base === 'object') ? vals.base : {};
        const advancedVals = (vals.advanced && typeof vals.advanced === 'object') ? vals.advanced : {};
        const currencyVals = (vals.currencies && typeof vals.currencies === 'object') ? vals.currencies : {};
        const refinedVals = (vals.refined && typeof vals.refined === 'object') ? vals.refined : {};

        // Preferred dynamic structure
        Object.entries(baseVals).forEach(([k, v]) => entries.push([`base:${k}`, v]));
        Object.entries(advancedVals).forEach(([k, v]) => entries.push([`advanced:${k}`, v]));
        Object.entries(currencyVals).forEach(([k, v]) => entries.push([`currencies:${k}`, v]));

        // Backward-compatible refined fallback when advanced bucket is absent.
        if (Object.keys(advancedVals).length === 0) {
          Object.entries(refinedVals).forEach(([k, v]) => entries.push([`advanced:${k}`, v]));
        }
        // REMOVE legacy flat structure fallback. Only render canonical dynamic resources.
      }

      const nonZeroEntries = entries.filter(([_, v]) => toFiniteNumber(v, 0) !== 0);
      return `<details style="margin-top:6px;"${g.open ? ' open' : ''}>
        <summary>${g.label}</summary>
        <div class="res-panel">
          ${nonZeroEntries.length === 0 ? '<div class="muted">none</div>' : nonZeroEntries.map(([k, v]) => {
            return `<div class="res-kv"><span>${escapeHtml(labelKey(k))}</span><span>${fmtNum(v)}</span></div>`;
          }).join('')}
        </div>
      </details>`;
    }).join('');
  }

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
            ${renderResourceGroups('base', res.base || {})}
          </details>

          <details style="margin-top:8px;">
            <summary>Advanced Resources</summary>
            ${renderResourceGroups('advanced', res.advanced || {})}
          </details>

          <details style="margin-top:8px;" open>
            <summary>Income</summary>
            ${renderIncomeGroups()}
          </details>

          <label style="margin-top:12px;display:block;">About</label>
          <textarea id="aboutField" rows="4">${data.nation.about_text || ''}</textarea>
          <div class="row"><button class="primary" id="saveAbout">Save About</button><span id="aboutMsg" class="muted"></span></div>
        </div>
        <div>
          <p><strong>Total Army Rating:</strong> ${fmtNum(totalArmyRating)}</p>
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
  const isAdmin = user.role === 'admin';
  let adminPlayers = [];
  let selectedPlayerId = '';

  if (isAdmin) {
    const usersRes = await api('/api/admin/users?role=player');
    adminPlayers = usersRes && usersRes.ok ? await usersRes.json() : [];
    const withNation = adminPlayers.filter(p => Number(p.nation_id || 0) > 0);
    if (withNation.length > 0) {
      selectedPlayerId = String(withNation[0].id);
    }
  }

  const STAT_KEYS = ['ATK', 'DEF', 'DMG', 'HP', 'MVT', 'RNG', 'ACT'];

  const renderStatsRows = (stats) => {
    const entries = Object.entries(stats || {});
    if (entries.length === 0) return '<div class="muted">No stats.</div>';
    return entries.map(([k, v]) => `<div class="res-kv"><span>${escapeHtml(String(k))}</span><span>${escapeHtml(String(v))}</span></div>`).join('');
  };

  const renderRatingBreakdown = (breakdown) => {
    if (!breakdown || typeof breakdown !== 'object') {
      return '<div class="muted">No rating breakdown.</div>';
    }
    const inputs = breakdown.inputs || {};
    const weights = breakdown.weights || {};
    const components = breakdown.components || {};
    return `
      <div class="res-kv"><span>ATK</span><span>${fmtNum(inputs.ATK || 0)} x ${fmtNum(weights.ATK || 0)} = ${fmtNum(components.ATK || 0)}</span></div>
      <div class="res-kv"><span>DEF</span><span>${fmtNum(inputs.DEF || 0)} x ${fmtNum(weights.DEF || 0)} = ${fmtNum(components.DEF || 0)}</span></div>
      <div class="res-kv"><span>DMG</span><span>${fmtNum(inputs.DMG || 0)} x ${fmtNum(weights.DMG || 0)} = ${fmtNum(components.DMG || 0)}</span></div>
      <div class="res-kv"><span>HP</span><span>${fmtNum(inputs.HP || 0)} x ${fmtNum(weights.HP || 0)} = ${fmtNum(components.HP || 0)}</span></div>
      <div class="res-kv"><span>MVT</span><span>${fmtNum(inputs.MVT || 0)} x ${fmtNum(weights.MVT || 0)} = ${fmtNum(components.MVT || 0)}</span></div>
      <div class="res-kv"><span>RNG</span><span>${fmtNum(inputs.RNG || 0)} x ${fmtNum(weights.RNG || 0)} = ${fmtNum(components.RNG || 0)}</span></div>
      <div class="res-kv"><span>ACT</span><span>${fmtNum(inputs.ACT || 0)} x ${fmtNum(weights.ACT || 0)} = ${fmtNum(components.ACT || 0)}</span></div>
      <div class="res-kv"><span>Score</span><span>${fmtNum(breakdown.score || 0)}</span></div>
      <div class="res-kv"><span>Divisor</span><span>${fmtNum(breakdown.divisor || 10)}</span></div>
      <div class="res-kv"><span>Formula Rating</span><span>${fmtNum(breakdown.formula_rating || 0)}</span></div>
      <div class="res-kv"><span>Final Rating</span><span>${fmtNum(breakdown.rating || 0)}${breakdown.source === 'override' ? ' (override)' : ''}</span></div>
    `;
  };

  const renderRatingHelpBubble = (cfg, options = {}) => {
    const config = cfg || {};
    const isAdminView = Boolean(options.isAdmin);
    const atk = Number(config.atk ?? 2);
    const def = Number(config.def ?? 1.5);
    const dmg = Number(config.dmg ?? 3);
    const hp = Number(config.hp ?? 2);
    const mvt = Number(config.mvt ?? 1);
    const rng = Number(config.rng ?? 1);
    const act = Number(config.act ?? 1);
    const divisor = Number(config.divisor ?? 10);

    return `
      <details style="margin-top:8px;">
        <summary title="Help" style="cursor:pointer;display:flex;align-items:center;gap:8px;">
          <span style="display:inline-flex;width:18px;height:18px;border-radius:999px;align-items:center;justify-content:center;background:#35577e;color:#fff;font-weight:700;font-size:12px;">?</span>
          <span><strong>${isAdminView ? 'How Rating Formula Fields Work' : 'How Army Rating Is Calculated'}</strong></span>
        </summary>
        <div class="res-panel" style="margin-top:6px;">
          <div class="muted" style="margin-bottom:6px;">
            Each stat field is a <strong>weight</strong>. Higher weight means that stat contributes more to final unit rating.
          </div>
          <div class="res-kv"><span>ATK</span><span>Attack importance (${fmtNum(atk)})</span></div>
          <div class="res-kv"><span>DEF</span><span>Defense importance (${fmtNum(def)})</span></div>
          <div class="res-kv"><span>DMG</span><span>Damage output importance (${fmtNum(dmg)})</span></div>
          <div class="res-kv"><span>HP</span><span>Durability importance (${fmtNum(hp)})</span></div>
          <div class="res-kv"><span>MVT</span><span>Movement value importance (${fmtNum(mvt)})</span></div>
          <div class="res-kv"><span>RNG</span><span>Range value importance (${fmtNum(rng)})</span></div>
          <div class="res-kv"><span>ACT</span><span>Action economy importance (${fmtNum(act)})</span></div>
          <div class="res-kv"><span>Divisor</span><span>Scales rating down/up after score sum (${fmtNum(divisor)})</span></div>
          <div style="margin-top:8px;font-size:12px;white-space:pre-wrap;">Score = (ATK x ${fmtNum(atk)}) + (DEF x ${fmtNum(def)}) + (DMG x ${fmtNum(dmg)}) + (HP x ${fmtNum(hp)}) + (MVT x ${fmtNum(mvt)}) + (RNG x ${fmtNum(rng)}) + (ACT x ${fmtNum(act)})\nFormula Rating = Score / ${fmtNum(divisor)}\nFinal Rating = override rating (if set) or formula rating</div>
          <div class="muted" style="margin-top:8px;">
            Tip: increase a weight to make that stat matter more globally; lower the divisor to increase all formula-based ratings.
          </div>
        </div>
      </details>
    `;
  };

  const renderUnitCards = (units, options) => {
    if (!Array.isArray(units) || units.length === 0) {
      return '<div class="muted">No units.</div>';
    }

    return units.map((u) => {
      const displayName = String(u.custom_name || u.display_name || 'Unit');
      const baseName = String(u.display_name || 'Unit');
      const instanceIndex = Number(u.instance_index || 1);
      const sourceQty = Number(u.source_qty || 1);
      const rating = Number(u.rating || 0);
      const effectiveClass = String(u.effective_class_name || u.class_name || '');
      const effectiveStatus = String(u.effective_status || u.status || 'owned');
      const effectiveRace = String(u.race || '');
      const effectiveTerrain = String(u.terrain || '');
      const adminNote = String(u.admin_note || '');
      const nameEditor = options.allowNameEdit ? `
        <div class="row" style="margin-top:8px;">
          <label style="min-width:90px;">Unit Name</label>
          <input class="combatUnitNameInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" value="${escapeHtml(String(u.custom_name || ''))}" placeholder="Set custom unit name">
          ${options.allowStatEdit
            ? `<button class="primary combatUnitEditSave" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" style="background:#2f6a41;">Save Unit</button>`
            : `<button class="primary combatUnitNameSave" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" style="background:#314f72;">Save Name</button>`}
        </div>
      ` : '';

      const statsEditor = options.allowStatEdit ? `
        <div class="row" style="margin-top:8px;">
          <label style="min-width:90px;">Class</label>
          <input class="combatUnitMetaInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-field="class_name" value="${escapeHtml(effectiveClass)}" placeholder="Class">
          <label style="min-width:75px;">Status</label>
          <input class="combatUnitMetaInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-field="status" value="${escapeHtml(effectiveStatus)}" placeholder="Status">
        </div>
        <div class="row" style="margin-top:8px;">
          <label style="min-width:90px;">Race</label>
          <input class="combatUnitMetaInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-field="race" value="${escapeHtml(effectiveRace)}" placeholder="Race">
          <label style="min-width:75px;">Terrain</label>
          <input class="combatUnitMetaInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-field="terrain" value="${escapeHtml(effectiveTerrain)}" placeholder="Terrain">
        </div>
        <div class="res-panel" style="margin-top:8px;">
          <div class="res-kv"><span>ATK</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="ATK" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.ATK ?? ''))}"></span></div>
          <div class="res-kv"><span>DEF</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="DEF" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.DEF ?? ''))}"></span></div>
          <div class="res-kv"><span>DMG</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="DMG" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.DMG ?? ''))}"></span></div>
          <div class="res-kv"><span>HP</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="HP" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.HP ?? ''))}"></span></div>
          <div class="res-kv"><span>MVT</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="MVT" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.MVT ?? ''))}"></span></div>
          <div class="res-kv"><span>RNG</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="RNG" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.RNG ?? ''))}"></span></div>
          <div class="res-kv"><span>ACT</span><span><input class="combatUnitStatInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" data-stat="ACT" type="number" step="0.01" value="${escapeHtml(String(u.effective_stats?.ACT ?? ''))}"></span></div>
          <div class="res-kv"><span>Rating</span><span><input class="combatUnitRatingInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" type="number" step="0.01" value="${escapeHtml(String(u.stats_override?.rating ?? u.effective_stats?.rating ?? ''))}"></span></div>
        </div>
        <div style="margin-top:8px;">
          <label style="font-size:12px;display:block;">Admin Note</label>
          <textarea class="combatUnitNoteInput" data-unit-id="${u.id}" data-instance-index="${instanceIndex}" rows="3" placeholder="Private admin notes for this specific unit instance...">${escapeHtml(adminNote)}</textarea>
        </div>
      ` : '';

      return `
        <details class="combat-unit-card">
          <summary>
            <span>${escapeHtml(displayName)}${sourceQty > 1 ? ` <span class="muted">(#${instanceIndex})</span>` : ''}</span>
            <span>Rating: ${fmtNum(rating)}</span>
          </summary>
          <div style="margin-top:8px;">
            <div class="muted">Base: ${escapeHtml(baseName)}</div>
            <div class="muted">Status: ${escapeHtml(effectiveStatus)} | Class: ${escapeHtml(effectiveClass || '-')} | Race: ${escapeHtml(effectiveRace || '-')} | Terrain: ${escapeHtml(effectiveTerrain || '-')}</div>
            ${options.allowStatEdit && adminNote ? `<div class="muted" style="margin-top:6px;"><strong>Admin Note:</strong> ${escapeHtml(adminNote)}</div>` : ''}
            <div style="margin-top:8px;"><strong>Effective Stats</strong></div>
            <div class="res-panel">${renderStatsRows(u.effective_stats)}</div>
            <details style="margin-top:8px;">
              <summary>Army Rating Breakdown</summary>
              <div class="res-panel" style="margin-top:6px;">${renderRatingBreakdown(u.rating_breakdown)}</div>
            </details>
            ${nameEditor}
            ${statsEditor}
          </div>
        </details>
      `;
    }).join('');
  };

  const renderOrders = (orders) => {
    if (!Array.isArray(orders) || orders.length === 0) {
      return '<div class="muted">No combat orders submitted.</div>';
    }

    const normalizeOrderStatus = (value) => {
      const v = String(value || '').trim().toLowerCase();
      if (v === 'approved' || v === 'denied') return v;
      return 'pending';
    };

    return orders.map((o) => `
      <div class="combat-order-item">
        <div style="font-weight:700;">${escapeHtml(String(o.title || 'Combat Order'))}</div>
        <div class="muted" style="font-size:12px;">${escapeHtml(String(o.created_at || ''))}</div>
        <div class="muted" style="font-size:12px;">Status: <strong>${escapeHtml(normalizeOrderStatus(o.order_status))}</strong></div>
        <div style="white-space:break-spaces;margin-top:6px;">${escapeHtml(String(o.body || ''))}</div>
        ${o.review_note ? `<div style="white-space:break-spaces;margin-top:6px;"><strong>Review Note:</strong> ${escapeHtml(String(o.review_note || ''))}</div>` : ''}
        ${isAdmin ? `
          <div class="row" style="margin-top:8px;align-items:flex-start;">
            <select class="combatOrderStatusSelect" data-order-id="${o.id}">
              <option value="pending" ${normalizeOrderStatus(o.order_status) === 'pending' ? 'selected' : ''}>pending</option>
              <option value="approved" ${normalizeOrderStatus(o.order_status) === 'approved' ? 'selected' : ''}>approved</option>
              <option value="denied" ${normalizeOrderStatus(o.order_status) === 'denied' ? 'selected' : ''}>denied</option>
            </select>
            <input class="combatOrderReviewNote" data-order-id="${o.id}" value="${escapeHtml(String(o.review_note || ''))}" placeholder="Optional review note">
            <button class="primary combatOrderStatusSave" data-order-id="${o.id}" style="background:#314f72;">Update</button>
          </div>
        ` : ''}
      </div>
    `).join('');
  };

  const loadData = async () => {
    if (isAdmin && !selectedPlayerId) {
      return { snapshot: null, orders: [], ratingConfig: null };
    }

    const snapshotUrl = isAdmin
      ? `/api/admin/combat/snapshot?user_id=${encodeURIComponent(selectedPlayerId)}`
      : '/api/me/combat/snapshot';
    const ordersUrl = isAdmin
      ? `/api/admin/combat/orders?user_id=${encodeURIComponent(selectedPlayerId)}`
      : '/api/me/combat/orders';

    const calls = [api(snapshotUrl), api(ordersUrl)];
    if (isAdmin) calls.push(api('/api/admin/combat/rating-config'));
    const [snapshotRes, ordersRes, ratingRes] = await Promise.all(calls);
    const snapshot = snapshotRes && snapshotRes.ok ? await snapshotRes.json() : null;
    const orders = ordersRes && ordersRes.ok ? await ordersRes.json() : [];
    const ratingConfig = isAdmin && ratingRes && ratingRes.ok
      ? await ratingRes.json()
      : { atk: 2, def: 1.5, dmg: 3, hp: 2, mvt: 1, rng: 1, act: 1, divisor: 10 };
    return { snapshot, orders, ratingConfig };
  };

  const render = async () => {
    view.innerHTML = `<div class="card"><h2>Combat</h2><div class="muted">Loading combat data...</div></div>`;
    const { snapshot, orders, ratingConfig } = await loadData();

    const commanders = snapshot?.commanders || [];
    const units = snapshot?.units || [];
    const nationName = String(snapshot?.nation?.name || '-');
    const totalArmyRating = Number(snapshot?.total_army_rating || 0);
    const sampleBreakdown = [...commanders, ...units].map((u) => u && u.rating_breakdown).find((b) => b && typeof b === 'object');
    const effectiveRatingConfig = isAdmin
      ? (ratingConfig || { atk: 2, def: 1.5, dmg: 3, hp: 2, mvt: 1, rng: 1, act: 1, divisor: 10 })
      : {
          atk: Number(sampleBreakdown?.weights?.ATK ?? 2),
          def: Number(sampleBreakdown?.weights?.DEF ?? 1.5),
          dmg: Number(sampleBreakdown?.weights?.DMG ?? 3),
          hp: Number(sampleBreakdown?.weights?.HP ?? 2),
          mvt: Number(sampleBreakdown?.weights?.MVT ?? 1),
          rng: Number(sampleBreakdown?.weights?.RNG ?? 1),
          act: Number(sampleBreakdown?.weights?.ACT ?? 1),
          divisor: Number(sampleBreakdown?.divisor ?? 10),
        };

    view.innerHTML = `
      <div class="card">
        <h2>Combat</h2>
        <div class="combat-layout">
          <div>
            <div class="combat-commanders">
              <h3 style="margin-top:0;">Commanders</h3>
              <div class="muted" style="margin-bottom:8px;"><strong>Total Army Rating:</strong> ${fmtNum(totalArmyRating)}</div>
              ${renderUnitCards(commanders, { allowNameEdit: true, allowStatEdit: isAdmin })}
            </div>
            <div>
              <h3 style="margin-top:0;">Units - ${escapeHtml(nationName)}</h3>
              <div class="combat-unit-grid">
                ${renderUnitCards(units, { allowNameEdit: true, allowStatEdit: isAdmin })}
              </div>
            </div>
          </div>
          <div class="combat-orders">
            ${isAdmin ? `
              <h3 style="margin-top:0;">Rating Formula</h3>
              <div class="res-panel" style="margin-bottom:8px;">
                ${renderRatingHelpBubble(effectiveRatingConfig, { isAdmin: true })}
                <div class="row"><label style="min-width:55px;">ATK</label><input id="combatRatingAtk" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.atk ?? 2))}"><label style="min-width:55px;">DEF</label><input id="combatRatingDef" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.def ?? 1.5))}"></div>
                <div class="row"><label style="min-width:55px;">DMG</label><input id="combatRatingDmg" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.dmg ?? 3))}"><label style="min-width:55px;">HP</label><input id="combatRatingHp" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.hp ?? 2))}"></div>
                <div class="row"><label style="min-width:55px;">MVT</label><input id="combatRatingMvt" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.mvt ?? 1))}"><label style="min-width:55px;">RNG</label><input id="combatRatingRng" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.rng ?? 1))}"></div>
                <div class="row"><label style="min-width:55px;">ACT</label><input id="combatRatingAct" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.act ?? 1))}"><label style="min-width:55px;">/</label><input id="combatRatingDivisor" type="number" step="0.01" value="${escapeHtml(String(ratingConfig?.divisor ?? 10))}"></div>
                <div class="row"><button class="primary" id="saveCombatRatingConfigBtn" style="background:#2f6a41;">Save Formula</button><span id="combatRatingConfigMsg" class="muted"></span></div>
              </div>
              <h3 style="margin-top:0;">Orders</h3>
              <div class="row" style="margin-bottom:8px;">
                <label style="min-width:85px;">Player</label>
                <select id="combatAdminPlayerSelect">
                  <option value="">- Select Player -</option>
                  ${adminPlayers.map(p => `<option value="${p.id}" ${String(p.id) === String(selectedPlayerId) ? 'selected' : ''}>${escapeHtml(String(p.name || ('User #' + p.id)))}${p.nation_name ? ' (' + escapeHtml(String(p.nation_name)) + ')' : ''}</option>`).join('')}
                </select>
              </div>
            ` : `
              <h3 style="margin-top:0;">Orders</h3>
              <div class="res-panel" style="margin-bottom:8px;">
                ${renderRatingHelpBubble(effectiveRatingConfig, { isAdmin: false })}
              </div>
              <label style="font-size:12px;display:block;">Order Title (optional)</label>
              <input id="combatOrderTitle" type="text" placeholder="Example: Border Patrol Deployment">
              <label style="font-size:12px;display:block;margin-top:8px;">Order Text</label>
              <textarea id="combatOrderBody" rows="6" placeholder="Write your combat orders for admin review..."></textarea>
              <div class="row" style="margin-top:8px;"><button class="primary" id="submitCombatOrderBtn">Submit Order</button><span class="muted" id="combatOrderMsg"></span></div>
            `}
            <div style="margin-top:8px;" id="combatOrdersList">${renderOrders(orders)}</div>
          </div>
        </div>
      </div>
    `;

    if (isAdmin) {
      const select = document.getElementById('combatAdminPlayerSelect');
      if (select) {
        select.onchange = async () => {
          selectedPlayerId = select.value || '';
          await render();
        };
      }
    }

    const normalizeNumeric = (value) => {
      const n = Number(value);
      return Number.isFinite(n) ? n : null;
    };

    document.querySelectorAll('.combatUnitNameSave').forEach((btn) => {
      btn.onclick = async () => {
        const unitId = btn.dataset.unitId;
        const instanceIndex = Number(btn.dataset.instanceIndex || 1);
        const input = document.querySelector(`.combatUnitNameInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"]`);
        if (!input) return;
        btn.disabled = true;
        const save = await api('/api/me/units/' + encodeURIComponent(unitId) + '/name', {
          method: 'PATCH',
          body: JSON.stringify({ custom_name: input.value || '' }),
        });
        btn.disabled = false;
        if (!save || !save.ok) {
          alert('Unit name save failed.');
          return;
        }
        barkIfEnabled();
        await render();
      };
    });

    document.querySelectorAll('.combatUnitEditSave').forEach((btn) => {
      btn.onclick = async () => {
        const unitId = btn.dataset.unitId;
        const instanceIndex = Number(btn.dataset.instanceIndex || 1);
        const nameInput = document.querySelector(`.combatUnitNameInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"]`);
        const stats = {};
        STAT_KEYS.forEach((key) => {
          const statInput = document.querySelector(`.combatUnitStatInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"][data-stat="${key}"]`);
          const value = normalizeNumeric(statInput?.value);
          if (value !== null) {
            stats[key] = value;
          }
        });
        const ratingInput = document.querySelector(`.combatUnitRatingInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"]`);
        const classInput = document.querySelector(`.combatUnitMetaInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"][data-field="class_name"]`);
        const statusInput = document.querySelector(`.combatUnitMetaInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"][data-field="status"]`);
        const raceInput = document.querySelector(`.combatUnitMetaInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"][data-field="race"]`);
        const terrainInput = document.querySelector(`.combatUnitMetaInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"][data-field="terrain"]`);
        const noteInput = document.querySelector(`.combatUnitNoteInput[data-unit-id="${unitId}"][data-instance-index="${instanceIndex}"]`);
        const ratingValue = ratingInput ? String(ratingInput.value || '').trim() : '';

        btn.disabled = true;
        const save = await api('/api/admin/combat/units/' + encodeURIComponent(unitId) + '/stats', {
          method: 'PUT',
          body: JSON.stringify({
            instance_index: instanceIndex,
            custom_name: nameInput ? (nameInput.value || '') : '',
            class_name: classInput ? (classInput.value || '') : '',
            status: statusInput ? (statusInput.value || '') : '',
            race: raceInput ? (raceInput.value || '') : '',
            terrain: terrainInput ? (terrainInput.value || '') : '',
            admin_note: noteInput ? (noteInput.value || '') : '',
            rating: ratingValue === '' ? null : Number(ratingValue),
            stats_override_json: stats,
          }),
        });
        btn.disabled = false;
        if (!save || !save.ok) {
          alert(await readErrorMessage(save, 'Unit update failed.'));
          return;
        }
        barkIfEnabled();
        await render();
      };
    });

    document.querySelectorAll('.combatOrderStatusSave').forEach((btn) => {
      btn.onclick = async () => {
        const orderId = btn.dataset.orderId;
        const statusEl = document.querySelector(`.combatOrderStatusSelect[data-order-id="${orderId}"]`);
        const noteEl = document.querySelector(`.combatOrderReviewNote[data-order-id="${orderId}"]`);
        if (!statusEl) return;

        btn.disabled = true;
        const res = await api('/api/admin/combat/orders/' + encodeURIComponent(orderId) + '/status', {
          method: 'PUT',
          body: JSON.stringify({
            order_status: statusEl.value || 'pending',
            review_note: noteEl ? (noteEl.value || '') : '',
          }),
        });
        btn.disabled = false;

        if (!res || !res.ok) {
          alert(await readErrorMessage(res, 'Failed to update order status.'));
          return;
        }

        barkIfEnabled();
        await render();
      };
    });

    const saveRatingConfigBtn = document.getElementById('saveCombatRatingConfigBtn');
    if (saveRatingConfigBtn) {
      saveRatingConfigBtn.onclick = async () => {
        const msg = document.getElementById('combatRatingConfigMsg');
        const payload = {
          atk: Number(document.getElementById('combatRatingAtk').value || 0),
          def: Number(document.getElementById('combatRatingDef').value || 0),
          dmg: Number(document.getElementById('combatRatingDmg').value || 0),
          hp: Number(document.getElementById('combatRatingHp').value || 0),
          mvt: Number(document.getElementById('combatRatingMvt').value || 0),
          rng: Number(document.getElementById('combatRatingRng').value || 0),
          act: Number(document.getElementById('combatRatingAct').value || 0),
          divisor: Number(document.getElementById('combatRatingDivisor').value || 10),
        };
        saveRatingConfigBtn.disabled = true;
        const res = await api('/api/admin/combat/rating-config', { method: 'PUT', body: JSON.stringify(payload) });
        saveRatingConfigBtn.disabled = false;
        if (!res || !res.ok) {
          msg.textContent = await readErrorMessage(res, 'Rating formula save failed.');
          return;
        }
        msg.textContent = 'Saved.';
        barkIfEnabled();
        await render();
      };
    }

    const submitBtn = document.getElementById('submitCombatOrderBtn');
    if (submitBtn) {
      submitBtn.onclick = async () => {
        const title = document.getElementById('combatOrderTitle').value || '';
        const body = document.getElementById('combatOrderBody').value || '';
        const msg = document.getElementById('combatOrderMsg');
        if (!body.trim()) {
          msg.textContent = 'Order text is required.';
          return;
        }

        submitBtn.disabled = true;
        const res = await api('/api/me/combat/orders', {
          method: 'POST',
          body: JSON.stringify({ title, body }),
        });
        submitBtn.disabled = false;

        if (!res || !res.ok) {
          msg.textContent = await readErrorMessage(res, 'Failed to submit order.');
          return;
        }

        document.getElementById('combatOrderTitle').value = '';
        document.getElementById('combatOrderBody').value = '';
        msg.textContent = 'Order submitted.';
        barkIfEnabled();
        await render();
      };
    }
  };

  await render();
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
  const MAP_BACKUP_FORMAT = 'azveria-map-backup-v1';
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
  const mapMaxZoomPct = clamp(toFiniteNumber(settings.map_max_zoom_pct, 180), 100, 300);
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
  let editorBackgroundPath = null;
  let editorBackgroundObjectUrl = null;
  const editorBgOpacitySessionKey = `azveria_map_editor_bg_opacity_${user?.id || 'anon'}`;
  let editorBackgroundOpacity = clamp(toFiniteNumber(sessionStorage.getItem(editorBgOpacitySessionKey), 45), 0, 100) / 100;
  let terrainGrid = new Uint8Array(mapWidth * mapHeight);
  let ownerGrid = new Int32Array(mapWidth * mapHeight);

  const politicalNationMap = new Map();
  const seedPoliticalNationMap = (meta = []) => {
    politicalNationMap.clear();
    nations.forEach(n => {
      politicalNationMap.set(Number(n.id), {
        id: Number(n.id),
        name: n.name,
        alliance_name: n.alliance_name || '',
        visibility: (n.visibility && typeof n.visibility === 'object') ? n.visibility : { terrain: true, alliance_name: true },
        races: [],
        dirty: false,
      });
    });

    (Array.isArray(meta) ? meta : []).forEach(n => {
      const id = Number(n.id || 0);
      if (!id) return;
      const existing = politicalNationMap.get(id) || {
        id,
        name: user.role === 'admin' ? (n.name || `Nation ${id}`) : `Nation ${id}`,
        alliance_name: '',
        visibility: { terrain: true, alliance_name: false },
        races: [],
        dirty: false,
      };
      if (user.role === 'admin') {
        existing.name = n.name || existing.name;
        existing.alliance_name = n.alliance_name || existing.alliance_name || '';
        existing.races = Array.isArray(n.races) ? n.races : [];
      }
      politicalNationMap.set(id, existing);
    });
  };
  seedPoliticalNationMap(politicalNationMeta);

  const politicalNationsArray = () => Array.from(politicalNationMap.values()).sort((a, b) => a.name.localeCompare(b.name));

  view.innerHTML = `
    <div class="card">
      <h2>Map</h2>
      <div class="map-right-external" id="mapAdminButtons">
        ${user.role === 'admin' ? '<button class="primary" id="openTerrainEditorBtn">Terrain Editor</button><button class="primary" id="openPoliticalEditorBtn">Political Editor</button><button class="primary" id="downloadMapBackupBtn" style="background:#2f4f6a;">Download Map Backup</button><input id="uploadMapBackupInput" type="file" accept="application/json,.json" style="display:none;"><button class="primary" id="uploadMapBackupBtn" style="background:#5a3f7f;">Upload Map Backup</button><button class="primary" id="recalcTerrainStatsBtn" style="background:#2f6a41;">Recalculate Terrain Stats</button><button class="primary" id="resetMapBtn" style="background:#8a1a1a;">Reset Map</button>' : ''}
      </div>
      <div class="map-shell">
        <div>
          <div class="map-editor-header" id="mapTopControls"></div>
          <div class="map-stage-wrap" id="mapStageWrap">
            <canvas id="mapCanvas" class="map-canvas"></canvas>
            <button class="primary map-floating" id="mapFullscreenBtn" style="right:10px;top:10px;">Fullscreen</button>
            <div class="map-floating map-info-box" id="mapNationInfo" style="display:none;"></div>
          </div>
          <div class="map-controls-dock" id="mapControlsDock">
            <div class="map-bottom-left" id="mapBottomLeftTools" style="display:none;"></div>
            <div class="map-bottom-center" id="mapBottomCenter">
              <label class="map-small-label" for="mapZoomPercent">Zoom</label>
              <input id="mapZoomPercent" type="range" min="-25" max="${mapMaxZoomPct}" step="1" value="0">
            </div>
            <div class="map-bottom-right" id="mapBottomRight">
              <div class="map-floating" style="position:relative;right:auto;bottom:auto;display:flex;gap:8px;align-items:center;">
                <label class="map-small-label" for="terrainOpacity">Terrain Opacity</label>
                <input id="terrainOpacity" type="range" min="0" max="100" value="55">
              </div>
            </div>
          </div>
          <div class="row" style="margin-top:10px;justify-content:space-between;flex-wrap:wrap;">
            <div id="mapSaveArea"></div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
              <span class="map-busy-indicator" id="mapBusyIndicator" style="display:none;"><span class="map-spinner"></span><span id="mapBusyText">Working...</span></span>
              <span class="muted" id="mapPayloadSizeIndicator" title="Estimated JSON payload size for editor-state save."></span>
              <span class="map-status-message" id="mapStatusMsg"></span>
            </div>
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
  const mapControlsDock = document.getElementById('mapControlsDock');
  const mapBottomLeftTools = document.getElementById('mapBottomLeftTools');
  const mapBottomCenter = document.getElementById('mapBottomCenter');
  const mapBottomRight = document.getElementById('mapBottomRight');
  const mapSaveArea = document.getElementById('mapSaveArea');
  const mapBusyIndicator = document.getElementById('mapBusyIndicator');
  const mapBusyText = document.getElementById('mapBusyText');
  const mapPayloadSizeIndicator = document.getElementById('mapPayloadSizeIndicator');
  const mapStatusMsg = document.getElementById('mapStatusMsg');
  const MAP_STATUS_INFO_MS = 15000;
  const MAP_STATUS_WARN_MS = 30000;
  let mapStatusMsgClearTimer = null;
  let mapBusyDepth = 0;

  const formatMapStatusTimestamp = (date = new Date()) => {
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    const ss = String(date.getSeconds()).padStart(2, '0');
    return `[${hh}:${mm}:${ss}]`;
  };

  const setMapStatus = (message, { clearAfterMs = 0, state = '' } = {}) => {
    if (mapStatusMsgClearTimer) {
      clearTimeout(mapStatusMsgClearTimer);
      mapStatusMsgClearTimer = null;
    }
    const body = String(message || '').trim();
    mapStatusMsg.textContent = body ? `${formatMapStatusTimestamp()} ${body}` : '';
    mapStatusMsg.dataset.state = state || '';
    if (clearAfterMs > 0 && mapStatusMsg.textContent) {
      const expected = mapStatusMsg.textContent;
      mapStatusMsgClearTimer = setTimeout(() => {
        if (mapStatusMsg.textContent === expected) {
          mapStatusMsg.textContent = '';
          mapStatusMsg.dataset.state = '';
        }
        mapStatusMsgClearTimer = null;
      }, clearAfterMs);
    }
  };

  const setMapBusy = (isBusy, message = 'Working...') => {
    if (!mapBusyIndicator) return;
    if (isBusy) {
      mapBusyDepth++;
      if (mapBusyText) {
        mapBusyText.textContent = String(message || 'Working...');
      }
      mapBusyIndicator.style.display = 'inline-flex';
      return;
    }

    mapBusyDepth = Math.max(0, mapBusyDepth - 1);
    if (mapBusyDepth === 0) {
      mapBusyIndicator.style.display = 'none';
    }
  };

  let mode = 'view';
  const minZoomPct = -25;
  const maxZoomPct = mapMaxZoomPct;
  const minZoomSensitivity = 0.25;
  const maxZoomSensitivity = 3;
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
  let mapSaveInProgress = false;
  const activeTouchPointers = new Map();
  let pinchGesture = null;
  let pseudoFullscreenActive = false;

  const getZoomSensitivity = () => clamp(
    toFiniteNumber(settings.map_zoom_sensitivity, 1),
    minZoomSensitivity,
    maxZoomSensitivity
  );

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
        <div class="row" style="margin-top:8px;gap:8px;flex-wrap:wrap;">
          <button class="primary" type="button" id="terrainColorResetBtn">Reset Colors</button>
          <button class="primary" type="button" id="terrainColorSaveBtn">Save Colors</button>
          <span class="muted" id="terrainColorMsg"></span>
        </div>
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

    const colorMsgEl = root.querySelector('#terrainColorMsg');
    const saveColorsBtn = root.querySelector('#terrainColorSaveBtn');
    const resetColorsBtn = root.querySelector('#terrainColorResetBtn');

    if (resetColorsBtn) {
      resetColorsBtn.onclick = () => {
        colorOverrides = {};
        terrainLayerDirty = true;
        waterLayerDirty = true;
        if (colorMsgEl) colorMsgEl.textContent = 'Colors reset to defaults. Click Save Colors to persist.';
        renderSidebar();
        render();
      };
    }

    if (saveColorsBtn) {
      saveColorsBtn.onclick = async () => {
        if (mapSaveInProgress) return;
        mapSaveInProgress = true;
        saveColorsBtn.disabled = true;
        if (resetColorsBtn) resetColorsBtn.disabled = true;
        if (colorMsgEl) colorMsgEl.textContent = 'Saving...';
        setMapBusy(true, 'Saving terrain colors...');

        try {
          const saveRes = await api('/api/admin/maps/editor-state', {
            method: 'POST',
            timeout: 120000,
            body: JSON.stringify(buildEditorStatePayload()),
          });

          if (!saveRes || !saveRes.ok) {
            const msg = await readErrorMessage(saveRes, 'Failed to save terrain colors.');
            setMapStatus(msg, { state: 'error' });
            if (colorMsgEl) colorMsgEl.textContent = msg;
            return;
          }

          updateMapPayloadIndicator(true);
          setMapStatus('Terrain colors saved.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
          if (colorMsgEl) colorMsgEl.textContent = 'Terrain colors saved.';
        } catch (error) {
          const message = error?.message || 'Failed to save terrain colors.';
          setMapStatus(message, { state: 'error' });
          if (colorMsgEl) colorMsgEl.textContent = message;
        } finally {
          mapSaveInProgress = false;
          saveColorsBtn.disabled = false;
          if (resetColorsBtn) resetColorsBtn.disabled = false;
          setMapBusy(false);
        }
      };
    }
  };

  const imageCache = new Map();
  const normalizeStoragePath = (path) => String(path || '').replace(/^\/?storage\//, '').trim();
  const loadImage = (path) => {
    const source = String(path || '').trim();
    if (!source) return Promise.resolve(null);
    if (imageCache.has(source)) return imageCache.get(source);
    const p = new Promise((resolve) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => resolve(null);
      if (source.startsWith('blob:') || source.startsWith('data:') || source.startsWith('http://') || source.startsWith('https://') || source.startsWith('/')) {
        img.src = source;
      } else {
        img.src = `/storage/${source.replace(/^\/+/, '')}`;
      }
    });
    imageCache.set(source, p);
    return p;
  };

  const layerImages = {
    main: await loadImage(layerByType.main || ''),
    terrain: await loadImage(layerByType.terrain || ''),
    political: await loadImage(layerByType.political || ''),
  };
  let editorBgImage = null;

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

  const getZoomFactor = (zoomValue) => {
    const z = toFiniteNumber(zoomValue, 0);
    if (z >= 0) {
      return Math.pow(2, z / 52);
    }
    const negativeFactor = 1 / (1 + ((-z) / 35));
    return Math.max(0.5, negativeFactor);
  };

  const getScaleForZoom = (zoomValue) => {
    const viewW = canvas.width || Math.max(200, Math.floor(stage.getBoundingClientRect().width));
    const viewH = canvas.height || Math.max(200, Math.floor(stage.getBoundingClientRect().height));
    const fitScale = Math.min(viewW / mapWidth, viewH / mapHeight);
    const factor = getZoomFactor(zoomValue);
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
        if (mapType === 'alliance') {
          const nation = getNationById(ownerId);
          color = nation?.alliance_name ? mapAllianceColor(nation.alliance_name) : '#7d7d7d';
          if (mapType === 'alliance' && !nation?.alliance_name) color = '#7d7d7d';
        } else if (mapType === 'political' || mode === 'political-editor') {
          color = mapNationColor(ownerId);
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

  const normalizePoliticalNationColorOverrides = (raw) => {
    if (!raw || typeof raw !== 'object') return {};
    const out = {};
    Object.entries(raw).forEach(([k, v]) => {
      const key = String(k || '').trim();
      const color = String(v || '').trim();
      if (!key) return;
      if (/^#[0-9A-Fa-f]{6}$/.test(color)) out[key] = color;
    });
    return out;
  };

  let allianceColorOverrides = normalizeAllianceColorOverrides(settings.alliance_color_overrides || {});
  let politicalNationColorOverrides = normalizePoliticalNationColorOverrides(settings.political_nation_color_overrides || {});

  const mapAllianceColor = (name) => {
    const s = String(name || '').trim().toLowerCase();
    if (!s) return '#808080';
    if (allianceColorOverrides[s]) return allianceColorOverrides[s];
    let hash = 0;
    for (let i = 0; i < s.length; i++) hash = ((hash << 5) - hash + s.charCodeAt(i)) | 0;
    const hue = Math.abs(hash) % 360;
    return `hsl(${hue} 68% 54%)`;
  };

  const mapNationColor = (nationId) => {
    const key = String(Number(nationId || 0));
    if (!key || key === '0') return '#7d7d7d';
    if (politicalNationColorOverrides[key]) return politicalNationColorOverrides[key];
    const seed = Number(key);
    const hue = Math.abs((seed * 47) % 360);
    return `hsl(${hue} 72% 52%)`;
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
    const findNearestOwnerPixel = (owner, targetX, targetY, maxRadius = 220) => {
      const cx = clamp(Math.round(targetX), 0, mapWidth - 1);
      const cy = clamp(Math.round(targetY), 0, mapHeight - 1);

      const centerIndex = idx(cx, cy);
      if (ownerGrid[centerIndex] === owner) {
        return { x: cx, y: cy };
      }

      for (let r = 1; r <= maxRadius; r++) {
        const minX = Math.max(0, cx - r);
        const maxX = Math.min(mapWidth - 1, cx + r);
        const minY = Math.max(0, cy - r);
        const maxY = Math.min(mapHeight - 1, cy + r);

        for (let x = minX; x <= maxX; x++) {
          const topI = idx(x, minY);
          if (ownerGrid[topI] === owner) return { x, y: minY };
          const bottomI = idx(x, maxY);
          if (ownerGrid[bottomI] === owner) return { x, y: maxY };
        }
        for (let y = minY + 1; y < maxY; y++) {
          const leftI = idx(minX, y);
          if (ownerGrid[leftI] === owner) return { x: minX, y };
          const rightI = idx(maxX, y);
          if (ownerGrid[rightI] === owner) return { x: maxX, y };
        }
      }

      return null;
    };

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

      const centroidX = sumX / count;
      const centroidY = sumY / count;
      const ownerAnchor = findNearestOwnerPixel(owner, centroidX, centroidY) || {
        x: centroidX,
        y: centroidY,
      };

      labels.push({
        owner,
        x: centroidX,
        y: centroidY,
        anchorX: ownerAnchor.x,
        anchorY: ownerAnchor.y,
        size: count,
        name,
      });
    }
    labelCache = labels;
  };

  const drawPoliticalLabels = (viewW, viewH) => {
    if (!labelCache.length) computeLabels();

    const occupies = [];
    const intersects = (a, b) => (
      a.left < b.right &&
      a.right > b.left &&
      a.top < b.bottom &&
      a.bottom > b.top
    );

    const labels = labelCache
      .slice()
      .sort((a, b) => (toFiniteNumber(b.size, 0) - toFiniteNumber(a.size, 0)));

    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    labels.forEach((label, i) => {
      const wx = toFiniteNumber(label.anchorX, label.x);
      const wy = toFiniteNumber(label.anchorY, label.y);
      const anchorX = transform.originX + (wx * transform.scale);
      const anchorY = transform.originY + (wy * transform.scale);
      if (anchorX < -140 || anchorY < -120 || anchorX > viewW + 140 || anchorY > viewH + 120) {
        return;
      }

      const sizeBoost = Math.sqrt(Math.max(1, toFiniteNumber(label.size, 1))) / 5;
      const zoomMultiplier = zoomPct >= 0
        ? (1 + (zoomPct * 0.006))
        : (1 + (zoomPct * 0.003));
      const fontPx = clamp((11 + sizeBoost) * clamp(zoomMultiplier, 0.85, 2.0), 10, 40);

      ctx.font = `${Math.round(fontPx)}px Trebuchet MS`;
      const text = String(label.name || '');
      const textWidth = ctx.measureText(text).width;
      const textHeight = fontPx * 1.1;
      const pad = 5;
      const stepX = Math.max(textWidth + 16, 28);
      const stepY = Math.max(textHeight + 12, 20);

      const candidates = [
        { dx: 0, dy: 0 },
        { dx: stepX * 0.55, dy: 0 },
        { dx: -stepX * 0.55, dy: 0 },
        { dx: 0, dy: stepY },
        { dx: 0, dy: -stepY },
        { dx: stepX * 0.55, dy: stepY },
        { dx: -stepX * 0.55, dy: stepY },
        { dx: stepX * 0.55, dy: -stepY },
        { dx: -stepX * 0.55, dy: -stepY },
      ];

      const buildBox = (cx, cy) => ({
        left: cx - (textWidth / 2) - pad,
        right: cx + (textWidth / 2) + pad,
        top: cy - (textHeight / 2) - pad,
        bottom: cy + (textHeight / 2) + pad,
      });

      const candidateIsOnOwnerLand = (cx, cy) => {
        const worldX = Math.floor((cx - transform.originX) / transform.scale);
        const worldY = Math.floor((cy - transform.originY) / transform.scale);
        if (!inBounds(worldX, worldY)) return false;
        return ownerGrid[idx(worldX, worldY)] === label.owner;
      };

      let chosen = null;
      for (const candidate of candidates) {
        const cx = anchorX + candidate.dx;
        const cy = anchorY + candidate.dy;
        if (!candidateIsOnOwnerLand(cx, cy)) continue;
        const box = buildBox(cx, cy);
        if (occupies.some(used => intersects(box, used))) continue;
        chosen = { cx, cy, box };
        break;
      }

      if (!chosen) {
        for (let ring = 1; ring <= 4 && !chosen; ring++) {
          const sidePref = (i % 2 === 0) ? [1, -1] : [-1, 1];
          for (const side of sidePref) {
            const cx = anchorX + (side * (stepX * (0.5 + ring * 0.45)));
            const cy = anchorY + (((ring % 2 === 0) ? 1 : -1) * Math.ceil(ring / 2) * stepY);
            if (!candidateIsOnOwnerLand(cx, cy)) continue;
            const box = buildBox(cx, cy);
            if (occupies.some(used => intersects(box, used))) continue;
            chosen = { cx, cy, box };
            break;
          }
        }
      }

      if (!chosen) {
        const cx = anchorX;
        const cy = anchorY;
        chosen = { cx, cy, box: buildBox(cx, cy) };
      }

      occupies.push(chosen.box);

      const leaderDist = Math.hypot(chosen.cx - anchorX, chosen.cy - anchorY);
      if (leaderDist > 8 && leaderDist <= 90) {
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = 'rgba(0,0,0,0.45)';
        ctx.beginPath();
        ctx.moveTo(anchorX, anchorY);
        ctx.lineTo(chosen.cx, chosen.cy);
        ctx.stroke();
      }

      ctx.lineWidth = Math.max(2, fontPx / 6);
      ctx.strokeStyle = 'rgba(0,0,0,0.72)';
      ctx.fillStyle = '#f8f8f8';
      ctx.strokeText(text, chosen.cx, chosen.cy);
      ctx.fillText(text, chosen.cx, chosen.cy);
    });
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
    const scale = getScaleForZoom(zoomPct);
    transform.scale = scale;
    transform.originX = ((viewW - mapWidth * scale) / 2) + panX;
    transform.originY = ((viewH - mapHeight * scale) / 2) + panY;

    ctx.clearRect(0, 0, viewW, viewH);
    ctx.fillStyle = '#0f1520';
    ctx.fillRect(0, 0, viewW, viewH);

    ctx.save();
    ctx.translate(transform.originX, transform.originY);
    ctx.scale(scale, scale);

    if (layerImages.main) {
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

    if ((mode === 'terrain-editor' || mode === 'political-editor') && editorBgImage) {
      ctx.globalAlpha = editorBackgroundOpacity;
      ctx.drawImage(editorBgImage, 0, 0, mapWidth, mapHeight);
      ctx.globalAlpha = 1;
    }

    ctx.restore();

    if (mapType === 'political' || mapType === 'alliance' || mode === 'political-editor') {
      drawPoliticalLabels(viewW, viewH);
    }

    updateMapPayloadIndicator();
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
    const canViewAlliance = (n?.visibility?.alliance_name !== false);
    const canViewTerrain = (n?.visibility?.terrain !== false);
    const races = user.role === 'admin' ? ((n?.races || []).join(', ') || '-') : '-';
    const nationColor = (mapType === 'alliance')
      ? ((n?.alliance_name ? mapAllianceColor(n.alliance_name) : '#7d7d7d'))
      : mapNationColor(selectedNationId);
    mapNationInfo.style.display = 'block';
    mapNationInfo.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
        <strong>${esc(n?.name || `Nation ${selectedNationId}`)}</strong>
        <button class="primary" id="closeNationInfoBtn" style="padding:2px 8px;">X</button>
      </div>
      <div class="map-small-label" style="margin-top:4px;">Alliance: ${canViewAlliance ? esc(n?.alliance_name || '-') : 'Hidden by visibility rules'}</div>
      <div class="map-small-label">Races: ${esc(races)}</div>
      <div class="map-small-label">Color: <span aria-label="Nation color swatch" style="display:inline-block;width:14px;height:14px;border-radius:3px;border:1px solid rgba(0,0,0,0.4);vertical-align:middle;background:${nationColor};"></span></div>
      <div class="map-small-label">Owned Terrain (pixels): ${canViewTerrain ? fmtNum(pixels) : 'Hidden by visibility rules'}</div>
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
        <button class="primary" id="editorBgClearBtn" type="button" style="padding:4px 8px;">Clear</button>
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
      setMapStatus('Grid resized and reset to all water.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'info' });
    };

    document.getElementById('editorBgUpload').onchange = async (e) => {
      if (!e.target.files || !e.target.files.length) return;
      setMapBusy(true, 'Loading reference image...');
      const file = e.target.files[0];
      try {
        if (editorBackgroundObjectUrl) {
          URL.revokeObjectURL(editorBackgroundObjectUrl);
          editorBackgroundObjectUrl = null;
        }
        editorBackgroundObjectUrl = URL.createObjectURL(file);
        editorBackgroundPath = editorBackgroundObjectUrl;
        editorBgImage = await loadImage(editorBackgroundPath);
        if (!editorBgImage) {
          URL.revokeObjectURL(editorBackgroundObjectUrl);
          editorBackgroundObjectUrl = null;
          editorBackgroundPath = null;
          setMapStatus('Reference upload failed.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
          return;
        }
        render();
        setMapStatus('Reference image loaded for this session only.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
      } finally {
        setMapBusy(false);
      }
    };

    document.getElementById('editorBgClearBtn').onclick = () => {
      if (editorBackgroundObjectUrl) {
        URL.revokeObjectURL(editorBackgroundObjectUrl);
        editorBackgroundObjectUrl = null;
      }
      editorBackgroundPath = null;
      editorBgImage = null;
      const upload = document.getElementById('editorBgUpload');
      if (upload) upload.value = '';
      render();
      setMapStatus('Reference image cleared.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'info' });
    };

    const bgOpacityInput = document.getElementById('editorBgOpacity');
    const bgOpacityLabel = document.getElementById('editorBgOpacityLabel');
    bgOpacityInput.oninput = (e) => {
      const pct = clamp(toFiniteNumber(e.target.value, 100), 0, 100);
      editorBackgroundOpacity = pct / 100;
      sessionStorage.setItem(editorBgOpacitySessionKey, String(Math.round(pct)));
      bgOpacityLabel.textContent = `${pct}%`;
      render();
    };
  };

  const renderBottomTools = () => {
    const isMobileTools = window.matchMedia('(max-width: 900px)').matches;
    const inEditorMode = (mode === 'terrain-editor' || mode === 'political-editor');
    if (mapControlsDock) {
      mapControlsDock.classList.toggle('mobile-map-ui', isMobileTools && inEditorMode);
    }

    mapBottomLeftTools.style.display = (mode === 'terrain-editor' || mode === 'political-editor') ? 'block' : 'none';
    if (mapBottomCenter) {
      mapBottomCenter.style.display = (isMobileTools && inEditorMode) ? 'none' : 'block';
    }
    mapBottomRight.style.display = (isMobileTools && inEditorMode) ? 'none' : 'flex';

    if (isMobileTools && inEditorMode) {
      mapBottomRight.innerHTML = '';
      mapBottomLeftTools.innerHTML = `
        <div class="map-floating" style="position:relative;left:auto;bottom:auto;">
          <div class="map-editor-dock-grid">
            <label class="map-small-label">Tool</label>
            <select id="mapToolSelect"><option value="move">Move</option><option value="brush">Brush</option><option value="fill">Bucket</option><option value="outline">Outline</option></select>
            <label class="map-small-label">Size</label>
            <input id="mapBrushSize" type="range" min="1" max="200" value="${brushSize}">
            <span class="map-small-label" id="mapBrushSizeLabel">${brushSize}px</span>
            <label class="map-small-label" for="mapZoomPercentMobile">Zoom</label>
            <input id="mapZoomPercentMobile" type="range" min="-25" max="100" step="1" value="${Math.round(zoomTargetPct)}">
            <label class="map-small-label" for="terrainOpacityMobile">Terrain Opacity</label>
            <input id="terrainOpacityMobile" type="range" min="0" max="100" value="${Math.round(terrainOpacity * 100)}">
          </div>
          ${mode === 'terrain-editor' ? `
            <details open style="margin-top:8px;">
              <summary class="map-small-label">Terrain Types</summary>
              <div class="map-editor-dock-terrain-list" style="margin-top:6px;">
                ${TERRAIN_KEYS.map(k => `<button class="primary mapTerrainSelectBR" data-key="${k}" style="${selectedTerrainType === k ? 'outline:2px solid var(--accent);' : ''}">${labelTerrainKey(k)}</button>`).join('')}
              </div>
            </details>
          ` : ''}
          <div style="margin-top:8px;">
            <button class="primary mapSaveTrigger" type="button" style="width:100%;">Save</button>
          </div>
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
      document.getElementById('mapZoomPercentMobile').oninput = (e) => {
        zoomTargetPct = clamp(toFiniteNumber(e.target.value, 0), minZoomPct, maxZoomPct);
        const desktopZoom = document.getElementById('mapZoomPercent');
        if (desktopZoom) desktopZoom.value = String(Math.round(zoomTargetPct));
        animateZoomToTarget();
      };
      document.getElementById('terrainOpacityMobile').oninput = (e) => {
        terrainOpacity = clamp(toFiniteNumber(e.target.value, 55), 0, 100) / 100;
        render();
      };
      mapBottomLeftTools.querySelectorAll('.mapTerrainSelectBR').forEach(btn => {
        btn.onclick = () => {
          selectedTerrainType = btn.dataset.key;
          renderBottomTools();
        };
      });
      bindMapSaveTriggers();

      canvas.style.cursor = selectedTool === 'move' ? 'grab' : 'crosshair';
      return;
    }

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
        <button class="primary mapSaveTrigger" type="button">Save</button>
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

    bindMapSaveTriggers();

    canvas.style.cursor = selectedTool === 'move' ? 'grab' : 'crosshair';
  };

  const assignOutlinedLandToNation = () => {
    if (mode !== 'political-editor' || !territoryEditing || !politicalEditNationId) {
      setMapStatus('Enable Political territory editing first.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
      return;
    }
    if (outlinePoints.length < 3 || !outlineClosed) {
      setMapStatus('Cannot assign: outline is not fully encapsulated (closed).', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
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
      setMapStatus('No unowned enclosed land found inside outline.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
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
      setMapStatus('Cannot assign: outline does not fully encapsulate a region.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
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
    setMapStatus('Encapsulated region assigned to selected nation.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
    renderSidebar();
    render();
  };

  const syncNationTerrainStats = async () => {
    const knownNationIds = new Set((Array.isArray(nations) ? nations : []).map(n => Number(n.id || 0)).filter(Boolean));
    const nationPayload = politicalNationsArray().map(n => ({
      id: n.id,
      name: n.name,
      alliance_name: n.alliance_name || '',
      races: n.races || [],
      pixels: nationPixelCount(n.id),
    })).filter(n => knownNationIds.has(Number(n.id || 0)));

    const skippedUnknownNations = Math.max(0, politicalNationsArray().length - nationPayload.length);

    let failedNationUpdates = 0;
    const failedDetails = [];
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
        timeout: 90000,
        body: JSON.stringify({
          name: nation.name,
          alliance_name: nation.alliance_name,
          terrain_square_miles: terrainPayload,
        }),
      });
      if (!nationSaveRes || !nationSaveRes.ok) {
        failedNationUpdates++;
        failedDetails.push({
          nation_id: nation.id,
          nation_name: nation.name,
          status: nationSaveRes?.status || null,
          message: await readErrorMessage(nationSaveRes, 'Nation terrain sync failed.'),
        });
      }
    }

    return {
      ok: failedNationUpdates === 0,
      failedNationUpdates,
      failedDetails,
      updatedCount: nationPayload.length,
      skippedUnknownNations,
    };
  };

  const buildEditorStatePayload = () => {
    const nationPayload = politicalNationsArray().map(n => ({
      id: n.id,
      name: n.name,
      alliance_name: n.alliance_name || '',
      races: n.races || [],
      pixels: nationPixelCount(n.id),
    }));

    return {
      width: mapWidth,
      height: mapHeight,
      terrain_color_overrides: colorOverrides,
      terrain_strokes: terrainStrokes,
      political_strokes: politicalStrokes,
      political_nations: nationPayload,
    };
  };

  let mapPayloadIndicatorLastStamp = 0;
  let mapPayloadIndicatorLastBytes = -1;
  const formatPayloadBytes = (bytes) => {
    if (!Number.isFinite(bytes) || bytes < 0) return '-';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  };

  const updateMapPayloadIndicator = (force = false) => {
    if (!mapPayloadSizeIndicator) return;
    const now = Date.now();
    if (!force && now - mapPayloadIndicatorLastStamp < 1200) return;
    mapPayloadIndicatorLastStamp = now;

    try {
      const json = JSON.stringify(buildEditorStatePayload());
      const bytes = new Blob([json]).size;
      if (!force && bytes === mapPayloadIndicatorLastBytes) return;
      mapPayloadIndicatorLastBytes = bytes;

      mapPayloadSizeIndicator.textContent = `Save payload: ${formatPayloadBytes(bytes)}`;
      if (bytes >= 8 * 1024 * 1024) {
        mapPayloadSizeIndicator.style.color = '#c62828';
      } else if (bytes >= 2 * 1024 * 1024) {
        mapPayloadSizeIndicator.style.color = '#9b5a1e';
      } else {
        mapPayloadSizeIndicator.style.color = '';
      }
    } catch {
      mapPayloadSizeIndicator.textContent = 'Save payload: unavailable';
      mapPayloadSizeIndicator.style.color = '#c62828';
    }
  };

  const persistCurrentMapState = async ({ successMessage = 'Map saved.' } = {}) => {
    if (mapSaveInProgress) {
      setMapStatus('Map save is already in progress...');
      return false;
    }
    mapSaveInProgress = true;
    try {
      const payload = buildEditorStatePayload();
      const saveStateRes = await api('/api/admin/maps/editor-state', {
        method: 'POST',
        timeout: 180000,
        body: JSON.stringify(payload),
      });
      if (!saveStateRes || !saveStateRes.ok) {
        setMapStatus(await readErrorMessage(saveStateRes, 'Failed to save map editor state.'), { state: 'error' });
        return false;
      }

      const syncResult = await syncNationTerrainStats();
      if (!syncResult.ok) {
        captureDeveloperLog('warning', 'Map save partial success: nation terrain sync failures', {
          failed_count: syncResult.failedNationUpdates,
          failed_details: syncResult.failedDetails || [],
          skipped_unknown_nations: syncResult.skippedUnknownNations || 0,
        }, { source: 'map.save' });
        setMapStatus(`Map state saved. ${syncResult.failedNationUpdates} nation terrain update(s) failed.${syncResult.skippedUnknownNations ? ` Skipped ${syncResult.skippedUnknownNations} unknown nation id(s) from backup.` : ''}`, { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
        return true;
      }

      unsavedChanges = false;
      updateMapPayloadIndicator(true);
      setMapStatus(`${successMessage}${syncResult.skippedUnknownNations ? ` Skipped ${syncResult.skippedUnknownNations} unknown nation id(s) from backup.` : ''}`, { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
      return true;
    } catch (error) {
      const message = (error && error.message) ? error.message : 'Map save failed due to a network or server error.';
      captureDeveloperLog('error', 'Map save failed', {
        message,
        stack: error?.stack || '',
      }, { source: 'map.save' });
      setMapStatus(message, { state: 'error' });
      return false;
    } finally {
      mapSaveInProgress = false;
    }
  };

  const bindMapSaveTriggers = () => {
    const setSaveButtonsBusy = (isBusy) => {
      document.querySelectorAll('.mapSaveTrigger').forEach((b) => {
        b.disabled = !!isBusy;
        b.textContent = isBusy ? 'Saving...' : 'Save';
      });
    };

    document.querySelectorAll('.mapSaveTrigger').forEach(btn => {
      btn.onclick = async (event) => {
        if (event) event.preventDefault();
        if (mapSaveInProgress) return;
        setSaveButtonsBusy(true);
        setMapBusy(true, 'Saving map...');
        setMapStatus('Save requested. Saving map now... large maps can take up to a couple of minutes.', { state: 'info' });
        try {
          await persistCurrentMapState({ successMessage: 'Map saved.' });
        } finally {
          setMapBusy(false);
          setSaveButtonsBusy(false);
        }
      };
    });
  };

  const applyEditorStatePayload = (payload) => {
    const imported = (payload && typeof payload === 'object') ? payload : {};
    mapWidth = clamp(toFiniteNumber(imported.width, 1200), 100, 5000);
    mapHeight = clamp(toFiniteNumber(imported.height, 700), 100, 5000);
    terrainStrokes = Array.isArray(imported.terrain_strokes) ? imported.terrain_strokes.slice() : [{ tool: 'fill', terrain: 'water', x: 0, y: 0 }];
    politicalStrokes = Array.isArray(imported.political_strokes) ? imported.political_strokes.slice() : [];
    politicalNationMeta = Array.isArray(imported.political_nations) ? imported.political_nations.slice() : [];
    colorOverrides = (imported.terrain_color_overrides && typeof imported.terrain_color_overrides === 'object')
      ? { ...imported.terrain_color_overrides }
      : {};

    seedPoliticalNationMap(politicalNationMeta);
    terrainGrid = new Uint8Array(mapWidth * mapHeight);
    ownerGrid = new Int32Array(mapWidth * mapHeight);
    selectedNationId = 0;
    politicalEditNationId = 0;
    territoryEditing = false;
    selectedTool = mode === 'terrain-editor' ? 'brush' : selectedTool;
    brushSize = mode === 'terrain-editor' ? terrainDefaultBrushSize : brushSize;
    outlinePoints = [];
    outlineClosed = false;
    lastOutlinePoint = null;
    labelCache = [];
    terrainLayerDirty = true;
    waterLayerDirty = true;
    politicalLayerDirty = true;
    politicalNeedsFullRebuild = false;
    politicalNeedsPostPaintBorderUpdate = false;
    unsavedChanges = true;

    resizeLayerCanvases();
    rebuildTerrainFromStrokes();
    rebuildPoliticalFromStrokes();
    computeLabels();
    renderTopEditorControls();
    renderBottomTools();
    renderSidebar();
    resizeCanvas();
    updateMapPayloadIndicator(true);
    render();
  };

  const extractEditorStateFromBackup = (raw) => {
    if (!raw || typeof raw !== 'object') return null;
    if (raw.editor_state && typeof raw.editor_state === 'object') {
      if (raw.format && raw.format !== MAP_BACKUP_FORMAT) return null;
      return raw.editor_state;
    }
    if (
      Object.prototype.hasOwnProperty.call(raw, 'width')
      || Object.prototype.hasOwnProperty.call(raw, 'height')
      || Object.prototype.hasOwnProperty.call(raw, 'terrain_strokes')
      || Object.prototype.hasOwnProperty.call(raw, 'political_strokes')
    ) {
      return raw;
    }
    return null;
  };

  const buildMapBackupObject = () => ({
    format: MAP_BACKUP_FORMAT,
    exported_at: new Date().toISOString(),
    exported_by_user_id: Number(user?.id || 0) || null,
    editor_state: buildEditorStatePayload(),
  });

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
        setMapStatus('Outline cleared.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'info' });
        renderSidebar();
        render();
      };

      document.getElementById('politicalDoneBtn').onclick = () => {
        territoryEditing = false;
        const pixels = nationPixelCount(politicalEditNationId);
        setMapStatus(`Nation territory committed: ${pixels.toLocaleString()} pixels.`, { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
        renderSidebar();
      };

      document.getElementById('addPoliticalNationBtn').onclick = async () => {
        const name = window.prompt('Nation name for new territory:', 'New Nation');
        if (!name) return;
        const response = await api('/api/admin/nations', { method: 'POST', body: JSON.stringify({ name }) });
        if (!response || !response.ok) {
          setMapStatus('Failed to create nation record.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
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
        ${mapType === 'political' ? `
          <div class="setting-group" style="margin-top:8px;">
            <h3 style="margin-top:0;">Political Nation Colors (Your View)</h3>
            <div class="map-small-label">These colors are saved per player.</div>
            <div class="map-scroll-list" style="max-height:180px;margin-top:6px;">
              ${nations.map(n => {
                const key = String(Number(n.id || 0));
                const value = politicalNationColorOverrides[key] || mapNationColor(key);
                return `
                  <div class="terrain-color-row">
                    <label style="font-size:12px;">${esc(n.name || `Nation ${key}`)}</label>
                    <input type="color" class="politicalNationColorInput" data-key="${esc(key)}" value="${value}">
                  </div>
                `;
              }).join('') || '<div class="muted">No nations found.</div>'}
            </div>
            <div class="row" style="margin-top:8px;">
              <button class="primary" id="savePoliticalNationColorsBtn">Save Political Colors</button>
              <button class="primary" id="resetPoliticalNationColorsBtn">Reset</button>
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

      if (mapType === 'political') {
        mapSidePanel.querySelectorAll('.politicalNationColorInput').forEach(input => {
          input.addEventListener('input', () => {
            const key = String(input.dataset.key || '').trim();
            if (!key) return;
            if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) {
              politicalNationColorOverrides[key] = input.value;
              politicalLayerDirty = true;
              render();
            }
          });
        });

        const savePoliticalNationColorsBtn = document.getElementById('savePoliticalNationColorsBtn');
        if (savePoliticalNationColorsBtn) {
          savePoliticalNationColorsBtn.onclick = async () => {
            const payload = normalizePoliticalNationColorOverrides(politicalNationColorOverrides);
            const saveRes = await api('/api/me/settings', {
              method: 'PATCH',
              body: JSON.stringify({ political_nation_color_overrides: payload }),
            });
            if (!saveRes || !saveRes.ok) {
              mapStatusMsg.textContent = 'Failed to save political nation colors.';
              return;
            }
            settings.political_nation_color_overrides = payload;
            mapStatusMsg.textContent = 'Political nation colors saved for your account.';
          };
        }

        const resetPoliticalNationColorsBtn = document.getElementById('resetPoliticalNationColorsBtn');
        if (resetPoliticalNationColorsBtn) {
          resetPoliticalNationColorsBtn.onclick = () => {
            politicalNationColorOverrides = {};
            settings.political_nation_color_overrides = {};
            politicalLayerDirty = true;
            renderSidebar();
            render();
          };
        }
      }
      const renderTerrainStats = (sqMiles, options = {}) => {
        if (options.restricted) {
          document.getElementById('mapTerrainStats').innerHTML = '<div class="muted">Terrain is hidden by visibility rules for this nation.</div>';
          return;
        }
        const normalized = normalizeTerrainColorStats(sqMiles);
        const total = Math.max(1, Object.values(normalized).reduce((sum, val) => sum + toFiniteNumber(val, 0), 0));
        document.getElementById('mapTerrainStats').innerHTML = TERRAIN_KEYS.map((k) => {
          const v = normalized[k] || 0;
          const value = toFiniteNumber(v, 0);
          const pct = ((value / total) * 100).toFixed(1);
          return `<div class="res-kv"><span>${labelTerrainKey(k)}</span><span>${fmtNum(value)} <span class="muted" style="font-size:11px;">(${pct}%)</span></span></div>`;
        }).join('') || '<div class="muted">No data</div>';
      };
      renderTerrainStats(myTerrainSqMiles);
      document.getElementById('mapNationSelectView').onchange = async (e) => {
        if (e.target.value === 'me') {
          renderTerrainStats(myTerrainSqMiles);
          return;
        }
        const selectedNationId = Number(e.target.value);
        const selectedNation = nations.find(n => Number(n.id) === selectedNationId);
        if (selectedNation?.visibility?.terrain === false) {
          renderTerrainStats({}, { restricted: true });
          return;
        }
        const detailRes = await api('/api/nations/' + e.target.value);
        if (!detailRes || !detailRes.ok) return;
        const detail = await detailRes.json();
        if (detail?.visibility?.terrain === false) {
          renderTerrainStats({}, { restricted: true });
          return;
        }
        renderTerrainStats(detail.terrain?.square_miles_json || {});
      };
    }

    mapSaveArea.innerHTML = (mode === 'terrain-editor' || mode === 'political-editor')
      ? '<button class="primary mapSaveTrigger" id="saveMapEditorBtn" type="button">Save</button>'
      : '';
    bindMapSaveTriggers();
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
        if (mapType === 'alliance') {
          const nation = getNationById(owner);
          color = nation?.alliance_name ? mapAllianceColor(nation.alliance_name) : '#7d7d7d';
        } else if (mapType === 'political' || mode === 'political-editor') {
          color = mapNationColor(owner);
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
    const mobileZoom = document.getElementById('mapZoomPercentMobile');
    if (mobileZoom) mobileZoom.value = String(Math.round(zoomTargetPct));
    animateZoomToTarget();
  };

  stage.addEventListener('wheel', (e) => {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const wheelIntensity = clamp(Math.abs(toFiniteNumber(e.deltaY, 0)) / 120, 0.35, 3.25);
    const delta = (e.deltaY < 0 ? 2.6 : -2.6) * wheelIntensity * getZoomSensitivity();
    zoomTargetPct = clamp(zoomTargetPct + delta, minZoomPct, maxZoomPct);
    document.getElementById('mapZoomPercent').value = String(Math.round(zoomTargetPct));
    const mobileZoom = document.getElementById('mapZoomPercentMobile');
    if (mobileZoom) mobileZoom.value = String(Math.round(zoomTargetPct));
    animateZoomToTarget({ sx: e.clientX - rect.left, sy: e.clientY - rect.top });
  }, { passive: false });

  canvas.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'touch') {
      e.preventDefault();
      activeTouchPointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
      canvas.setPointerCapture(e.pointerId);
      if (activeTouchPointers.size >= 2) {
        const points = Array.from(activeTouchPointers.values());
        const first = points[0];
        const second = points[1];
        const centerX = (first.x + second.x) / 2;
        const centerY = (first.y + second.y) / 2;
        pinchGesture = {
          distance: Math.max(1, Math.hypot(second.x - first.x, second.y - first.y)),
          centerX,
          centerY,
        };
        dragging = false;
        downPoint = null;
        dragAction = 'none';
        canvas.classList.remove('dragging');
        canvas.style.cursor = 'grab';
        return;
      }
    }

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
    if (e.pointerType === 'touch' && activeTouchPointers.has(e.pointerId)) {
      activeTouchPointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
      if (pinchGesture && activeTouchPointers.size >= 2) {
        e.preventDefault();
        const points = Array.from(activeTouchPointers.values());
        const first = points[0];
        const second = points[1];
        const centerX = (first.x + second.x) / 2;
        const centerY = (first.y + second.y) / 2;
        const distance = Math.max(1, Math.hypot(second.x - first.x, second.y - first.y));
        const rect = canvas.getBoundingClientRect();
        const centerDx = centerX - pinchGesture.centerX;
        const centerDy = centerY - pinchGesture.centerY;
        if (Math.abs(centerDx) > 0.01 || Math.abs(centerDy) > 0.01) {
          panX += centerDx;
          panY += centerDy;
        }
        const ratio = distance / Math.max(1, pinchGesture.distance);
        const zoomDelta = Math.log2(Math.max(0.25, ratio)) * 28 * getZoomSensitivity();
        if (Math.abs(zoomDelta) > 0.001) {
          const nextZoom = clamp(zoomPct + zoomDelta, minZoomPct, maxZoomPct);
          zoomTargetPct = nextZoom;
          document.getElementById('mapZoomPercent').value = String(Math.round(nextZoom));
          const mobileZoom = document.getElementById('mapZoomPercentMobile');
          if (mobileZoom) mobileZoom.value = String(Math.round(nextZoom));
          setZoom(nextZoom, { sx: centerX - rect.left, sy: centerY - rect.top });
        } else {
          scheduleRender();
        }
        pinchGesture.distance = distance;
        pinchGesture.centerX = centerX;
        pinchGesture.centerY = centerY;
        return;
      }
    }

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
    if (mode !== 'view' && zoomPct <= 0) return;
    panX = downPoint.panX + (e.clientX - downPoint.x);
    panY = downPoint.panY + (e.clientY - downPoint.y);
    scheduleRender();
  });

  const releasePointer = (e) => {
    if (e.pointerType === 'touch') {
      activeTouchPointers.delete(e.pointerId);
      if (activeTouchPointers.size < 2) {
        pinchGesture = null;
      }
    }

    if (canvas.hasPointerCapture(e.pointerId)) {
      canvas.releasePointerCapture(e.pointerId);
    }

    if (e.pointerType === 'touch' && !dragging && activeTouchPointers.size === 1 && mode === 'view') {
      const remaining = Array.from(activeTouchPointers.values())[0];
      if (remaining) {
        dragging = true;
        downPoint = { x: remaining.x, y: remaining.y, panX, panY };
        dragAction = 'none';
        canvas.classList.add('dragging');
        canvas.style.cursor = 'grabbing';
        return;
      }
    }

    if (!dragging) {
      canvas.classList.remove('dragging');
      if (selectedTool === 'move') {
        canvas.style.cursor = 'grab';
      } else {
        canvas.style.cursor = ((mode === 'terrain-editor' || mode === 'political-editor') ? 'crosshair' : 'grab');
      }
      return;
    }

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

  // Fallback for mobile browsers where Pointer Events are partial or disabled.
  if (!window.PointerEvent) {
    const touchState = {
      dragging: false,
      downPoint: null,
      pinchDistance: 0,
      pinchCenterX: 0,
      pinchCenterY: 0,
    };

    const firstTouchPoint = (touches) => {
      if (!touches || !touches.length) return null;
      const t = touches[0];
      return { x: t.clientX, y: t.clientY };
    };

    const twoTouchPoints = (touches) => {
      if (!touches || touches.length < 2) return null;
      const a = touches[0];
      const b = touches[1];
      return {
        a: { x: a.clientX, y: a.clientY },
        b: { x: b.clientX, y: b.clientY },
      };
    };

    canvas.addEventListener('touchstart', (e) => {
      if (!e.touches?.length) return;
      e.preventDefault();

      if (e.touches.length >= 2) {
        const points = twoTouchPoints(e.touches);
        if (!points) return;
        const centerX = (points.a.x + points.b.x) / 2;
        const centerY = (points.a.y + points.b.y) / 2;
        touchState.pinchDistance = Math.max(1, Math.hypot(points.b.x - points.a.x, points.b.y - points.a.y));
        touchState.pinchCenterX = centerX;
        touchState.pinchCenterY = centerY;
        touchState.dragging = false;
        touchState.downPoint = null;
        return;
      }

      const p = firstTouchPoint(e.touches);
      if (!p) return;
      touchState.dragging = true;
      touchState.downPoint = { x: p.x, y: p.y, panX, panY };
    }, { passive: false });

    canvas.addEventListener('touchmove', (e) => {
      if (!e.touches?.length) return;
      e.preventDefault();

      if (e.touches.length >= 2) {
        const points = twoTouchPoints(e.touches);
        if (!points) return;
        const centerX = (points.a.x + points.b.x) / 2;
        const centerY = (points.a.y + points.b.y) / 2;
        const distance = Math.max(1, Math.hypot(points.b.x - points.a.x, points.b.y - points.a.y));
        const rect = canvas.getBoundingClientRect();
        const centerDx = centerX - touchState.pinchCenterX;
        const centerDy = centerY - touchState.pinchCenterY;
        if (Math.abs(centerDx) > 0.01 || Math.abs(centerDy) > 0.01) {
          panX += centerDx;
          panY += centerDy;
        }
        const ratio = distance / Math.max(1, touchState.pinchDistance);
        const zoomDelta = Math.log2(Math.max(0.25, ratio)) * 28 * getZoomSensitivity();
        if (Math.abs(zoomDelta) > 0.001) {
          const nextZoom = clamp(zoomPct + zoomDelta, minZoomPct, maxZoomPct);
          zoomTargetPct = nextZoom;
          document.getElementById('mapZoomPercent').value = String(Math.round(nextZoom));
          const mobileZoom = document.getElementById('mapZoomPercentMobile');
          if (mobileZoom) mobileZoom.value = String(Math.round(nextZoom));
          setZoom(nextZoom, { sx: centerX - rect.left, sy: centerY - rect.top });
        } else {
          scheduleRender();
        }
        touchState.pinchDistance = distance;
        touchState.pinchCenterX = centerX;
        touchState.pinchCenterY = centerY;
        touchState.dragging = false;
        touchState.downPoint = null;
        return;
      }

      if (!touchState.dragging || !touchState.downPoint) return;
      const p = firstTouchPoint(e.touches);
      if (!p) return;
      panX = touchState.downPoint.panX + (p.x - touchState.downPoint.x);
      panY = touchState.downPoint.panY + (p.y - touchState.downPoint.y);
      scheduleRender();
    }, { passive: false });

    const touchEndHandler = (e) => {
      if (e.touches?.length === 1) {
        const p = firstTouchPoint(e.touches);
        if (p) {
          touchState.dragging = true;
          touchState.downPoint = { x: p.x, y: p.y, panX, panY };
        }
      } else {
        touchState.dragging = false;
        touchState.downPoint = null;
      }
      if (!e.touches?.length) {
        touchState.pinchDistance = 0;
      }
    };

    canvas.addEventListener('touchend', touchEndHandler, { passive: false });
    canvas.addEventListener('touchcancel', touchEndHandler, { passive: false });
  }

  const updateFullscreenButtonLabel = () => {
    const isNativeFullscreen = (document.fullscreenElement === stage) || (document.webkitFullscreenElement === stage);
    mapFullscreenBtn.textContent = (isNativeFullscreen || pseudoFullscreenActive) ? 'Exit Fullscreen' : 'Fullscreen';
  };

  const enterPseudoFullscreen = () => {
    stage.classList.add('map-pseudo-fullscreen');
    document.body.classList.add('map-fullscreen-lock');
    pseudoFullscreenActive = true;
    updateFullscreenButtonLabel();
    resizeCanvas();
    render();
  };

  const exitPseudoFullscreen = () => {
    stage.classList.remove('map-pseudo-fullscreen');
    document.body.classList.remove('map-fullscreen-lock');
    pseudoFullscreenActive = false;
    updateFullscreenButtonLabel();
    resizeCanvas();
    render();
  };

  const requestStageFullscreen = async () => {
    try {
      if (stage.requestFullscreen) {
        await stage.requestFullscreen();
        return true;
      }
      if (stage.webkitRequestFullscreen) {
        stage.webkitRequestFullscreen();
        return true;
      }
    } catch {
      return false;
    }
    return false;
  };

  const exitNativeFullscreen = async () => {
    if (document.fullscreenElement) {
      await document.exitFullscreen();
      return;
    }
    if (document.webkitFullscreenElement && document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    }
  };

  mapFullscreenBtn.onclick = async () => {
    try {
      const nativeActive = (document.fullscreenElement === stage) || (document.webkitFullscreenElement === stage);
      if (nativeActive || pseudoFullscreenActive) {
        if (nativeActive) {
          await exitNativeFullscreen();
        }
        if (pseudoFullscreenActive) {
          exitPseudoFullscreen();
        }
        updateFullscreenButtonLabel();
        return;
      }
      const enteredNative = await requestStageFullscreen();
      if (!enteredNative) {
        enterPseudoFullscreen();
      } else {
        updateFullscreenButtonLabel();
      }
    } catch {}
  };

  const onFullscreenChange = () => {
    const activeFullscreenElement = document.fullscreenElement || document.webkitFullscreenElement || null;
    if (activeFullscreenElement !== stage && pseudoFullscreenActive) {
      exitPseudoFullscreen();
      return;
    }
    updateFullscreenButtonLabel();
    resizeCanvas();
    render();
  };

  document.addEventListener('fullscreenchange', onFullscreenChange);
  document.addEventListener('webkitfullscreenchange', onFullscreenChange);

  if (user.role === 'admin') {
    document.getElementById('openTerrainEditorBtn').onclick = () => setMode('terrain-editor');
    document.getElementById('openPoliticalEditorBtn').onclick = () => setMode('political-editor');
    const downloadMapBackupBtn = document.getElementById('downloadMapBackupBtn');
    if (downloadMapBackupBtn) {
      downloadMapBackupBtn.onclick = () => {
        try {
          const backup = buildMapBackupObject();
          const stamp = String(backup.exported_at || new Date().toISOString()).replace(/[:.]/g, '-');
          const fileName = `azveria-map-backup-${stamp}.json`;
          const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = fileName;
          document.body.appendChild(link);
          link.click();
          link.remove();
          URL.revokeObjectURL(url);
          setMapStatus('Map backup downloaded.', { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
        } catch {
          setMapStatus('Failed to download map backup.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
        }
      };
    }

    const uploadMapBackupInput = document.getElementById('uploadMapBackupInput');
    const uploadMapBackupBtn = document.getElementById('uploadMapBackupBtn');
    if (uploadMapBackupBtn && uploadMapBackupInput) {
      uploadMapBackupBtn.onclick = () => uploadMapBackupInput.click();
      uploadMapBackupInput.onchange = async (event) => {
        const file = event?.target?.files?.[0];
        if (!file) return;
        setMapBusy(true, 'Uploading backup and applying map data...');
        try {
          const text = await file.text();
          const parsed = JSON.parse(text);
          const importedState = extractEditorStateFromBackup(parsed);
          if (!importedState) {
            setMapStatus('Invalid backup file format.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
            uploadMapBackupInput.value = '';
            return;
          }

          applyEditorStatePayload(importedState);
          setMode('terrain-editor');
          const saved = await persistCurrentMapState({ successMessage: 'Map backup imported and saved.' });
          if (!saved) {
            setMapStatus('Backup loaded locally. Server save failed. See previous error details.', { state: 'error' });
          }
        } catch {
          setMapStatus('Failed to import map backup.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
        } finally {
          setMapBusy(false);
        }
        uploadMapBackupInput.value = '';
      };
    }

    document.getElementById('recalcTerrainStatsBtn').onclick = async () => {
      const ok = window.confirm('Recalculate terrain stats for all nations from the current map pixels?');
      if (!ok) return;
      setMapBusy(true, 'Recalculating terrain stats...');
      try {
        const syncResult = await syncNationTerrainStats();
        if (!syncResult.ok) {
          setMapStatus(`Recalculation failed for ${syncResult.failedNationUpdates} nation(s).`, { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
          return;
        }
        setMapStatus(`Terrain stats recalculated for ${syncResult.updatedCount} nation(s).`, { clearAfterMs: MAP_STATUS_INFO_MS, state: 'success' });
        renderSidebar();
      } finally {
        setMapBusy(false);
      }
    };
    document.getElementById('resetMapBtn').onclick = async () => {
      const firstWarning = window.confirm('This will permanently reset the entire map, clear all map layers, and reset all nation terrain map values. Continue?');
      if (!firstWarning) return;

      const phrase = window.prompt('Type exactly: confirm reset of map');
      if ((phrase || '').trim() !== 'confirm reset of map') {
        setMapStatus('Map reset cancelled: confirmation text did not match.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
        return;
      }

      setMapBusy(true, 'Resetting map...');
      try {
        const resetRes = await api('/api/admin/maps/reset', { method: 'POST' });
        if (!resetRes || !resetRes.ok) {
          setMapStatus('Failed to reset map.', { clearAfterMs: MAP_STATUS_WARN_MS, state: 'error' });
          return;
        }

        await loadMap();
      } finally {
        setMapBusy(false);
      }
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
    renderBottomTools();
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
      const resourceDefsRes = await api('/api/resources');
      const resourceDefs = resourceDefsRes && resourceDefsRes.ok ? await resourceDefsRes.json() : { base: {}, advanced: {} };
      setDynamicResourceLabels(resourceDefs);
      const resources = d.resources || {};
      const extra = safeJsonParse(resources.extra_json, {}) || {};
      const advanced = resources.advanced || extra.advanced || extra.refined || {};
      const base = resources.base || {
        cow: resources.cow,
        wood: resources.wood,
        ore: resources.ore,
        food: resources.food,
      };
      const currencies = extra.currencies || {};
      const terrainSqMiles = normalizeTerrainSquareMiles(d.terrain?.square_miles_json || {});
      const terrainTotal = Math.max(1, Object.values(terrainSqMiles).reduce((sum, value) => sum + toFiniteNumber(value, 0), 0));
      const sections = [];

      const renderSection = (title, body) => `
        <details open style="margin-top:10px;">
          <summary>${escapeHtml(title)}</summary>
          <div class="res-panel" style="margin-top:6px;">${body}</div>
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

      // Base Resources
      if (visibility.resources_base && base) {
        const groups = resourceDefs.base || {};
        const html = Object.entries(groups).map(([group, defs]) => {
          return `<details style="margin-top:6px;" open>
            <summary>${group}</summary>
            <div class="res-panel">
              ${defs.length === 0 ? '<div class="muted">none</div>' : defs.map(def => {
                const val = base?.[def.name] ?? 0;
                return `<div class="res-kv"><span>${escapeHtml(def.display_name)}</span><span>${fmtNum(val)}</span></div>`;
              }).join('')}
            </div>
          </details>`;
        }).join('');
        sections.push(renderSection('Base Resources', html));
      }

      // Advanced Resources
      const canViewAdvanced = !!(visibility.resources_advanced ?? visibility.resources_refined);
      if (canViewAdvanced && advanced) {
        const groups = resourceDefs.advanced || {};
        const html = Object.entries(groups).map(([group, defs]) => {
          return `<details style="margin-top:6px;" open>
            <summary>${group}</summary>
            <div class="res-panel">
              ${defs.length === 0 ? '<div class="muted">none</div>' : defs.map(def => {
                const val = advanced?.[def.name] ?? 0;
                return `<div class="res-kv"><span>${escapeHtml(def.display_name)}</span><span>${fmtNum(val)}</span></div>`;
              }).join('')}
            </div>
          </details>`;
        }).join('');
        sections.push(renderSection('Advanced Resources', html));
      }

      if (visibility.resources_currencies) {
        const currencyHtml = Object.entries(currencies || {})
          .filter(([, value]) => toFiniteNumber(value, 0) !== 0)
          .map(([key, value]) => `<div class="res-kv"><span>${escapeHtml(labelKey(`currencies:${key}`))}</span><span>${fmtNum(value)}</span></div>`)
          .join('');
        sections.push(renderSection('Currencies', currencyHtml || '<div class="muted">No currency data visible.</div>'));
      }

      if (visibility.terrain && d.terrain) {
        const terrainHtml = Object.entries(terrainSqMiles)
          .filter(([, value]) => toFiniteNumber(value, 0) > 0)
          .map(([key, value]) => `<div class="res-kv"><span>${escapeHtml(labelTerrainKey(key))}</span><span>${fmtNum(value)} sq mi <span class="muted" style="font-size:11px;">(${((toFiniteNumber(value, 0) / terrainTotal) * 100).toFixed(1)}%)</span></span></div>`)
          .join('') || '<div class="muted">No terrain data visible.</div>';
        sections.push(renderSection('Terrain', terrainHtml));
      }

      if (visibility.army_rating && d.army_rating !== null && d.army_rating !== undefined) {
        sections.push(renderSection('Army Rating', `<div class="res-kv"><span>Total Army Rating</span><span>${fmtNum(d.army_rating)}</span></div>`));
      }

      if (visibility.units) {
        const units = Array.isArray(d.units) ? d.units : extractList(d.units);
        const unitsHtml = units.length > 0
          ? units.map(unit => `<div class="res-kv"><span>${Number(unit.qty || 0)}X ${escapeHtml(unit.display_name || unit.custom_name || 'Unit')}</span><span>&nbsp;</span></div>`).join('')
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
  const defsRes = await api('/api/resources');
  const shopDefs = defsRes && defsRes.ok ? await defsRes.json() : { base: {}, advanced: {} };
  setDynamicResourceLabels(shopDefs);

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

  const buildShopResourceKeys = () => {
    const out = [];
    const seen = new Set();
    ['base', 'advanced'].forEach(type => {
      const groups = shopDefs?.[type] || {};
      Object.values(groups).forEach(arr => {
        (arr || []).forEach(def => {
          const name = String(def?.name || '').trim();
          if (!name) return;
          const key = `${type}:${name}`;
          if (seen.has(key)) return;
          seen.add(key);
          out.push(key);
        });
      });
    });
    return out;
  };

  const ALL_COST_KEYS = buildShopResourceKeys();
  if (ALL_COST_KEYS.length === 0) {
    ALL_COST_KEYS.push('base:cow', 'base:wood', 'base:ore', 'base:food');
  }

  const ensureCostKeyOption = (key) => {
    const normalized = canonicalResourceKey(key) || String(key || '').trim();
    if (!normalized) return '';
    if (!ALL_COST_KEYS.includes(normalized)) {
      ALL_COST_KEYS.push(normalized);
    }
    return normalized;
  };

  const resourceOptionsMarkup = (selectedKey = '') => {
    const normalizedSelected = ensureCostKeyOption(selectedKey);
    return ALL_COST_KEYS.map(ck => `<option value="${ck}" ${ck === normalizedSelected ? 'selected' : ''}>${labelKey(ck)}</option>`).join('');
  };

  function costEditorRows(costObj, itemId) {
    const rows = Object.entries(costObj).map(([k,v]) => {
      const selectedKey = ensureCostKeyOption(k);
      return `
      <div class="row cost-row" style="align-items:center;gap:4px;">
        <select class="cost-key" style="flex:1;padding:4px;">
          ${resourceOptionsMarkup(selectedKey)}
        </select>
        <input type="number" class="cost-val" value="${v}" style="width:80px;padding:4px;">
        <button type="button" class="danger" onclick="this.closest('.cost-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>
      </div>`;
    }).join('');
    return `<div id="cost-rows-${itemId}">${rows}</div>
      <button type="button" onclick="addCostRow(${itemId})" style="font-size:12px;margin-top:4px;background:none;border:1px solid #aaa;border-radius:6px;padding:3px 8px;cursor:pointer;">+ Add</button>`;
  }

  function jsonEditorRows(obj, itemId, editorIdPrefix) {
    const rows = Object.entries(obj || {}).map(([k, v]) => {
      const selectedKey = ensureCostKeyOption(k);
      return `
      <div class="row dyn-row" style="align-items:center;gap:4px;">
        <select class="dyn-key" style="flex:1;padding:4px;">
          ${resourceOptionsMarkup(selectedKey)}
        </select>
        <input type="number" class="dyn-val" value="${v}" style="width:80px;padding:4px;">
        <button type="button" class="danger" onclick="this.closest('.dyn-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>
      </div>`;
    }).join('');
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
        ${resourceOptionsMarkup()}
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
        ${resourceOptionsMarkup()}
      </select>
      <input type="number" class="dyn-val" value="1" style="width:80px;padding:4px;">
      <button type="button" class="danger" onclick="this.closest('.dyn-row').remove()" style="background:none;border:none;cursor:pointer;font-size:16px;padding:0;">✕</button>`;
    container.appendChild(div);
  };

  function readCostRows(itemId) {
    const rows = document.querySelectorAll(`#cost-rows-${itemId} .cost-row`);
    const obj = {};
    rows.forEach(row => {
      const k = canonicalResourceKey(row.querySelector('.cost-key').value);
      const v = Number(row.querySelector('.cost-val').value);
      if (k && v) obj[k] = v;
    });
    return obj;
  }

  function readDynRows(prefix, itemId) {
    const rows = document.querySelectorAll(`#${prefix}-${itemId} .dyn-row`);
    const obj = {};
    rows.forEach(row => {
      const k = canonicalResourceKey(row.querySelector('.dyn-key').value);
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
  const [defaultsRes, usersRes, defsRes] = await Promise.all([
    api('/api/admin/new-account-defaults'),
    api('/api/admin/users?role=player'),
    api('/api/admin/resources'),
  ]);
  const d = await defaultsRes.json();
  const players = await usersRes.json();
  const defs = defsRes && defsRes.ok ? await defsRes.json() : { base: {}, advanced: {} };
  setDynamicResourceLabels(defs);
  const terrainSq = d.terrain_square_miles || {};
  const isStrongTempPassword = (value) => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(String(value || ''));
  const createPasswordSeed = isStrongTempPassword(d.default_temp_password) ? d.default_temp_password : 'Password123';

  const optionGroups = (type) => {
    const groups = defs[type] || {};
    return Object.entries(groups).map(([group, arr]) => {
      if (!arr.length) return '';
      const label = type === 'advanced' ? `Advanced Resources - ${group}` : `Base Resources - ${group}`;
      const options = arr.map(def => `<option value="${type}|${def.name}">${escapeHtml(def.display_name)} (${escapeHtml(group)})</option>`).join('');
      return `<optgroup label="${escapeHtml(label)}">${options}</optgroup>`;
    }).join('');
  };
  const allResourceOptions = `${optionGroups('base')}${optionGroups('advanced')}`;

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
        <summary>Starting Resources (Dynamic)</summary>
        <div class="row" style="gap:8px;align-items:flex-end;flex-wrap:wrap;">
          <div style="min-width:280px;flex:1;">
            <label style="font-size:12px;">Resource</label>
            <select id="na-start-resource">${allResourceOptions}</select>
          </div>
          <div style="min-width:140px;">
            <label style="font-size:12px;">Amount</label>
            <input id="na-start-amount" type="number" value="0">
          </div>
          <button class="primary" type="button" id="na-start-add">Add</button>
        </div>
        <div id="na-start-rows" style="display:grid;gap:6px;margin-top:8px;"></div>
      </details>

      <details style="margin-top:8px;">
        <summary>Income Per Game Year (Dynamic)</summary>
        <div class="row" style="gap:8px;align-items:flex-end;flex-wrap:wrap;">
          <div style="min-width:280px;flex:1;">
            <label style="font-size:12px;">Resource</label>
            <select id="na-income-resource">${allResourceOptions}</select>
          </div>
          <div style="min-width:140px;">
            <label style="font-size:12px;">Amount</label>
            <input id="na-income-amount" type="number" value="0">
          </div>
          <button class="primary" type="button" id="na-income-add">Add</button>
        </div>
        <div id="na-income-rows" style="display:grid;gap:6px;margin-top:8px;"></div>
        <div class="muted" id="na-resource-msg" style="font-size:12px;"></div>
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
      <input id="na-create-password" value="${createPasswordSeed}">
      <label>Role</label>
      <select id="na-create-role"><option value="player">Player</option><option value="admin">Admin</option></select>
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="na-create-nation" checked> Create nation for this account</label>
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="na-force-reset" checked> Require password reset on first login</label>
      <div class="row" style="flex-wrap:wrap;align-items:center;gap:8px;">
        <button class="primary" type="button" id="createManagedAccountBtn">Create Account</button>
        <button class="primary" type="button" id="saveNewAccountDefaults">Save Defaults</button>
        <span class="muted" id="createManagedAccountMsg"></span>
        <span class="muted" id="saveNewAccountDefaultsMsg"></span>
      </div>

      <hr style="margin:12px 0;">
      <h3>Delete Player Account</h3>
      <p class="danger" style="margin-top:0;">This permanently removes the player account and its owned nation data.</p>
      <label>Player Account</label>
      <select id="deletePlayerId">${players.map(player => `<option value="${player.id}" data-name="${player.name.replace(/"/g, '&quot;')}">${player.name} (${player.email})</option>`).join('')}</select>
      <label>Type the exact username to confirm deletion</label>
      <input id="deletePlayerConfirmName" placeholder="Exact username required">
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;"><input type="checkbox" id="deletePlayerPurgeData"> Also purge map/editor references and player-linked app data artifacts</label>
      <label id="deletePlayerPurgeConfirmWrap" style="display:none;">Type PURGE PLAYER DATA to enable purge mode</label>
      <input id="deletePlayerPurgeConfirm" style="display:none;" placeholder="PURGE PLAYER DATA">
      <div class="row"><button class="primary" id="deletePlayerBtn" style="background:#8a1a1a;">Delete Player Permanently</button><span class="muted" id="deletePlayerMsg"></span></div>

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

  const normalizeRows = (rows) => {
    if (!Array.isArray(rows)) return [];
    const seen = new Set();
    const out = [];
    rows.forEach(row => {
      const type = row?.type === 'advanced' ? 'advanced' : 'base';
      const name = String(row?.name || '').trim();
      if (!name) return;
      const key = `${type}|${name}`;
      if (seen.has(key)) return;
      seen.add(key);
      out.push({ type, name, amount: Number(row?.amount || 0) });
    });
    return out;
  };

  const hasCanonicalStarting = Array.isArray(d.starting_resources);
  const hasCanonicalIncome = Array.isArray(d.income_resources);

  const startRows = normalizeRows(
    hasCanonicalStarting
      ? d.starting_resources
      : [
          ...Object.entries(d.resources || {}).map(([name, amount]) => ({ type: 'base', name, amount })),
          ...Object.entries(d.refined_resources || {}).map(([name, amount]) => ({ type: 'advanced', name, amount })),
        ]
  );
  // Only use canonical dynamic resources for income
  const incomeRows = normalizeRows(Array.isArray(d.income_resources) ? d.income_resources : []);

  const displayName = (type, name) => {
    const groups = defs[type] || {};
    for (const arr of Object.values(groups)) {
      const found = (arr || []).find(def => def.name === name);
      if (found) return found.display_name;
    }
    return name;
  };

  const renderRows = (rows, containerId, removeClass, amountClass) => {
    const el = document.getElementById(containerId);
    if (!el) return;
    if (!rows.length) {
      el.innerHTML = '<div class="muted">No rows configured.</div>';
      return;
    }
    el.innerHTML = rows.map((row, idx) => `
      <div class="row" style="border:1px solid var(--border);border-radius:8px;padding:6px;align-items:center;gap:8px;">
        <div style="min-width:90px;">${row.type === 'advanced' ? 'Advanced' : 'Base'}</div>
        <div style="flex:1;">${escapeHtml(displayName(row.type, row.name))} <span class="muted">(${escapeHtml(row.name)})</span></div>
        <input type="number" class="${amountClass}" data-idx="${idx}" value="${Number(row.amount || 0)}" style="max-width:140px;">
        <button class="primary ${removeClass}" type="button" data-idx="${idx}" style="background:#8a1a1a;">Remove</button>
      </div>
    `).join('');
  };

  const rerenderRows = () => {
    renderRows(startRows, 'na-start-rows', 'na-start-remove', 'na-start-amount-input');
    renderRows(incomeRows, 'na-income-rows', 'na-income-remove', 'na-income-amount-input');

    document.querySelectorAll('.na-start-amount-input').forEach(input => {
      input.addEventListener('input', () => {
        const idx = Number(input.dataset.idx);
        if (Number.isFinite(idx) && startRows[idx]) startRows[idx].amount = Number(input.value || 0);
      });
    });
    document.querySelectorAll('.na-income-amount-input').forEach(input => {
      input.addEventListener('input', () => {
        const idx = Number(input.dataset.idx);
        if (Number.isFinite(idx) && incomeRows[idx]) incomeRows[idx].amount = Number(input.value || 0);
      });
    });

    document.querySelectorAll('.na-start-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.dataset.idx);
        if (Number.isFinite(idx) && startRows[idx]) {
          startRows.splice(idx, 1);
          rerenderRows();
        }
      });
    });
    document.querySelectorAll('.na-income-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.dataset.idx);
        if (Number.isFinite(idx) && incomeRows[idx]) {
          incomeRows.splice(idx, 1);
          rerenderRows();
        }
      });
    });
  };

  const addUniqueRow = (rows, rawValue, amount) => {
    const msgEl = document.getElementById('na-resource-msg');
    if (!rawValue || !rawValue.includes('|')) {
      if (msgEl) msgEl.textContent = 'Select a resource first.';
      return;
    }
    const [typeRaw, nameRaw] = rawValue.split('|', 2);
    const type = typeRaw === 'advanced' ? 'advanced' : 'base';
    const name = String(nameRaw || '').trim();
    if (!name) {
      if (msgEl) msgEl.textContent = 'Invalid resource selection.';
      return;
    }
    const duplicate = rows.some(row => row.type === type && row.name === name);
    if (duplicate) {
      if (msgEl) msgEl.textContent = 'Duplicate resources are not allowed.';
      return;
    }
    rows.push({ type, name, amount: Number(amount || 0) });
    if (msgEl) msgEl.textContent = '';
    rerenderRows();
  };

  document.getElementById('na-start-add')?.addEventListener('click', () => {
    addUniqueRow(startRows, document.getElementById('na-start-resource')?.value, document.getElementById('na-start-amount')?.value);
  });
  document.getElementById('na-income-add')?.addEventListener('click', () => {
    addUniqueRow(incomeRows, document.getElementById('na-income-resource')?.value, document.getElementById('na-income-amount')?.value);
  });
  rerenderRows();

  document.getElementById('na-create-role').addEventListener('change', (e) => {
    const createNationToggle = document.getElementById('na-create-nation');
    if (e.target.value === 'admin') {
      createNationToggle.checked = false;
    } else {
      createNationToggle.checked = true;
    }
  });

  const deletePlayerPurgeToggle = document.getElementById('deletePlayerPurgeData');
  const deletePlayerPurgeConfirmWrap = document.getElementById('deletePlayerPurgeConfirmWrap');
  const deletePlayerPurgeConfirmInput = document.getElementById('deletePlayerPurgeConfirm');
  const updateDeletePurgeUi = () => {
    const enabled = !!deletePlayerPurgeToggle?.checked;
    if (deletePlayerPurgeConfirmWrap) {
      deletePlayerPurgeConfirmWrap.style.display = enabled ? 'block' : 'none';
    }
    if (deletePlayerPurgeConfirmInput) {
      deletePlayerPurgeConfirmInput.style.display = enabled ? 'block' : 'none';
      if (!enabled) deletePlayerPurgeConfirmInput.value = '';
    }
  };
  deletePlayerPurgeToggle?.addEventListener('change', updateDeletePurgeUi);
  updateDeletePurgeUi();

  document.getElementById('saveNewAccountDefaults').onclick = async () => {
    const payload = {
      nation_name_template: document.getElementById('na-nation-template').value,
      leader_name_template: document.getElementById('na-leader-template').value,
      alliance_name: document.getElementById('na-alliance').value,
      default_temp_password: document.getElementById('na-temp-password').value,
      about_text: document.getElementById('na-about').value,
      starting_resources: startRows,
      income_resources: incomeRows,
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
    const msgEl = document.getElementById('createManagedAccountMsg');
    const password = document.getElementById('na-create-password').value;
    if (!isStrongTempPassword(password)) {
      if (msgEl) msgEl.textContent = 'Password must be at least 8 chars and include uppercase, lowercase, and a number.';
      return;
    }

    const payload = {
      name: document.getElementById('na-create-name').value.trim(),
      email: document.getElementById('na-create-email').value.trim(),
      password,
      role: document.getElementById('na-create-role').value,
      create_nation: document.getElementById('na-create-nation').checked,
      force_password_reset: document.getElementById('na-force-reset').checked,
    };
    const create = await api('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
    if (msgEl) msgEl.textContent = create?.ok ? 'Account created.' : await readErrorMessage(create, 'The account could not be created.');
    if (create?.ok) {
      await loadNewAccounts();
    }
    barkIfEnabled();
  };

  document.getElementById('deletePlayerBtn').onclick = async () => {
    const userId = Number(document.getElementById('deletePlayerId').value);
    const confirmName = document.getElementById('deletePlayerConfirmName').value.trim();
    const purgePlayerData = !!document.getElementById('deletePlayerPurgeData')?.checked;
    const purgeConfirmation = String(document.getElementById('deletePlayerPurgeConfirm')?.value || '').trim();
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
    if (purgePlayerData && purgeConfirmation.toUpperCase() !== 'PURGE PLAYER DATA') {
      document.getElementById('deletePlayerMsg').textContent = 'Type PURGE PLAYER DATA to confirm purge mode.';
      return;
    }
    const prompt = purgePlayerData
      ? `Delete ${expectedName} forever and purge related map/player data artifacts across the app?`
      : `Delete ${expectedName} forever? This also removes the player's nation data.`;
    if (!window.confirm(prompt)) {
      return;
    }

    const res = await api(`/api/admin/users/${userId}`, {
      method: 'DELETE',
      body: JSON.stringify({
        confirmation_name: confirmName,
        purge_player_data: purgePlayerData,
        purge_confirmation: purgeConfirmation,
      })
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
        <h3 style="margin:0 0 8px 0;">Map</h3>
        <label for="mapZoomSensitivity">Map Zoom Sensitivity: <span id="mapZoomSensitivityLabel">100%</span></label>
        <input id="mapZoomSensitivity" type="range" min="25" max="300" step="5" value="100">
      </div>
      ${user.role === 'admin' ? `
      <div class="setting-group">
        <h3 style="margin:0 0 8px 0;">Admin Global Map Settings</h3>
        <label for="mapMaxZoomGlobal">Global Max Zoom Percent (all players)</label>
        <input id="mapMaxZoomGlobal" type="number" min="100" max="300" step="1" value="180">
      </div>
      ` : ''}
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
  const zoomSensitivityInput = document.getElementById('mapZoomSensitivity');
  const zoomSensitivityLabel = document.getElementById('mapZoomSensitivityLabel');
  const clampPct = (value, min, max) => Math.max(min, Math.min(max, value));
  const mapZoomSensitivityPct = Math.round(clampPct(toFiniteNumber(settings.map_zoom_sensitivity, 1), 0.25, 3) * 100);
  zoomSensitivityInput.value = String(mapZoomSensitivityPct);
  zoomSensitivityLabel.textContent = `${mapZoomSensitivityPct}%`;
  zoomSensitivityInput.oninput = (e) => {
    const nextPct = clampPct(toFiniteNumber(e.target.value, 100), 25, 300);
    zoomSensitivityLabel.textContent = `${Math.round(nextPct)}%`;
  };
  const adminMapMaxZoomInput = user.role === 'admin' ? document.getElementById('mapMaxZoomGlobal') : null;
  if (adminMapMaxZoomInput) {
    const globalMaxZoom = clampPct(toFiniteNumber(settings.map_max_zoom_pct, 180), 100, 300);
    adminMapMaxZoomInput.value = String(Math.round(globalMaxZoom));
  }

  document.getElementById('saveSettings').onclick = async () => {
    try {
      const payload = {
        theme: document.getElementById('theme').value,
        color_blind_mode: document.getElementById('cb').value,
        dog_bark_enabled: document.getElementById('goofySound').checked,
        font_mode: document.getElementById('fontMode').value,
        show_unread_chat_badge: document.getElementById('showUnreadChatBadge').checked,
        map_zoom_sensitivity: clampPct(toFiniteNumber(zoomSensitivityInput.value, 100), 25, 300) / 100,
      };
      const userRes = await api('/api/me/settings', { method: 'PATCH', body: JSON.stringify(payload) });
      if (!userRes?.ok) {
        throw new Error(await readErrorMessage(userRes, 'Settings could not be saved.'));
      }
      if (adminMapMaxZoomInput) {
        const adminPayload = {
          map_max_zoom_pct: Math.round(clampPct(toFiniteNumber(adminMapMaxZoomInput.value, 180), 100, 300)),
        };
        const adminRes = await api('/api/admin/map-settings', { method: 'PATCH', body: JSON.stringify(adminPayload) });
        if (!adminRes?.ok) {
          throw new Error(await readErrorMessage(adminRes, 'Global map settings could not be saved.'));
        }
        settings.map_max_zoom_pct = adminPayload.map_max_zoom_pct;
      }
      settings = { ...settings, ...payload };
      setTheme(settings.theme);
      applyColorBlindMode(settings.color_blind_mode);
      setFontMode(settings.font_mode);
      refreshChatBadge();
      barkIfEnabled();
    } catch (error) {
      window.alert(error?.message || 'Settings could not be saved.');
    }
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

  const nationsRes = await api('/api/admin/nations');
  const nationsPayload = await nationsRes.json();
  const nations = extractList(nationsPayload);

  view.innerHTML = `
    <div class="card">
      <h2>All Nations (Admin)</h2>
      <div class="card">
        <h3 style="margin-top:0;">Nation Management</h3>
        <div class="alln-panel" style="margin-bottom:12px;">
          <h4 class="alln-panel-title">Nation Stats Editor</h4>
          <label style="font-size:12px;">Select Nation To Edit</label>
          <select id="adminNationSelect" style="margin-top:4px;">
            <option value="">Select a nation...</option>
            ${(nations || []).map(n => `<option value="${n.id}">${n.name}</option>`).join('')}
          </select>
          <div id="adminNationEditor" class="nation-editor-shell" style="margin-top:8px;"><div class="muted">Select a nation from the dropdown to edit.</div></div>
        </div>

        <div class="alln-grid">
          <div class="alln-panel">
            <h4 class="alln-panel-title">Create Placeholder Nation</h4>
            <input id="newPlaceholder" placeholder="New placeholder nation name">
            <button class="primary" id="createPlaceholder" style="margin-top:8px; width:100%;">Create Placeholder Nation</button>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 style="margin-top:0;">Nation Visibility Matrix</h3>
        <p class="muted" style="margin-top:0;">Control what one nation can see about another nation in Other Nations.</p>
        <div class="vis-controls-grid">
          <div>
            <label style="font-size:12px;">Nation View (viewer)</label>
            <select id="visViewerNation"></select>
          </div>
          <div>
            <label style="font-size:12px;">Nation To Be Seen (subject)</label>
            <select id="visSubjectNation"></select>
          </div>
          <button class="primary" id="loadVisibilityRulesBtn">Load Rules</button>
        </div>
        <div id="visRuleGrid" class="list" style="margin-top:8px;max-height:260px;">Select nations to load rules.</div>
        <div class="row"><button class="primary" id="saveVisibilityRulesBtn">Save Visibility Rules</button><span class="muted" id="saveVisibilityMsg"></span></div>
      </div>
    </div>
  `;

  const visFieldsRes = await api('/api/admin/visibility/fields');
  const visFields = visFieldsRes && visFieldsRes.ok ? (await visFieldsRes.json()) : [];

  const visViewer = document.getElementById('visViewerNation');
  const visSubject = document.getElementById('visSubjectNation');
  const nationOptions = (nations || []).map(n => `<option value="${n.owner_user_id}">${n.name}</option>`).join('');
  visViewer.innerHTML = `<option value="">Select viewer nation</option>${nationOptions}`;
  visSubject.innerHTML = `<option value="">Select subject nation</option>${nationOptions}`;

  const renderVisGrid = (ruleMap = {}) => {
    document.getElementById('visRuleGrid').innerHTML = visFields.map(field => `
      <label class="vis-rule-row">
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
      document.getElementById('saveVisibilityMsg').textContent = 'Viewer and subject must be different nations.';
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
      document.getElementById('saveVisibilityMsg').textContent = 'Viewer and subject must be different nations.';
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
    const nationSelectEl = document.getElementById('adminNationSelect');
    if (nationSelectEl && String(nationSelectEl.value || '') !== String(id)) {
      nationSelectEl.value = String(id);
    }

    const detailRes = await api('/api/nations/' + id);
    const d = await detailRes.json();


    // Fetch resource definitions for dynamic base/advanced resource fields
    const resourceDefsRes = await api('/api/resources');
    const resourceDefs = resourceDefsRes && resourceDefsRes.ok ? await resourceDefsRes.json() : { base: {}, advanced: {} };
    const extra = safeJsonParse(d.resources?.extra_json, {}) || {};
    const baseRes = {
      cow: Number(d.resources?.cow || 0),
      wood: Number(d.resources?.wood || 0),
      ore: Number(d.resources?.ore || 0),
      food: Number(d.resources?.food || 0),
      ...(extra.base || {}),
      ...(d.resources?.base || {}),
    };
    const advancedRes = d.resources?.advanced || extra.advanced || extra.refined || {};

    // Canonical dynamic income rows: [{type:'base'|'advanced', name:'resource_name', amount:number}]
    const initialIncomeRows = [];
    if (Array.isArray(extra.income_resources) && extra.income_resources.length > 0) {
      extra.income_resources.forEach(entry => {
        const type = entry?.type === 'advanced' ? 'advanced' : 'base';
        const name = String(entry?.name || '').trim();
        if (!name) return;
        initialIncomeRows.push({ type, name, amount: Number(entry?.amount || 0) });
      });
    } else {
      const incomeMap = d.resources?.income || extra.income || {};
      Object.entries(incomeMap).forEach(([key, value]) => {
        if (key.includes(':')) {
          const [rawType, rawName] = key.split(':', 2);
          const type = rawType === 'advanced' ? 'advanced' : 'base';
          const name = String(rawName || '').trim();
          if (!name) return;
          initialIncomeRows.push({ type, name, amount: Number(value || 0) });
          return;
        }
        if (key.startsWith('ref_')) {
          const name = key.substring(4);
          if (!name) return;
          initialIncomeRows.push({ type: 'advanced', name, amount: Number(value || 0) });
          return;
        }
        if (key.startsWith('cur_')) {
          return;
        }
        initialIncomeRows.push({ type: 'base', name: key, amount: Number(value || 0) });
      });
    }

    const hasConfiguredIncome = initialIncomeRows.length > 0;
    const seenIncomeRows = new Set();
    const dedupedIncomeRows = [];
    initialIncomeRows.forEach(row => {
      const type = row?.type === 'advanced' ? 'advanced' : 'base';
      const name = String(row?.name || '').trim();
      if (!name) return;
      const key = `${type}|${name}`;
      if (seenIncomeRows.has(key)) return;
      seenIncomeRows.add(key);
      dedupedIncomeRows.push({ type, name, amount: Number(row?.amount || 0) });
    });
    initialIncomeRows.length = 0;
    initialIncomeRows.push(...dedupedIncomeRows);

    // Ensure income rows include all base resources currently associated to this nation.
    Object.keys(baseRes || {}).forEach(name => {
      const resourceName = String(name || '').trim();
      if (!resourceName) return;
      const key = `base|${resourceName}`;
      if (seenIncomeRows.has(key)) return;
      const defaultAmount = !hasConfiguredIncome
        ? (resourceName === 'cow' ? 30 : (resourceName === 'wood' || resourceName === 'ore' || resourceName === 'food' ? 3 : 0))
        : 0;
      seenIncomeRows.add(key);
      initialIncomeRows.push({ type: 'base', name: resourceName, amount: defaultAmount });
    });
    let sqMiles = {};
    try { sqMiles = d.terrain?.square_miles_json ? JSON.parse(d.terrain.square_miles_json) : {}; } catch {}

    // Render grouped resource inputs
    function makeResourceInputs(type, values) {
      const groups = resourceDefs[type] || {};
      return Object.entries(groups).map(([group, defs]) => `
        <details style="margin:6px 0;" open>
          <summary style="font-size:13px;">${group}</summary>
          <div class="nation-editor-grid" style="margin-top:6px;">
            ${defs.map(def => `<label style="font-size:12px;">${escapeHtml(def.display_name)}<input id="${type}-res-${def.name}" type="number" value="${values[def.name] || 0}" style="margin-top:4px;"></label>`).join('')}
          </div>
        </details>
      `).join('');
    }

    function buildIncomeResourceOptions() {
      const buildGroup = (type) => {
        const groups = resourceDefs[type] || {};
        return Object.entries(groups).map(([group, defs]) => {
          const options = defs.map(def => `<option value="${type}|${def.name}">${escapeHtml(def.display_name)} (${escapeHtml(group)})</option>`).join('');
          if (!options) return '';
          const label = type === 'base' ? `Base Resources - ${group}` : `Advanced Resources - ${group}`;
          return `<optgroup label="${escapeHtml(label)}">${options}</optgroup>`;
        }).join('');
      };

      return `${buildGroup('base')}${buildGroup('advanced')}`;
    }

    document.getElementById('adminNationEditor').innerHTML = `
      <div class="nation-editor-block" style="margin-top:0;">
        <div class="nation-editor-grid">
          <label>Name<input id="nName" value="${d.nation.name}" style="margin-top:4px;"></label>
          <label>Leader<input id="nLeader" value="${d.nation.leader_name || ''}" style="margin-top:4px;"></label>
          <label>Alliance<input id="nAlliance" value="${d.nation.alliance_name || ''}" style="margin-top:4px;"></label>
        </div>
        <label style="display:block;margin-top:8px;">About<textarea id="nAbout" style="margin-top:4px;">${d.nation.about_text || ''}</textarea></label>
      </div>
      <details style="margin-top:8px;" open>
        <summary>Base Resources</summary>
        <div class="nation-editor-block">${makeResourceInputs('base', baseRes)}</div>
      </details>
      <details style="margin-top:8px;">
        <summary>Advanced Resources</summary>
        <div class="nation-editor-block">${makeResourceInputs('advanced', advancedRes)}</div>
      </details>
      <details style="margin-top:8px;">
        <summary>Income Per Game Year</summary>
        <div class="nation-editor-block">
          <div class="defaults-admin-form">
            <div>
              <label style="font-size:12px;">Add Resource Income</label>
              <select id="nIncomeResourceSelect">${buildIncomeResourceOptions()}</select>
            </div>
            <div>
              <label style="font-size:12px;">Amount Per Year</label>
              <input id="nIncomeAmountInput" type="number" value="0">
            </div>
            <button class="primary" type="button" id="nAddIncomeRowBtn">Add Income Row</button>
          </div>
          <div id="nIncomeRows" style="margin-top:8px;display:grid;gap:6px;"></div>
          <div class="muted" id="nIncomeMsg" style="font-size:12px;"></div>
        </div>
      </details>
      <details style="margin-top:8px;">
        <summary>Terrain Square Miles</summary>
        <div class="nation-editor-block">
          <div class="nation-editor-grid">
            <label>Grassland<input id="nSqGrassland" type="number" value="${sqMiles.grassland || 0}" style="margin-top:4px;"></label>
            <label>Mountain<input id="nSqMountain" type="number" value="${sqMiles.mountain || 0}" style="margin-top:4px;"></label>
            <label>Freshwater<input id="nSqFreshwater" type="number" value="${sqMiles.freshwater || 0}" style="margin-top:4px;"></label>
            <label>Hills<input id="nSqHills" type="number" value="${sqMiles.hills || 0}" style="margin-top:4px;"></label>
            <label>Desert<input id="nSqDesert" type="number" value="${sqMiles.desert || 0}" style="margin-top:4px;"></label>
            <label>Sea Front<input id="nSqSeafront" type="number" value="${sqMiles.seafront || 0}" style="margin-top:4px;"></label>
          </div>
        </div>
      </details>
      <div class="row"><button class="primary" id="saveNation">Save Nation</button><span class="muted" id="saveNationMsg"></span></div>

      <hr style="margin:12px 0;">
      <h3 style="margin:0 0 8px;">Owned Units / Buildings</h3>
      <details open style="margin-bottom:8px;">
        <summary>Units (${(d.units || []).length})</summary>
        <div class="list" style="max-height:220px;">${(d.units || []).map(u => `
          <div class="admin-asset-row">
            <div>${escapeHtml(u.display_name || 'Unit')} x${Number(u.qty || 0)} <span class="muted">(${escapeHtml(u.status || 'owned')})</span></div>
            <div style="display:flex;gap:6px;align-items:center;">
              <input id="removeUnitQty-${u.id}" type="number" min="1" max="${Number(u.qty || 1)}" value="1" style="width:70px;padding:4px;">
              <button class="primary admin-asset-remove removeUnitBtn" data-unit-id="${u.id}">Remove</button>
            </div>
          </div>
        `).join('') || '<div class="muted">No units</div>'}</div>
      </details>
      <details open style="margin-bottom:8px;">
        <summary>Buildings (${(d.buildings || []).length})</summary>
        <div class="list" style="max-height:220px;">${(d.buildings || []).map(b => `
          <div class="admin-asset-row">
            <div>${escapeHtml(b.display_name || 'Building')} L${Number(b.level || 1)} <span class="muted">(${escapeHtml(b.status || 'built')})</span></div>
            <button class="primary admin-asset-remove removeBuildingBtn" data-building-id="${b.id}">Remove</button>
          </div>
        `).join('') || '<div class="muted">No buildings</div>'}</div>
      </details>

      <hr style="margin:12px 0;">
      <h3 style="margin:0 0 8px;">Add Unit</h3>
      <div class="admin-picker">
        <label style="font-size:13px;">Search Units</label>
        <input id="unitCatalogSearch" placeholder="Type to filter units...">
        <select id="unitCatId" class="admin-picker-list" size="8" style="margin-top:6px;"></select>
        <div class="row" style="margin-top:6px;">
          <div style="min-width:120px;"><label style="font-size:12px;">Quantity</label><input id="unitQty" type="number" value="1" min="1"></div>
          <div style="min-width:180px;"><label style="font-size:12px;">Status</label><select id="unitStatus"><option value="owned">Owned</option><option value="training">Training</option></select></div>
        </div>
        <div class="row" style="margin-top:6px;"><button class="primary" id="addUnitBtn">Add Unit</button><span class="muted" id="addUnitMsg"></span></div>
      </div>

      <h3 style="margin:12px 0 8px;">Add Building</h3>
      <div class="admin-picker">
        <label style="font-size:13px;">Search Buildings</label>
        <input id="buildingCatalogSearch" placeholder="Type to filter buildings...">
        <select id="buildingCatId" class="admin-picker-list" size="8" style="margin-top:6px;"></select>
        <div class="row" style="margin-top:6px;">
          <div style="min-width:100px;"><label style="font-size:12px;">Level</label><input id="buildingLevel" type="number" value="1" min="1"></div>
          <div style="min-width:180px;"><label style="font-size:12px;">Status</label><select id="buildingStatus"><option value="built">Built</option><option value="constructing">Constructing</option><option value="upgrading">Upgrading</option></select></div>
          <div style="min-width:120px;"><label style="font-size:12px;">Quantity</label><input id="buildingQty" type="number" value="1" min="1"></div>
        </div>
        <div class="row" style="margin-top:6px;"><button class="primary" id="addBuildingBtn">Add Building</button><span class="muted" id="addBuildingMsg"></span></div>
      </div>
    `;

    const incomeRows = initialIncomeRows.slice();
    const incomeDisplayName = (type, name) => {
      const defs = resourceDefs[type] || {};
      for (const defsInGroup of Object.values(defs)) {
        const found = (defsInGroup || []).find(def => def.name === name);
        if (found) return found.display_name;
      }
      return name;
    };

    const renderIncomeRows = () => {
      const rowsEl = document.getElementById('nIncomeRows');
      if (!rowsEl) return;
      if (incomeRows.length === 0) {
        rowsEl.innerHTML = '<div class="muted">No yearly income resources configured.</div>';
        return;
      }
      rowsEl.innerHTML = incomeRows.map((row, idx) => {
        return `
          <div class="nation-income-row ${row.type === 'advanced' ? 'advanced' : 'base'}">
            <span class="type-pill">${row.type === 'advanced' ? 'ADV' : 'BASE'}</span>
            <div class="name">${escapeHtml(incomeDisplayName(row.type, row.name))} <span class="muted">(${escapeHtml(row.name)})</span></div>
            <input type="number" class="n-income-row-amount amt" data-idx="${idx}" value="${Number(row.amount || 0)}">
            <button class="primary n-remove-income-row" type="button" data-idx="${idx}" style="background:#8a1a1a;">Remove</button>
          </div>
        `;
      }).join('');

      rowsEl.querySelectorAll('.n-income-row-amount').forEach(input => {
        input.addEventListener('input', () => {
          const idx = Number(input.dataset.idx);
          if (!Number.isFinite(idx) || !incomeRows[idx]) return;
          incomeRows[idx].amount = Number(input.value || 0);
        });
      });

      rowsEl.querySelectorAll('.n-remove-income-row').forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.dataset.idx);
          if (!Number.isFinite(idx) || !incomeRows[idx]) return;
          incomeRows.splice(idx, 1);
          renderIncomeRows();
          const msgEl = document.getElementById('nIncomeMsg');
          if (msgEl) msgEl.textContent = '';
        });
      });
    };

    document.getElementById('nAddIncomeRowBtn')?.addEventListener('click', () => {
      const selectEl = document.getElementById('nIncomeResourceSelect');
      const amountEl = document.getElementById('nIncomeAmountInput');
      const msgEl = document.getElementById('nIncomeMsg');
      const raw = String(selectEl?.value || '');
      if (!raw.includes('|')) {
        if (msgEl) msgEl.textContent = 'Select a resource to add.';
        return;
      }
      const [typeRaw, nameRaw] = raw.split('|', 2);
      const type = typeRaw === 'advanced' ? 'advanced' : 'base';
      const name = String(nameRaw || '').trim();
      if (!name) {
        if (msgEl) msgEl.textContent = 'Select a valid resource to add.';
        return;
      }
      const duplicate = incomeRows.some(row => row.type === type && row.name === name);
      if (duplicate) {
        if (msgEl) msgEl.textContent = 'That resource is already in the income list.';
        return;
      }
      incomeRows.push({ type, name, amount: Number(amountEl?.value || 0) });
      if (msgEl) msgEl.textContent = '';
      renderIncomeRows();
    });

    renderIncomeRows();

    const [unitCatalogRes, buildingCatalogRes] = await Promise.all([
      api('/api/admin/unit-catalog'),
      api('/api/admin/building-catalog'),
    ]);
    const unitCatalog = unitCatalogRes && unitCatalogRes.ok ? await unitCatalogRes.json() : [];
    const buildingCatalog = buildingCatalogRes && buildingCatalogRes.ok ? await buildingCatalogRes.json() : [];

    const unitSelect = document.getElementById('unitCatId');
    const buildingSelect = document.getElementById('buildingCatId');

    const renderUnitOptions = (term = '') => {
      const needle = String(term || '').trim().toLowerCase();
      const filtered = unitCatalog.filter(u => {
        const hay = `${u.display_name || ''} ${u.class_name || ''} ${u.code || ''}`.toLowerCase();
        return !needle || hay.includes(needle);
      });
      unitSelect.innerHTML = filtered.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || 'Unit')} [${escapeHtml(u.class_name || 'unit')}]</option>`).join('');
    };

    const renderBuildingOptions = (term = '') => {
      const needle = String(term || '').trim().toLowerCase();
      const filtered = buildingCatalog.filter(b => {
        const hay = `${b.display_name || ''} ${b.code || ''}`.toLowerCase();
        return !needle || hay.includes(needle);
      });
      buildingSelect.innerHTML = filtered.map(b => `<option value="${b.id}">${escapeHtml(b.display_name || 'Building')} [${escapeHtml(b.code || '')}]</option>`).join('');
    };

    renderUnitOptions();
    renderBuildingOptions();
    document.getElementById('unitCatalogSearch').addEventListener('input', (e) => renderUnitOptions(e.target.value));
    document.getElementById('buildingCatalogSearch').addEventListener('input', (e) => renderBuildingOptions(e.target.value));

    document.getElementById('saveNation').onclick = async () => {
      // Collect dynamic base/advanced resources
      const collectResourceInputs = (type) => {
        const out = {};
        const inputs = document.querySelectorAll(`[id^="${type}-res-"]`);
        inputs.forEach(input => {
          const name = input.id.replace(`${type}-res-`, '');
          out[name] = Number(input.value);
        });
        return out;
      };
      const baseResources = collectResourceInputs('base');
      const advancedResources = collectResourceInputs('advanced');
      const incomePayload = {};
      incomeRows.forEach(row => {
        if (!row?.name) return;
        const type = row.type === 'advanced' ? 'advanced' : 'base';
        incomePayload[`${type}:${row.name}`] = Number(row.amount || 0);
      });
      const payload = {
        name: document.getElementById('nName').value,
        leader_name: document.getElementById('nLeader').value,
        alliance_name: document.getElementById('nAlliance').value,
        about_text: document.getElementById('nAbout').value,
        resources: {
          base: baseResources,
          advanced: advancedResources,
        },
        income: incomePayload,
        terrain_square_miles: {
          grassland: Number(document.getElementById('nSqGrassland').value),
          mountain: Number(document.getElementById('nSqMountain').value),
          freshwater: Number(document.getElementById('nSqFreshwater').value),
          hills: Number(document.getElementById('nSqHills').value),
          desert: Number(document.getElementById('nSqDesert').value),
          seafront: Number(document.getElementById('nSqSeafront').value),
        },
      };
      const save = await api('/api/admin/nations/' + id, { method: 'PUT', body: JSON.stringify(payload) });
      document.getElementById('saveNationMsg').textContent = save.ok ? 'Saved' : 'Failed';
      barkIfEnabled();
    };

    document.getElementById('addUnitBtn').onclick = async () => {
      const unitCatalogId = Number(document.getElementById('unitCatId').value);
      const qty = Number(document.getElementById('unitQty').value);
      const status = document.getElementById('unitStatus').value;
      if (!unitCatalogId) { document.getElementById('addUnitMsg').textContent = 'Select a unit from the list.'; return; }
      const r = await api('/api/admin/nations/' + id + '/units', { method: 'POST', body: JSON.stringify({ unit_catalog_id: unitCatalogId, qty, status }) });
      document.getElementById('addUnitMsg').textContent = r.ok ? 'Added!' : 'Failed';
      if (r.ok) {
        openEditor(id);
      }
      barkIfEnabled();
    };

    document.getElementById('addBuildingBtn').onclick = async () => {
      const buildingCatalogId = Number(document.getElementById('buildingCatId').value);
      const level = Number(document.getElementById('buildingLevel').value || 1);
      const status = document.getElementById('buildingStatus').value;
      const qty = Number(document.getElementById('buildingQty').value || 1);
      if (!buildingCatalogId) { document.getElementById('addBuildingMsg').textContent = 'Select a building from the list.'; return; }
      const r = await api('/api/admin/nations/' + id + '/buildings', {
        method: 'POST',
        body: JSON.stringify({ building_catalog_id: buildingCatalogId, level, status, qty }),
      });
      document.getElementById('addBuildingMsg').textContent = r.ok ? 'Added!' : 'Failed';
      if (r.ok) {
        openEditor(id);
      }
      barkIfEnabled();
    };

    document.querySelectorAll('.removeUnitBtn').forEach(btn => {
      btn.onclick = async () => {
        const rowId = Number(btn.dataset.unitId);
        const qtyInput = document.getElementById('removeUnitQty-' + rowId);
        const qty = Math.max(1, Number(qtyInput?.value || 1));
        const r = await api('/api/admin/nations/' + id + '/units/' + rowId, {
          method: 'DELETE',
          body: JSON.stringify({ qty }),
        });
        if (r.ok) {
          openEditor(id);
        }
      };
    });

    document.querySelectorAll('.removeBuildingBtn').forEach(btn => {
      btn.onclick = async () => {
        const rowId = Number(btn.dataset.buildingId);
        const r = await api('/api/admin/nations/' + id + '/buildings/' + rowId, {
          method: 'DELETE',
        });
        if (r.ok) {
          openEditor(id);
        }
      };
    });
  };

  document.getElementById('adminNationSelect')?.addEventListener('change', (e) => {
    const id = Number(e.target.value || 0);
    if (!id) {
      document.getElementById('adminNationEditor').innerHTML = '<div class="muted">Select a nation from the dropdown to edit.</div>';
      return;
    }
    openEditor(id);
  });

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
    // --- Document Visibility Controls (Admin Only) ---
    async function loadDocVisibility(code) {
      const controls = document.getElementById('docVisibilityControls');
      const saveBtn = document.getElementById('saveDocVisibilityBtn');
      const msg = document.getElementById('saveDocVisibilityMsg');
      if (!controls || !saveBtn) return;
      controls.innerHTML = '';
      saveBtn.disabled = true;
      msg.textContent = '';
      if (!code) return;
      controls.innerHTML = 'Loading...';
      const res = await api(`/api/admin/game-documents/${encodeURIComponent(code)}/visibility`);
      if (!res || !res.ok) {
        controls.innerHTML = '<span class="muted">Failed to load visibility settings.</span>';
        return;
      }
      const data = await res.json();
      const playersRes = await api('/api/players');
      const players = playersRes && playersRes.ok ? (await playersRes.json()) : [];
      const selectedPlayerIds = Array.isArray(data.player_ids) ? data.player_ids.map(v => Number(v)) : [];
      const isAllSelected = data.visibility_type === 'all';
      const isAdminSelected = data.visibility_type === 'admin' || (data.visibility_type === 'role' && String(data.role_name || '').toLowerCase() === 'admin');
      const isCustomSelected = data.visibility_type === 'custom';

      controls.innerHTML = `
        <div class="doc-vis-panel">
          <label class="doc-vis-label">Visibility Selection</label>
          <div id="docVisDesktopWrap" style="display:none;">
            <select id="docVisMulti" class="doc-vis-select" multiple size="10">
              <option value="__all" ${isAllSelected ? 'selected' : ''}>All Players</option>
              <option value="__admin" ${isAdminSelected ? 'selected' : ''}>Admin Players</option>
              <option value="" disabled>--------------------</option>
              ${players.map(p => `<option value="${p.id}" ${isCustomSelected && selectedPlayerIds.includes(Number(p.id)) ? 'selected' : ''}>${escapeHtml(p.name || ('User #' + p.id))}</option>`).join('')}
            </select>
            <div class="doc-vis-help">Pick one mode: All Players, Admin Players, or one/more specific users.</div>
          </div>
          <div id="docVisMobileWrap" style="display:none;">
            <div class="doc-vis-help" style="margin-top:0;margin-bottom:8px;">Pick one mode: All Players, Admin Players, or one/more specific users.</div>
            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;"><input type="checkbox" class="docVisMobileChoice" value="__all" ${isAllSelected ? 'checked' : ''}> All Players</label>
            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;"><input type="checkbox" class="docVisMobileChoice" value="__admin" ${isAdminSelected ? 'checked' : ''}> Admin Players</label>
            <div class="doc-vis-help" style="margin-top:6px;">Specific users</div>
            <div class="list" style="max-height:220px;">
              ${players.map(p => `<label style="display:flex;align-items:center;gap:8px;padding:6px 0;"><input type="checkbox" class="docVisMobileChoice" value="${p.id}" ${isCustomSelected && selectedPlayerIds.includes(Number(p.id)) ? 'checked' : ''}> ${escapeHtml(p.name || ('User #' + p.id))}</label>`).join('') || '<span class="muted">No players available.</span>'}
            </div>
          </div>
        </div>
      `;

      const multi = document.getElementById('docVisMulti');
      const desktopWrap = document.getElementById('docVisDesktopWrap');
      const mobileWrap = document.getElementById('docVisMobileWrap');
      const mobileChoices = () => Array.from(document.querySelectorAll('.docVisMobileChoice'));
      const usingMobilePicker = window.matchMedia('(max-width: 900px)').matches || window.matchMedia('(pointer: coarse)').matches;

      if (desktopWrap) desktopWrap.style.display = usingMobilePicker ? 'none' : 'block';
      if (mobileWrap) mobileWrap.style.display = usingMobilePicker ? 'block' : 'none';

      const getSelectedValues = () => {
        if (usingMobilePicker) {
          return mobileChoices().filter(el => el.checked).map(el => String(el.value || ''));
        }
        return multi ? Array.from(multi.selectedOptions).map(o => o.value) : [];
      };

      const enforceExclusiveMode = () => {
        const selected = getSelectedValues();
        const hasAll = selected.includes('__all');
        const hasAdmin = selected.includes('__admin');
        const hasUsers = selected.some(v => v !== '__all' && v !== '__admin' && v !== '');

        if (usingMobilePicker) {
          const boxes = mobileChoices();
          if (hasAll) {
            boxes.forEach(box => {
              if (box.value !== '__all') box.checked = false;
            });
            return;
          }
          if (hasAdmin) {
            boxes.forEach(box => {
              if (box.value !== '__admin') box.checked = false;
            });
            return;
          }
          if (hasUsers) {
            boxes.forEach(box => {
              if (box.value === '__all' || box.value === '__admin') box.checked = false;
            });
            return;
          }
          const adminBox = boxes.find(box => box.value === '__admin');
          if (adminBox) adminBox.checked = true;
          return;
        }

        if (!multi) return;
        if (hasAll) {
          Array.from(multi.options).forEach(opt => {
            if (opt.value !== '__all') opt.selected = false;
          });
          return;
        }

        if (hasAdmin) {
          Array.from(multi.options).forEach(opt => {
            if (opt.value !== '__admin') opt.selected = false;
          });
          return;
        }

        if (hasUsers) {
          Array.from(multi.options).forEach(opt => {
            if (opt.value === '__all' || opt.value === '__admin') opt.selected = false;
          });
          return;
        }

        // Keep a safe default when nothing is selected.
        const adminOption = Array.from(multi.options).find(opt => opt.value === '__admin');
        if (adminOption) adminOption.selected = true;
      };

      if (multi) multi.addEventListener('change', enforceExclusiveMode);
      mobileChoices().forEach(box => box.addEventListener('change', enforceExclusiveMode));
      enforceExclusiveMode();

      saveBtn.disabled = false;
      saveBtn.onclick = async () => {
        saveBtn.disabled = true;
        msg.textContent = '';

        const selectedValues = getSelectedValues();
        const allSelected = selectedValues.includes('__all');
        const adminSelected = selectedValues.includes('__admin');
        const playerIds = selectedValues
          .filter(v => v !== '__all' && v !== '__admin' && v !== '')
          .map(v => Number(v))
          .filter(v => Number.isInteger(v) && v > 0);

        let type = 'admin';
        if (allSelected) {
          type = 'all';
        } else if (adminSelected) {
          type = 'admin';
        } else if (playerIds.length > 0) {
          type = 'custom';
        }

        const payload = {
          visibility_type: type,
          role_name: null,
          player_ids: type === 'custom' ? playerIds : null,
        };
        const res = await api(`/api/admin/game-documents/${encodeURIComponent(code)}/visibility`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        });
        if (res && res.ok) {
          msg.textContent = 'Visibility updated.';
          setTimeout(() => { if (msg.textContent === 'Visibility updated.') msg.textContent = ''; }, 3000);
        } else {
          msg.textContent = await readErrorMessage(res, 'Failed to update visibility.');
        }
        saveBtn.disabled = false;
      };
    }
  const isAdmin = user.role === 'admin';
  const listRes = await api(isAdmin ? '/api/admin/game-documents' : '/api/game-documents');
  let docs = listRes && listRes.ok ? await listRes.json() : [];

  view.innerHTML = `
    <div class="card">
      <h2>Information</h2>
      <div class="doc-toolbar">
        <div>
          <label style="font-size:12px;">Document</label>
          <select id="gameDocSelect">
            <option value="">- Select a document -</option>
            ${docs.map(d => `<option value="${escapeHtml(d.code)}">${escapeHtml(d.title)}</option>`).join('')}
          </select>
        </div>
        ${isAdmin ? `
          <div>
            <label style="font-size:12px;">Document Name</label>
            <input id="gameDocTitle" type="text" placeholder="Document display name" disabled>
          </div>
          <div class="doc-toolbar-actions">
            <button class="primary" id="gameDocEditBtn" style="background:#314f72;" disabled>Edit</button>
            <button class="primary" id="gameDocSaveBtn" style="display:none;" disabled>Save</button>
            <button id="gameDocCancelBtn" style="display:none;">Cancel</button>
            <button id="gameDocDownloadAllBtn" style="background:#2a5934;">Download All</button>
          </div>
        ` : `
          <div></div>
          <div class="doc-toolbar-actions"></div>
        `}
      </div>
      <div class="row" style="margin-top:8px;"><span class="muted" id="gameDocMsg"></span></div>
      <p id="gameDocHint" class="muted" style="margin:6px 0 0;">Select a document above to view its contents.</p>
      <div id="gameDocLoading" style="display:none;margin-top:8px;" class="muted">Loading...</div>
      <div id="gameDocRead" class="doc-read" style="display:none;"></div>
      <textarea id="gameDocText" rows="24" readonly class="doc-editor" style="display:none;"></textarea>
      ${isAdmin ? `
        <div class="doc-visibility">
          <h3>Document Visibility</h3>
          <div id="docVisibilityControls"></div>
          <div class="row" style="margin-top:8px;">
            <button class="primary" id="saveDocVisibilityBtn" disabled>Save Visibility</button>
            <span class="muted" id="saveDocVisibilityMsg"></span>
          </div>
        </div>
        <div class="doc-create">
          <h3>Create New Document</h3>
          <div class="doc-create-grid">
            <div>
              <label style="font-size:12px;">Title</label>
              <input id="newGameDocTitle" type="text" placeholder="Example: Naval Combat Rules">
            </div>
            <div>
              <label style="font-size:12px;">Code (optional)</label>
              <input id="newGameDocCode" type="text" placeholder="Example: naval_combat_rules">
            </div>
          </div>
          <label style="font-size:12px; margin-top:8px; display:block;">Initial Content</label>
          <textarea id="newGameDocContent" rows="6" placeholder="Write the initial rules text..."></textarea>
          <div class="row" style="margin-top:8px;">
            <button class="primary" id="createGameDocBtn" style="background:#2f6a41;">Create Document</button>
            <span class="muted" id="createGameDocMsg"></span>
          </div>
        </div>
      ` : ''}
    </div>
  `;

  let currentCode = '';
  let originalContent = '';
  let originalTitle = '';

  const select = document.getElementById('gameDocSelect');
  const readView = document.getElementById('gameDocRead');
  const text = document.getElementById('gameDocText');
  const titleInput = isAdmin ? document.getElementById('gameDocTitle') : null;
  const editBtn = isAdmin ? document.getElementById('gameDocEditBtn') : null;
  const saveBtn = isAdmin ? document.getElementById('gameDocSaveBtn') : null;
  const cancelBtn = isAdmin ? document.getElementById('gameDocCancelBtn') : null;
  const downloadAllBtn = isAdmin ? document.getElementById('gameDocDownloadAllBtn') : null;
  const createBtn = isAdmin ? document.getElementById('createGameDocBtn') : null;
  const createMsg = isAdmin ? document.getElementById('createGameDocMsg') : null;
  const msg = document.getElementById('gameDocMsg');
  const hint = document.getElementById('gameDocHint');
  const loading = document.getElementById('gameDocLoading');

  if (isAdmin) {
    const controls = document.getElementById('docVisibilityControls');
    if (controls) {
      controls.innerHTML = '<span class="muted">Select a document to configure visibility.</span>';
    }
  }

  const renderDocReadView = (content) => {
    // Render as plain text so spacing and line breaks match saved content exactly.
    readView.textContent = String(content || '');
  };

  const refreshSelectOptions = (selectedCode = '') => {
    const sorted = [...docs].sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
    docs = sorted;
    select.innerHTML = `
      <option value="">- Select a document -</option>
      ${sorted.map(d => `<option value="${escapeHtml(d.code)}">${escapeHtml(d.title)}</option>`).join('')}
    `;
    if (selectedCode) select.value = selectedCode;
  };

  if (isAdmin && downloadAllBtn) {
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
  }

  const showReadOnly = () => {
    text.readOnly = true;
    text.style.display = 'none';
    readView.style.display = 'block';
    if (titleInput) titleInput.disabled = true;
    if (editBtn) editBtn.style.display = '';
    if (saveBtn) saveBtn.style.display = 'none';
    if (cancelBtn) cancelBtn.style.display = 'none';
  };

  const showEditMode = () => {
    text.readOnly = false;
    text.style.display = 'block';
    readView.style.display = 'none';
    if (titleInput) titleInput.disabled = false;
    if (editBtn) editBtn.style.display = 'none';
    if (saveBtn) {
      saveBtn.style.display = '';
      saveBtn.disabled = false;
    }
    if (cancelBtn) cancelBtn.style.display = '';
    text.focus();
  };

  select.onchange = async () => {
    currentCode = select.value;

    // Load document visibility controls if admin.
    if (isAdmin && currentCode) {
      loadDocVisibility(currentCode);
    } else if (isAdmin) {
      const controls = document.getElementById('docVisibilityControls');
      if (controls) controls.innerHTML = '<span class="muted">Select a document to configure visibility.</span>';
      const saveBtn = document.getElementById('saveDocVisibilityBtn');
      if (saveBtn) saveBtn.disabled = true;
    }

    text.value = '';
    text.style.display = 'none';
    readView.style.display = 'none';
    readView.textContent = '';
    hint.style.display = 'none';
    msg.textContent = '';
    if (titleInput) {
      titleInput.value = '';
      titleInput.disabled = true;
    }
    if (editBtn) editBtn.disabled = true;

    if (!currentCode) {
      hint.style.display = '';
      loading.style.display = 'none';
      return;
    }

    loading.style.display = 'block';
    const res = await api((isAdmin ? '/api/admin/game-documents/' : '/api/game-documents/') + encodeURIComponent(currentCode));
    loading.style.display = 'none';

    if (!res || !res.ok) {
      msg.textContent = 'Failed to load document.';
      return;
    }

    const doc = await res.json();
    originalTitle = String(doc.title || '');
    originalContent = doc.content_text || '';
    if (titleInput) titleInput.value = originalTitle;
    renderDocReadView(originalContent);
    text.value = originalContent;
    showReadOnly();
    if (editBtn) editBtn.disabled = false;
  };

  if (isAdmin && editBtn) {
    editBtn.onclick = () => {
      if (!currentCode) return;
      showEditMode();
    };
  }

  if (isAdmin && cancelBtn) {
    cancelBtn.onclick = () => {
      text.value = originalContent;
      if (titleInput) titleInput.value = originalTitle;
      showReadOnly();
    };
  }

  if (isAdmin && saveBtn) {
    saveBtn.onclick = async () => {
      if (!currentCode) return;
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';
      const normalizedTitle = titleInput ? String(titleInput.value || '').trim() : '';
      const payload = {
        content_text: text.value,
        title: normalizedTitle || originalTitle,
      };
      const res = await api('/api/admin/game-documents/' + encodeURIComponent(currentCode), {
        method: 'PUT',
        body: JSON.stringify(payload),
      });
      saveBtn.textContent = 'Save';
      if (res && res.ok) {
        originalContent = text.value;
        renderDocReadView(originalContent);
        if (titleInput) {
          originalTitle = normalizedTitle || originalTitle;
          titleInput.value = originalTitle;
          const selectedOption = select.options[select.selectedIndex];
          if (selectedOption && originalTitle) selectedOption.textContent = originalTitle;
        }
        msg.textContent = 'Saved.';
        setTimeout(() => { if (msg.textContent === 'Saved.') msg.textContent = ''; }, 3000);
        showReadOnly();
      } else {
        saveBtn.disabled = false;
        msg.textContent = 'Save failed.';
      }
    };
  }

  if (isAdmin && createBtn) {
    createBtn.onclick = async () => {
      if (!createMsg) return;
      createMsg.textContent = '';
      const titleEl = document.getElementById('newGameDocTitle');
      const codeEl = document.getElementById('newGameDocCode');
      const contentEl = document.getElementById('newGameDocContent');
      const title = String(titleEl?.value || '').trim();
      const code = String(codeEl?.value || '').trim();
      const contentText = String(contentEl?.value || '');

      if (!title) {
        createMsg.textContent = 'Title is required.';
        return;
      }

      createBtn.disabled = true;
      createBtn.textContent = 'Creating...';
      const res = await api('/api/admin/game-documents', {
        method: 'POST',
        body: JSON.stringify({ title, code, content_text: contentText }),
      });
      createBtn.disabled = false;
      createBtn.textContent = 'Create Document';

      if (!res || !res.ok) {
        createMsg.textContent = await readErrorMessage(res, 'Failed to create document.');
        return;
      }

      const created = await res.json();
      const createdCode = String(created.code || '');
      const createdTitle = String(created.title || title);
      docs.push({ code: createdCode, title: createdTitle, updated_at: null });
      refreshSelectOptions(createdCode);
      if (titleEl) titleEl.value = '';
      if (codeEl) codeEl.value = '';
      if (contentEl) contentEl.value = '';
      createMsg.textContent = 'Document created.';
      await select.onchange();
    };
  }
}

async function loadTimeTracker() {
  const [res, historyRes] = await Promise.all([
    api('/api/admin/time-tracker'),
    api('/api/admin/time-tracker/pause-history'),
  ]);
  const d = await res.json();
  const pauseHistory = historyRes && historyRes.ok ? await historyRes.json() : [];

  const historyRows = Array.isArray(pauseHistory) && pauseHistory.length
    ? pauseHistory.map((row) => {
      const pausedBy = row.paused_by_name || (row.paused_by_user_id ? `User #${row.paused_by_user_id}` : '-');
      const resumedBy = row.resumed_by_name || (row.resumed_by_user_id ? `User #${row.resumed_by_user_id}` : '-');
      const status = row.resumed_at ? 'Resumed' : 'Paused';
      return `<div class="res-kv"><span>${status}: ${row.paused_at || '-'}</span><span>${row.resumed_at || 'Active Pause'}</span></div>
        <div class="map-small-label" style="margin:-4px 0 6px 0;">By: ${escapeHtml(String(pausedBy))}${row.resumed_at ? ` | Resumed By: ${escapeHtml(String(resumedBy))}` : ''}${row.pause_note ? ` | Note: ${escapeHtml(String(row.pause_note))}` : ''}</div>`;
    }).join('')
    : '<div class="muted">No pause history yet.</div>';

  view.innerHTML = `
    <div class="card">
      <h2>Time Tracker</h2>
      <div class="list">
        <div><strong>Started:</strong> ${d.started_at}</div>
        <div><strong>Current Game Year:</strong> ${d.current_game_year}</div>
        <div><strong>Elapsed Hours This Year:</strong> ${d.elapsed_hours_in_year} / ${Number(d.hours_per_year || 0).toFixed(2)} hours</div>
        <div><strong>Status:</strong> ${d.is_paused ? 'Paused' : 'In Progress'}</div>
        <div><strong>Paused At:</strong> ${d.paused_at || '-'}</div>
        <div><strong>Seconds Per In-Game Year:</strong> ${d.seconds_per_year}</div>
        <div><strong>Processed Years:</strong> ${d.processed_years}</div>
        <div><strong>Processed This Load:</strong> ${d.processed_now}</div>
        <div class="muted" style="margin-top:8px;">Auto increment uses real time. Manual mode lets admins advance years explicitly.</div>
        <details style="margin-top:8px;">
          <summary>Pause History</summary>
          <div style="margin-top:6px;">${historyRows}</div>
        </details>
      </div>
      <div class="row" style="margin-top:8px;">
        <label style="min-width:220px;">Pause Note (optional)</label>
        <input id="ttPauseNote" type="text" placeholder="Example: Weekend freeze during event setup">
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
        <button class="primary" id="ttPause" style="background:#8a5a1a;" ${d.is_paused ? 'disabled' : ''}>Pause Tracker</button>
        <button class="primary" id="ttResume" style="background:#2f6a41;" ${d.is_paused ? '' : 'disabled'}>Resume Tracker</button>
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

  document.getElementById('ttPause').onclick = async () => {
    const pauseNote = document.getElementById('ttPauseNote').value || '';
    const r = await api('/api/admin/time-tracker/pause', { method: 'POST', body: JSON.stringify({ pause_note: pauseNote }) });
    document.getElementById('ttMsg').textContent = r.ok ? 'Tracker paused' : await readErrorMessage(r, 'Pause failed');
    if (r.ok) loadTimeTracker();
    barkIfEnabled();
  };

  document.getElementById('ttResume').onclick = async () => {
    const r = await api('/api/admin/time-tracker/resume', { method: 'POST' });
    document.getElementById('ttMsg').textContent = r.ok ? 'Tracker resumed' : await readErrorMessage(r, 'Resume failed');
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

async function loadDeveloperOptionsPage() {
  if (user.role !== 'admin') {
    view.innerHTML = '<div class="card"><h2>Developer Options</h2><p class="danger">Admin access required.</p></div>';
    return;
  }

  view.innerHTML = `
    <div class="card">
      <h2>Developer Options</h2>
      <p class="muted">App-wide diagnostics and error insights. Logs are shown with a short summary first; expand any row for full details.</p>
      <div class="row" style="align-items:flex-end;gap:8px;flex-wrap:wrap;">
        <label>Level
          <select id="devLogLevelFilter">
            <option value="all">All</option>
            <option value="error">Error</option>
            <option value="warning">Warning</option>
            <option value="info">Information</option>
          </select>
        </label>
        <label>Search
          <input id="devLogQuery" type="text" placeholder="message, section, source" style="min-width:220px;">
        </label>
        <label>Limit
          <input id="devLogLimit" type="number" min="10" max="1000" value="200" style="width:100px;">
        </label>
        <button class="primary" id="devLogRefreshBtn">Refresh</button>
        <button class="primary" id="devLogClearBtn">Clear Logs</button>
      </div>
      <div class="row" style="margin-top:10px;gap:8px;flex-wrap:wrap;">
        <button class="primary" id="devLogTestInfo">Test Info</button>
        <button class="primary" id="devLogTestWarn">Test Warning</button>
        <button class="primary" id="devLogTestError">Test Error</button>
        <a class="primary" href="/docs/developer" target="_blank" rel="noopener noreferrer" style="text-decoration:none;display:inline-flex;align-items:center;">Open Developer Docs</a>
      </div>
      <div class="muted" id="devLogMeta" style="margin-top:8px;"></div>
      <div id="devLogList" style="margin-top:10px;display:grid;gap:8px;"></div>
    </div>
    <div class="card" style="margin-top:12px;">
      <h3>Logging Settings</h3>
      <div class="row" style="gap:16px;flex-wrap:wrap;">
        <label><input type="checkbox" id="devCaptureError"> Capture Error</label>
        <label><input type="checkbox" id="devCaptureWarning"> Capture Warning</label>
        <label><input type="checkbox" id="devCaptureInfo"> Capture Information</label>
        <label><input type="checkbox" id="devAutoCaptureClient"> Auto-capture client errors</label>
        <label>Max entries
          <input type="number" id="devMaxEntries" min="100" max="5000" style="width:110px;">
        </label>
        <button class="primary" id="devSaveSettingsBtn">Save Settings</button>
      </div>
      <div class="muted" id="devSettingsMsg" style="margin-top:8px;"></div>
    </div>
    <div class="card" style="margin-top:12px;border:1px solid #8a1a1a;">
      <h3 style="color:#8a1a1a;">Danger Zone</h3>
      <p class="danger" style="margin-top:0;">Destructive operations below can permanently remove data. Read prompts carefully before continuing.</p>
      <div class="row" style="gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <button class="primary" id="devZombieCleanupPreviewBtn" style="background:#7b5a1a;">Preview Zombie Cleanup</button>
        <button class="primary" id="devZombieCleanupBtn" style="background:#8a1a1a;">Purge Zombie Data</button>
        <span class="muted" id="devZombieCleanupMsg"></span>
      </div>
      <div id="devZombieCleanupDetails" style="margin-top:10px;"></div>
    </div>
  `;

  const levelFilter = document.getElementById('devLogLevelFilter');
  const queryInput = document.getElementById('devLogQuery');
  const limitInput = document.getElementById('devLogLimit');
  const listEl = document.getElementById('devLogList');
  const metaEl = document.getElementById('devLogMeta');
  const settingsMsg = document.getElementById('devSettingsMsg');
  const zombieCleanupMsg = document.getElementById('devZombieCleanupMsg');
  const zombieCleanupDetailsEl = document.getElementById('devZombieCleanupDetails');
  const clampLocal = (value, min, max) => Math.max(min, Math.min(max, value));

  const requireTypedDangerConfirm = (title, warning, phrase) => {
    const proceed = window.confirm(`${warning}\n\nYou will need to type: ${phrase}`);
    if (!proceed) return false;
    const typed = window.prompt(`${title}\nType exactly: ${phrase}`, '');
    return String(typed || '').trim() === phrase;
  };

  const renderZombieCleanupDetails = (payload) => {
    if (!zombieCleanupDetailsEl) return;
    const detailMap = (payload && typeof payload.preview_details === 'object' && payload.preview_details)
      ? payload.preview_details
      : {};
    const rows = Object.values(detailMap);
    if (!rows.length) {
      zombieCleanupDetailsEl.innerHTML = '';
      return;
    }

    zombieCleanupDetailsEl.innerHTML = rows.map((row) => {
      const label = escapeHtml(String(row?.label || 'Category'));
      const reason = escapeHtml(String(row?.reason || ''));
      const count = toFiniteNumber(row?.count, 0);
      const examples = Array.isArray(row?.examples) ? row.examples : [];
      const examplesJson = escapeHtml(JSON.stringify(examples, null, 2));
      return `
        <details style="margin-bottom:8px;">
          <summary style="cursor:pointer;"><strong>${label}</strong> - ${count} item(s)</summary>
          <div class="muted" style="margin-top:6px;">${reason}</div>
          <div style="margin-top:6px;">Sample records (up to 25):</div>
          <pre style="margin-top:6px;white-space:pre-wrap;">${examplesJson}</pre>
        </details>
      `;
    }).join('');
  };

  const formatWhen = (iso) => {
    const d = new Date(iso || '');
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleString();
  };

  const detailsForLog = (log) => {
    const details = {
      id: log.id || '',
      level: log.level || '',
      timestamp: log.timestamp || '',
      source: log.source || '',
      section: log.section || '',
      url: log.url || '',
      actor_user_id: log.actor_user_id || null,
      context: log.context || {},
    };
    return escapeHtml(JSON.stringify(details, null, 2));
  };

  const renderLogs = (logs, total) => {
    metaEl.textContent = `Showing ${logs.length} log(s). Total matching: ${total}.`;
    if (!logs.length) {
      listEl.innerHTML = '<div class="muted">No logs for the selected filters.</div>';
      return;
    }
    listEl.innerHTML = logs.map((log) => {
      const level = String(log.level || 'info').toUpperCase();
      const levelColor = log.level === 'error' ? '#b00020' : (log.level === 'warning' ? '#9b5a1e' : '#1f5ca8');
      return `
        <details>
          <summary style="cursor:pointer;line-height:1.4;">
            <strong style="color:${levelColor};">${level}</strong>
            <span style="margin-left:8px;">${escapeHtml(log.summary || 'No summary')}</span>
            <span class="muted" style="margin-left:8px;">${escapeHtml(formatWhen(log.timestamp))}</span>
          </summary>
          <pre style="margin-top:8px;white-space:pre-wrap;">${detailsForLog(log)}</pre>
        </details>
      `;
    }).join('');
  };

  const reloadLogs = async () => {
    listEl.innerHTML = '<div class="muted">Loading logs...</div>';
    const params = new URLSearchParams();
    params.set('level', levelFilter.value || 'all');
    params.set('limit', String(clampLocal(toFiniteNumber(limitInput.value, 200), 10, 1000)));
    const q = String(queryInput.value || '').trim();
    if (q) params.set('query', q);
    const res = await api(`/api/admin/developer/logs?${params.toString()}`, { silentLog: true });
    if (!res || !res.ok) {
      listEl.innerHTML = `<div class="danger">${escapeHtml(await readErrorMessage(res, 'Failed to load developer logs.'))}</div>`;
      return;
    }
    const payload = await parseJsonResponse(res, { logs: [], total: 0 });
    const logs = Array.isArray(payload?.logs) ? payload.logs : [];
    renderLogs(logs, toFiniteNumber(payload?.total, logs.length));
  };

  const reloadSettings = async () => {
    settingsMsg.textContent = '';
    const res = await api('/api/admin/developer/log-settings', { silentLog: true });
    if (!res || !res.ok) {
      settingsMsg.textContent = await readErrorMessage(res, 'Failed to load settings.');
      return;
    }
    const payload = await parseJsonResponse(res, {});
    developerLogSettings = { ...developerLogSettingsDefaults, ...(payload || {}) };
    document.getElementById('devCaptureError').checked = !!developerLogSettings.capture_error;
    document.getElementById('devCaptureWarning').checked = !!developerLogSettings.capture_warning;
    document.getElementById('devCaptureInfo').checked = !!developerLogSettings.capture_info;
    document.getElementById('devAutoCaptureClient').checked = !!developerLogSettings.auto_capture_client;
    document.getElementById('devMaxEntries').value = String(developerLogSettings.max_entries || 2000);
  };

  document.getElementById('devLogRefreshBtn').onclick = reloadLogs;
  document.getElementById('devLogClearBtn').onclick = async () => {
    const confirmed = requireTypedDangerConfirm(
      'Clear Developer Logs',
      'Warning: this permanently deletes all current developer logs.',
      'CLEAR DEV LOGS'
    );
    if (!confirmed) {
      metaEl.textContent = 'Log clear cancelled: confirmation phrase did not match.';
      return;
    }
    const res = await api('/api/admin/developer/logs', { method: 'DELETE', silentLog: true });
    if (!res || !res.ok) {
      metaEl.textContent = await readErrorMessage(res, 'Failed to clear logs.');
      return;
    }
    metaEl.textContent = 'Logs cleared.';
    await reloadLogs();
  };

  document.getElementById('devSaveSettingsBtn').onclick = async () => {
    const payload = {
      capture_error: !!document.getElementById('devCaptureError').checked,
      capture_warning: !!document.getElementById('devCaptureWarning').checked,
      capture_info: !!document.getElementById('devCaptureInfo').checked,
      auto_capture_client: !!document.getElementById('devAutoCaptureClient').checked,
      max_entries: clampLocal(toFiniteNumber(document.getElementById('devMaxEntries').value, 2000), 100, 5000),
    };
    const res = await api('/api/admin/developer/log-settings', {
      method: 'PUT',
      silentLog: true,
      body: JSON.stringify(payload),
    });
    if (!res || !res.ok) {
      settingsMsg.textContent = await readErrorMessage(res, 'Failed to save settings.');
      return;
    }
    const saved = await parseJsonResponse(res, {});
    developerLogSettings = { ...developerLogSettingsDefaults, ...(saved?.settings || payload) };
    settingsMsg.textContent = 'Settings saved.';
  };

  document.getElementById('devLogTestInfo').onclick = async () => {
    await captureDeveloperLog('info', 'Manual test information log', { by: 'admin', section: activeSectionName }, { force: true, source: 'developer.page' });
    await reloadLogs();
  };
  document.getElementById('devLogTestWarn').onclick = async () => {
    await captureDeveloperLog('warning', 'Manual test warning log', { by: 'admin', section: activeSectionName }, { force: true, source: 'developer.page' });
    await reloadLogs();
  };
  document.getElementById('devLogTestError').onclick = async () => {
    await captureDeveloperLog('error', 'Manual test error log', { by: 'admin', section: activeSectionName }, { force: true, source: 'developer.page' });
    await reloadLogs();
  };

  document.getElementById('devZombieCleanupBtn').onclick = async () => {
    zombieCleanupMsg.textContent = '';
    if (zombieCleanupDetailsEl) zombieCleanupDetailsEl.innerHTML = '';
    const confirmed = requireTypedDangerConfirm(
      'Purge Zombie Data',
      'Warning: this will remove lingering data references for deleted accounts (map editor references, topbar overrides, and orphaned developer log actor links).',
      'PURGE ZOMBIE DATA'
    );
    if (!confirmed) {
      zombieCleanupMsg.textContent = 'Cleanup cancelled: confirmation phrase did not match.';
      return;
    }

    const res = await api('/api/admin/developer/cleanup-zombie-data', {
      method: 'POST',
      silentLog: true,
      body: JSON.stringify({ confirmation_text: 'PURGE ZOMBIE DATA' }),
    });
    if (!res || !res.ok) {
      zombieCleanupMsg.textContent = await readErrorMessage(res, 'Zombie-data cleanup failed.');
      return;
    }
    const payload = await parseJsonResponse(res, {});
    const removed = toFiniteNumber(payload?.total_removed, 0);
    zombieCleanupMsg.textContent = `Cleanup complete. Removed ${removed} lingering item(s). Expand details below for category breakdown and sample records.`;
    renderZombieCleanupDetails(payload);
    await reloadLogs();
  };

  document.getElementById('devZombieCleanupPreviewBtn').onclick = async () => {
    zombieCleanupMsg.textContent = '';
    if (zombieCleanupDetailsEl) zombieCleanupDetailsEl.innerHTML = '';
    const res = await api('/api/admin/developer/cleanup-zombie-data', {
      method: 'POST',
      silentLog: true,
      body: JSON.stringify({ dry_run: true }),
    });
    if (!res || !res.ok) {
      zombieCleanupMsg.textContent = await readErrorMessage(res, 'Zombie cleanup preview failed.');
      return;
    }

    const payload = await parseJsonResponse(res, {});
    const removed = toFiniteNumber(payload?.total_removed, 0);
    zombieCleanupMsg.textContent = `Preview complete: ${removed} item(s) would be removed. Expand details below for the full category and sample record view.`;
    renderZombieCleanupDetails(payload);
  };

  await reloadSettings();
  await reloadLogs();
}

async function init() {
  let settingsRes = null;
  try {
    settingsRes = await api('/api/me/settings');
  } catch {}

  if (settingsRes && settingsRes.ok) {
    settings = await settingsRes.json();
    setTheme(settings.theme);
    applyColorBlindMode(settings.color_blind_mode);
    setFontMode(settings.font_mode || 'normal');
  }

  await loadDeveloperLogSettingsClient();
  installGlobalDeveloperErrorHandlers();

  // Never block navigation rendering on topbar resource refresh failures.
  loadResources().catch(() => {});

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
if (user.role !== 'admin') {
  const devOption = helpSelect.querySelector('option[value="developer-options"]');
  if (devOption) devOption.remove();
}
helpSelect.addEventListener('change', async (e) => {
  if (e.target.value === 'about') {
    await loadAboutPage();
  }
  if (e.target.value === 'docs') {
    window.open(user.role === 'admin' ? '/docs/admin' : '/docs/player', '_blank');
  }
  if (e.target.value === 'developer-options') {
    await loadSection('Developer Options');
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
