<?php
session_start();
$errors = [];
$successMessage = '';
$name = '';
$email = '';
$phone = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) < 3) {
        $errors['name'] = 'Name must be at least 3 characters long.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($phone !== '') {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{8,15}$/', $cleanPhone)) {
            $errors['phone'] = 'Please enter a valid phone number with at least 8 digits.';
        }
    }

    if ($message === '') {
        $errors['message'] = 'Message is required.';
    } elseif (mb_strlen($message) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long.';
    }

    if (empty($errors)) {
        $successMessage = 'Thank you! Your message has been received. We will contact you shortly.';
        $name = $email = $phone = $message = '';
    }
}

function old(string $key): string
{
    global ${$key};
    return htmlspecialchars(${$key} ?? '', ENT_QUOTES, 'UTF-8');
}

function showError(string $key): string
{
    global $errors;
    return isset($errors[$key]) ? '<span class="field-error">' . htmlspecialchars($errors[$key], ENT_QUOTES, 'UTF-8') . '</span>' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact | WIN Lost Property</title>
  <meta name="description" content="Contact WIN Lost Property Management System for campus support and inquiries.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <style>
    :root {
      --purple: #5b2c91;
      --blue: #0056d6;
      --ink: #14203b;
      --text: #16253d;
      --muted: #5e6d84;
      --surface: #ffffff;
      --surface-soft: #f7f8fc;
      --border: rgba(20,32,59,0.12);
      --shadow: 0 28px 80px rgba(20,32,59,.08);
      --radius: 28px;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #f4f6fb; color: var(--text); }
    a { color: inherit; text-decoration: none; }
    body, button, input, textarea { font: 100% Inter, system-ui, sans-serif; }
    header { background: #fff; border-bottom: 1px solid rgba(20,32,59,.08); box-shadow: 0 10px 30px rgba(20,32,59,.04); }
    .page { max-width: 1180px; margin: 0 auto; padding: 1.5rem; display: grid; gap: 1.5rem; }
    .topbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 0; }
    .brand { display: inline-flex; align-items: center; gap: 0.85rem; font-weight: 700; color: var(--ink); }
    .brand-logo { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 16px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; font-weight: 700; }
    .brand-text { display: grid; gap: 0.1rem; }
    .brand-text strong { font-size: 1rem; }
    .brand-text span { color: var(--muted); font-size: 0.9rem; }
    .nav-links { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .nav-links a { padding: 0.85rem 1rem; border-radius: 999px; background: #fff; border: 1px solid var(--border); font-weight: 600; transition: background .18s ease, transform .18s ease; }
    .nav-links a:hover, .nav-links a.active { background: rgba(0,86,214,.08); border-color: rgba(0,86,214,.2); color: var(--blue); transform: translateY(-1px); }
    .hero-banner { background: linear-gradient(180deg, rgba(0,86,214,.08), rgba(255,255,255,0.95)); border-radius: var(--radius); padding: 2.5rem; box-shadow: var(--shadow); display: grid; gap: 1.5rem; }
    .hero-banner .hero-copy { display: grid; gap: 1rem; }
    .hero-banner .hero-copy h1 { margin: 0; font-size: clamp(2.25rem, 3.5vw, 4rem); line-height: 1.02; }
    .hero-banner .hero-copy p { margin: 0; max-width: 760px; color: var(--muted); line-height: 1.8; }
    .stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .stat-card { background: #fff; border-radius: 22px; padding: 1.25rem 1.5rem; border: 1px solid var(--border); box-shadow: 0 18px 40px rgba(20,32,59,.05); }
    .stat-card strong { display: block; font-size: 1.5rem; color: var(--ink); }
    .stat-card span { color: var(--muted); font-size: 0.95rem; }
    .content-grid { display: grid; gap: 2rem; }
    .contacts-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .contact-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 1.75rem; display: grid; gap: 1.25rem; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .contact-card:hover { transform: translateY(-3px); border-color: rgba(0,86,214,.18); box-shadow: 0 28px 70px rgba(0,86,214,.08); }
    .contact-card h2 { margin: 0; font-size: 1.35rem; }
    .contact-card p { margin: 0; color: var(--muted); line-height: 1.75; }
    .contact-info { display: grid; gap: 0.85rem; margin-top: 1rem; }
    .contact-detail { display: flex; align-items: flex-start; gap: 0.85rem; }
    .detail-icon { width: 2rem; min-width: 2rem; height: 2rem; display: grid; place-items: center; border-radius: 50%; background: rgba(0,86,214,.08); color: var(--blue); }
    .detail-text { display: grid; gap: 0.15rem; }
    .detail-text strong { display: block; color: var(--ink); font-weight: 700; }
    .detail-text span, .detail-text a { color: var(--muted); line-height: 1.6; }
    .detail-text a { color: var(--blue); }
    .form-panel { background: #fff; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 2.25rem; }
    .form-panel h2 { margin: 0 0 0.75rem; font-size: 1.8rem; }
    .form-panel p { margin: 0 0 1.5rem; color: var(--muted); line-height: 1.7; }
    .form-panel form { display: grid; gap: 1.25rem; }
    .form-grid { display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
    .form-grid .input-group:nth-child(4) { grid-column: 1 / -1; }
    .input-group { display: grid; gap: 0.55rem; }
    label { font-weight: 700; color: var(--ink); }
    input, textarea { width: 100%; border-radius: 18px; border: 1px solid rgba(20,32,59,.14); background: var(--surface-soft); padding: 0.95rem 1rem; color: var(--ink); font: inherit; transition: border-color .18s ease, box-shadow .18s ease; }
    textarea { min-height: 170px; resize: vertical; padding-top: 1rem; }
    input:focus, textarea:focus { outline: none; border-color: rgba(0,86,214,.75); box-shadow: 0 0 0 4px rgba(0,86,214,.12); }
    .field-error { color: #bf1d1d; font-size: 0.95rem; margin-top: 0.35rem; display: block; }
    .button { display: inline-flex; align-items: center; justify-content: center; padding: 1rem 1.5rem; border-radius: 999px; border: none; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; font-weight: 700; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; margin-top: 0.25rem; justify-self: center; }
    .button i { margin-right: 0.6rem; }
    .directions-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.7rem; border-radius: 12px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; font-weight: 700; font-size: 0.95rem; text-decoration: none; box-shadow: 0 8px 20px rgba(20,32,59,.08); }
    .directions-btn i { margin-right: 0.35rem; }
    .leaflet-directions-control { padding: 6px; }
    .open-link { color: var(--blue); font-weight: 700; font-size: 0.95rem; text-decoration: none; position: relative; display: inline-flex; align-items: center; }
    .open-link svg { width: 14px; height: 14px; margin-right: 0.5rem; display: inline-block; vertical-align: middle; color: var(--blue); }
    .open-link .tooltip-text { position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); background: #fff; color: var(--ink); padding: 0.35rem 0.6rem; border-radius: 8px; box-shadow: 0 8px 20px rgba(20,32,59,.08); font-size: 0.85rem; white-space: nowrap; opacity: 0; pointer-events: none; border: 1px solid var(--border); transition: opacity .12s ease; z-index: 9999; }
    .open-link:hover .tooltip-text, .open-link:focus .tooltip-text { opacity: 1; pointer-events: auto; }
    .button:hover { transform: translateY(-1px); box-shadow: 0 18px 36px rgba(20,32,59,.14); background: linear-gradient(135deg, #2b28b2, #0047c5); }
    .alert { border-radius: 18px; padding: 1rem 1.25rem; background: rgba(0,86,214,.08); border: 1px solid rgba(0,86,214,.18); color: #00317a; margin-bottom: 1.25rem; }
    .map-panel { background: linear-gradient(180deg, #fff, #f4f6fb); border-radius: var(--radius); border: 1px solid rgba(20,32,59,.08); box-shadow: var(--shadow); padding: 2rem; display: grid; gap: 1.5rem; }
    .map-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .map-header h2 { margin: 0; font-size: 1.45rem; }
    .map-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .leaflet-container { width: 100%; height: 320px; border-radius: 16px; box-shadow: 0 18px 40px rgba(20,32,59,.06); }
    .map-placeholder { display: none; }
    .map-placeholder::before { content: ''; }
    .map-placeholder .map-labels { position: relative; z-index: 1; display: grid; gap: 1rem; text-align: center; }
    .map-labels span { display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 999px; background: rgba(255,255,255,.92); padding: 0.65rem 1rem; font-size: 0.95rem; color: var(--ink); box-shadow: 0 10px 30px rgba(20,32,59,.08); }
    .muted-text { color: var(--muted); margin: 0; }
    .footer { background: #fff; border-top: 1px solid rgba(20,32,59,.08); padding: 2rem 1.5rem; border-radius: 0 0 var(--radius) var(--radius); }
    .footer-grid { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; }
    .footer-copy { color: var(--muted); }
    .social-links { display: flex; gap: 0.75rem; }
    .social-links a { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 14px; background: #f4f6fb; color: var(--ink); transition: transform .18s ease, background .18s ease; }
    .social-links a:hover { background: rgba(0,86,214,.12); transform: translateY(-2px); color: var(--blue); }
    @media (max-width: 980px) { .contacts-grid, .stats-grid, .map-grid { grid-template-columns: 1fr; } }
    @media (max-width: 720px) { .page { padding: 1rem; } .form-grid { grid-template-columns: 1fr; } .topbar { flex-direction: column; align-items: stretch; } .map-header { flex-direction: column; align-items: start; } }
  </style>
</head>
<body>
  <header>
    <div class="page topbar">
      <a href="index.html#home" class="brand">
        <div class="brand-logo">WIN</div>
        <div class="brand-text">
          <strong>WIN Lost Property</strong>
          <span>Campus recovery platform</span>
        </div>
      </a>
      <div class="nav-links">
        <a href="index.html#home">Home</a>
        <a href="login.php">Login</a>
        <a href="contact.php" class="active">Contact</a>
      </div>
    </div>
  </header>

  <main class="page">
    <section class="hero-banner">
      <div class="hero-copy">
        <p class="eyebrow">Contact support</p>
        <h1>Contact WIN Lost Property</h1>
        <p>From lost item reports to claim support, our campus recovery team is here to help. Submit a message, browse campus contacts, or reach out directly to the campus nearest you.</p>
      </div>
      <div class="stats-grid">
        <article class="stat-card">
          <strong>2</strong>
          <span>Campuses supported</span>
        </article>
        <article class="stat-card">
          <strong>24/7</strong>
          <span>Support coverage</span>
        </article>
        <article class="stat-card">
          <strong>100%</strong>
          <span>Student-first service</span>
        </article>
      </div>
    </section>

    <section class="content-grid">
      <div class="contacts-grid">
        <article class="contact-card">
          <h2>Sydney Campus</h2>
          <div class="contact-info">
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-map-pin"></i></span>
              <div class="detail-text">
                <strong>Location</strong>
                <span>Level 1-5, 302-306 Elizabeth Street, Surry Hills NSW 2010</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-phone"></i></span>
              <div class="detail-text">
                <strong>Phone</strong>
                <span>+61 2 8252 9999</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-envelope"></i></span>
              <div class="detail-text">
                <strong>Email</strong>
                <span><a href="mailto:info@wentworth.edu">info@wentworth.edu</a></span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-clock"></i></span>
              <div class="detail-text">
                <strong>Office hours</strong>
                <span>Mon - Fri, 8:30am - 5:30pm</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-user"></i></span>
              <div class="detail-text">
                <strong>Student support</strong>
                <span>Student Services</span>
              </div>
            </div>
          </div>
        </article>

        <article class="contact-card">
          <h2>Canberra Campus</h2>
          <div class="contact-info">
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-map-pin"></i></span>
              <div class="detail-text">
                <strong>Location</strong>
                <span>Level 3, 127 Murray Street, Canberra ACT 2601</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-phone"></i></span>
              <div class="detail-text">
                <strong>Phone</strong>
                <span>+61 2 6268 5555</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-envelope"></i></span>
              <div class="detail-text">
                <strong>Email</strong>
                <span><a href="mailto:canberra@wentworth.edu">canberra@wentworth.edu</a></span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-clock"></i></span>
              <div class="detail-text">
                <strong>Office hours</strong>
                <span>Mon - Fri, 8:30am - 5:30pm</span>
              </div>
            </div>
            <div class="contact-detail">
              <span class="detail-icon"><i class="fas fa-user"></i></span>
              <div class="detail-text">
                <strong>Student support</strong>
                <span>Student Services</span>
              </div>
            </div>
          </div>
        </article>
      </div>

      <section class="form-panel">
        <h2>Send us a message</h2>
        <p>Fill out the details below and our team will respond as soon as possible.</p>

        <?php if ($successMessage): ?>
          <div class="alert" role="status"><?= $successMessage ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="form-grid">
            <div class="input-group">
              <label for="name">Name</label>
              <input id="name" name="name" type="text" placeholder="Your name" value="<?= old('name') ?>" required>
              <?= showError('name') ?>
            </div>
            <div class="input-group">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" placeholder="you@example.com" value="<?= old('email') ?>" required>
              <?= showError('email') ?>
            </div>
            <div class="input-group">
              <label for="phone">Phone (optional)</label>
              <input id="phone" name="phone" type="tel" placeholder="+61 2 8252 9999" value="<?= old('phone') ?>">
              <?= showError('phone') ?>
            </div>
            <div class="input-group">
              <label for="message">Message</label>
              <textarea id="message" name="message" placeholder="How can we help you?" required><?= old('message') ?></textarea>
              <?= showError('message') ?>
            </div>
          </div>
          <button type="submit" class="button"><i class="fas fa-paper-plane"></i>Send message</button>
        </form>
      </section>
    </section>

    <section class="map-panel">
      <div class="map-header">
        <h2>Campus locations</h2>
        <p class="muted-text">Explore our Sydney and Canberra locations and see where support is available.</p>
      </div>
      <div class="map-grid">
        <div id="map-sydney" class="leaflet-container" aria-label="Sydney campus map"></div>
        <div id="map-canberra" class="leaflet-container" aria-label="Canberra campus map"></div>
      </div>
    </section>
  </main>
  <footer class="footer page">
    <div class="footer-grid">
      <p class="footer-copy">WIN Lost Property Management System • Helping students and staff recover lost items across campus.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
  </footer>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    (function(){
      // Coordinates (exact as provided)
      const sydneyCoords = [-33.8869, 151.2093];
      const canberraCoords = [-35.2796, 149.1300];

      // Initialize maps
      const mapOpts = { scrollWheelZoom: false, attributionControl: true };
      const mapSydney = L.map('map-sydney', mapOpts).setView(sydneyCoords, 15);
      const mapCanberra = L.map('map-canberra', mapOpts).setView(canberraCoords, 15);

      // Tile layer
      const tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
      L.tileLayer(tileUrl, { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(mapSydney);
      L.tileLayer(tileUrl, { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(mapCanberra);

      // Custom SVG marker icon (blue pin with white inner circle)
      const svgMarker = `
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="46" viewBox="0 0 36 46" aria-hidden="true">
          <path d="M18 0C9 0 2 6.8 2 15.2c0 11.2 14.6 28.4 15.2 29.1.4.5 1.1.5 1.5 0 .6-.7 15.2-17.9 15.2-29.1C34 6.8 27 0 18 0z" fill="#0056d6"/>
          <circle cx="18" cy="15" r="6" fill="#ffffff"/>
        </svg>`;
      const svgUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svgMarker);
      const campusIcon = L.icon({ iconUrl: svgUrl, iconSize: [36,46], iconAnchor: [18,46], popupAnchor: [0,-38] });

      const sydneyAddress = '302-306 Elizabeth Street Surry Hills NSW 2010 Australia';
      const canberraAddress = '15 Barry Drive Turner ACT 2612 Australia';

      // Localization: detect language and provide translations for button text
      const userLang = (navigator.language || navigator.userLanguage || 'en').split('-')[0];
      const TRANSLATIONS = {
        en: { get_directions: 'Get directions', open_maps: 'Open in Google Maps', directions: 'Directions', open_maps_tooltip: 'Opens Google Maps with this address pre-filled' },
        fr: { get_directions: 'Itinéraire', open_maps: 'Ouvrir dans Google Maps', directions: 'Itinéraire', open_maps_tooltip: 'Ouvre Google Maps avec cette adresse' },
        es: { get_directions: 'Cómo llegar', open_maps: 'Abrir en Google Maps', directions: 'Indicaciones', open_maps_tooltip: 'Abre Google Maps con esta dirección' },
        de: { get_directions: 'Route anzeigen', open_maps: 'In Google Maps öffnen', directions: 'Wegbeschreibung', open_maps_tooltip: 'Öffnet Google Maps mit dieser Adresse' },
        ja: { get_directions: '経路を表示', open_maps: 'Google マップで開く', directions: '経路', open_maps_tooltip: 'この住所で Google マップを開く' },
        zh: { get_directions: '路线', open_maps: '在 Google 地图中打开', directions: '路线', open_maps_tooltip: '在 Google 地图中使用此地址打开' }
      };
      const L10N = TRANSLATIONS[userLang] || TRANSLATIONS['en'];

      const sydneyPopup = `
        <strong>Sydney Campus</strong><br>${sydneyAddress}<br>
        <a class="directions-btn" href="https://www.google.com/maps/dir/?api=1&destination=${sydneyCoords[0]},${sydneyCoords[1]}" target="_blank" rel="noopener"><i class="fas fa-location-arrow"></i>${L10N.get_directions}</a>
        <div style="margin-top:8px;"><a class="open-link" aria-label="${L10N.open_maps}" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(sydneyAddress)}" target="_blank" rel="noopener">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51L15.42 17.49"/><path d="M15.41 6.51L8.59 10.49"/></svg>
          ${L10N.open_maps}
          <span class="tooltip-text" role="tooltip">${L10N.open_maps_tooltip}</span>
        </a></div>`;

      const canberraPopup = `
        <strong>Canberra Campus</strong><br>${canberraAddress}<br>
        <a class="directions-btn" href="https://www.google.com/maps/dir/?api=1&destination=${canberraCoords[0]},${canberraCoords[1]}" target="_blank" rel="noopener"><i class="fas fa-location-arrow"></i>${L10N.get_directions}</a>
        <div style="margin-top:8px;"><a class="open-link" aria-label="${L10N.open_maps}" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(canberraAddress)}" target="_blank" rel="noopener">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51L15.42 17.49"/><path d="M15.41 6.51L8.59 10.49"/></svg>
          ${L10N.open_maps}
          <span class="tooltip-text" role="tooltip">${L10N.open_maps_tooltip}</span>
        </a></div>`;

      L.marker(sydneyCoords, { icon: campusIcon }).addTo(mapSydney).bindPopup(sydneyPopup);
      L.marker(canberraCoords, { icon: campusIcon }).addTo(mapCanberra).bindPopup(canberraPopup);

      // Add a small top-right directions control on each map
      function addDirectionsControl(map, coords, label) {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${coords[0]},${coords[1]}`;
        const control = L.control({ position: 'topright' });
        control.onAdd = function() {
          const el = L.DomUtil.create('div', 'leaflet-directions-control');
          el.innerHTML = `<a class="directions-btn" href="${url}" target="_blank" rel="noopener"><i class="fas fa-location-arrow"></i> ${L10N.directions}</a>`;
          L.DomEvent.disableClickPropagation(el);
          return el;
        };
        control.addTo(map);
      }

      addDirectionsControl(mapSydney, sydneyCoords, 'Sydney Campus');
      addDirectionsControl(mapCanberra, canberraCoords, 'Canberra Campus');

      // Make maps resize correctly when page layout changes
      setTimeout(()=>{ mapSydney.invalidateSize(); mapCanberra.invalidateSize(); }, 300);
    })();
  </script>
</body>
</html>
