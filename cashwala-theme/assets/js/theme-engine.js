(function () {
  'use strict';

  var d = document;

  function debounce(fn, delay) {
    var t;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(null, args);
      }, delay);
    };
  }

  function initScrollReveal() {
    var cards = d.querySelectorAll('.cw-card');
    if (!cards.length || !('IntersectionObserver' in window)) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    cards.forEach(function (card) {
      observer.observe(card);
    });
  }

  function initClickPhysics() {
    d.addEventListener('mousedown', function (e) {
      var btn = e.target.closest('.cw-buy-btn, .cw-submit, .cw-skin-btn');
      if (!btn) return;
      btn.style.transform = 'scale(0.96)';
    });

    d.addEventListener('mouseup', function () {
      d.querySelectorAll('.cw-buy-btn, .cw-submit, .cw-skin-btn').forEach(function (btn) {
        btn.style.transform = '';
      });
    });
  }

  function initLiveSearch() {
    var input = d.querySelector('[data-live-search-input]');
    var results = d.querySelector('[data-live-search-results]');
    if (!input || !results || typeof cashwalaEngine === 'undefined') return;

    var run = debounce(function () {
      var term = input.value.trim();
      if (term.length < 1) {
        results.innerHTML = '';
        return;
      }

      var payload = new URLSearchParams();
      payload.append('action', 'cashwala_live_search');
      payload.append('nonce', cashwalaEngine.nonce);
      payload.append('term', term);

      fetch(cashwalaEngine.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString()
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        results.innerHTML = data && data.success ? data.data : '<p>No product found.</p>';
      })
      .catch(function () {
        results.innerHTML = '<p>No product found.</p>';
      });
    }, 220);

    input.addEventListener('input', run);
  }

  function initSkinToggle() {
    var buttons = d.querySelectorAll('.cw-skin-btn');
    if (!buttons.length || typeof cashwalaEngine === 'undefined') return;

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var skin = btn.getAttribute('data-skin');
        if (!skin) return;

        var payload = new URLSearchParams();
        payload.append('action', 'cashwala_switch_skin');
        payload.append('nonce', cashwalaEngine.skinNonce);
        payload.append('skin', skin);

        fetch(cashwalaEngine.ajaxUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString()
        }).then(function () { window.location.reload(); });
      });
    });
  }

  function initLeadFlow() {
    var modal = d.getElementById('cw-lead-modal');
    var form = d.getElementById('cw-lead-form');
    var status = d.getElementById('cw-lead-status');
    var productInput = d.getElementById('cw-product-id');

    if (!modal || !form || !productInput || typeof cashwalaEngine === 'undefined') return;

    function openModal(productId) {
      productInput.value = productId;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    }

    d.addEventListener('click', function (e) {
      var buyBtn = e.target.closest('.cw-buy-btn');
      if (buyBtn) {
        e.preventDefault();
        openModal(buyBtn.getAttribute('data-product-id'));
      }

      if (e.target.matches('[data-close-modal]') || e.target === modal) {
        closeModal();
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      status.textContent = 'Saving your details...';

      var fd = new FormData(form);
      var payload = new URLSearchParams();
      payload.append('action', 'cashwala_capture_lead');
      payload.append('nonce', cashwalaEngine.nonce);
      payload.append('product_id', fd.get('product_id'));
      payload.append('name', fd.get('name'));
      payload.append('email', fd.get('email'));
      payload.append('whatsapp', fd.get('whatsapp'));

      fetch(cashwalaEngine.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString()
      })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (!resp.success || !resp.data.checkout_url) {
          throw new Error('Lead capture failed.');
        }

        status.textContent = cashwalaEngine.leadSuccessText;
        window.location.href = resp.data.checkout_url;
      })
      .catch(function () {
        status.textContent = 'Could not continue to payment. Please try again.';
      });
    });
  }

  d.addEventListener('DOMContentLoaded', function () {
    initScrollReveal();
    initClickPhysics();
    initLiveSearch();
    initSkinToggle();
    initLeadFlow();
  });
})();
