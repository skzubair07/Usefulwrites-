(function () {
  'use strict';

  if (typeof cwcrData === 'undefined') return;

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  const post = (action, payload = {}) => {
    const form = new FormData();
    form.append('action', action);
    form.append('nonce', cwcrData.nonce);
    Object.keys(payload).forEach((k) => form.append(k, payload[k] || ''));

    return fetch(cwcrData.ajaxUrl, { method: 'POST', body: form }).then((r) => r.json());
  };

  const initBlock = (root) => {
    if (!root) return;

    const leadForm = $('.cwcr-lead-form', root);
    const revealBtn = $('.cwcr-reveal-btn', root);
    const result = $('.cwcr-reveal-result', root);
    const after = $('.cwcr-after-text', root);
    const codeEl = $('.cwcr-coupon-code', root);
    const copyBtn = $('.cwcr-copy-btn', root);
    const copyMsg = $('.cwcr-copy-msg', root);
    const countdown = $('.cwcr-countdown', root);

    let currentCoupon = '';

    const renderCountdown = (seconds) => {
      let remaining = seconds;
      const tick = () => {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        countdown.textContent = `Offer expires in ${m}:${String(s).padStart(2, '0')}`;
        remaining -= 1;
        if (remaining < 0) {
          countdown.textContent = 'Coupon expired. Refresh to get a new code.';
          clearInterval(timer);
        }
      };
      tick();
      const timer = setInterval(tick, 1000);
    };

    const showCoupon = (data) => {
      result.hidden = false;
      after.textContent = data.messageAfter || '';
      codeEl.textContent = data.coupon || '';
      currentCoupon = data.coupon || '';
      if (data.expiresIn) renderCountdown(Number(data.expiresIn));
    };

    const submitLead = (payload = {}) =>
      post('cwcr_submit_lead', payload).then((res) => {
        if (!res.success) throw new Error((res.data && res.data.message) || 'Submission failed');
        showCoupon(res.data);
      });

    if (leadForm) {
      leadForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(leadForm);
        submitLead({
          email: formData.get('email') || '',
          phone: formData.get('phone') || '',
        }).catch((err) => {
          alert(err.message);
        });
      });
    }

    if (revealBtn) {
      revealBtn.addEventListener('click', () => {
        if (cwcrData.revealAction === 'email') {
          if (leadForm) {
            leadForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
          }
        }

        if (cwcrData.revealAction === 'timer') {
          revealBtn.disabled = true;
          let wait = 5;
          const old = revealBtn.textContent;
          revealBtn.textContent = `Please wait ${wait}s`;
          const interval = setInterval(() => {
            wait -= 1;
            revealBtn.textContent = wait > 0 ? `Please wait ${wait}s` : 'Unlocking...';
            if (wait <= 0) {
              clearInterval(interval);
              submitLead({}).catch((err) => alert(err.message));
              revealBtn.textContent = old;
              revealBtn.disabled = false;
            }
          }, 1000);
          return;
        }

        submitLead({}).catch((err) => alert(err.message));
      });
    }

    if (copyBtn) {
      copyBtn.addEventListener('click', async () => {
        if (!currentCoupon) return;
        try {
          await navigator.clipboard.writeText(currentCoupon);
          copyMsg.textContent = cwcrData.messages.copied;
        } catch (e) {
          copyMsg.textContent = cwcrData.messages.failure;
        }
      });
    }
  };

  const popup = $('#cwcr-popup-overlay');
  if (popup && cwcrData.context === 'popup') {
    const closeBtn = $('.cwcr-close', popup);

    const openPopup = () => {
      popup.classList.add('is-open');
      popup.setAttribute('aria-hidden', 'false');
    };

    const closePopup = () => {
      popup.classList.remove('is-open');
      popup.setAttribute('aria-hidden', 'true');
    };

    if (closeBtn) closeBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', (e) => {
      if (e.target === popup) closePopup();
    });

    if (cwcrData.triggerType === 'page_load') {
      openPopup();
    } else if (cwcrData.triggerType === 'delay') {
      setTimeout(openPopup, Number(cwcrData.triggerDelay || 0) * 1000);
    } else if (cwcrData.triggerType === 'scroll') {
      const scrollHandler = () => {
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0;
        if (progress >= Number(cwcrData.triggerScroll || 50)) {
          openPopup();
          window.removeEventListener('scroll', scrollHandler);
        }
      };
      window.addEventListener('scroll', scrollHandler, { passive: true });
    } else if (cwcrData.triggerType === 'exit_intent' || cwcrData.exitIntent) {
      const exitHandler = (e) => {
        if (e.clientY <= 5) {
          openPopup();
          document.removeEventListener('mouseout', exitHandler);
        }
      };
      document.addEventListener('mouseout', exitHandler);
    }

    initBlock(popup);
  }

  $$('.cwcr-inline').forEach(initBlock);
})();
