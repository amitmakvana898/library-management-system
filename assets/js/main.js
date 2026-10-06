/**
 * Library Management System - Next-Gen Clean Luxury JS Engine
 * High-performance, butter-smooth micro-interactions, Command Palette (Ctrl+K),
 * Nova AI assistant, Voice Search, Universal Password Toggles, and 1-Click Demo Fill.
 */

// --- 1. Sound Synthesizer (Default Muted) ---
class LuxuryAudioEngine {
  constructor() {
    this.enabled = localStorage.getItem('lms_sound_fx') === 'true';
    this.audioCtx = null;
  }

  init() {
    if (!this.audioCtx && (window.AudioContext || window.webkitAudioContext)) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      this.audioCtx = new AudioCtx();
    }
  }

  playClick() {
    if (!this.enabled) return;
    this.init();
    if (!this.audioCtx) return;

    try {
      const osc = this.audioCtx.createOscillator();
      const gain = this.audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(600, this.audioCtx.currentTime);
      gain.gain.setValueAtTime(0.02, this.audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.04);

      osc.connect(gain);
      gain.connect(this.audioCtx.destination);
      osc.start();
      osc.stop(this.audioCtx.currentTime + 0.04);
    } catch (e) {}
  }

  toggle() {
    this.enabled = !this.enabled;
    localStorage.setItem('lms_sound_fx', this.enabled ? 'true' : 'false');
    return this.enabled;
  }
}

const luxuryAudio = new LuxuryAudioEngine();

// --- 2. Universal Command Palette (Ctrl + K) ---
function initCommandPalette() {
  const backdrop = document.getElementById('command-palette-backdrop');
  const input = document.getElementById('command-search-input');
  const list = document.getElementById('command-results-list');

  if (!backdrop || !input) return;

  function openPalette() {
    backdrop.classList.add('active');
    input.value = '';
    filterCommands('');
    setTimeout(() => input.focus(), 50);
  }

  function closePalette() {
    backdrop.classList.remove('active');
  }

  // Global Keyboard Shortcut (Ctrl+K or Cmd+K or ESC)
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      backdrop.classList.contains('active') ? closePalette() : openPalette();
    } else if (e.key === 'Escape' && backdrop.classList.contains('active')) {
      closePalette();
    }
  });

  // Universal Delegated Trigger Handler
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.trigger-command-palette, [data-trigger="command-palette"]');
    if (btn) {
      e.preventDefault();
      openPalette();
      return;
    }
    if (e.target === backdrop) {
      closePalette();
    }
  });

  function filterCommands(query) {
    if (!list) return;
    const items = list.querySelectorAll('.command-item');
    const q = query.toLowerCase().trim();
    items.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = (!q || text.includes(q)) ? 'flex' : 'none';
    });
  }

  input.addEventListener('input', (e) => {
    filterCommands(e.target.value);
  });
}

// --- 3. Web Speech Voice Search ---
function initVoiceSearch() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.voice-search-btn');
    if (!btn) return;

    e.preventDefault();
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    let target = btn.getAttribute('data-target-input') || btn.getAttribute('data-target') || 'input[name="q"]';
    let inputEl = document.getElementById(target) || document.querySelector(target) || document.querySelector('input[name="q"]');

    if (!SpeechRecognition) {
      showToast('Voice Search is not supported by your current browser. Please type keyword.', 'info');
      if (inputEl) inputEl.focus();
      return;
    }

    if (!inputEl) return;

    try {
      const recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';

      btn.classList.add('listening');
      showToast('Listening... Please speak your book title or author.', 'info');

      recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        inputEl.value = transcript;
        btn.classList.remove('listening');
        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
        inputEl.focus();
        showToast(`Searching for "${transcript}"...`, 'success');
      };

      recognition.onerror = () => {
        btn.classList.remove('listening');
        showToast('Microphone access denied or error. Please type search keyword.', 'warning');
      };

      recognition.onend = () => {
        btn.classList.remove('listening');
      };

      recognition.start();
    } catch (err) {
      btn.classList.remove('listening');
      showToast('Could not start microphone. Please type keyword directly.', 'warning');
    }
  });
}

