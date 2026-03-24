(function () {
  'use strict';

  const app = window.CashWalaSB || null;
  if (!app || !app.settings) {
    return;
  }

  const settings = app.settings;
  const root = document.getElementById('cw-sb-root');
  if (!root) {
    return;
  }

  const toArray = (str) => String(str || '')
    .split('\n')
    .map((item) => item.trim())
    .filter(Boolean);

  const randomFrom = (items) => items[Math.floor(Math.random() * items.length)];

  const track = (type) => {
    if (!app.ajaxUrl || !app.nonce) {
      return;
    }

    const body = new URLSearchParams();
    body.append('action', 'cw_sb_track');
    body.append('type', type);
    body.append('nonce', app.nonce);

    fetch(app.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body
    }).catch(() => null);
  };

  const applyColors = () => {
    const style = document.documentElement.style;
    style.setProperty('--cw-sb-primary', settings.primary_color || '#0f172a');
    style.setProperty('--cw-sb-accent', settings.accent_color || '#16a34a');
    style.setProperty('--cw-sb-text', settings.text_color || '#ffffff');
    style.setProperty('--cw-sb-bg', settings.background_color || '#111827');
  };

  const triggerGate = {
    delayed: false,
    scrolled: false,
    exit: false,
    canShow() {
      const required = [
        Number(settings.trigger_delay || 0) > 0 ? this.delayed : true,
        Number(settings.trigger_scroll || 0) > 0 ? this.scrolled : true,
        Number(settings.trigger_exit_intent || 0) === 1 ? this.exit : true
      ];

      return required.some(Boolean);
    }
  };

  setTimeout(() => {
    triggerGate.delayed = true;
  }, Math.max(0, Number(settings.trigger_delay || 0)) * 1000);

  const scrollTarget = Math.max(1, Number(settings.trigger_scroll || 25));
  window.addEventListener('scroll', () => {
    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    if (maxScroll <= 0) return;
    const progress = (window.scrollY / maxScroll) * 100;
    if (progress >= scrollTarget) {
      triggerGate.scrolled = true;
    }
  }, { passive: true });

  if (Number(settings.trigger_exit_intent || 0) === 1) {
    document.addEventListener('mouseleave', (event) => {
      if (event.clientY <= 0) {
        triggerGate.exit = true;
      }
    });
  }

  const initNotifications = () => {
    if (Number(settings.notifications_enabled || 0) !== 1) return;

    const wrapper = root.querySelector('[data-cw-sb-widget="notification"]');
    if (!wrapper) return;

    const names = toArray(settings.names);
    const cities = toArray(settings.cities);
    const products = toArray(settings.products);
    const variants = toArray(settings.message_variations);
    const msg = wrapper.querySelector('.cw-sb-message');
    if (!names.length || !cities.length || !products.length || !variants.length || !msg) return;

    const duration = Math.max(2, Number(settings.notifications_duration || 4)) * 1000;
    const loop = Math.max(3, Number(settings.notifications_interval || 8)) * 1000;

    const showNotification = () => {
      if (!triggerGate.canShow()) {
        return;
      }
      const text = `${randomFrom(names)} from ${randomFrom(cities)} ${randomFrom(variants)} ${randomFrom(products)}`;
      msg.textContent = text;
      wrapper.hidden = false;
      wrapper.classList.add('show');
      track('impression');

      window.setTimeout(() => {
        wrapper.classList.remove('show');
      }, duration - 350);

      window.setTimeout(() => {
        wrapper.hidden = true;
      }, duration);
    };

    showNotification();
    window.setInterval(showNotification, loop);
  };

  const initTimer = () => {
    if (Number(settings.timer_enabled || 0) !== 1) return;

    const timerEl = root.querySelector('[data-cw-sb-widget="timer"]');
    if (!timerEl) return;
    const clock = timerEl.querySelector('.cw-sb-timer-clock');
    if (!clock) return;

    const key = `cw_sb_timer_${app.pageId || 'global'}`;

    const format = (seconds) => {
      const safe = Math.max(0, seconds);
      const hours = Math.floor(safe / 3600);
      const mins = Math.floor((safe % 3600) / 60);
      const secs = safe % 60;
      return hours > 0
        ? `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
        : `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    let endTimestamp;
    if (settings.timer_type === 'fixed' && settings.fixed_end_datetime) {
      endTimestamp = Date.parse(settings.fixed_end_datetime + ':00Z');
    } else {
      const stored = Number(localStorage.getItem(key) || 0);
      if (stored > Date.now()) {
        endTimestamp = stored;
      } else {
        const duration = Math.max(60, Number(settings.timer_duration || 600)) * 1000;
        endTimestamp = Date.now() + duration;
        localStorage.setItem(key, String(endTimestamp));
      }
    }

    timerEl.hidden = false;
    const tick = () => {
      const rem = Math.floor((endTimestamp - Date.now()) / 1000);
      if (rem <= 0) {
        clock.textContent = '00:00';
        return;
      }
      clock.textContent = format(rem);
    };

    tick();
    window.setInterval(tick, 1000);
  };

  const initCounter = () => {
    const stack = root.querySelector('[data-cw-sb-widget="counter"]');
    if (!stack) return;

    const counterText = stack.querySelector('.cw-sb-counter-text');
    const stockText = stack.querySelector('.cw-sb-stock-text');
    const badges = stack.querySelector('[data-cw-sb-widget="badges"]');
    const showCounter = Number(settings.counter_enabled || 0) === 1;
    const showStock = Number(settings.low_stock_enabled || 0) === 1;

    if (!showCounter) {
      const counterNode = stack.querySelector('.cw-sb-counter');
      if (counterNode) counterNode.remove();
    }

    if (!showStock) {
      const stockNode = stack.querySelector('.cw-sb-stock');
      if (stockNode) stockNode.remove();
    }

    if (Number(settings.trust_badges_enabled || 0) === 1 && badges) {
      toArray(settings.trust_badges).forEach((badge) => {
        const li = document.createElement('li');
        li.textContent = `✓ ${badge}`;
        badges.appendChild(li);
      });
    } else if (badges) {
      badges.remove();
    }

    if (!showCounter && !showStock && (!badges || !badges.children.length)) return;

    const min = Math.max(1, Number(settings.counter_min || 10));
    const max = Math.max(min, Number(settings.counter_max || min + 10));
    const refresh = Math.max(3, Number(settings.counter_refresh || 12)) * 1000;

    const counterTick = () => {
      const count = Math.floor(Math.random() * (max - min + 1)) + min;
      if (counterText) {
        counterText.textContent = `${count} people are viewing this page`;
      }
    };

    let stockValue = Number(sessionStorage.getItem('cw_sb_stock') || 0);
    const stockMin = Math.max(1, Number(settings.low_stock_min || 3));
    const stockMax = Math.max(stockMin, Number(settings.low_stock_max || 10));

    const stockTick = () => {
      if (!stockText) return;
      if (settings.low_stock_mode === 'static') {
        stockValue = Math.max(1, Number(settings.low_stock_static || 5));
      } else if (!stockValue) {
        stockValue = Math.floor(Math.random() * (stockMax - stockMin + 1)) + stockMin;
      } else if (Number(settings.low_stock_autodec || 0) === 1) {
        stockValue = Math.max(stockMin, stockValue - (Math.random() > 0.6 ? 1 : 0));
      }
      sessionStorage.setItem('cw_sb_stock', String(stockValue));
      stockText.textContent = `Only ${stockValue} copies left`;
    };

    stack.hidden = false;
    counterTick();
    stockTick();
    window.setInterval(counterTick, refresh);
    window.setInterval(stockTick, refresh + 2000);
  };

  const initCta = () => {
    if (Number(settings.cta_enabled || 0) !== 1) return;

    const cta = root.querySelector('[data-cw-sb-widget="cta"]');
    if (!cta) return;

    const text = cta.querySelector('.cw-sb-cta-text');
    const button = cta.querySelector('.cw-sb-cta-btn');
    if (!text || !button) return;

    text.textContent = settings.cta_text || 'Get This Plugin Now - ₹99';
    button.textContent = settings.cta_button_text || 'Buy Now';

    button.addEventListener('click', () => {
      track('click');
      if (settings.cta_action === 'scroll') {
        const target = document.querySelector(settings.cta_scroll_target || '#buy-now');
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
      }

      const url = settings.cta_link || app.homeUrl || '/';
      window.location.href = url;
    });

    if (app.isMobile) {
      cta.hidden = false;
    } else {
      setTimeout(() => {
        if (triggerGate.canShow()) {
          cta.hidden = false;
        }
      }, 1200);
    }
  };

  applyColors();
  initNotifications();
  initTimer();
  initCounter();
  initCta();
})();
