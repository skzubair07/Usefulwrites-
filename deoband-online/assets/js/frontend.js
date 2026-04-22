/* Deoband Online app interactions - optimized mobile-first behaviors. */
jQuery(function ($) {
  var $search = $('#do-search');
  var $results = $('#do-search-results');
  var $loader = $('#doSearchLoading');
  var searchTimer = null;

  function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function highlight(text, query) {
    if (!query) return text;
    var safe = escapeRegExp(query.trim());
    if (!safe) return text;
    return String(text).replace(new RegExp('(' + safe + ')', 'ig'), '<mark>$1</mark>');
  }

  function renderSearch(res, query) {
    if (!res.success || !res.data.length) {
      $results.removeClass('show').empty();
      return;
    }

    var html = '';
    res.data.forEach(function (item) {
      html += '<div class="do-search-item" data-id="' + item.id + '">' +
        '<strong>' + highlight(item.question, query) + '</strong>' +
        '</div>';
    });

    $results.html(html).addClass('show');
  }

  $search.on('input', function () {
    var query = $(this).val();
    clearTimeout(searchTimer);

    if (query.length < 2) {
      $loader.removeClass('active');
      $results.removeClass('show').empty();
      return;
    }

    searchTimer = setTimeout(function () {
      $loader.addClass('active');
      $.post(doAjax.ajaxUrl, {
        action: 'do_smart_search',
        nonce: doAjax.nonce,
        query: query
      }).done(function (res) {
        renderSearch(res, query);
      }).always(function () {
        $loader.removeClass('active');
      });
    }, 180);
  });

  $(document).on('click', '.do-search-item', function () {
    var text = $(this).text().trim();
    $search.val(text);
    $results.removeClass('show').empty();
  });

  $(document).on('click', '.do-like-btn', function () {
    var $btn = $(this);
    $btn.addClass('is-loading');
    $.post(doAjax.ajaxUrl, {
      action: 'do_like_masail',
      nonce: doAjax.nonce,
      id: $btn.data('id')
    }).always(function () {
      $btn.removeClass('is-loading');
    });
  });

  $(document).on('click', '.do-masail-card', function (e) {
    if ($(e.target).closest('.do-btn, a, button').length) {
      return;
    }
    $.post(doAjax.ajaxUrl, {
      action: 'do_track_click',
      nonce: doAjax.nonce,
      masail_id: $(this).data('id')
    });
  });

  $(document).on('click', '.do-save-btn', function () {
    var id = String($(this).data('id'));
    var key = 'do_saved_masail';
    var list = [];

    try {
      list = JSON.parse(localStorage.getItem(key) || '[]');
    } catch (e) {
      list = [];
    }

    if (list.indexOf(id) === -1) {
      list.push(id);
      localStorage.setItem(key, JSON.stringify(list));
      $(this).text('✓ Saved');
    } else {
      $(this).text('★ Save');
    }
  });

  $('.do-btn').on('click', function () {
    $(this).addClass('clicked');
    var $el = $(this);
    setTimeout(function () { $el.removeClass('clicked'); }, 180);
  });
});