// --- 5. Mobile Sidebar Drawer Controller ---
function initMobileSidebar() {
  const sidebar = document.querySelector('.dashboard-sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');

  document.addEventListener('click', (e) => {
    const toggleBtn = e.target.closest('#sidebar-toggle, .sidebar-toggle');
    if (toggleBtn) {
      e.preventDefault();
      if (sidebar) sidebar.classList.toggle('show');
      if (backdrop) backdrop.classList.toggle('show');
      return;
    }
    if (backdrop && e.target === backdrop) {
      if (sidebar) sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    }
  });
}

// --- 6. Universal Password Eye Toggles ---
function initPasswordToggles() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.toggle-password-visibility');
    if (!btn) return;

    e.preventDefault();
    let targetSelector = btn.getAttribute('data-target') || btn.getAttribute('data-target-input');
    if (!targetSelector) return;

    let input = null;
    if (targetSelector.startsWith('#') || targetSelector.startsWith('.')) {
      input = document.querySelector(targetSelector);
    } else {
      input = document.getElementById(targetSelector) || document.querySelector(targetSelector);
    }

    if (!input) return;

    const icon = btn.querySelector('i') || btn;
    if (input.type === 'password') {
      input.type = 'text';
      if (icon.classList.contains('fa-eye')) {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        icon.className = 'fa-solid fa-eye-slash text-primary';
      }
    } else {
      input.type = 'password';
      if (icon.classList.contains('fa-eye-slash')) {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      } else {
        icon.className = 'fa-solid fa-eye text-muted';
      }
    }
  });
}

// --- 7. Quick Fill Demo Credentials Controller ---
function initDemoCredentialButtons() {
  document.addEventListener('click', (e) => {
    const studentBtn = e.target.closest('#quickFillStudent, .btn-demo-student');
    if (studentBtn) {
      e.preventDefault();
      const emailInput = document.getElementById('studentEmailInput') || document.getElementById('email') || document.querySelector('input[type="email"], input[name="email"]');
      const passInput = document.getElementById('studentPasswordInput') || document.getElementById('password') || document.querySelector('input[type="password"], input[name="password"]');
      if (emailInput) emailInput.value = 'amit@gmail.com';
      if (passInput) passInput.value = '111111';
      showToast('Student credentials auto-filled! (amit@gmail.com / 111111)', 'info');
      return;
    }

    const adminBtn = e.target.closest('#quickFillAdmin, .btn-demo-admin');
    if (adminBtn) {
      e.preventDefault();
      const emailInput = document.getElementById('adminEmailInput') || document.getElementById('email') || document.querySelector('input[type="email"], input[name="email"]');
      const passInput = document.getElementById('adminPasswordInput') || document.getElementById('password') || document.querySelector('input[type="password"], input[name="password"]');
      if (emailInput) emailInput.value = 'admin@gmail.com';
      if (passInput) passInput.value = 'admin123';
      showToast('Admin credentials auto-filled! (admin@gmail.com / admin123)', 'info');
      return;
    }
  });
}

// --- 8. Universal Theme Toggle & Sound Controls ---
function initThemeAndSound() {
  function updateThemeIcons() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    document.querySelectorAll('#theme-toggle, .theme-toggle').forEach(btn => {
      btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-warning"></i>' : '<i class="fa-solid fa-moon text-primary"></i>';
      btn.setAttribute('title', isDark ? 'Switch to Light Theme' : 'Switch to Dark Theme');
    });
  }
  updateThemeIcons();

  // Universal Delegated Theme Click Handler
  document.addEventListener('click', (e) => {
    const themeBtn = e.target.closest('#theme-toggle, .theme-toggle');
    if (themeBtn) {
      e.preventDefault();
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('lms_theme', newTheme);
      document.cookie = `lms_theme=${newTheme}; path=/; max-age=31536000; SameSite=Lax`;
      
      updateThemeIcons();
      showToast(`Switched to ${newTheme === 'dark' ? 'Dark 🌙' : 'Light ☀️'} theme!`, 'info');
      luxuryAudio.playClick();
      return;
    }

    const soundBtn = e.target.closest('#sound-toggle, .sound-toggle');
    if (soundBtn) {
      e.preventDefault();
      const enabled = luxuryAudio.toggle();
      soundBtn.innerHTML = enabled ? '<i class="fa-solid fa-volume-high text-primary"></i>' : '<i class="fa-solid fa-volume-xmark text-muted"></i>';
      showToast(enabled ? 'Sound Effects Enabled 🔊' : 'Sound Effects Muted 🔇', 'info');
      return;
    }
  });
}

