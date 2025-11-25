const SELECTORS = {
  root: '[data-article-carousel]',
  track: '[data-carousel-track]',
  prev: '[data-carousel-prev]',
  next: '[data-carousel-next]',
  dots: '[data-carousel-dots]',
  dot: '[data-carousel-dot]',
};

function initCarousel(root) {
  const track = root.querySelector(SELECTORS.track);
  if (!track) return;

  const slides = Array.from(track.children);
  if (slides.length <= 1) return; // No need to init for single slide

  let currentIndex = 0;

  const prevBtn = root.querySelector(SELECTORS.prev);
  const nextBtn = root.querySelector(SELECTORS.next);
  const dotsContainer = root.querySelector(SELECTORS.dots);

  const dots = dotsContainer
    ? Array.from(dotsContainer.querySelectorAll(SELECTORS.dot))
    : [];

  const update = () => {
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    dots.forEach((dot, idx) => {
      dot.classList.toggle('is-active', idx === currentIndex);
    });
  };

  const goTo = (index) => {
    if (index < 0) {
      currentIndex = slides.length - 1;
    } else if (index >= slides.length) {
      currentIndex = 0;
    } else {
      currentIndex = index;
    }
    update();
  };

  prevBtn?.addEventListener('click', () => goTo(currentIndex - 1));
  nextBtn?.addEventListener('click', () => goTo(currentIndex + 1));

  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => goTo(idx));
  });

  update();
}

document.addEventListener('DOMContentLoaded', () => {
  document
    .querySelectorAll(SELECTORS.root)
    .forEach((root) => initCarousel(root));
});

