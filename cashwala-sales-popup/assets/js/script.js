(function () {
    if (typeof window.CWSalesPopup === 'undefined') {
        return;
    }

    const cfg = window.CWSalesPopup;
    const container = document.createElement('div');
    container.id = 'cw-sales-popup-container';
    container.className = cfg.position === 'bottom-left' ? 'bottom-left' : 'bottom-right';
    document.body.appendChild(container);

    const audio = cfg.sound_enabled && cfg.sound_url ? new Audio(cfg.sound_url) : null;
    let activePopups = [];

    const withJitter = (base) => {
        if (!cfg.randomized_timing) {
            return base;
        }
        const delta = Math.floor(Math.random() * (cfg.random_variation + 1));
        return Math.max(1200, base + (Math.random() > 0.5 ? delta : -delta));
    };

    const trackClick = () => {
        const payload = new URLSearchParams({ action: 'cw_sales_popup_click', nonce: cfg.nonce });
        fetch(cfg.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
        }).catch(() => {});
    };

    const showPopup = (html) => {
        if (cfg.display_mode === 'single') {
            container.innerHTML = '';
            activePopups = [];
        } else if (activePopups.length >= cfg.max_popups) {
            const old = activePopups.shift();
            old.remove();
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const popup = wrapper.firstElementChild;
        if (!popup) {
            return;
        }

        popup.style.background = cfg.background_color;
        popup.style.color = cfg.text_color;
        popup.style.borderRadius = cfg.border_radius + 'px';
        popup.style.boxShadow = cfg.shadow;
        container.appendChild(popup);
        activePopups.push(popup);

        requestAnimationFrame(() => popup.classList.add('is-visible'));

        popup.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', trackClick, { passive: true });
        });

        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(() => {});
        }

        setTimeout(() => {
            popup.classList.remove('is-visible');
            setTimeout(() => popup.remove(), 300);
            activePopups = activePopups.filter((node) => node !== popup);
        }, cfg.show_duration);
    };

    const fetchPopup = () => {
        const payload = new URLSearchParams({ action: 'cw_sales_popup_get', nonce: cfg.nonce });
        return fetch(cfg.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
        })
            .then((res) => res.json())
            .then((res) => {
                if (res && res.success && res.data && res.data.html) {
                    showPopup(res.data.html);
                    return true;
                }
                return false;
            })
            .catch(() => false);
    };

    const loop = () => {
        fetchPopup().finally(() => {
            if (cfg.loop_enabled) {
                setTimeout(loop, withJitter(cfg.interval));
            }
        });
    };

    setTimeout(loop, cfg.initial_delay);
})();
