// ─── DATA ───────────────────────────────────────────────────────────────────
const projects = [
  {
    id: 'samagrah',
    name: 'Samagrah',
    status: 'dev',
    emoji: '🕉️',
    icon: './public/samagran/sama.jpeg',
    screenshots: [
      './public/samagran/1.jpeg',
      './public/samagran/3.jpeg',
      './public/samagran/4.jpeg',
      './public/samagran/5.jpeg',

       './public/samagran/6.jpeg',
      './public/samagran/7.jpeg',
      './public/samagran/8.jpeg',
      './public/samagran/9.jpeg',
      './public/samagran/10.jpeg',
      './public/samagran/12.jpeg',
      './public/samagran/13.jpeg',
      './public/samagran/13.jpeg',
      './public/samagran/14.jpeg',
       './public/samagran/15.jpeg',
      './public/samagran/16.jpeg',
       './public/samagran/17.jpeg',
      './public/samagran/20.jpeg',
    ],
    role: 'Flutter Developer',
    company: 'Kriti Digital Solutions',
    period: 'Jan 2026 – Present',
    description: 'A religious service platform for booking Pandits and purchasing pooja kits for rituals.',
    tags: ['Flutter', 'Riverpod', 'Religious'],
    playstore: null,
  },
  {
    id: 'eyehospital',
    name: 'Eye Hospital',
    status: 'dev',
    emoji: '👁️',
    icon: './public/eye_hospital/icon.jpeg',
    screenshots: [
      './public/eye_hospital/ss1.png',
      './public/eye_hospital/ss2.png',
      './public/eye_hospital/ss3.png',
    ],
    role: 'Flutter Developer',
    company: 'Kriti Digital Solutions',
    period: 'Jan 2026 – Present',
    description: 'A healthcare app for booking doctor appointments, managing records, and ordering spectacles online.',
    tags: ['Flutter', 'GetX', 'Healthcare'],
    playstore: null,
  },
  {
    id: 'nsquare',
    name: 'N Square International',
    status: 'dev',
    emoji: '🎬',
    icon: './public/n_inter/n.jpeg',
    screenshots: [
      './public/n_inter/ss1.png',
      './public/n_inter/ss2.png',
      './public/n_inter/ss3.png',
    ],
    role: 'Flutter Developer',
    company: 'Arema Technologies',
    period: 'Jul 2025 – Dec 2025',
    description: 'A short drama streaming platform with categorized content and subscriptions.',
    tags: ['Flutter', 'GetX', 'OTT / Streaming'],
    playstore: null,
  },
  {
    id: 'oyecam',
    name: 'Oyecam',
    status: 'dev',
    emoji: '📹',
    icon: './public/oyecam/oyecam.jpeg',
    screenshots: [
      './public/oyecam/ss1.png',
      './public/oyecam/ss2.png',
      './public/oyecam/ss3.png',
    ],
    role: 'Flutter Developer',
    company: 'Arema Technologies',
    period: 'Jul 2025 – Dec 2025',
    description: 'A social platform with chat, calls, live streaming, and a coin-based gifting system.',
    tags: ['Flutter', 'Provider', 'Social / Live'],
    playstore: null,
  },
  {
    id: 'tocken',
    name: 'Tocken App',
    status: 'published',
    emoji: '🎟️',
    icon: './public/tocken/tocken.jpeg',
    screenshots: [
      './public/tocken/3.jpeg',
      './public/tocken/4.jpeg',
      './public/tocken/5.jpeg',

       './public/tocken/6.jpeg',
      './public/tocken/7.jpeg',
      './public/tocken/8.jpeg',

       './public/tocken/1.jpeg',
      './public/tocken/2.jpeg',
      './public/tocken/9.jpeg',

       './public/tocken/10.jpeg',
      './public/tocken/11.jpeg',
      './public/tocken/12.jpeg',

       './public/tocken/13.jpeg',
      './public/tocken/14.jpeg',
    
    ],
    role: 'Flutter Developer (Junior)',
    company: 'Webhopers Infotech Pvt. Ltd.',
    period: 'Aug 2024 – Jun 2025',
    description: 'A queue management system for digital token handling with real-time updates.',
    tags: ['Flutter', 'GetX', 'Queue System'],
    playstore: 'https://play.google.com/store/search?q=tocken',
  },
  {
    id: 'mitratender',
    name: 'Mitra Tender',
    status: 'published',
    emoji: '📄',
    icon: 'public/mitratender/icon.png',
    screenshots: [
      'public/mitratender/ss1.png',
      'public/mitratender/ss2.png',
      'public/mitratender/ss3.png',
    ],
    role: 'Flutter Developer (Junior)',
    company: 'Webhopers Infotech Pvt. Ltd.',
    period: 'Aug 2024 – Jun 2025',
    description: 'A tender search platform with advanced filtering, document downloads, and real-time data.',
    tags: ['Flutter', 'GetX', 'Business'],
    playstore: 'https://play.google.com/store/search?q=mitra+tender',
  },
  {
    id: 'opastrip',
    name: 'Opas Trip',
    status: 'published',
    emoji: '✈️',
    icon: 'public/opastrip/icon.png',
    screenshots: [
      'public/opastrip/ss1.png',
      'public/opastrip/ss2.png',
      'public/opastrip/ss3.png',
    ],
    role: 'Flutter Developer (Junior)',
    company: 'Webhopers Infotech Pvt. Ltd.',
    period: 'Aug 2024 – Jun 2025',
    description: 'A travel booking app with flights, hotels, and tour package booking features.',
    tags: ['Flutter', 'GetX', 'Travel'],
    playstore: 'https://play.google.com/store/search?q=opas+trip',
  },
];

