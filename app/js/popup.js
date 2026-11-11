const popupTrigger = document.querySelector('.popup-trigger');
const popupOverlay = document.getElementById('popup-overlay');
const popupCloseBtn = document.querySelector('.product-form .close-btn');

popupTrigger.addEventListener('click', () => {
  popupOverlay.style.display = 'flex';
});

popupCloseBtn.addEventListener('click', () => {
  popupOverlay.style.display = 'none';
});
 
window.addEventListener('click', (e) => {
  if (e.target === popupOverlay) {
    popupOverlay.style.display = 'none';
  }
});
