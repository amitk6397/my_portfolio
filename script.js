

// ─── DATA ────────────────────────────────────────────────────────────────────
const projects = [
  { id:'playOn', name:'PLAYON', status:'dev', emoji:'🎮', icon:'./public/playOn/logo.jpeg',
     screenshots:[
      './public/playOn/1.jpeg',
      './public/playOn/2.jpeg',
      './public/playOn/3.jpeg',
      './public/playOn/4.jpeg',
      './public/playOn/5.jpeg',
      './public/playOn/6.jpeg',
      './public/playOn/7.jpeg',
      './public/playOn/8.jpeg',
      './public/playOn/9.jpeg',
      './public/playOn/10.jpeg',
      './public/playOn/11.jpeg',
      './public/playOn/12.jpeg',
      './public/playOn/13.jpeg',
     
    
    ], role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'April 2026 – Present', description:'A dynamic sports streaming platform that brings you live matches, highlights, and updates across cricket, football, tennis, basketball, and more. Watch real-time action, catch match recaps, stream live TV channels, and stay updated with upcoming match schedules and tournaments—all in one place.', tags:['Flutter','GetX','Dio','Sports / OTT'], playstore:null },
  { 
    id:'samagran',
     name:'Samagran',
      status:'dev',
       emoji:'🕉️',
        icon:'./public/samagran/sama.jpeg',
         screenshots:[
          './public/samagran/1.jpeg',
          './public/samagran/3.jpeg',
          './public/samagran/4.jpeg',
          './public/samagran/5.jpeg',
          './public/samagran/6.jpeg',
          './public/samagran/2.jpeg',
          './public/samagran/7.jpeg',
          './public/samagran/8.jpeg',
          './public/samagran/9.jpeg',
          './public/samagran/10.jpeg',
          './public/samagran/11.jpeg',
          './public/samagran/12.jpeg',
          './public/samagran/13.jpeg',
          './public/samagran/15.jpeg',
          './public/samagran/16.jpeg',
           './public/samagran/12.jpeg',
          './public/samagran/17.jpeg',
          './public/samagran/20.jpeg',
        ],
           role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'Jan 2026 – Present', description:'A religious service platform for booking Pandits and purchasing pooja kits for rituals.', tags:['Flutter','Riverpod','Firebase FCM','Agora RTC','Razorpay Payments','Location','RESTApis'], playstore:null },
  { id:'eyehospital', name:'Eye Hospital', status:'dev', emoji:'👁️', icon:'./public/eye_hospital/icon.jpeg', screenshots:['./public/eye_hospital/ss1.png','./public/eye_hospital/ss2.png','./public/eye_hospital/ss3.png'], role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'Jan 2026 – Present', description:'A healthcare app for booking doctor appointments, managing records, and ordering spectacles online.', tags:['Flutter','GetX','Cashfree Payments','Healthcare'], playstore:null },
  { id:'nsquare', name:'N Square Shorts', status:'dev', emoji:'🎬', icon:'./public/n_inter/logo.jpeg',
     screenshots:[
      './public/n_inter/1.jpeg',
      
      './public/n_inter/2.jpeg',
      './public/n_inter/3.jpeg',
       './public/n_inter/4.jpeg',
      
      './public/n_inter/5.jpeg',
      './public/n_inter/6.jpeg',
       './public/n_inter/7.jpeg',
      
      './public/n_inter/8.jpeg',
      './public/n_inter/9.jpeg',
       './public/n_inter/10.jpeg',
      
      './public/n_inter/11.jpeg',
      
    
    ], role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'Mar 2026 – Apr 2026', description:'A short drama streaming platform with categorized content and subscriptions.', tags:['Flutter','GetX','OTT / Streaming'], playstore:null },
  { id:'oyecam', name:'Oyecam', status:'dev', emoji:'📹', icon:'./public/oyecam/oyecam.jpeg', screenshots:['./public/oyecam/ss1.png','./public/oyecam/ss2.png','./public/oyecam/ss3.png'], role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'Feb 2026 – Pending', description:'A social platform with chat, calls, live streaming, and a coin-based gifting system.', tags:['GetX','Hive','Agora RTC','Razorpay Payments','Dio','socket.io','Social / Communication'], playstore:null },
  { id:'tocken', name:'Tocken App', status:'published', emoji:'🎟️', icon:'./public/tocken/tocken.jpeg',
     screenshots:[
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
    
    ], role:'Senior Flutter Developer', company:'Kriti Digital Solutions', period:'Jan 2026 – Mar 2026', description:'A queue management system for digital token handling with real-time updates.', tags:['Provider','Http','PhonePe Payments','Flutter','Shared Preferences'], playstore:'https://play.google.com/store/search?q=tocken' },
  { id:'mitratender', name:'Mitra Tender', status:'published', emoji:'📄', icon:'./public/mt/logo.jpeg',
     screenshots:[
      './public/mt/1.jpeg',
      './public/mt/3.jpeg',
      './public/mt/4.jpeg',
      './public/mt/5.jpeg',
      './public/mt/6.jpeg',
      './public/mt/2.jpeg',
      './public/mt/7.jpeg',
      './public/mt/8.jpeg',
      './public/mt/9.jpeg',
      './public/mt/10.jpeg',
    ], role:'Flutter Developer (Junior)', company:'Arema Technologies', period:'Aug 2025 – Oct 2025', description:'A tender search platform with advanced filtering, document downloads, and real-time data.', tags:['Flutter','GetX','Razorpay Payments','Dio'], playstore:'https://play.google.com/store/search?q=mitra+tender' },
  { id:'opastrip', name:'Opas Trip', status:'published', emoji:'✈️', icon:'public/opastrip/icon.png', screenshots:['public/opastrip/ss1.png','public/opastrip/ss2.png','public/opastrip/ss3.png'], role:'Flutter Developer (Junior)', company:'Arema Technologies', period:'Jun 2025 – Dec 2025', description:'A travel booking app with flights, hotels, and tour package booking features.', tags:['Flutter','GetX','Razorpay Payments','http','TripJack Services','Travel / Booking'], playstore:'https://play.google.com/store/search?q=opas+trip' },
];

