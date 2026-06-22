<?php
require_once __DIR__ . '/db.php';

$db = Database::getInstance()->getConnection();

// Fetch About Settings
$stmt = $db->query("SELECT * FROM about_settings WHERE id = 1");
$about = $stmt->fetch();

if (!$about) {
    die("Application not properly initialized. Please check connection and try again.");
}

// Compute dynamic stats
$projCount = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$expCount = $db->query("SELECT COUNT(*) FROM experience")->fetchColumn();

// Process projects for JS output
$projectsQuery = $db->query("SELECT * FROM projects ORDER BY id ASC")->fetchAll();
$js_projects = [];
foreach ($projectsQuery as $p) {
    $js_projects[] = [
        'id' => $p['project_key'],
        'name' => $p['name'],
        'status' => $p['status'],
        'emoji' => $p['emoji'] ?: '🎮',
        'icon' => $p['icon'],
        'screenshots' => json_decode($p['screenshots']) ?: [],
        'role' => $p['role'],
        'company' => $p['company'],
        'period' => $p['period'],
        'description' => $p['description'],
        'tags' => $p['tags'] ? array_map('trim', explode(',', $p['tags'])) : [],
        'playstore' => $p['playstore']
    ];
}

// Name splitting for hero design
$name_parts = explode(' ', $about['hero_name']);
$line1 = $name_parts[0];
$line2 = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>

  <script>
    (function() {
      const saved = localStorage.getItem('ak-theme');
      if (saved && saved !== 'dark') {
        document.documentElement.setAttribute('data-theme', saved);
      }
    })();
  </script>

  <!-- ── TAB TITLE ── -->
  <title><?php echo htmlspecialchars($about['hero_name']); ?> | Developer Portfolio</title>

  <!-- ── FAVICON / TAB ICON ── -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,
    %3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E
      %3Crect width='64' height='64' rx='14' fill='%2300E5FF'/%3E
      %3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle'
        font-family='monospace' font-weight='700' font-size='28' fill='%23080A0F'%3EAK%3C/text%3E
      %3C/svg%3E"/>

  <!-- ── FONTS ── -->
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet"/>
  
  <!-- ── ICONS ── -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <!-- ── STYLES ── -->
  <link rel="stylesheet" href="style.css"/>

  <script>
    // Pass dynamic database projects into JavaScript
    window.portfolioProjects = <?php echo json_encode($js_projects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  </script>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- NAV -->
<nav id="nav">
  <div class="nav-inner" style="display:contents">
    <a class="nav-logo" data-page="home">
      <div class="nav-logo-box">AK</div>
      <div class="nav-logo-text"><?php echo htmlspecialchars($about['hero_name']); ?> <span>/ <?php echo htmlspecialchars($about['stack_text']); ?></span></div>
    </a>
    <ul class="nav-links" id="navLinks">
      <li><a data-page="home" class="active"><i class="fa-solid fa-user"></i>About</a></li>
      <li><a data-page="skills"><i class="fa-solid fa-code"></i>Skills</a></li>
      <li><a data-page="experience"><i class="fa-solid fa-briefcase"></i>Work</a></li>
      <li><a data-page="projects"><i class="fa-solid fa-layer-group"></i>Projects</a></li>
      <li><a data-page="contact"><i class="fa-solid fa-paper-plane"></i>Contact</a></li>
    </ul>
    <div class="theme-switcher" id="themeSwitcher">
      <button class="theme-btn active" data-theme-val="dark" onclick="setTheme('dark')">
        <i class="fa-solid fa-moon"></i>
        <span class="theme-btn-tooltip">Dark</span>
      </button>
      <button class="theme-btn" data-theme-val="light" onclick="setTheme('light')">
        <i class="fa-solid fa-sun"></i>
        <span class="theme-btn-tooltip">Light</span>
      </button>
    </div>
    <a href="#page-contact" onclick="navigate('contact')" class="nav-cta"><i class="fa-solid fa-wand-magic-sparkles"></i> Hire Me</a>
    <button class="mob-btn" id="mobBtn"><i class="fa-solid fa-bars"></i></button>
  </div>
</nav>

<div id="pages">

<!-- ═══ HOME ═══ -->
<div class="page active" id="page-home">
  <div class="hero-bg">
    <div class="orb1"></div>
    <div class="orb2"></div>
    <div class="grid"></div>
  </div>

  <div class="hero">
    <div class="hero-left">
      <div class="hero-label">
        <span class="pulse-dot"></span>
        Available for Opportunities
      </div>
      <h1 class="hero-name">
        <span class="line1"><?php echo htmlspecialchars($line1); ?></span>
        <span class="line2"><?php echo htmlspecialchars($line2); ?></span>
      </h1>
      <div class="hero-role-wrap">
        <div class="role-chip">
          <i class="fa-solid fa-terminal" style="font-size:11px;opacity:0.6"></i>
          <span id="tw"></span><span class="role-cursor"></span>
        </div>
      </div>
      <p class="hero-desc"><?php echo htmlspecialchars($about['hero_desc']); ?></p>
      <div class="hero-btns">
        <a href="#page-contact" onclick="navigate('contact')" class="btn-primary"><i class="fa-solid fa-envelope"></i> Let's Talk</a>
        <a href="<?php echo htmlspecialchars($about['resume_url']); ?>" download class="btn-ghost"><i class="fa-solid fa-download"></i> Download CV</a>
      </div>
      <div class="hero-socials">
        <a href="<?php echo htmlspecialchars($about['linkedin_url']); ?>" target="_blank" class="social-pill"><i class="fa-brands fa-linkedin"></i></a>
        <a href="<?php echo htmlspecialchars($about['github_url']); ?>" target="_blank" class="social-pill"><i class="fa-brands fa-github"></i></a>
        <a href="mailto:<?php echo htmlspecialchars($about['contact_email']); ?>" class="social-pill"><i class="fa-solid fa-envelope"></i></a>
      </div>
    </div>
    
    <div class="hero-right">
      <div class="hero-card">
        <div class="card-glow"></div>
 
        <!-- BIG PHOTO BANNER AT TOP -->
        <div class="hc-photo-wrap">
          <img src="<?php echo htmlspecialchars($about['photo_url']); ?>" alt="<?php echo htmlspecialchars($about['hero_name']); ?>" onerror="this.style.display='none'"/>
          <div class="hc-photo-overlay"></div>
          <div class="hc-photo-initials">AK</div>
          <div class="hc-photo-badges">
            <span class="hc-badge filled"><i class="fa-brands fa-flutter"></i> <?php echo htmlspecialchars($about['stack_text']); ?> Expert</span>
            <span class="hc-badge plain"><i class="fa-solid fa-server"></i> Full Stack</span>
          </div>
        </div>
 
        <!-- CARD BODY -->
        <div class="hc-body">
          <div class="hc-name"><?php echo htmlspecialchars($about['hero_name']); ?></div>
          <div class="hc-loc"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($about['stack_text']); ?> Developer · <?php echo htmlspecialchars($about['contact_location']); ?></div>
          <div class="hc-divider"></div>
          <div class="hc-grid">
            <div class="hc-item">
              <div class="hc-label">Status</div>
              <div class="hc-val green">● <?php echo htmlspecialchars($about['status_text']); ?></div>
            </div>
            <div class="hc-item">
              <div class="hc-label">Stack</div>
              <div class="hc-val"><?php echo htmlspecialchars($about['stack_text']); ?></div>
            </div>
            <div class="hc-item">
              <div class="hc-label">Experience</div>
              <div class="hc-val"><?php echo htmlspecialchars($about['experience_years']); ?> Years</div>
            </div>
            <div class="hc-item">
              <div class="hc-label">Email</div>
              <div class="hc-val small"><?php echo htmlspecialchars($about['contact_email']); ?></div>
            </div>
          </div>
          <div class="hc-tags">
            <span class="hc-tag"><i class="fa-solid fa-rocket"></i> <?php echo $projCount; ?>+ Projects</span>
            <span class="hc-tag"><i class="fa-solid fa-microchip"></i> 15+ Tech</span>
            <span class="hc-tag"><i class="fa-solid fa-star"></i> <?php echo htmlspecialchars($about['experience_years']); ?> Years</span>
          </div>
        </div>
 
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-strip">
    <div class="stats-inner">
      <div class="stat-card">
        <div class="stat-icon-wrap"><i class="fa-solid fa-cubes"></i></div>
        <div>
          <span class="stat-num"><?php echo $projCount; ?>+</span>
          <div class="stat-lbl">Projects Shipped</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap"><i class="fa-solid fa-calendar-days"></i></div>
        <div>
          <span class="stat-num"><?php echo htmlspecialchars($about['experience_years']); ?> Years</span>
          <div class="stat-lbl">Experience</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap"><i class="fa-solid fa-microchip"></i></div>
        <div>
          <span class="stat-num">15+</span>
          <div class="stat-lbl">Technologies</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ABOUT SECTION -->
  <div class="section">
    <div class="section-header">
      <div class="section-label">Who I Am</div>
      <h2 class="section-title">A bit about <em>me</em></h2>
      <p class="section-sub">A Flutter developer passionate about building beautiful, performant mobile applications.</p>
    </div>
    <div class="about-grid">
      <div class="acard col8">
        <div class="acard-label"><i class="fa-solid fa-pen-nib"></i> My Story</div>
        <h3><?php echo htmlspecialchars($about['story_title']); ?></h3>
        <p><?php echo htmlspecialchars($about['story_desc']); ?></p>
        <div class="tag-row">
          <span class="chip filled"><i class="fa-brands fa-flutter"></i> Flutter</span>
          <span class="chip filled">Dart</span>
          <span class="chip">GetX</span>
          <span class="chip">Provider</span>
          <span class="chip">Riverpod</span>
        </div>
      </div>
      <div class="acard col4">
        <div class="acard-label"><i class="fa-solid fa-graduation-cap"></i> Education</div>
        <h3><?php echo htmlspecialchars($about['education_title']); ?></h3>
        <p><?php echo htmlspecialchars($about['education_desc']); ?></p>
        <div class="tag-row">
          <span class="chip filled">MCA</span>
          <span class="chip">Aligarh</span>
        </div>
      </div>
      <div class="acard col4">
        <div class="acard-label"><i class="fa-solid fa-server"></i> Backend & APIs</div>
        <h3><?php echo htmlspecialchars($about['backend_title']); ?></h3>
        <p><?php echo htmlspecialchars($about['backend_desc']); ?></p>
        <div class="tag-row">
          <span class="chip filled"><i class="fa-brands fa-node-js"></i> Node.js</span>
          <span class="chip">Spring Boot</span>
          <span class="chip">JWT</span>
        </div>
      </div>
      <div class="acard col4">
        <div class="acard-label"><i class="fa-solid fa-lightbulb"></i> Philosophy</div>
        <h3><?php echo htmlspecialchars($about['philosophy_title']); ?></h3>
        <p><?php echo htmlspecialchars($about['philosophy_desc']); ?></p>
        <div class="tag-row">
          <span class="chip filled">Clean Code</span>
          <span class="chip">MVVM</span>
        </div>
      </div>
      <div class="acard col4">
        <div class="acard-label"><i class="fa-solid fa-fire"></i> Firebase</div>
        <h3><?php echo htmlspecialchars($about['firebase_title']); ?></h3>
        <p><?php echo htmlspecialchars($about['firebase_desc']); ?></p>
        <div class="tag-row">
          <span class="chip filled">Firestore</span>
          <span class="chip">FCM</span>
          <span class="chip">Storage</span>
        </div>
      </div>
      <div class="acard col12">
        <div class="acard-label"><i class="fa-solid fa-id-card"></i> Quick Info</div>
        <h3 style="margin-bottom:18px">Details</h3>
        <div class="quick-grid">
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-location-dot"></i> Location</div><div class="qi-val"><?php echo htmlspecialchars($about['contact_location']); ?></div></div>
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-envelope"></i> Email</div><div class="qi-val" style="font-size:12px"><?php echo htmlspecialchars($about['contact_email']); ?></div></div>
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-phone"></i> Phone</div><div class="qi-val"><?php echo htmlspecialchars($about['contact_phone']); ?></div></div>
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-circle-check"></i> Status</div><div class="qi-val green"><?php echo htmlspecialchars($about['status_text']); ?></div></div>
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-layer-group"></i> Stack</div><div class="qi-val"><?php echo htmlspecialchars($about['stack_text']); ?></div></div>
          <div class="qi"><div class="qi-lbl"><i class="fa-solid fa-heart"></i> Passion</div><div class="qi-val"><?php echo htmlspecialchars($about['passion_text']); ?></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SKILLS ═══ -->
<div class="page" id="page-skills">
  <div class="section" style="padding-top:60px">
    <div class="section-header">
      <div class="section-label">Capabilities</div>
      <h2 class="section-title">Skills &amp; <em>Technologies</em></h2>
      <p class="section-sub">Everything I use to build production-ready Flutter apps and backend systems.</p>
    </div>
    <div class="skills-grid">
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-code"></i> Languages</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">Core Languages</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">Languages I work with daily.</p>
        <div class="tag-row"><span class="chip filled">Dart</span><span class="chip filled">Java</span><span class="chip">JavaScript</span><span class="chip filled">Python</span><span class="chip">HTML</span><span class="chip">CSS</span></div>
      </div>
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-mobile-screen-button"></i> Frontend</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">UI &amp; Mobile</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">Beautiful, responsive mobile &amp; web interfaces.</p>
        <div class="tag-row"><span class="chip filled"><i class="fa-brands fa-flutter"></i> Flutter</span><span class="chip"><i class="fa-brands fa-react"></i> React.js</span></div>
      </div>
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-server"></i> Backend</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">Server Side</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">RESTful APIs, auth &amp; server-side logic.</p>
        <div class="tag-row"><span class="chip filled"><i class="fa-brands fa-node-js"></i> Node.js</span><span class="chip filled"><i class="fa-brands fa-python"></i> FastAPI</span><span class="chip">Spring Boot</span><span class="chip">RESTful APIs</span><span class="chip">JWT Auth</span></div>
      </div>
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-database"></i> Databases</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">Data &amp; Firebase</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">Data persistence, real-time sync &amp; cloud services.</p>
        <div class="tag-row"><span class="chip filled">Firestore</span><span class="chip">MySQL</span><span class="chip">Hive</span><span class="chip">SQflite</span><span class="chip">Firebase Auth</span></div>
      </div>
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-sitemap"></i> State Management</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">App Architecture</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">Patterns and tools for scalable app state.</p>
        <div class="tag-row"><span class="chip filled">GetX</span><span class="chip filled">Riverpod</span><span class="chip">Provider</span><span class="chip">MVC</span><span class="chip">MVVM</span></div>
      </div>
      <div class="skill-card">
        <div class="acard-label"><i class="fa-solid fa-toolbox"></i> Tools</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">Dev Tools</h3>
        <p style="font-size:13.5px;color:var(--text2);margin-bottom:14px">Version control, HTTP clients &amp; build tools.</p>
        <div class="tag-row"><span class="chip filled"><i class="fa-brands fa-git-alt"></i> Git</span><span class="chip"><i class="fa-brands fa-github"></i> GitHub</span><span class="chip">Bitbucket</span><span class="chip">Maven</span><span class="chip">Dio</span></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ EXPERIENCE ═══ -->
<div class="page" id="page-experience">
  <div class="section" style="padding-top:60px">
    <div class="section-header">
      <div class="section-label">Career</div>
      <h2 class="section-title">Work <em>Experience</em></h2>
      <p class="section-sub">Professional roles where I've shipped production Flutter apps and backend solutions.</p>
    </div>

    <div class="exp-timeline">
      <?php
      $experiences = $db->query("SELECT * FROM experience ORDER BY sort_order ASC, id DESC")->fetchAll();
      foreach ($experiences as $exp):
          $isCurrentClass = $exp['is_current'] ? 'current' : '';
          $typeLabel = $exp['type'] === 'fulltime' ? 'Full-Time' : ($exp['type'] === 'intern' ? 'Internship' : htmlspecialchars($exp['type']));
          $typeClass = htmlspecialchars($exp['type']);
          $statusIcon = $exp['type'] === 'fulltime' ? '<i class="fa-solid fa-circle-dot"></i>' : '<i class="fa-solid fa-seedling"></i>';
      ?>
      <div class="exp-entry <?php echo $isCurrentClass; ?>">
        <div class="exp-card <?php echo $isCurrentClass; ?>">
          <div class="exp-meta">
            <div class="exp-period"><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($exp['period']); ?></div>
            <div class="exp-company"><?php echo htmlspecialchars($exp['company']); ?></div>
            <span class="exp-type-chip <?php echo $typeClass; ?>"><?php echo $statusIcon; ?> <?php echo $typeLabel; ?></span>
          </div>
          <div class="exp-content">
            <div class="exp-role-title"><?php echo htmlspecialchars($exp['role']); ?></div>
            <p class="exp-desc"><?php echo htmlspecialchars($exp['description']); ?></p>
            <?php if ($exp['is_current']): ?>
            <div class="exp-current-badge">
              <span class="dot"></span>
              Current Role
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ═══ PROJECTS ═══ -->
<div class="page" id="page-projects">
  <div class="section" style="padding-top:60px">
    <div class="section-header">
      <div class="section-label">Portfolio</div>
      <h2 class="section-title">Things I've <em>built</em></h2>
      <p class="section-sub">Real-world Flutter applications — from concept to production.</p>
    </div>
    <div class="filter-bar" id="filterBar">
      <button class="filter-btn active" data-filter="all"><i class="fa-solid fa-border-all"></i> All Projects</button>
      <button class="filter-btn" data-filter="published"><i class="fa-brands fa-google-play"></i> Published</button>
      <button class="filter-btn" data-filter="dev"><i class="fa-solid fa-hammer"></i> In Development</button>
    </div>
    <div class="projects-grid" id="projectsGrid"></div>
  </div>
</div>

<!-- ═══ CONTACT ═══ -->
<div class="page" id="page-contact">
  <div class="section" style="padding-top:60px">
    <div class="section-header">
      <div class="section-label">Get In Touch</div>
      <h2 class="section-title">Let's build <em>together</em></h2>
      <p class="section-sub">Open to freelance, full-time roles, or exciting collaborations.</p>
    </div>
    <div class="contact-grid">
      <div class="contact-card">
        <div class="acard-label"><i class="fa-solid fa-address-card"></i> Contact Info</div>
        <h3 style="font-size:20px;font-weight:700;margin-bottom:20px">Reach Out</h3>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="fa-solid fa-envelope"></i></div>
          <div><div class="ci-label">Email</div><div class="ci-val" style="font-size:13px"><?php echo htmlspecialchars($about['contact_email']); ?></div></div>
        </div>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="fa-solid fa-phone"></i></div>
          <div><div class="ci-label">Phone</div><div class="ci-val"><?php echo htmlspecialchars($about['contact_phone']); ?></div></div>
        </div>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div><div class="ci-label">Location</div><div class="ci-val"><?php echo htmlspecialchars($about['contact_location']); ?></div></div>
        </div>
        <div class="social-links-row">
          <a href="<?php echo htmlspecialchars($about['linkedin_url']); ?>" class="sl-btn" target="_blank"><i class="fa-brands fa-linkedin"></i> LinkedIn</a>
          <a href="<?php echo htmlspecialchars($about['github_url']); ?>" class="sl-btn" target="_blank"><i class="fa-brands fa-github"></i> GitHub</a>
        </div>
      </div>
      <div class="contact-card">
        <div class="acard-label"><i class="fa-solid fa-message"></i> Send a Message</div>
        <h3 style="font-size:20px;font-weight:700;margin-bottom:20px">Write to me</h3>
        <div class="form-group">
          <i class="fa-solid fa-user form-icon"></i>
          <input type="text" id="cName" placeholder="Your Name" required />
        </div>
        <div class="form-group">
          <i class="fa-solid fa-envelope form-icon"></i>
          <input type="email" id="cEmail" placeholder="Your Email" required />
        </div>
        <div class="form-group">
          <i class="fa-solid fa-message form-icon" style="top:18px;transform:none"></i>
          <textarea id="cMessage" rows="5" placeholder="Tell me about your project or opportunity..." required></textarea>
        </div>
        <button type="button" class="send-btn" onclick="submitContact()"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        <div class="form-success" id="formSuccess"><i class="fa-solid fa-circle-check"></i> Message sent — I'll get back to you soon.</div>
      </div>
    </div>
  </div>
</div>

</div><!-- /pages -->

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-left">
      <div class="nav-logo-box" style="width:30px;height:30px;font-size:11px">AK</div>
      <span>Designed &amp; built by <strong><?php echo htmlspecialchars($about['hero_name']); ?></strong></span>
    </div>
    <div class="footer-right">
      <span><i class="fa-solid fa-mobile-screen-button"></i> <?php echo htmlspecialchars($about['stack_text']); ?> Developer</span>
      <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($about['contact_location']); ?></span>
      <span>© <?php echo date('Y'); ?></span>
    </div>
  </div>
</footer>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal" id="modal">
    <div class="modal-accent-line"></div>
    <div class="modal-header">
      <div class="modal-header-left">
        <div class="modal-icon-wrap" id="modalIcon"></div>
        <div>
          <div class="modal-title" id="modalName"></div>
          <div class="modal-sub" id="modalRole"></div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-status" id="modalStatus"></div>
      <div class="ss-label">App Screenshots</div>
      <div class="ss-scroll" id="modalScreenshots"></div>
      <div class="m-section-label">About this app</div>
      <div class="modal-desc" id="modalDesc"></div>
      <div class="detail-grid2" id="modalDetails"></div>
      <div class="m-section-label">Tech Stack</div>
      <div class="tech-tags" id="modalTech"></div>
      <div id="modalStore"></div>
    </div>
  </div>
</div>

<!-- LIGHTBOX -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="if(event.target===this)closeLightbox()">
  <button class="lb-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
  <button class="lb-nav prev" onclick="lightboxPrev()"><i class="fa-solid fa-chevron-left"></i></button>
  <div class="lb-img-wrap"><img id="lightboxImg" src="" alt="Screenshot"/></div>
  <button class="lb-nav next" onclick="lightboxNext()"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="lb-counter" id="lightboxCounter">1 / 3</div>
</div>

<script src="script.js"></script>
</body>
</html>
