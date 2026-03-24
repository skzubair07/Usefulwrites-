(function() {
  'use strict';

  const bar = document.getElementById('cw-sticky-bar');
  if (!bar || typeof cwStickyBar === 'undefined') {
    return;
  }

  const cfg = cwStickyBar;
  const messageEl = bar.querySelector('[data-role="cw-message"]');
  const countdownEl = bar.querySelector('[data-role="cw-countdown"]');
  let lastScrollY = window.scrollY;
  let hasShown = false;

  function setCookie(name, value, minutes) {
    const expires = new Date(Date.now() + minutes * 60000).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Lax`;
  }

  function getCookie(name) {
    return document.cookie.split('; ').reduce((acc, item) => {
      const [k, v] = item.split('=');
      return k === name ? v : acc;
    }, null);
  }

  function showBar() {
    if (hasShown) return;
    if (cfg.closeEnabled && getCookie('cw_sb_closed') === '1') return;
    bar.classList.add('is-visible');
    hasShown = true;
  }

  function setupTrigger() {
    if (cfg.showTrigger === 'delay') {
      window.setTimeout(showBar, Math.max(0, cfg.showDelay) * 1000);
      return;
    }

    function onScrollTrigger() {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = docHeight <= 0 ? 0 : (scrollTop / docHeight) * 100;
      if (progress >= cfg.showScrollPercent) {
        showBar();
        window.removeEventListener('scroll', onScrollTrigger);
      }
    }

    window.addEventListener('scroll', onScrollTrigger, { passive: true });
    onScrollTrigger();
  }

  function setupHideOnScroll() {
    if (!cfg.hideOnScroll) return;

    window.addEventListener('scroll', function() {
      if (!bar.classList.contains('is-visible')) {
        return;
      }

      const currentY = window.scrollY;
      if (currentY > lastScrollY + 8) {
        bar.classList.add('is-hidden-scroll');
      } else if (currentY < lastScrollY - 8) {
        bar.classList.remove('is-hidden-scroll');
      }
      lastScrollY = currentY;
    }, { passive: true });
  }

  function setupMessageRotation() {
    if (!Array.isArray(cfg.messages) || cfg.messages.length <= 1 || !messageEl) return;

    let index = 0;
    window.setInterval(function() {
      index = (index + 1) % cfg.messages.length;
      messageEl.textContent = cfg.messages[index];
    }, Math.max(1, cfg.rotationSpeed) * 1000);
  }

  function setupCountdown() {
    if (!cfg.countdownEnabled || !countdownEl || cfg.timerDuration <= 0) return;

    let remaining = cfg.timerDuration;
    const timer = window.setInterval(function() {
      if (remaining <= 0) {
        countdownEl.textContent = 'Offer ended';
        window.clearInterval(timer);
        return;
      }
      const h = Math.floor(remaining / 3600);
      const m = Math.floor((remaining % 3600) / 60);
      const s = remaining % 60;
      countdownEl.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
      remaining--;
    }, 1000);
  }

  function setupClose() {
    const closeBtn = bar.querySelector('[data-role="cw-close"]');
    if (!closeBtn) return;

    closeBtn.addEventListener('click', function() {
      bar.classList.remove('is-visible');
      if (cfg.closeEnabled) {
        setCookie('cw_sb_closed', '1', Math.max(0, cfg.reappearAfter));
      }
    });
  }

  function trackClicks() {
    bar.querySelectorAll('[data-role="cw-track-click"]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const form = new FormData();
        form.append('action', 'cw_sb_track_click');
        form.append('nonce', cfg.nonce);
        form.append('button_text', btn.getAttribute('data-button-text') || '');

        fetch(cfg.ajaxUrl, {
          method: 'POST',
          body: form,
          credentials: 'same-origin'
        }).catch(function() {});
      });
    });
  }

  setupTrigger();
  setupHideOnScroll();
  setupMessageRotation();
  setupCountdown();
  setupClose();
  trackClicks();
})();