// ─── NAV ──────────────────────────────────────────────────────────────────────
function navigate(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  document.querySelectorAll('.nav-links a').forEach(a => a.classList.toggle('active', a.dataset.page === page));
  window.scrollTo(0, 0);
  if (page === 'projects') renderProjects('all');
  closeMobileMenu();
}
document.querySelectorAll('[data-page]').forEach(el => {
  el.addEventListener('click', e => { e.preventDefault(); navigate(el.dataset.page); });
});

// ─── MOBILE MENU ──────────────────────────────────────────────────────────────
const mobBtn = document.getElementById('mobBtn');
const navLinksEl = document.getElementById('navLinks');
function closeMobileMenu() {
  navLinksEl.classList.remove('open');
  mobBtn.textContent = '☰';
}
mobBtn.addEventListener('click', () => {
  const open = navLinksEl.classList.toggle('open');
  mobBtn.textContent = open ? '✕' : '☰';
});

// ─── TYPING ───────────────────────────────────────────────────────────────────
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

// ─── PROJECTS RENDER ─────────────────────────────────────────────────────────
function renderProjects(filter) {
  const grid = document.getElementById('projectsGrid');
  const filtered = filter === 'all' ? projects : projects.filter(p => p.status === filter);
  grid.innerHTML = filtered.map(p => {
    const desc = p.description.length > 100 ? p.description.slice(0, 100) + '…' : p.description;
    const tags = p.tags.slice(0, 4).map(t => `<span class="pc-tag">${t}</span>`).join('');
    return `
      <div class="project-card" onclick="openModal('${p.id}')">
        <div class="pc-top">
          <div class="pc-icon">
            <img src="${p.icon}" alt="${p.name}" onerror="this.style.display='none';this.parentElement.textContent='${p.emoji}';" />
          </div>
          <span class="pc-status ${p.status === 'published' ? 'pub' : ''}">${p.status === 'published' ? 'Published' : 'In Dev'}</span>
        </div>
        <div class="pc-body">
          <div class="pc-name">${p.name}</div>
          <div class="pc-desc">${desc}</div>
          <div class="pc-tags">${tags}</div>
          <div class="pc-footer">
            <span class="pc-cta">View Details</span>
            <div class="pc-arrow">→</div>
          </div>
        </div>
      </div>
    `;
  }).join('');
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.toggle('active', b.dataset.filter === filter));
}
document.getElementById('filterBar').addEventListener('click', e => {
  const btn = e.target.closest('.filter-btn');
  if (btn) renderProjects(btn.dataset.filter);
});

