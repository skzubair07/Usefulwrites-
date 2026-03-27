(function () {
  'use strict';

  var body = document.body;

  function debounce(fn, delay) {
    var timer;
    return function () {
      var context = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }

  function initDarkMode() {
    var toggle = document.querySelector('[data-dark-toggle]');
    if (!toggle || typeof cashwalaTheme === 'undefined' || !cashwalaTheme.darkModeEnabled) {
      return;
    }

    var stored = localStorage.getItem('cashwala_dark_mode');
    if (stored === '1') {
      body.classList.add('cashwala-dark-mode');
    }

    toggle.addEventListener('click', function () {
      body.classList.toggle('cashwala-dark-mode');
      localStorage.setItem('cashwala_dark_mode', body.classList.contains('cashwala-dark-mode') ? '1' : '0');
    });
  }

  function initLiveSearch() {
    var input = document.querySelector('[data-live-search-input]');
    var resultWrap = document.querySelector('[data-live-search-results]');
    var loader = document.querySelector('[data-search-loader]');

    if (!input || !resultWrap || typeof cashwalaTheme === 'undefined') {
      return;
    }

    var runSearch = debounce(function () {
      var term = input.value.trim();

      if (term.length < 1) {
        resultWrap.innerHTML = '';
        if (loader) {
          loader.hidden = true;
        }
        return;
      }

      if (loader) {
        loader.hidden = false;
      }

      var payload = new URLSearchParams();
      payload.append('action', 'cashwala_live_search');
      payload.append('nonce', cashwalaTheme.nonce);
      payload.append('term', term);

      fetch(cashwalaTheme.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: payload.toString()
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data && data.success && typeof data.data === 'string') {
            resultWrap.innerHTML = data.data;
            return;
          }
          resultWrap.innerHTML = '<p class="cashwala-no-result">No results found</p>';
        })
        .catch(function () {
          resultWrap.innerHTML = '<p class="cashwala-no-result">No results found</p>';
        })
        .finally(function () {
          if (loader) {
            loader.hidden = true;
          }
        });
    }, 300);

    input.addEventListener('input', runSearch);
  }

  function initRepeatableLinks() {
    var wrappers = document.querySelectorAll('.cashwala-repeatable');
    if (!wrappers.length) {
      return;
    }

    wrappers.forEach(function (wrapper) {
      var list = wrapper.querySelector('.cashwala-repeatable-list');
      var addButton = wrapper.querySelector('.cashwala-add-row');
      var fieldKey = wrapper.getAttribute('data-field-key');

      function reIndexRows() {
        var rows = list.querySelectorAll('.cashwala-repeatable-row');
        rows.forEach(function (row, index) {
          var textInput = row.querySelector('input[type="text"]');
          var urlInput = row.querySelector('input[type="url"]');
          if (textInput) {
            textInput.name = 'cashwala_theme_options[' + fieldKey + '][' + index + '][text]';
          }
          if (urlInput) {
            urlInput.name = 'cashwala_theme_options[' + fieldKey + '][' + index + '][url]';
          }
        });
      }

      if (addButton) {
        addButton.addEventListener('click', function () {
          var row = document.createElement('div');
          row.className = 'cashwala-repeatable-row';
          row.style.marginBottom = '8px';
          row.style.display = 'flex';
          row.style.gap = '8px';
          row.style.alignItems = 'center';
          row.style.maxWidth = '720px';
          row.innerHTML = '' +
            '<input type="text" class="regular-text" placeholder="Text">' +
            '<input type="url" class="regular-text" placeholder="https://">' +
            '<button type="button" class="button-link-delete cashwala-remove-row">Remove</button>';
          list.appendChild(row);
          reIndexRows();
        });
      }

      list.addEventListener('click', function (event) {
        if (event.target && event.target.classList.contains('cashwala-remove-row')) {
          var rows = list.querySelectorAll('.cashwala-repeatable-row');
          if (rows.length > 1) {
            event.target.closest('.cashwala-repeatable-row').remove();
            reIndexRows();
          }
        }
      });

      reIndexRows();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initLiveSearch();
    initRepeatableLinks();
  });
})();
