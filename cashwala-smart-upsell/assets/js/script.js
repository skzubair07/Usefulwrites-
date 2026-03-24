(function () {
  "use strict";

  function setSessionShown() {
    document.cookie = "cw_upsell_shown=1; path=/";
  }

  function isSessionShown() {
    return document.cookie.indexOf("cw_upsell_shown=1") !== -1;
  }

  function track(type) {
    if (!window.cwUpsellData || !window.fetch) {
      return;
    }

    var body = new URLSearchParams();
    body.append("action", "cw_upsell_track");
    body.append("nonce", cwUpsellData.nonce);
    body.append("type", type);

    fetch(cwUpsellData.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
      credentials: "same-origin"
    }).catch(function () {});
  }

  function attachTracking(scope) {
    scope.querySelectorAll("[data-cw-track]").forEach(function (el) {
      el.addEventListener("click", function () {
        var type = el.getAttribute("data-cw-track");
        track(type);
        if (type === "skip") {
          document.getElementById("cw-upsell-modal")?.classList.remove("cw-visible");
        }
      });
    });
  }

  function showByTrigger(modal) {
    if (!modal || !window.cwUpsellData) {
      return;
    }

    if (cwUpsellData.behavior === "once" && isSessionShown()) {
      return;
    }

    var canShow = false;
    if (cwUpsellData.triggerEvent === "buy_now") {
      canShow = document.querySelector(".cw-upsell-buy-now-marker") !== null;
    } else if (cwUpsellData.triggerEvent === "add_to_cart") {
      canShow = document.querySelector("form.cart") !== null;
    } else if (cwUpsellData.triggerEvent === "checkout") {
      canShow = document.querySelector(".cw-upsell-checkout-trigger") !== null;
    } else if (cwUpsellData.triggerEvent === "custom_hook") {
      canShow = document.querySelector(".cw-upsell-custom-trigger") !== null;
    }

    if (!canShow) {
      return;
    }

    setTimeout(function () {
      modal.classList.add("cw-visible");
      track("view");
      setSessionShown();
    }, parseInt(cwUpsellData.delay, 10) || 0);
  }

  document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("cw-upsell-modal");
    var inline = document.getElementById("cw-upsell-inline");

    if (modal) {
      attachTracking(modal);
      modal.querySelector(".cw-close")?.addEventListener("click", function () {
        modal.classList.remove("cw-visible");
      });
      modal.querySelector(".cw-upsell-backdrop")?.addEventListener("click", function () {
        modal.classList.remove("cw-visible");
      });
      showByTrigger(modal);
    }

    if (inline) {
      attachTracking(inline);
      track("view");
    }
  });
})();