// --- 9. Dynamic Live Count-Up Number Animation ---
function initNumberCounters() {
  const counterElements = document.querySelectorAll('.stat-card-modern h3, .stat-value, [data-counter], .counter-anim');
  
  counterElements.forEach(el => {
    // Avoid double counting
    if (el.dataset.animated === 'true') return;
    
    const rawText = el.textContent.trim();
    // Match prefix (like ₹, $, #), number (with decimals/commas), and suffix (like +, %, /mo)
    const match = rawText.match(/^([^\d]*)([\d,.]+)(.*)$/);
    if (!match) return;

    const prefix = match[1] || '';
    const numStr = match[2].replace(/,/g, '');
    const suffix = match[3] || '';
    const target = parseFloat(numStr);

    if (isNaN(target)) return;

    el.dataset.animated = 'true';
    const isFloat = numStr.includes('.');
    const decimals = isFloat ? numStr.split('.')[1].length : 0;
    const duration = 1200; // ms
    const startTime = performance.now();

    function updateCount(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Smooth easeOutCubic curve
      const easeProgress = 1 - Math.pow(1 - progress, 3);
      const currentVal = target * easeProgress;

      const formattedNumber = isFloat 
        ? currentVal.toFixed(decimals) 
        : Math.floor(currentVal).toLocaleString();

      el.textContent = `${prefix}${formattedNumber}${suffix}`;

      if (progress < 1) {
        requestAnimationFrame(updateCount);
      } else {
        el.textContent = rawText; // set exact original target
      }
    }

    // Trigger animation via IntersectionObserver when visible
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          requestAnimationFrame(updateCount);
          obs.unobserve(el);
        }
      });
    }, { threshold: 0.1 });

    observer.observe(el);
  });
}

// --- 10. Butter-Smooth Scroll & Stagger Reveal ---
function initScrollReveal() {
  const revealElements = document.querySelectorAll('.scroll-reveal, .book-card, .custom-card, .custom-table-card, .feature-card, .review-card');
  
  if (!revealElements.length) return;

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry, idx) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('is-revealed');
          entry.target.classList.add('anim-fade-up');
        }, (idx % 4) * 60);
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

  revealElements.forEach(el => {
    el.classList.add('scroll-reveal');
    observer.observe(el);
  });
}

// --- 11. Material Glow Wave Button Ripple ---
function initButtonRipples() {
  const rippleButtons = document.querySelectorAll('.btn-primary-custom, .btn-secondary-custom, .btn-emerald, .btn-emerald-custom, .btn-custom');

  rippleButtons.forEach(btn => {
    btn.addEventListener('click', function (e) {
      luxuryAudio.playClick();

      const rect = this.getBoundingClientRect();
      const circle = document.createElement('span');
      const diameter = Math.max(rect.width, rect.height);
      const radius = diameter / 2;

      circle.style.width = circle.style.height = `${diameter}px`;
      circle.style.left = `${e.clientX - rect.left - radius}px`;
      circle.style.top = `${e.clientY - rect.top - radius}px`;
      circle.classList.add('btn-ripple');

      const existingRipple = this.querySelector('.btn-ripple');
      if (existingRipple) {
        existingRipple.remove();
      }

      this.appendChild(circle);

      setTimeout(() => {
        circle.remove();
      }, 600);
    });
  });
}

// --- 12. Dashboard Grid Stagger Animations ---
function initStaggerAnimations() {
  const grids = document.querySelectorAll('.dashboard-content .row, .container .row');
  grids.forEach(grid => {
    const cards = grid.querySelectorAll('.col-lg-3, .col-md-6, .col-lg-4, .col-lg-6');
    cards.forEach((card, index) => {
      card.style.animationDelay = `${(index + 1) * 0.07}s`;
      card.classList.add('anim-fade-up');
    });
  });
}

