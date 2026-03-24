(function ($) {
  'use strict';

  const overlay = document.getElementById('lmp-overlay');
  if (!overlay || !window.lmpPopupData) {
    return;
  }

  const popup = document.getElementById('lmp-popup');
  const closeBtn = overlay.querySelector('.lmp-close');
  const form = document.getElementById('lmp-form');
  const responseBox = overlay.querySelector('.lmp-response');
  const storageKey = 'lmp_popup_seen';
  const shown = localStorage.getItem(storageKey);
  let hasTrackedImpression = false;

  function trackImpression() {
    if (hasTrackedImpression) return;
    hasTrackedImpression = true;

    $.post(lmpPopupData.ajax_url, {
      action: 'lmp_track_impression',
      nonce: lmpPopupData.nonce
    });
  }

  function showPopup() {
    if (lmpPopupData.show_once_session && shown) {
      return;
    }

    overlay.classList.add('active');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('lmp-modal-open');
    trackImpression();

    if (lmpPopupData.show_once_session) {
      localStorage.setItem(storageKey, '1');
    }
  }

  function closePopup() {
    overlay.classList.remove('active');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('lmp-modal-open');
  }

  function bindTriggers() {
    switch (lmpPopupData.trigger_type) {
      case 'exit_intent':
        document.addEventListener('mouseout', function (event) {
          if (event.clientY <= 8) {
            showPopup();
          }
        }, { once: true });
        break;
      case 'scroll':
        window.addEventListener('scroll', function onScroll() {
          const doc = document.documentElement;
          const scrollTop = window.pageYOffset || doc.scrollTop;
          const trackLength = doc.scrollHeight - doc.clientHeight;
          if (!trackLength) return;
          const percent = Math.round((scrollTop / trackLength) * 100);

          if (percent >= parseInt(lmpPopupData.scroll_percent, 10)) {
            showPopup();
            window.removeEventListener('scroll', onScroll);
          }
        });
        break;
      case 'time_delay':
      default:
        setTimeout(showPopup, parseInt(lmpPopupData.delay_seconds, 10) * 1000);
        break;
    }
  }

  function setResponse(message, isError) {
    responseBox.textContent = message;
    responseBox.classList.toggle('error', !!isError);
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closePopup);
  }

  overlay.addEventListener('click', function (event) {
    if (event.target === overlay) {
      closePopup();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closePopup();
    }
  });

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(form);
      formData.append('action', 'lmp_save_lead');
      formData.append('nonce', lmpPopupData.nonce);

      setResponse('Submitting...', false);

      $.ajax({
        url: lmpPopupData.ajax_url,
        method: 'POST',
        data: Object.fromEntries(formData.entries()),
        success: function (res) {
          if (res.success) {
            setResponse(res.data.message || 'Submitted successfully.', false);
            form.reset();
            if (res.data.redirect_url) {
              window.location.href = res.data.redirect_url;
              return;
            }
            setTimeout(closePopup, 1200);
          } else {
            setResponse((res.data && res.data.message) || 'Unable to submit. Try again.', true);
          }
        },
        error: function (xhr) {
          const response = xhr.responseJSON;
          setResponse((response && response.data && response.data.message) || 'Unexpected error occurred.', true);
        }
      });
    });
  }

  bindTriggers();
})(jQuery);