// ─── MODAL ────────────────────────────────────────────────────────────────────
function openModal(id) {
  const p = projects.find(x => x.id === id);
  if (!p) return;
  const icon = document.getElementById('modalIcon');
  icon.innerHTML = `<img src="${p.icon}" alt="${p.name}" onerror="this.style.display='none';this.parentElement.textContent='${p.emoji}';" />`;
  document.getElementById('modalName').textContent = p.name;
  document.getElementById('modalRole').textContent = p.role + ' at ' + p.company;
  const sb = document.getElementById('modalStatus');
  sb.className = 'modal-status' + (p.status === 'published' ? ' pub' : '');
  sb.textContent = p.status === 'published' ? '↗ Published on Play Store' : '◎ In Development';
  document.getElementById('modalScreenshots').innerHTML = p.screenshots.map((ss, idx) => `
    <div class="ss-thumb" onclick="openLightbox(${JSON.stringify(p.screenshots)}, ${idx})">
      <img src="${ss}" alt="Screenshot ${idx+1}" onerror="this.parentElement.innerHTML='<div class=\\'ss-empty\\'><span>No Image</span></div>';" />
    </div>
  `).join('');
  document.getElementById('modalDesc').textContent = p.description;
  document.getElementById('modalDetails').innerHTML = `
    <div class="detail-item"><div class="detail-label">Role</div><div class="detail-val">${p.role}</div></div>
    <div class="detail-item"><div class="detail-label">Company</div><div class="detail-val">${p.company}</div></div>
    <div class="detail-item"><div class="detail-label">Period</div><div class="detail-val">${p.period}</div></div>
    <div class="detail-item"><div class="detail-label">Status</div><div class="detail-val">${p.status === 'published' ? 'Published' : 'In Development'}</div></div>
  `;
  document.getElementById('modalTech').innerHTML = p.tags.map(t => `<span class="tech-tag">${t}</span>`).join('');
  document.getElementById('modalStore').innerHTML = p.playstore
    ? `<a href="${p.playstore}" target="_blank" class="store-btn">View on Play Store ↗</a>` : '';
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

// ─── LIGHTBOX ─────────────────────────────────────────────────────────────────
let lbImgs = [], lbIdx = 0;
function openLightbox(images, index) {
  lbImgs = images; lbIdx = index; renderLb();
  document.getElementById('lightboxOverlay').classList.add('active');
}
function closeLightbox() { document.getElementById('lightboxOverlay').classList.remove('active'); }
function lightboxNext() { lbIdx = (lbIdx + 1) % lbImgs.length; renderLb(); }
function lightboxPrev() { lbIdx = (lbIdx - 1 + lbImgs.length) % lbImgs.length; renderLb(); }
function renderLb() {
  document.getElementById('lightboxImg').src = lbImgs[lbIdx];
  document.getElementById('lightboxCounter').textContent = `${lbIdx + 1} / ${lbImgs.length}`;
}

// ─── KEYBOARD ─────────────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (document.getElementById('lightboxOverlay').classList.contains('active')) {
    if (e.key === 'ArrowRight') lightboxNext();
    if (e.key === 'ArrowLeft') lightboxPrev();
    if (e.key === 'Escape') closeLightbox();
    return;
  }
  if (e.key === 'Escape') closeModal();
});

// ─── CONTACT ──────────────────────────────────────────────────────────────────
function submitContact() {
  const name = document.getElementById('cName').value.trim();
  const email = document.getElementById('cEmail').value.trim();
  const msg = document.getElementById('cMessage').value.trim();
  if (!name || !email || !msg) { alert('Please fill in all fields.'); return; }
  window.location.href = `mailto:amitk15042003@gmail.com?subject=Portfolio Contact from ${encodeURIComponent(name)}&body=${encodeURIComponent('Name: '+name+'\nEmail: '+email+'\n\nMessage:\n'+msg)}`;
  document.getElementById('formSuccess').style.display = 'block';
}