// ─── NAVIGATION ─────────────────────────────────────────────────────────────
let currentPage = 'home';

function navigate(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  document.querySelectorAll('.nav-links a').forEach(a => {
    a.classList.toggle('active', a.dataset.page === page);
  });
  currentPage = page;
  window.scrollTo(0, 0);
  closeMobileMenu();
  initReveal();
  if (page === 'projects') renderProjects('all');
  if (page === 'home') initCounters();
}

document.querySelectorAll('[data-page]').forEach(el => {
  el.addEventListener('click', e => { e.preventDefault(); navigate(el.dataset.page); });
});

// ─── MOBILE MENU ────────────────────────────────────────────────────────────
const mobBtn = document.getElementById('mobBtn');
const navLinks = document.getElementById('navLinks');
function closeMobileMenu() {
  navLinks.classList.remove('open');
  mobBtn.innerHTML = '<i class="ri-menu-line"></i>';
}
mobBtn.addEventListener('click', () => {
  const open = navLinks.classList.toggle('open');
  mobBtn.innerHTML = open ? '<i class="ri-close-line"></i>' : '<i class="ri-menu-line"></i>';
});

// ─── NAV SCROLL ─────────────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
  document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 50);
});

// ─── CURSOR ─────────────────────────────────────────────────────────────────
const cur = document.getElementById('cur');
const curR = document.getElementById('curRing');
let mx = 0, my = 0, rx = 0, ry = 0, cs = 1;
document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  const sp = Math.sqrt((e.movementX || 0) ** 2 + (e.movementY || 0) ** 2);
  cs = Math.min(1.5, Math.max(0.8, 1 + sp * 0.01));
});
(function tick() {
  cur.style.left = mx + 'px'; cur.style.top = my + 'px';
  cur.style.transform = `translate(-50%,-50%) scale(${cs})`;
  rx += (mx - rx) * 0.15; ry += (my - ry) * 0.15;
  curR.style.left = rx + 'px'; curR.style.top = ry + 'px';
  curR.style.transform = `translate(-50%,-50%) scale(${cs * 0.7})`;
  requestAnimationFrame(tick);
})();
document.addEventListener('mouseover', e => {
  const el = e.target.closest('a,button,.stat-card,.info-card,.tag,.chip,.btn-primary,.btn-ghost,.project-card,.filter-btn,.screenshot-thumb,.lightbox-nav');
  if (el) { cur.classList.add('hover'); curR.classList.add('hover'); }
  else { cur.classList.remove('hover'); curR.classList.remove('hover'); }
});