// --- 13. High-Performance Cosmic Particle Canvas Engine ---
function initHeroParticleCanvas() {
  const canvas = document.getElementById('heroParticleCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height, particles = [];
  let mouse = { x: -1000, y: -1000, radius: 120 };

  function resize() {
    width = canvas.width = canvas.parentElement.offsetWidth;
    height = canvas.height = canvas.parentElement.offsetHeight;
  }

  window.addEventListener('resize', resize);
  resize();

  class Particle {
    constructor() {
      this.x = Math.random() * width;
      this.y = Math.random() * height;
      this.vx = (Math.random() - 0.5) * 0.8;
      this.vy = (Math.random() - 0.5) * 0.8;
      this.radius = Math.random() * 2 + 1;
      this.color = Math.random() > 0.4 ? '#6366f1' : '#06b6d4';
      this.alpha = Math.random() * 0.5 + 0.2;
    }

    update() {
      this.x += this.vx;
      this.y += this.vy;

      if (this.x < 0 || this.x > width) this.vx *= -1;
      if (this.y < 0 || this.y > height) this.vy *= -1;

      // Mouse repulsion/interaction
      const dx = mouse.x - this.x;
      const dy = mouse.y - this.y;
      const dist = Math.sqrt(dx * dx + dy * dy);

      if (dist < mouse.radius) {
        const force = (mouse.radius - dist) / mouse.radius;
        const maxMove = 2;
        this.x -= (dx / dist) * force * maxMove;
        this.y -= (dy / dist) * force * maxMove;
      }
    }

    draw() {
      ctx.save();
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.globalAlpha = this.alpha;
      ctx.shadowBlur = 8;
      ctx.shadowColor = this.color;
      ctx.fill();
      ctx.restore();
    }
  }

  const count = Math.min(Math.floor(width / 18), 75);
  particles = [];
  for (let i = 0; i < count; i++) {
    particles.push(new Particle());
  }

  window.addEventListener('mousemove', (e) => {
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
  });

  window.addEventListener('mouseleave', () => {
    mouse.x = -1000;
    mouse.y = -1000;
  });

  function animate() {
    ctx.clearRect(0, 0, width, height);

    // Draw connecting lines
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < 110) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = '#6366f1';
          ctx.globalAlpha = (1 - dist / 110) * 0.25;
          ctx.lineWidth = 0.75;
          ctx.stroke();
        }
      }
    }

    particles.forEach(p => {
      p.update();
      p.draw();
    });

    requestAnimationFrame(animate);
  }

  animate();
}

// --- 14. 3D Perspective Gyro/Mouse Tilt Engine ---
function init3DTiltEngine() {
  const tiltElements = document.querySelectorAll('.tilt-3d, .hero-hologram-card, .book-card-clean, .process-step-card');

  tiltElements.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = ((y - centerY) / centerY) * -9; // Max 9 deg
      const rotateY = ((x - centerX) / centerX) * 9;

      card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.025, 1.025, 1.025)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
    });
  });
}

// --- 15. Dynamic Headline Keyword Typewriter ---
function initDynamicTypewriter() {
  const typeTarget = document.getElementById('heroRotatingKeyword');
  if (!typeTarget) return;

  const words = ['Infinite Knowledge', 'Smart Researches', 'Global E-Books', 'Digital Library', 'Instant QR Loans'];
  let wordIdx = 0;
  let charIdx = 0;
  let isDeleting = false;

  function type() {
    const currentWord = words[wordIdx];

    if (isDeleting) {
      typeTarget.textContent = currentWord.substring(0, charIdx - 1);
      charIdx--;
    } else {
      typeTarget.textContent = currentWord.substring(0, charIdx + 1);
      charIdx++;
    }

    let typeSpeed = isDeleting ? 45 : 90;

    if (!isDeleting && charIdx === currentWord.length) {
      typeSpeed = 2200; // Pause at end
      isDeleting = true;
    } else if (isDeleting && charIdx === 0) {
      isDeleting = false;
      wordIdx = (wordIdx + 1) % words.length;
      typeSpeed = 400; // Pause before new word
    }

    setTimeout(type, typeSpeed);
  }

  type();
}

