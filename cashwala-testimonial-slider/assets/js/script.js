(function () {
  'use strict';

  const parseConfig = (el) => {
    try {
      return JSON.parse(el.getAttribute('data-cwts-config') || '{}');
    } catch (e) {
      return {};
    }
  };

  const initSlider = (wrapper) => {
    const config = parseConfig(wrapper);
    const layout = config.layout || 'slider';
    const track = wrapper.querySelector('.cwts-track');
    if (!track || layout !== 'slider') {
      return;
    }

    const slides = Array.from(track.children);
    const prevBtn = wrapper.querySelector('.cwts-prev');
    const nextBtn = wrapper.querySelector('.cwts-next');
    const dotsWrap = wrapper.querySelector('.cwts-dots');
    let index = 0;
    let timer = null;

    const update = () => {
      track.style.transform = `translate3d(${-index * 100}%, 0, 0)`;
      if (dotsWrap) {
        dotsWrap.querySelectorAll('.cwts-dot').forEach((dot, i) => {
          dot.classList.toggle('is-active', i === index);
        });
      }
    };

    const next = () => {
      if (index < slides.length - 1) {
        index += 1;
      } else if (Number(config.loop) === 1) {
        index = 0;
      }
      update();
    };

    const prev = () => {
      if (index > 0) {
        index -= 1;
      } else if (Number(config.loop) === 1) {
        index = slides.length - 1;
      }
      update();
    };

    const startAutoplay = () => {
      if (timer) {
        clearInterval(timer);
      }
      const speed = Math.max(1000, Number(config.autoplaySpeed) || 4000);
      timer = setInterval(next, speed);
    };

    if (dotsWrap) {
      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'cwts-dot' + (i === 0 ? ' is-active' : '');
        dot.addEventListener('click', () => {
          index = i;
          update();
          startAutoplay();
        });
        dotsWrap.appendChild(dot);
      });
    }

    if (Number(config.navigation) !== 1) {
      if (prevBtn) prevBtn.style.display = 'none';
      if (nextBtn) nextBtn.style.display = 'none';
    }

    if (prevBtn) prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { next(); startAutoplay(); });

    let startX = 0;
    track.addEventListener('touchstart', (e) => {
      startX = e.changedTouches[0].clientX;
    }, { passive: true });

    track.addEventListener('touchend', (e) => {
      const endX = e.changedTouches[0].clientX;
      const delta = startX - endX;
      if (Math.abs(delta) > 40) {
        if (delta > 0) {
          next();
        } else {
          prev();
        }
      }
      startAutoplay();
    }, { passive: true });

    update();
    startAutoplay();
  };

  const fetchTestimonials = (wrapper) => {
    if (!window.cwtsData || !window.fetch) return;
    const config = parseConfig(wrapper);
    const formData = new FormData();
    formData.append('action', 'cwts_fetch_testimonials');
    formData.append('nonce', window.cwtsData.nonce);
    formData.append('limit', '20');

    fetch(window.cwtsData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then((response) => response.json())
      .then((json) => {
        if (!json.success || !json.data || !json.data.items) {
          return;
        }
        wrapper.setAttribute('data-cwts-fetched', '1');
        if (config.layout === 'slider') {
          initSlider(wrapper);
        }
      })
      .catch(() => {
        // Fail silently to keep UX smooth.
      });
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cwts-wrapper').forEach((wrapper) => {
      fetchTestimonials(wrapper);
      initSlider(wrapper);
    });
  });
})();
