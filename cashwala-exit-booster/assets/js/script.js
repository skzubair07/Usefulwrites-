(() => {
    'use strict';

    if (typeof window.CWExitBooster !== 'object') return;

    const cfg = window.CWExitBooster;
    const overlay = document.getElementById('cw-eib-overlay');
    if (!overlay) return;

    const popup = overlay.querySelector('.cw-eib-popup');
    const closeBtn = overlay.querySelector('.cw-eib-close');
    const form = overlay.querySelector('.cw-eib-form');
    const submitBtn = overlay.querySelector('.cw-eib-submit');
    const message = overlay.querySelector('.cw-eib-msg');
    const couponWrap = overlay.querySelector('.cw-eib-coupon-wrap');
    const couponCode = overlay.querySelector('.cw-eib-coupon-code');
    const copyBtn = overlay.querySelector('.cw-eib-copy');
    const waLink = overlay.querySelector('.cw-eib-wa-link');
    const countdownEl = overlay.querySelector('.cw-eib-countdown');

    const SKEY = 'cw_eib_seen';
    let popupShown = false;
    let inactivityTimer = null;
    let lastScrollY = window.scrollY;

    const trackEvent = (event) => {
        const body = new URLSearchParams();
        body.set('action', 'cw_eib_track_event');
        body.set('event', event);
        body.set('nonce', cfg.nonce);
        fetch(cfg.ajax_url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), credentials: 'same-origin' }).catch(() => {});
    };

    const shouldBlockByFrequency = () => cfg.settings.frequency === 'session_once' && sessionStorage.getItem(SKEY) === '1';

    const markSeen = () => {
        sessionStorage.setItem(SKEY, '1');
        const minutes = Number(cfg.settings.cookie_duration_minutes || 30);
        const expires = new Date(Date.now() + minutes * 60000).toUTCString();
        document.cookie = `cw_eib_seen=1; expires=${expires}; path=/; SameSite=Lax`;
    };

    const showPopup = () => {
        if (popupShown || shouldBlockByFrequency()) return;
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');
        popupShown = true;
        markSeen();
        trackEvent('views');
        startCountdown();
    };

    const hidePopup = () => {
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
    };

    const startCountdown = () => {
        if (!countdownEl) return;
        let remaining = Number(countdownEl.dataset.seconds || 0);
        if (remaining <= 0) {
            countdownEl.textContent = '';
            return;
        }
        const tick = () => {
            const m = Math.floor(remaining / 60).toString().padStart(2, '0');
            const s = (remaining % 60).toString().padStart(2, '0');
            countdownEl.textContent = `Offer expires in ${m}:${s}`;
            if (remaining <= 0) return;
            remaining -= 1;
            setTimeout(tick, 1000);
        };
        tick();
    };

    const revealCoupon = () => {
        if (couponWrap) couponWrap.hidden = false;
    };

    const handleVariant = () => {
        if (cfg.settings.popup_variant === 'whatsapp' && waLink) {
            waLink.hidden = false;
            waLink.href = cfg.whatsapp_url || '#';
            waLink.addEventListener('click', () => trackEvent('clicks'));
        }
    };

    const saveLead = async (payload) => {
        const body = new URLSearchParams();
        body.set('action', 'cw_eib_save_lead');
        body.set('nonce', cfg.nonce);
        body.set('source', cfg.settings.popup_variant);
        Object.entries(payload).forEach(([k, v]) => body.set(k, v));

        const resp = await fetch(cfg.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            credentials: 'same-origin'
        });
        return resp.json();
    };

    const bindForm = () => {
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            message.textContent = '';
            const fd = new FormData(form);
            const payload = {
                name: (fd.get('name') || '').toString().trim(),
                email: (fd.get('email') || '').toString().trim(),
                phone: (fd.get('phone') || '').toString().trim()
            };
            try {
                const data = await saveLead(payload);
                if (!data.success) {
                    message.textContent = data.data?.message || 'Submission failed.';
                    return;
                }
                message.textContent = 'Success! Your reward is unlocked.';
                trackEvent('clicks');
                revealCoupon();
                if (cfg.settings.popup_variant === 'whatsapp' && waLink) waLink.hidden = false;
            } catch (error) {
                message.textContent = 'Network error. Please try again.';
            } finally {
                submitBtn.disabled = false;
            }
        });
    };

    const bindCopy = () => {
        if (!copyBtn || !couponCode) return;
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(couponCode.textContent.trim());
                copyBtn.textContent = 'Copied!';
                setTimeout(() => { copyBtn.textContent = 'Copy Code'; }, 1500);
            } catch (e) {
                copyBtn.textContent = 'Copy failed';
            }
        });
    };

    const bindDesktopExit = () => {
        document.addEventListener('mouseout', (e) => {
            if (cfg.is_mobile) return;
            if (e.clientY <= 0) showPopup();
        }, { passive: true });
    };

    const bindMobileExitIntent = () => {
        if (!cfg.is_mobile) return;

        const resetInactivity = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => showPopup(), Number(cfg.settings.inactivity_seconds || 12) * 1000);
        };

        window.addEventListener('scroll', () => {
            const currentY = window.scrollY;
            if (currentY < lastScrollY && currentY > 80) showPopup();
            lastScrollY = currentY;
            resetInactivity();
        }, { passive: true });

        ['touchstart', 'touchmove', 'click'].forEach((evt) => {
            window.addEventListener(evt, resetInactivity, { passive: true });
        });

        resetInactivity();
    };

    const bindTriggerType = () => {
        const trigger = cfg.settings.trigger_type;

        if (cfg.is_test) {
            showPopup();
            return;
        }

        if (trigger === 'delay') {
            setTimeout(showPopup, Number(cfg.settings.delay_seconds || 5) * 1000);
            return;
        }

        if (trigger === 'scroll') {
            window.addEventListener('scroll', () => {
                const scrollTop = window.scrollY;
                const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                if (maxScroll <= 0) return;
                const percent = (scrollTop / maxScroll) * 100;
                if (percent >= Number(cfg.settings.scroll_percent || 50)) showPopup();
            }, { passive: true });
            return;
        }

        bindDesktopExit();
        bindMobileExitIntent();
    };

    closeBtn?.addEventListener('click', hidePopup);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) hidePopup();
    });

    bindForm();
    bindCopy();
    handleVariant();
    bindTriggerType();
})();