// --- 16. Universal Scroll Position Memory Engine ---
function initScrollMemory() {
  // Restore scroll position on page load
  const savedScroll = sessionStorage.getItem('lms_scroll_y');
  if (savedScroll !== null) {
    sessionStorage.removeItem('lms_scroll_y');
    const scrollPos = parseInt(savedScroll, 10);
    if (!isNaN(scrollPos) && scrollPos > 0) {
      window.scrollTo({ top: scrollPos, behavior: 'instant' });
      setTimeout(() => window.scrollTo({ top: scrollPos, behavior: 'instant' }), 40);
    }
  }

  // Auto-save scroll position before unloading or clicking action buttons
  window.addEventListener('beforeunload', () => {
    sessionStorage.setItem('lms_scroll_y', window.scrollY.toString());
  });

  document.addEventListener('click', (e) => {
    const link = e.target.closest('a, button');
    if (link && (link.getAttribute('href') || link.type === 'submit')) {
      sessionStorage.setItem('lms_scroll_y', window.scrollY.toString());
    }
  });
}

// --- 17. Luxury Floating Toast Notification System ---
function showToast(message, type = 'success') {
  let container = document.getElementById('lms-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'lms-toast-container';
    container.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:99999; display:flex; flex-direction:column; gap:10px; pointer-events:none;';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `lms-toast lms-toast-${type}`;
  toast.style.cssText = 'pointer-events:auto; min-width:300px; max-width:420px; padding:14px 18px; border-radius:12px; font-size:0.88rem; font-weight:600; display:flex; align-items:center; gap:12px; box-shadow:0 12px 32px rgba(0,0,0,0.35); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); opacity:0; transform:translateY(15px) scale(0.95); transition:all 0.3s cubic-bezier(0.16,1,0.3,1);';
  
  if (type === 'success') {
    toast.style.background = 'rgba(16, 185, 129, 0.95)';
    toast.style.color = '#ffffff';
    toast.style.border = '1px solid rgba(255,255,255,0.25)';
    toast.innerHTML = `<i class="fa-solid fa-circle-check fs-5"></i> <div class="flex-grow-1">${message}</div>`;
  } else if (type === 'danger' || type === 'error') {
    toast.style.background = 'rgba(239, 68, 68, 0.95)';
    toast.style.color = '#ffffff';
    toast.style.border = '1px solid rgba(255,255,255,0.25)';
    toast.innerHTML = `<i class="fa-solid fa-circle-xmark fs-5"></i> <div class="flex-grow-1">${message}</div>`;
  } else {
    toast.style.background = 'rgba(15, 23, 42, 0.95)';
    toast.style.color = '#f8fafc';
    toast.style.border = '1px solid rgba(99,102,241,0.4)';
    toast.innerHTML = `<i class="fa-solid fa-bell text-primary fs-5"></i> <div class="flex-grow-1">${message}</div>`;
  }

  container.appendChild(toast);
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0) scale(1)';
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(15px) scale(0.95)';
    setTimeout(() => toast.remove(), 350);
  }, 4000);
}