// ─── STARS CANVAS ───────────────────────────────────────────────────────────
(function () {
  const canvas = document.getElementById('stars-canvas');
  const ctx = canvas.getContext('2d');
  let W, H;
  const stars = [];
  const colors = ['#1a1a2e', '#16213e', '#0f3460', '#1a1a3e', '#2d2d5f'];
  const resize = () => { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; };
  resize(); window.addEventListener('resize', resize);
  for (let i = 0; i < 120; i++) {
    const r = Math.random() * 1.6 + 0.5;
    stars.push({ x: Math.random() * W, baseY: Math.random() * H, y: 0, r, alphaBase: Math.random() * 0.35 + 0.25, twinkleAmp: Math.random() * 0.18 + 0.08, twinkleFreq: Math.random() * 0.02 + 0.004, twinklePhase: Math.random() * Math.PI * 2, tumAmp: Math.random() * 6 + 4, tumFreq: Math.random() * 0.012 + 0.006, tumPhase: Math.random() * Math.PI * 2, drift: Math.random() * 0.002 + 0.001, color: colors[Math.floor(Math.random() * colors.length)], isSpecial: Math.random() < 0.12 });
  }
  let frame = 0;
  (function draw() {
    ctx.clearRect(0, 0, W, H); frame++;
    stars.forEach(s => {
      const bY = Math.sin(frame * s.tumFreq + s.tumPhase) * s.tumAmp;
      const sY = 1 + Math.abs(Math.sin(frame * s.tumFreq + s.tumPhase)) * 0.25;
      const sX = 1 / sY * 0.95;
      let al = s.alphaBase + Math.sin(frame * s.twinkleFreq + s.twinklePhase) * s.twinkleAmp;
      if (s.isSpecial) al += Math.sin(frame * 0.018 + s.twinklePhase) * 0.12;
      al = Math.max(0.15, Math.min(0.75, al));
      s.baseY -= s.drift;
      if (s.baseY < -8) { s.baseY = H + 8; s.x = Math.random() * W; }
      s.y = s.baseY + bY;
      ctx.save(); ctx.translate(s.x, s.y); ctx.scale(sX, sY);
      ctx.beginPath(); ctx.arc(0, 0, s.r, 0, Math.PI * 2);
      ctx.fillStyle = s.color; ctx.globalAlpha = al; ctx.fill();
      if (s.r > 1.2 || s.isSpecial) {
        const gr = s.isSpecial ? s.r * 6 : s.r * 4;
        const g = ctx.createRadialGradient(0, 0, 0, 0, 0, gr);
        g.addColorStop(0, s.color + 'AA'); g.addColorStop(0.5, s.color + '55'); g.addColorStop(1, 'transparent');
        ctx.beginPath(); ctx.arc(0, 0, gr, 0, Math.PI * 2);
        ctx.fillStyle = g; ctx.globalAlpha = al * 0.8; ctx.fill();
      }
      ctx.restore();
    });
    requestAnimationFrame(draw);
  })();
})();

// ─── TYPING EFFECT ──────────────────────────────────────────────────────────
const roles = ['Flutter Developer', 'Mobile App Engineer', 'Full Stack Developer', 'Clean Code Advocate'];
let ri = 0, ci = 0, del = false;
const twEl = document.getElementById('tw');
function type() {
  const cur = roles[ri];
  if (!del) { twEl.textContent = cur.slice(0, ++ci); if (ci === cur.length) { del = true; setTimeout(type, 1800); return; } }
  else { twEl.textContent = cur.slice(0, --ci); if (ci === 0) { del = false; ri = (ri + 1) % roles.length; } }
  setTimeout(type, del ? 60 : 100);
}
type();

