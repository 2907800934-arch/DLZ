document.addEventListener("DOMContentLoaded", () => {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const prefersReducedMotion = () => reduceMotion.matches;
  const navItems = Array.from(document.querySelectorAll(".foam-site-nav-item"));
  let closeNavTimer = null;
  const closingTimers = new WeakMap();
  const closingDuration = 140;
  const updateNavOpenState = () => {
    const hasOpenItem = navItems.some((item) => item.classList.contains("is-open"));

    document.body.classList.toggle("foam-nav-open", hasOpenItem);
  };

  const clearClosingState = (item) => {
    const timer = closingTimers.get(item);

    if (timer) {
      window.clearTimeout(timer);
      closingTimers.delete(item);
    }

    item.classList.remove("is-closing");
  };

  const markClosing = (item) => {
    clearClosingState(item);
    item.classList.add("is-closing");
    updateNavOpenState();

    const timer = window.setTimeout(() => {
      item.classList.remove("is-closing");
      closingTimers.delete(item);
      updateNavOpenState();
    }, closingDuration);

    closingTimers.set(item, timer);
  };

  const closeAllNavItems = (exceptItem = null, options = {}) => {
    const { instantClosePrevious = false } = options;

    navItems.forEach((item) => {
      if (item === exceptItem) {
        clearClosingState(item);
        item.classList.add("is-open");
        return;
      }

      const wasOpen = item.classList.contains("is-open");
      item.classList.remove("is-open");

      if (instantClosePrevious && wasOpen) {
        markClosing(item);
      } else {
        clearClosingState(item);
      }
    });

    updateNavOpenState();
  };

  const clearCloseTimer = () => {
    if (closeNavTimer) {
      window.clearTimeout(closeNavTimer);
      closeNavTimer = null;
    }
  };

  navItems.forEach((item) => {
    const panel = item.querySelector(".foam-site-nav-panel");
    const trigger = item.querySelector(".foam-site-nav__trigger");

    if (!panel || !trigger) {
      return;
    }

    const openItem = () => {
      clearCloseTimer();
      closeAllNavItems(item, { instantClosePrevious: true });
    };

    const queueClose = () => {
      clearCloseTimer();
      closeNavTimer = window.setTimeout(() => {
        closeAllNavItems(null, { instantClosePrevious: true });
      }, 120);
    };

    trigger.addEventListener("mouseenter", openItem);
    trigger.addEventListener("mouseleave", queueClose);
    item.addEventListener("focusin", openItem);
    item.addEventListener("focusout", (event) => {
      if (!item.contains(event.relatedTarget)) {
        queueClose();
      }
    });

    panel.addEventListener("mouseenter", openItem);
    panel.addEventListener("mouseleave", queueClose);
  });

  document.addEventListener("pointerdown", (event) => {
    if (!event.target.closest(".foam-site-nav")) {
      closeAllNavItems(null, { instantClosePrevious: true });
    }
  });

  updateNavOpenState();

  const scrollLinks = Array.from(document.querySelectorAll('a[href^="#"]:not([href="#"])'));
  const siteHeader = document.querySelector(".foam-site-header");
  const header = document.querySelector(".foam-site-header__inner");
  const mobileMenuToggle = document.querySelector(".foam-menu-toggle");
  const siteNav = document.querySelector(".foam-site-nav");
  const mobileMenuQuery = window.matchMedia("(max-width: 767px)");
  const heroSlides = Array.from(document.querySelectorAll(".foam-hero-slide"));
  const heroCarousel = document.querySelector(".foam-hero-carousel");
  const featuredCollectionHeading = document.querySelector(
    ".foam-home-section--product-showcase .foam-section-heading h2"
  );
  const lifeSectionHeading = document.querySelector(
    ".foam-home-section--life .foam-section-heading h2"
  );
  const shopToolbarHeading = document.querySelector(
    ".foam-shop-toolbar .foam-section-heading h1"
  );
  const lifeSlideTitles = Array.from(document.querySelectorAll(".foam-life-slide__copy h3"));
  const shopProductTitles = Array.from(
    document.querySelectorAll(
      ".post-type-archive-product .woocommerce-loop-product__title, .tax-product_cat .woocommerce-loop-product__title, .tax-product_tag .woocommerce-loop-product__title"
    )
  );
  const getHeaderOffset = () => (header ? Math.round(header.getBoundingClientRect().height + 28) : 48);

  if (featuredCollectionHeading) {
    const normalizedHeading = (featuredCollectionHeading.textContent || "")
      .replace(/\s+/g, " ")
      .trim()
      .toLowerCase();

    if (normalizedHeading.includes("elevated comfort") && normalizedHeading.includes("every day")) {
      featuredCollectionHeading.innerHTML = "<span>Elevated Comfort</span><span>Every Day</span>";
      featuredCollectionHeading.classList.add("foam-heading--two-line");
    }
  }

  if (lifeSectionHeading) {
    const normalizedLifeHeading = (lifeSectionHeading.textContent || "")
      .replace(/\s+/g, " ")
      .trim()
      .toLowerCase();

    if (normalizedLifeHeading.includes("comfort") && normalizedLifeHeading.includes("everyday")) {
      lifeSectionHeading.innerHTML = "<span>Comfort, seen in</span><span>everyday rituals</span>";
      lifeSectionHeading.classList.add("foam-heading--life-two-line");
    }
  }

  if (shopToolbarHeading) {
    const normalizedShopHeading = (shopToolbarHeading.textContent || "")
      .replace(/\s+/g, " ")
      .trim()
      .toLowerCase();

    if (
      normalizedShopHeading.includes("a quieter product edit") &&
      normalizedShopHeading.includes("compact rooms") &&
      normalizedShopHeading.includes("flexible living")
    ) {
      shopToolbarHeading.innerHTML =
        "<span>A quieter product edit for</span><span>compact rooms and flexible living</span>";
      shopToolbarHeading.classList.add("foam-heading--shop-two-line");
    }
  }

  if (shopProductTitles.length) {
    shopProductTitles.forEach((title) => {
      const text = (title.textContent || "").replace(/\s+/g, " ").trim();

      if (!text) {
        return;
      }

      title.textContent = text.replace(/^sonovafurn\s+/i, "");
    });
  }

  if (lifeSlideTitles.length) {
    const lifeTitleMap = new Map([
      ["start the day somewhere soft.", ["Start the day", "somewhere soft."]],
      ["slow mornings begin here.", ["Slow mornings", "begin here."]],
      ["comfort whenever someone stays over.", ["Comfort whenever", "someone stays over."]],
      ["made for evenings that last longer.", ["Made for evenings", "that last longer."]],
    ]);

    lifeSlideTitles.forEach((title) => {
      const normalizedText = (title.textContent || "")
        .replace(/\s+/g, " ")
        .trim()
        .toLowerCase();

      const lines = lifeTitleMap.get(normalizedText);

      if (!lines) {
        return;
      }

      title.innerHTML = lines.map((line) => `<span>${line}</span>`).join("");
      title.classList.add("foam-life-title--two-line");
    });
  }

  const syncMobileMenuState = () => {
    if (!siteHeader || !siteNav || !mobileMenuToggle) {
      return;
    }

    if (!mobileMenuQuery.matches) {
      siteHeader.classList.remove("is-mobile-menu-open");
      mobileMenuToggle.setAttribute("aria-expanded", "false");
      siteNav.hidden = false;
      return;
    }

    const isOpen = siteHeader.classList.contains("is-mobile-menu-open");
    siteNav.hidden = !isOpen;
    mobileMenuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    mobileMenuToggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
  };

  if (siteHeader && siteNav && mobileMenuToggle) {
    syncMobileMenuState();

    mobileMenuToggle.addEventListener("click", () => {
      siteHeader.classList.toggle("is-mobile-menu-open");
      syncMobileMenuState();
    });

    siteNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        if (!mobileMenuQuery.matches) {
          return;
        }

        siteHeader.classList.remove("is-mobile-menu-open");
        syncMobileMenuState();
      });
    });

    document.addEventListener("pointerdown", (event) => {
      if (!mobileMenuQuery.matches || !siteHeader.classList.contains("is-mobile-menu-open")) {
        return;
      }

      if (!event.target.closest(".foam-site-header")) {
        siteHeader.classList.remove("is-mobile-menu-open");
        syncMobileMenuState();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !mobileMenuQuery.matches) {
        return;
      }

      siteHeader.classList.remove("is-mobile-menu-open");
      syncMobileMenuState();
    });

    if (typeof mobileMenuQuery.addEventListener === "function") {
      mobileMenuQuery.addEventListener("change", syncMobileMenuState);
    }
  }

  scrollLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      const href = link.getAttribute("href");

      if (!href || href.length < 2) {
        return;
      }

      const target = document.querySelector(href);

      if (!target) {
        return;
      }

      event.preventDefault();

      const top = window.scrollY + target.getBoundingClientRect().top - getHeaderOffset();

      window.scrollTo({
        top: Math.max(0, top),
        behavior: prefersReducedMotion() ? "auto" : "smooth",
      });
    });
  });

  const revealTargets = Array.from(
    document.querySelectorAll(
      [
        ".foam-hero-shell",
        ".foam-home-features",
        ".foam-home-section",
        ".foam-section-card",
        ".foam-review-card",
        ".foam-home-feature",
        ".foam-technology-card",
        ".foam-lifestyle-tile",
        ".foam-home-editorial-media",
        ".foam-home-editorial-copy",
        ".foam-collection-card",
        ".foam-material-story-card",
        ".foam-inside-stack__story-card",
        ".foam-inside-story__statement",
        ".foam-shop-promo__media",
        ".foam-shop-promo__copy",
        ".foam-editorial-gallery__card",
        ".foam-legal-page",
        ".foam-legal-intro-wrap",
        ".foam-product-scenes-shell",
        ".foam-benefits-shell",
        ".foam-before-after-shell",
        ".foam-specifications-shell",
        ".foam-layers-shell",
        ".foam-faq-shell",
        ".foam-fbt-shell",
        ".foam-final-cta",
        ".woocommerce ul.products li.product",
      ].join(", ")
    )
  );

  const parallaxTargets = Array.from(
    document.querySelectorAll(
      [
        ".foam-scroll-card",
        ".foam-lifestyle-tile",
        ".foam-home-editorial-media",
        ".foam-review-card",
        ".foam-technology-card",
        ".woocommerce ul.products li.product",
      ].join(", ")
    )
  );

  if (!prefersReducedMotion()) {
    revealTargets.forEach((element, index) => {
      if (!element.classList.contains("foam-scroll-reveal")) {
        element.classList.add("foam-scroll-reveal");
      }

      if (
        element.matches(".foam-lifestyle-tile, .foam-home-editorial-media, .foam-review-card, .foam-technology-card, .woocommerce ul.products li.product") &&
        !element.classList.contains("foam-scroll-card")
      ) {
        element.classList.add("foam-scroll-card");
      }

      const delay = Math.min((index % 6) * 55, 220);
      element.style.setProperty("--foam-reveal-delay", `${delay}ms`);
    });
  } else {
    revealTargets.forEach((element) => {
      element.classList.add("is-revealed");
      element.classList.add("is-static");
    });
  }

  if (!prefersReducedMotion() && revealTargets.length) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add("is-revealed");
          revealObserver.unobserve(entry.target);
        });
      },
      {
        rootMargin: "0px 0px -8% 0px",
        threshold: 0.12,
      }
    );

    revealTargets.forEach((element) => revealObserver.observe(element));
  }

  let scrollRaf = null;
  const updateScrollEffects = () => {
    scrollRaf = null;

    const scrollTop = window.scrollY || window.pageYOffset || 0;
    document.body.classList.toggle("foam-nav-scrolled", scrollTop > 18);

    if (header) {
      const headerProgress = Math.max(0, Math.min(1, scrollTop / 220));
      header.style.setProperty("--foam-header-scale", (1 - headerProgress * 0.018).toFixed(3));
      header.style.setProperty("--foam-header-blur", `${(18 + headerProgress * 8).toFixed(2)}px`);
      header.style.setProperty("--foam-header-shadow", `${(0.06 + headerProgress * 0.045).toFixed(3)}`);
      header.style.setProperty("--foam-header-bg", (0.78 + headerProgress * 0.12).toFixed(3));
      header.style.setProperty("--foam-header-border", (0.72 + headerProgress * 0.18).toFixed(3));
    }

    if (prefersReducedMotion()) {
      return;
    }

    if (heroCarousel) {
      const heroRect = heroCarousel.getBoundingClientRect();
      const heroProgress = Math.max(0, Math.min(1, -heroRect.top / Math.max(heroRect.height, 1)));
      const heroScale = 1 - heroProgress * 0.022;
      const heroTranslate = heroProgress * -14;

      heroCarousel.style.setProperty("--foam-hero-scale", heroScale.toFixed(3));
      heroCarousel.style.setProperty("--foam-hero-y", `${heroTranslate.toFixed(2)}px`);
    }

    heroSlides.forEach((slide) => {
      if (!slide.classList.contains("is-active")) {
        return;
      }

      const slideRect = slide.getBoundingClientRect();
      const slideProgress = Math.max(0, Math.min(1, -slideRect.top / Math.max(slideRect.height, 1)));
      const imageScale = 1 + slideProgress * 0.018;
      const imageShift = slideProgress * 18;
      const contentShift = slideProgress * -12;

      slide.style.setProperty("--foam-hero-image-scale", imageScale.toFixed(3));
      slide.style.setProperty("--foam-hero-image-y", `${imageShift.toFixed(2)}px`);
      slide.style.setProperty("--foam-hero-content-y", `${contentShift.toFixed(2)}px`);
    });

    parallaxTargets.forEach((element) => {
      const rect = element.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

      if (rect.bottom < -40 || rect.top > viewportHeight + 40) {
        return;
      }

      const progress = (viewportHeight - rect.top) / (viewportHeight + rect.height);
      const clamped = Math.max(0, Math.min(1, progress));
      const centered = (clamped - 0.5) * 2;

      const translateY = centered * -8;
      const scale = 0.988 + (1 - Math.abs(centered)) * 0.016;
      const opacity = 0.94 + (1 - Math.abs(centered)) * 0.06;
      const rotate = centered * 0.9;

      element.style.setProperty("--foam-scroll-y", `${translateY.toFixed(2)}px`);
      element.style.setProperty("--foam-scroll-scale", scale.toFixed(3));
      element.style.setProperty("--foam-scroll-opacity", opacity.toFixed(3));
      element.style.setProperty("--foam-scroll-rotate", `${rotate.toFixed(2)}deg`);
    });
  };

  const requestScrollEffects = () => {
    if (scrollRaf !== null) {
      return;
    }

    scrollRaf = window.requestAnimationFrame(updateScrollEffects);
  };

  window.addEventListener("scroll", requestScrollEffects, { passive: true });
  window.addEventListener("resize", requestScrollEffects);
  if (typeof reduceMotion.addEventListener === "function") {
    reduceMotion.addEventListener("change", requestScrollEffects);
  }

  requestScrollEffects();

  const navPanels = document.querySelectorAll(".foam-site-nav-panel");

  navPanels.forEach((panel) => {
    const feature = panel.querySelector(".foam-site-nav-feature");
    const groupButtons = Array.from(panel.querySelectorAll(".foam-site-nav-subnav__button"));
    const groupPanels = Array.from(panel.querySelectorAll(".foam-site-nav-stage__panel"));
    const links = Array.from(panel.querySelectorAll(".foam-site-nav-menu__link"));

    if (!feature || !links.length) {
      return;
    }

    const meta = feature.querySelector(".foam-site-nav-feature__meta");
    const title = feature.querySelector("strong");
    const copy = feature.querySelector("em");
    const cta = feature.querySelector(".foam-site-nav-feature__cta");
    const defaultState = {
      image: feature.dataset.defaultImage || "",
      title: feature.dataset.defaultTitle || (title ? title.textContent : ""),
      copy: feature.dataset.defaultCopy || (copy ? copy.textContent : ""),
      meta: feature.dataset.defaultMeta || (meta ? meta.textContent : ""),
      url: feature.dataset.defaultUrl || (cta ? cta.getAttribute("href") : ""),
    };
    const initialGroupKey =
      groupButtons.find((button) => button.classList.contains("is-active"))?.dataset.groupTarget ||
      groupPanels[0]?.dataset.groupPanel ||
      "";

    const getGroupDefaultLink = (groupKey) =>
      links.find(
        (link) =>
          link.dataset.groupParent === groupKey &&
          (link.dataset.groupDefault === "true" || link.dataset.previewDefault === "true")
      ) || links.find((link) => link.dataset.groupParent === groupKey);

    const renderFeature = (link) => {
      const image = link?.dataset.previewImage || defaultState.image;
      const nextTitle = link?.dataset.previewTitle || defaultState.title;
      const nextCopy = link?.dataset.previewCopy || defaultState.copy;
      const nextMeta = link?.dataset.previewMeta || defaultState.meta;
      const nextUrl = link?.dataset.previewUrl || defaultState.url;

      feature.classList.add("is-switching");

      window.setTimeout(() => {
        if (image) {
          feature.style.backgroundImage = `linear-gradient(180deg, rgba(17, 17, 17, 0.03), rgba(17, 17, 17, 0.26)), url('${image}')`;
        } else {
          feature.style.backgroundImage = "none";
        }

        if (meta) {
          meta.textContent = nextMeta;
        }

        if (title) {
          title.textContent = nextTitle;
        }

        if (copy) {
          copy.textContent = nextCopy;
        }

        if (cta && nextUrl) {
          cta.setAttribute("href", nextUrl);
        }

        feature.classList.remove("is-switching");
      }, 110);
    };

    const setActiveLink = (activeLink) => {
      links.forEach((link) => {
        link.classList.toggle("is-active", link === activeLink);
      });
    };

    const setActiveGroup = (groupKey, options = {}) => {
      const { syncFeature = true } = options;

      if (!groupKey) {
        return;
      }

      groupButtons.forEach((button) => {
        const isActive = button.dataset.groupTarget === groupKey;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
      });

      groupPanels.forEach((groupPanel) => {
        const isActive = groupPanel.dataset.groupPanel === groupKey;
        groupPanel.classList.toggle("is-active", isActive);
        groupPanel.hidden = !isActive;
      });

      if (syncFeature) {
        const defaultLink = getGroupDefaultLink(groupKey);

        if (defaultLink) {
          setActiveLink(defaultLink);
          renderFeature(defaultLink);
        }
      }
    };

    links.forEach((link) => {
      const activate = () => {
        const parentGroup = link.dataset.groupParent || "";

        if (parentGroup) {
          setActiveGroup(parentGroup, { syncFeature: false });
        }

        setActiveLink(link);
        renderFeature(link);
      };

      link.addEventListener("mouseenter", activate);
      link.addEventListener("focus", activate);
    });

    groupButtons.forEach((button) => {
      const activateGroup = () => {
        setActiveGroup(button.dataset.groupTarget || "", { syncFeature: true });
      };

      button.addEventListener("mouseenter", activateGroup);
      button.addEventListener("focus", activateGroup);
      button.addEventListener("click", activateGroup);
    });

    const initialLink = links.find((link) => link.dataset.previewDefault === "true") || links[0];
    const resetToInitial = () => {
      if (initialGroupKey) {
        setActiveGroup(initialGroupKey, { syncFeature: false });
      }

      setActiveLink(initialLink);
      renderFeature(initialLink);
    };

    panel.addEventListener("mouseleave", () => {
      resetToInitial();
    });

    const trigger = panel.closest(".foam-site-nav-item")?.querySelector(".foam-site-nav__trigger");

    if (trigger) {
      trigger.addEventListener("mouseenter", resetToInitial);
      trigger.addEventListener("focus", resetToInitial);
    }

    resetToInitial();
  });

  const materialStories = document.querySelectorAll("[data-foam-material-story]");

  materialStories.forEach((story) => {
    const triggers = Array.from(story.querySelectorAll("[data-foam-material-trigger]"));
    const panels = Array.from(story.querySelectorAll("[data-foam-material-panel]"));
    const imageView = story.querySelector("[data-foam-material-image-view]");

    if (!triggers.length || !panels.length) {
      return;
    }

    let activeKey =
      triggers.find((trigger) => trigger.classList.contains("is-active"))?.dataset.foamMaterialTrigger ||
      triggers[0].dataset.foamMaterialTrigger;
    let autoRotate = null;

    const setActiveMaterial = (key) => {
      activeKey = key;

      story.classList.remove("is-fabric", "is-support", "is-springs");
      story.classList.add(`is-${key}`);

      triggers.forEach((trigger) => {
        const isActive = trigger.dataset.foamMaterialTrigger === key;
        trigger.classList.toggle("is-active", isActive);
        trigger.setAttribute("aria-pressed", isActive ? "true" : "false");

        if (isActive && imageView && trigger.dataset.foamMaterialImage) {
          story.classList.add("is-switching");

          window.setTimeout(() => {
            imageView.setAttribute("src", trigger.dataset.foamMaterialImage);
            story.classList.remove("is-switching");
          }, 120);
        }
      });

      panels.forEach((panel) => {
        panel.classList.toggle("is-active", panel.dataset.foamMaterialPanel === key);
      });
    };

    const stopAutoRotate = () => {
      if (autoRotate) {
        window.clearInterval(autoRotate);
        autoRotate = null;
      }
    };

    const startAutoRotate = () => {
      if (prefersReducedMotion() || triggers.length < 2) {
        return;
      }

      stopAutoRotate();

      autoRotate = window.setInterval(() => {
        const currentIndex = triggers.findIndex(
          (trigger) => trigger.dataset.foamMaterialTrigger === activeKey
        );
        const nextTrigger = triggers[(currentIndex + 1 + triggers.length) % triggers.length];

        if (!nextTrigger) {
          return;
        }

        setActiveMaterial(nextTrigger.dataset.foamMaterialTrigger);
      }, 5600);
    };

    triggers.forEach((trigger) => {
      const activate = () => {
        setActiveMaterial(trigger.dataset.foamMaterialTrigger);
        startAutoRotate();
      };

      trigger.addEventListener("click", activate);
      trigger.addEventListener("focus", activate);
    });

    story.addEventListener("mouseenter", stopAutoRotate);
    story.addEventListener("mouseleave", startAutoRotate);
    story.addEventListener("focusin", stopAutoRotate);
    story.addEventListener("focusout", (event) => {
      if (!story.contains(event.relatedTarget)) {
        startAutoRotate();
      }
    });

    setActiveMaterial(activeKey);
    startAutoRotate();
  });

  const carousels = document.querySelectorAll("[data-foam-hero-carousel]");

  carousels.forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll("[data-foam-hero-slide]"));
    const dots = Array.from(carousel.querySelectorAll("[data-foam-hero-dot]"));
    const prevButton = carousel.querySelector("[data-foam-hero-prev]");
    const nextButton = carousel.querySelector("[data-foam-hero-next]");

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
      if (prefersReducedMotion() || slides.length < 2) {
        return;
      }

      stopAutoRotate();
      autoRotate = window.setInterval(() => {
        setActiveSlide(activeIndex + 1);
      }, 9800);
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

    carousel.addEventListener("focusin", stopAutoRotate);
    carousel.addEventListener("focusout", startAutoRotate);

    setActiveSlide(activeIndex);
    startAutoRotate();
  });

  const designLab = document.querySelector("[data-foam-design-lab]");

  if (designLab && window.foamDesignLab) {
    const root = document.documentElement;
    const panel = designLab.querySelector("[data-foam-design-lab-panel]");
    const toggle = designLab.querySelector("[data-foam-design-lab-toggle]");
    const closeButton = designLab.querySelector("[data-foam-design-lab-close]");
    const copyButton = designLab.querySelector("[data-foam-design-copy]");
    const resetButton = designLab.querySelector("[data-foam-design-reset]");
    const exportView = designLab.querySelector("[data-foam-design-export]");
    const controls = Array.from(designLab.querySelectorAll("[data-foam-design-control]"));
    const storageKey = window.foamDesignLab.storageKey || "foamFormDesignLab.v1";
    const uiStateKey = `${storageKey}.ui`;
    const cssVars = controls.map((control) => control.dataset.cssVar).filter(Boolean);

    const setMeta = (selector, value) => {
      const element = designLab.querySelector(selector);

      if (element) {
        element.textContent = value || "—";
      }
    };

    setMeta("[data-foam-design-page-title]", window.foamDesignLab.pageTitle);
    setMeta("[data-foam-design-template]", window.foamDesignLab.template);
    setMeta("[data-foam-design-page-type]", window.foamDesignLab.pageType);
    setMeta("[data-foam-design-page-id]", window.foamDesignLab.pageId ? String(window.foamDesignLab.pageId) : "—");
    setMeta("[data-foam-design-page-path]", window.foamDesignLab.path || "/");
    setMeta(
      "[data-foam-design-body-classes]",
      Array.isArray(window.foamDesignLab.bodyClasses) ? window.foamDesignLab.bodyClasses.join(" ") : ""
    );

    const readStoredState = () => {
      try {
        return JSON.parse(window.localStorage.getItem(storageKey) || "{}");
      } catch (error) {
        return {};
      }
    };

    const saveStoredState = (state) => {
      try {
        window.localStorage.setItem(storageKey, JSON.stringify(state));
      } catch (error) {}
    };

    const getUiState = () => {
      try {
        return JSON.parse(window.localStorage.getItem(uiStateKey) || "{}");
      } catch (error) {
        return {};
      }
    };

    const setUiState = (state) => {
      try {
        window.localStorage.setItem(uiStateKey, JSON.stringify(state));
      } catch (error) {}
    };

    const rgbToHex = (value) => {
      const normalized = (value || "").trim();

      if (!normalized) {
        return "#ffffff";
      }

      if (normalized.startsWith("#")) {
        if (normalized.length === 4) {
          return `#${normalized[1]}${normalized[1]}${normalized[2]}${normalized[2]}${normalized[3]}${normalized[3]}`.toLowerCase();
        }

        return normalized.toLowerCase();
      }

      const match = normalized.match(/rgba?\(([^)]+)\)/i);

      if (!match) {
        return "#ffffff";
      }

      const [r, g, b] = match[1]
        .split(",")
        .slice(0, 3)
        .map((part) => Math.max(0, Math.min(255, Number.parseInt(part.trim(), 10) || 0)));

      return `#${[r, g, b]
        .map((channel) => channel.toString(16).padStart(2, "0"))
        .join("")}`.toLowerCase();
    };

    const formatValue = (value, unit = "") => {
      if (value === null || value === undefined || value === "") {
        return "";
      }

      const numeric = Number.parseFloat(value);

      if (Number.isNaN(numeric)) {
        return String(value);
      }

      const precision = unit === "em" ? 3 : 2;
      return `${numeric.toFixed(precision)}${unit}`;
    };

    const currentCssValue = (name) => getComputedStyle(root).getPropertyValue(name).trim();

    const applyCssValue = (name, value) => {
      root.style.setProperty(name, value);
    };

    const updateExport = () => {
      const cssBlock = [":root {"]
        .concat(
          cssVars.map((name) => {
            const value = currentCssValue(name);
            return `  ${name}: ${value};`;
          })
        )
        .concat("}")
        .join("\n");

      if (exportView) {
        exportView.textContent = cssBlock;
      }
    };

    const syncControl = (control) => {
      const cssVar = control.dataset.cssVar;
      const output = control.parentElement?.querySelector("[data-foam-design-value]");
      const value = currentCssValue(cssVar);
      const unit = control.dataset.unit || "";

      if (control.type === "color") {
        control.value = rgbToHex(value);
      } else if (control.type === "range") {
        control.value = String(Number.parseFloat(value) || 0);
      } else {
        control.value = value;
      }

      if (output) {
        output.textContent = control.type === "color" ? control.value : formatValue(control.value, unit);
      }
    };

    const syncAllControls = () => {
      controls.forEach(syncControl);
      updateExport();
    };

    const restoreStoredValues = () => {
      const state = readStoredState();

      Object.entries(state).forEach(([name, value]) => {
        if (!cssVars.includes(name)) {
          return;
        }

        applyCssValue(name, value);
      });
    };

    const persistControl = (control) => {
      const cssVar = control.dataset.cssVar;
      const unit = control.dataset.unit || "";
      const state = readStoredState();
      let nextValue = control.value;

      if (control.type === "range") {
        nextValue = `${nextValue}${unit}`;
      }

      state[cssVar] = nextValue;
      saveStoredState(state);
      applyCssValue(cssVar, nextValue);
      syncControl(control);
      updateExport();
    };

    const setOpenState = (isOpen) => {
      designLab.classList.toggle("is-open", isOpen);
      if (panel) {
        panel.hidden = !isOpen;
      }
      if (toggle) {
        toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      }
      setUiState({ open: isOpen });
    };

    restoreStoredValues();
    syncAllControls();

    controls.forEach((control) => {
      const eventName = control.type === "range" || control.type === "color" ? "input" : "change";
      control.addEventListener(eventName, () => persistControl(control));
      if (eventName !== "change") {
        control.addEventListener("change", () => persistControl(control));
      }
    });

    const uiState = getUiState();
    setOpenState(Boolean(uiState.open));

    if (toggle) {
      toggle.addEventListener("click", () => {
        setOpenState(!designLab.classList.contains("is-open"));
      });
    }

    if (closeButton) {
      closeButton.addEventListener("click", () => setOpenState(false));
    }

    if (resetButton) {
      resetButton.addEventListener("click", () => {
        cssVars.forEach((name) => root.style.removeProperty(name));
        try {
          window.localStorage.removeItem(storageKey);
        } catch (error) {}
        syncAllControls();
      });
    }

    if (copyButton) {
      copyButton.addEventListener("click", async () => {
        const payload = exportView ? exportView.textContent : "";

        try {
          await navigator.clipboard.writeText(payload);
          copyButton.textContent = "Copied";
          window.setTimeout(() => {
            copyButton.textContent = "Copy CSS";
          }, 1400);
        } catch (error) {
          copyButton.textContent = "Copy failed";
          window.setTimeout(() => {
            copyButton.textContent = "Copy CSS";
          }, 1600);
        }
      });
    }
  }
});