// --- 18. Smart Seamless AJAX Actions Engine ---
function initAjaxActions() {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.ajax-action-btn, [data-ajax="true"]');
    if (!btn) return;

    e.preventDefault();
    const href = btn.getAttribute('href');
    if (!href) return;

    const onclickAttr = btn.getAttribute('onclick');
    if (onclickAttr && !btn.dataset.confirmed) {
      const match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
      const promptText = match ? match[1] : 'Are you sure you want to proceed?';
      if (!confirm(promptText)) return;
    }

    const row = btn.closest('tr, .custom-card, .book-card-clean');
    const originalBtnHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    btn.style.pointerEvents = 'none';

    try {
      const separator = href.includes('?') ? '&' : '?';
      const response = await fetch(href + separator + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await response.json();

      if (data.success) {
        showToast(data.message || 'Action executed successfully!', 'success');
        luxuryAudio.playClick();

        if (row) {
          row.style.transition = 'all 0.4s ease';
          row.style.background = 'rgba(99, 102, 241, 0.15)';
          setTimeout(() => {
            row.style.background = '';
          }, 800);

          // Update Status Badge if present
          if (data.status === 'paid') {
            row.dataset.status = 'paid';
            const statusCell = row.querySelector('td:nth-child(6)');
            if (statusCell) statusCell.innerHTML = '<span class="badge-pill-success"><i class="fa-solid fa-circle-check"></i> Paid & Cleared</span>';
            const actionCell = row.querySelector('td.text-end, td:last-child');
            if (actionCell) {
              actionCell.innerHTML = `
                <div class="d-inline-flex gap-2 align-items-center">
                  <span class="badge-pill-success"><i class="fa-solid fa-check-double"></i> Settled</span>
                  <a href="print_receipt.php?id=${data.id || ''}" target="_blank" class="btn btn-sm btn-secondary-custom py-1 px-2 rounded-2" title="Print Return Receipt">
                    <i class="fa-solid fa-print"></i>
                  </a>
                </div>
              `;
            }
          } else if (data.status === 'waived') {
            row.dataset.status = 'waived';
            const statusCell = row.querySelector('td:nth-child(6)');
            if (statusCell) statusCell.innerHTML = '<span class="badge-pill-info"><i class="fa-solid fa-hand-holding-heart"></i> Waived</span>';
            const actionCell = row.querySelector('td.text-end, td:last-child');
            if (actionCell) {
              actionCell.innerHTML = '<span class="badge-pill-info"><i class="fa-solid fa-hand-holding-heart"></i> Exempted</span>';
            }
          }
        }
      } else {
        showToast(data.message || 'Action failed.', 'danger');
        btn.innerHTML = originalBtnHTML;
        btn.style.pointerEvents = 'auto';
      }
    } catch (err) {
      sessionStorage.setItem('lms_scroll_y', window.scrollY.toString());
      window.location.href = href;
    }
  });
}

// --- 16. Live Instant Auto-Suggest Search ---
function initLiveBookSearch() {
  const searchInputs = document.querySelectorAll('.live-search-input, #catalogSearchInput');
  
  searchInputs.forEach(input => {
    const parent = input.closest('.position-relative') || input.parentElement;
    let dropdown = parent.querySelector('.live-search-dropdown');
    if (!dropdown) {
      dropdown = document.createElement('div');
      dropdown.className = 'live-search-dropdown shadow-lg rounded-3 border d-none';
      parent.appendChild(dropdown);
    }

    let debounceTimer = null;

    input.addEventListener('input', () => {
      const q = input.value.trim();
      clearTimeout(debounceTimer);

      if (q.length < 1) {
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(async () => {
        try {
          const res = await fetch(`api_search_books.php?q=${encodeURIComponent(q)}`);
          const data = await res.json();

          if (data.status === 'success' && data.results && data.results.length > 0) {
            let html = '<div class="p-2 border-bottom small fw-bold text-muted d-flex justify-content-between align-items-center"><span>Matching Books</span><span class="badge bg-primary rounded-pill">' + data.results.length + '</span></div><div class="list-group list-group-flush">';
            data.results.forEach(b => {
              const isAvail = b.available_copies > 0;
              html += `
                <a href="${b.url}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-2 border-0">
                  <img src="${b.image}" alt="Book" class="rounded shadow-sm flex-shrink-0" style="width: 40px; height: 56px; object-fit: cover;">
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-bold text-truncate text-main small mb-0">${b.title}</div>
                    <small class="text-muted text-truncate d-block">${b.author} &bull; ${b.category}</small>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <span class="badge ${isAvail ? 'bg-success' : 'bg-danger'} bg-opacity-10 ${isAvail ? 'text-success' : 'text-danger'} px-2 py-0" style="font-size: 0.65rem;">
                        ${isAvail ? b.available_copies + ' Copies' : 'On Loan'}
                      </span>
                      ${b.has_ebook ? '<span class="badge bg-danger text-white px-2 py-0" style="font-size: 0.65rem;"><i class="fa-solid fa-file-pdf"></i> PDF</span>' : ''}
                    </div>
                  </div>
                  <i class="fa-solid fa-arrow-right text-muted small"></i>
                </a>
              `;
            });
            html += '</div>';
            dropdown.innerHTML = html;
            dropdown.classList.remove('d-none');
          } else {
            dropdown.innerHTML = `
              <div class="p-3 text-center text-muted small">
                <i class="fa-solid fa-book-skull me-1"></i> No matching titles found for "<strong>${q}</strong>"
              </div>
            `;
            dropdown.classList.remove('d-none');
          }
        } catch (e) {
          dropdown.classList.add('d-none');
        }
      }, 250);
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!parent.contains(e.target)) {
        dropdown.classList.add('d-none');
      }
    });

    // Close on ESC
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') dropdown.classList.add('d-none');
    });
  });
}