// ─── SCROLL REVEAL ──────────────────────────────────────────────────────────
function initReveal() {
  const obs = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && e.target.classList.add('in')), { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.reveal').forEach(el => { el.classList.remove('in'); obs.observe(el); });
}
initReveal();

// ─── STAT COUNTERS ──────────────────────────────────────────────────────────
function initCounters() {
  const cntObs = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    const el = e.target.querySelector('.stat-num');
    const t = parseInt(el.dataset.t);
    let n = 0;
    const id = setInterval(() => { n = Math.min(n + 1, t); el.textContent = n + '+'; if (n >= t) clearInterval(id); }, 50);
    cntObs.unobserve(e.target);
  }), { threshold: 0.5 });
  document.querySelectorAll('.stat-card').forEach(c => cntObs.observe(c));
}
initCounters();

// ─── IMAGE HELPER: render img with fallback to emoji ────────────────────────
function imgWithFallback(src, alt, fallbackEmoji, cssClass = '') {
  return `<img 
    src="${src}" 
    alt="${alt}" 
    class="${cssClass}" 
    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
  /><span class="img-fallback" style="display:none">${fallbackEmoji}</span>`;
}

// ─── PROJECTS RENDER ────────────────────────────────────────────────────────
let activeFilter = 'all';
function renderProjects(filter) {
  activeFilter = filter;
  const grid = document.getElementById('projectsGrid');
  const filtered = filter === 'all' ? projects : projects.filter(p => p.status === filter);
  grid.innerHTML = filtered.map((p, i) => `
    <div class="project-card" data-id="${p.id}" onclick="openModal('${p.id}')" style="animation-delay:${i * 0.07}s">
      <div class="card-inner">
        <div class="card-preview">
          <div class="card-preview-bg"></div>
          <span class="card-status ${p.status === 'published' ? 'status-published' : 'status-dev'}">${p.status === 'published' ? 'Published' : 'In Development'}</span>
          <div class="card-preview-phones">
            ${p.screenshots.map((ss, idx) => `
              <div class="phone-mock phone-mock-img">
                <img 
                  src="${ss}" 
                  alt="${p.name} screenshot ${idx + 1}" 
                  class="phone-ss-img"
                  onerror="this.parentElement.classList.add('phone-empty');this.style.display='none';"
                />
                <i class="ri-smartphone-line phone-placeholder-icon"></i>
              </div>
            `).join('')}
          </div>
        </div>
        <div class="card-body">
          <div class="card-app-icon-wrap">
            ${imgWithFallback(p.icon, p.name + ' icon', p.emoji, 'card-app-icon-img')}
          </div>
          <div class="card-name">${p.name}</div>
          <div class="card-desc">${p.description}</div>
          <div class="card-tags">${p.tags.map(t => `<span class="card-tag">${t}</span>`).join('')}</div>
          <div class="card-cta">
            <span class="card-cta-text">View Details</span>
            <div class="card-arrow"><i class="ri-arrow-right-line"></i></div>
          </div>
        </div>
      </div>
    </div>
  `).join('');

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.filter === filter);
  });
}

document.getElementById('filterBar').addEventListener('click', e => {
  const btn = e.target.closest('.filter-btn');
  if (btn) renderProjects(btn.dataset.filter);
});

// ─── LIGHTBOX ───────────────────────────────────────────────────────────────
let lightboxImages = [];
let lightboxIndex = 0;

function openLightbox(images, index) {
  lightboxImages = images;
  lightboxIndex = index;
  renderLightbox();
  document.getElementById('lightboxOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightboxOverlay').classList.remove('active');
}

function lightboxNext() {
  lightboxIndex = (lightboxIndex + 1) % lightboxImages.length;
  renderLightbox();
}

function lightboxPrev() {
  lightboxIndex = (lightboxIndex - 1 + lightboxImages.length) % lightboxImages.length;
  renderLightbox();
}

