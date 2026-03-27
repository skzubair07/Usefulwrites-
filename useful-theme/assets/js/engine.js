(() => {
  const modal = document.getElementById('quick-buy-modal');
  if (!modal) {
    return;
  }

  const titleTarget = document.getElementById('qb-title');
  const priceTarget = document.getElementById('qb-price');
  const buyNow = document.getElementById('qb-buy-now');
  const whatsapp = document.getElementById('qb-whatsapp');

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  };

  const openModal = (name, price) => {
    titleTarget.textContent = name;
    priceTarget.textContent = `₹${price}`;

    const encoded = encodeURIComponent(`Hi, I want to buy ${name} for ₹${price}. Please share payment details.`);
    whatsapp.href = `https://wa.me/?text=${encoded}`;
    buyNow.href = '#';

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  };

  document.addEventListener('click', (event) => {
    const openBtn = event.target.closest('[data-open-modal]');
    const closeBtn = event.target.closest('[data-close-modal]');

    if (openBtn) {
      const card = openBtn.closest('.product-card');
      if (!card) {
        return;
      }

      const title = card.dataset.productTitle || 'Product';
      const price = card.dataset.productPrice || '0.00';
      openModal(title, price);
      return;
    }

    if (closeBtn || event.target.classList.contains('qb-modal__overlay')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });
})();
