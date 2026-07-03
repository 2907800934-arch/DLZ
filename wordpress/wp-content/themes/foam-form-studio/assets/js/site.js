document.addEventListener("DOMContentLoaded", () => {
  const carousels = document.querySelectorAll("[data-foam-hero-carousel]");

  carousels.forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll("[data-foam-hero-slide]"));
    const dots = Array.from(carousel.querySelectorAll("[data-foam-hero-dot]"));
    const prevButton = carousel.querySelector("[data-foam-hero-prev]");
    const nextButton = carousel.querySelector("[data-foam-hero-next]");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (!slides.length) {
      return;
    }

    let activeIndex = slides.findIndex((slide) => slide.classList.contains("is-active"));
    let autoRotate = null;

    if (activeIndex < 0) {
      activeIndex = 0;
    }

    const setActiveSlide = (index) => {
      activeIndex = (index + slides.length) % slides.length;

      slides.forEach((slide, slideIndex) => {
        const isActive = slideIndex === activeIndex;

        slide.classList.toggle("is-active", isActive);
        slide.setAttribute("aria-hidden", isActive ? "false" : "true");
      });

      dots.forEach((dot, dotIndex) => {
        const isActive = dotIndex === activeIndex;

        dot.classList.toggle("is-active", isActive);
        dot.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    };

    const stopAutoRotate = () => {
      if (autoRotate) {
        window.clearInterval(autoRotate);
        autoRotate = null;
      }
    };

    const startAutoRotate = () => {
      if (reduceMotion || slides.length < 2) {
        return;
      }

      stopAutoRotate();
      autoRotate = window.setInterval(() => {
        setActiveSlide(activeIndex + 1);
      }, 6500);
    };

    if (prevButton) {
      prevButton.addEventListener("click", () => {
        setActiveSlide(activeIndex - 1);
        startAutoRotate();
      });
    }

    if (nextButton) {
      nextButton.addEventListener("click", () => {
        setActiveSlide(activeIndex + 1);
        startAutoRotate();
      });
    }

    dots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        setActiveSlide(index);
        startAutoRotate();
      });
    });

    carousel.addEventListener("pointerenter", stopAutoRotate);
    carousel.addEventListener("pointerleave", startAutoRotate);
    carousel.addEventListener("focusin", stopAutoRotate);
    carousel.addEventListener("focusout", startAutoRotate);

    setActiveSlide(activeIndex);
    startAutoRotate();
  });
});