function renderLightbox() {
  const img = document.getElementById('lightboxImg');
  const counter = document.getElementById('lightboxCounter');
  img.style.opacity = '0';
  img.src = lightboxImages[lightboxIndex];
  img.onload = () => { img.style.opacity = '1'; };
  img.onerror = () => { img.style.opacity = '0.3'; };
  counter.textContent = `${lightboxIndex + 1} / ${lightboxImages.length}`;
}

document.addEventListener('keydown', e => {
  if (document.getElementById('lightboxOverlay').classList.contains('active')) {
    if (e.key === 'ArrowRight') lightboxNext();
    if (e.key === 'ArrowLeft') lightboxPrev();
    if (e.key === 'Escape') closeLightbox();
    return;
  }
  if (e.key === 'Escape') closeModal();
});

// ─── MODAL ──────────────────────────────────────────────────────────────────
function openModal(id) {
  const p = projects.find(x => x.id === id);
  if (!p) return;

  // Header
  const iconWrap = document.getElementById('modalIcon');
  iconWrap.innerHTML = `
    <img src="${p.icon}" alt="${p.name} icon" class="modal-icon-img"
      onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
    <span class="modal-icon-fallback" style="display:none">${p.emoji}</span>
  `;

  document.getElementById('modalName').textContent = p.name;
  document.getElementById('modalRole').textContent = p.role + ' at ' + p.company;

  const sb = document.getElementById('modalStatus');
  sb.className = 'modal-status-badge ' + (p.status === 'published' ? 'status-published' : 'status-dev');
  sb.textContent = p.status === 'published' ? '📱 Published on Play Store' : '🚧 In Development';

  // Screenshots
  const ssRow = document.getElementById('modalScreenshots');
  ssRow.innerHTML = p.screenshots.map((ss, idx) => `
    <div class="screenshot-thumb" onclick="openLightbox(${JSON.stringify(p.screenshots)}, ${idx})" title="Tap to open">
      <img 
        src="${ss}" 
        alt="${p.name} screenshot ${idx + 1}" 
        class="screenshot-img"
        onerror="this.parentElement.classList.add('screenshot-empty');this.style.display='none';"
      />
      <div class="screenshot-empty-ui">
        <i class="ri-image-line"></i>
        <span>No Image</span>
      </div>
      <div class="screenshot-zoom-hint"><i class="ri-zoom-in-line"></i></div>
    </div>
  `).join('');

  document.getElementById('modalDesc').textContent = p.description;

  document.getElementById('modalDetails').innerHTML = `
    <div class="detail-item"><div class="detail-label">Role</div><div class="detail-value">${p.role}</div></div>
    <div class="detail-item"><div class="detail-label">Company</div><div class="detail-value">${p.company}</div></div>
    <div class="detail-item"><div class="detail-label">Period</div><div class="detail-value">${p.period}</div></div>
    <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">${p.status === 'published' ? 'Published' : 'In Development'}</div></div>
  `;

  document.getElementById('modalTech').innerHTML = p.tags.map(t => `<span class="tech-tag">${t}</span>`).join('');

  document.getElementById('modalStore').innerHTML = p.playstore
    ? `<a href="${p.playstore}" target="_blank" rel="noopener noreferrer" class="store-btn"><i class="ri-google-play-line"></i> View on Play Store</a>`
    : '';

  document.getElementById('modalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

function handleOverlayClick(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

// ─── CONTACT FORM ───────────────────────────────────────────────────────────
function submitContact() {
  const name = document.getElementById('cName').value.trim();
  const email = document.getElementById('cEmail').value.trim();
  const msg = document.getElementById('cMessage').value.trim();
  if (!name || !email || !msg) { alert('Please fill in all fields.'); return; }
  const mailto = `mailto:amitk15042003@gmail.com?subject=Portfolio Contact from ${encodeURIComponent(name)}&body=${encodeURIComponent('Name: ' + name + '\nEmail: ' + email + '\n\nMessage:\n' + msg)}`;
  window.location.href = mailto;
  document.getElementById('formSuccess').style.display = 'block';
}