// --- 17. Notify Me / Waitlist Subscriptions ---
function initWaitlistButtons() {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-waitlist-toggle');
    if (!btn) return;

    e.preventDefault();
    const bookId = btn.dataset.bookId;
    if (!bookId) return;

    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...';
    btn.disabled = true;

    try {
      const formData = new FormData();
      formData.append('book_id', bookId);

      const res = await fetch('toggle_waitlist.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      if (data.status === 'added') {
        btn.className = 'btn btn-success py-2 px-3 btn-waitlist-toggle';
        btn.innerHTML = '<i class="fa-solid fa-bell me-1"></i> Alert Active (Subscribed)';
        showToast(data.message, 'success');
        luxuryAudio.playClick();
      } else if (data.status === 'removed') {
        btn.className = 'btn btn-outline-warning py-2 px-3 btn-waitlist-toggle';
        btn.innerHTML = '<i class="fa-solid fa-bell me-1"></i> Notify Me When Available';
        showToast(data.message, 'info');
      } else {
        showToast(data.message || 'Action failed', 'danger');
        btn.innerHTML = originalHTML;
      }
    } catch (err) {
      showToast('Network error while updating alert.', 'danger');
      btn.innerHTML = originalHTML;
    } finally {
      btn.disabled = false;
    }
  });
}

// --- 18. Online Fine Payment Simulator ---
function initFinePayment() {
  const fineForm = document.getElementById('finePaymentForm');
  if (!fineForm) return;

  fineForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmFinePay');
    const originalText = btn ? btn.innerHTML : 'Pay';
    
    if (btn) {
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing Payment via Gateway...';
      btn.disabled = true;
    }

    try {
      const formData = new FormData(fineForm);
      formData.append('ajax', '1');

      const res = await fetch('pay_fine.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();

      if (data.status === 'success') {
        if (btn) btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Success!';
        showToast(data.message || 'Fine settled successfully!', 'success');
        luxuryAudio.playClick();
        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } else {
        showToast(data.message || 'Payment processing failed.', 'danger');
        if (btn) {
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      }
    } catch (err) {
      // Fallback normal submit
      fineForm.submit();
    }
  });
}

// --- 19. Quick Librarian Help Drawer / Modal ---
function initQuickHelp() {
  const form = document.getElementById('quickHelpForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn ? btn.innerHTML : 'Send';

    if (btn) {
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';
      btn.disabled = true;
    }

    try {
      const formData = new FormData(form);
      const res = await fetch('api_quick_help.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.status === 'success') {
        showToast(data.message, 'success');
        form.reset();
        const modalEl = document.getElementById('quickHelpModal');
        if (modalEl && window.bootstrap) {
          const m = bootstrap.Modal.getInstance(modalEl);
          if (m) m.hide();
        }
      } else {
        showToast(data.message || 'Failed to send query.', 'danger');
      }
    } catch (err) {
      showToast('Network error sending help request.', 'danger');
    } finally {
      if (btn) {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    }
  });
}

// --- DOM Ready Master Initialization ---
document.addEventListener('DOMContentLoaded', () => {
  initScrollMemory();
  initCommandPalette();
  initVoiceSearch();
  initMobileSidebar();
  initPasswordToggles();
  initDemoCredentialButtons();
  initThemeAndSound();
  initNumberCounters();
  initScrollReveal();
  initButtonRipples();
  initStaggerAnimations();
  initHeroParticleCanvas();
  init3DTiltEngine();
  initDynamicTypewriter();
  initAjaxActions();
  initLiveBookSearch();
  initWaitlistButtons();
  initFinePayment();
  initQuickHelp();
});



