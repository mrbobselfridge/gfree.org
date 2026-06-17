const setLinkTarget = (link, url) => {
    if (! link) {
        return;
    }

    let isExternal = false;

    try {
        const parsed = new URL(url, window.location.href);
        isExternal = ['http:', 'https:'].includes(parsed.protocol) && parsed.host !== window.location.host;
    } catch {
        isExternal = false;
    }

    if (isExternal) {
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        return;
    }

    link.removeAttribute('target');
    link.removeAttribute('rel');
};

document.querySelectorAll('[data-site-header]').forEach((header) => {
    const navToggle = header.querySelector('[data-nav-toggle]');
    const navMenu = header.querySelector('[data-nav-menu]');
    const submenuToggles = Array.from(header.querySelectorAll('[data-subnav-toggle]'));
    const mobileQuery = window.matchMedia('(max-width: 860px)');

    if (! navToggle || ! navMenu) {
        return;
    }

    const closeSubmenus = (except = null) => {
        submenuToggles.forEach((toggle) => {
            if (toggle === except) {
                return;
            }

            toggle.setAttribute('aria-expanded', 'false');
            toggle.closest('.concept-nav__item')?.classList.remove('is-subnav-open');
        });
    };

    const setMenuOpen = (isOpen) => {
        header.classList.toggle('is-nav-open', isOpen);
        navToggle.setAttribute('aria-expanded', String(isOpen));

        if (! isOpen) {
            closeSubmenus();
        }
    };

    navToggle.addEventListener('click', () => {
        setMenuOpen(! header.classList.contains('is-nav-open'));
    });

    submenuToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const item = toggle.closest('.concept-nav__item');
            const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';

            closeSubmenus(toggle);
            toggle.setAttribute('aria-expanded', String(shouldOpen));
            item?.classList.toggle('is-subnav-open', shouldOpen);
        });
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobileQuery.matches) {
                setMenuOpen(false);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (! mobileQuery.matches || header.contains(event.target)) {
            return;
        }

        setMenuOpen(false);
    });

    mobileQuery.addEventListener('change', (event) => {
        if (! event.matches) {
            setMenuOpen(false);
        }
    });
});

const updateHeroSlide = (carousel, slides, index) => {
    const slide = slides[index];

    if (! slide) {
        return;
    }

    carousel.querySelector('[data-hero-image]').style.backgroundImage = `url("${slide.image_url}")`;
    carousel.querySelector('[data-hero-eyebrow]').textContent = slide.eyebrow;
    carousel.querySelector('[data-hero-title]').textContent = slide.title;

    const subtitle = carousel.querySelector('[data-hero-subtitle]');
    subtitle.textContent = slide.subtitle || '';
    subtitle.hidden = ! slide.subtitle;

    const primary = carousel.querySelector('[data-hero-primary]');
    const secondary = carousel.querySelector('[data-hero-secondary]');
    const primaryLabel = slide.primary_label || '';
    const secondaryLabel = slide.secondary_label || '';

    primary.textContent = primaryLabel;
    primary.href = slide.primary_url;
    setLinkTarget(primary, slide.primary_url);
    primary.hidden = ! primaryLabel;

    secondary.textContent = secondaryLabel;
    secondary.href = slide.secondary_url;
    setLinkTarget(secondary, slide.secondary_url);
    secondary.hidden = ! secondaryLabel;

};

document.querySelectorAll('[data-hero-carousel]').forEach((carousel) => {
    const data = carousel.querySelector('[data-hero-slides]');

    if (! data) {
        return;
    }

    let slides = [];

    try {
        slides = JSON.parse(data.textContent);
    } catch {
        return;
    }

    if (slides.length < 2) {
        return;
    }

    let index = 0;
    const previous = carousel.querySelector('[data-hero-previous]');
    const next = carousel.querySelector('[data-hero-next]');

    previous?.addEventListener('click', () => {
        index = (index - 1 + slides.length) % slides.length;
        updateHeroSlide(carousel, slides, index);
    });

    next?.addEventListener('click', () => {
        index = (index + 1) % slides.length;
        updateHeroSlide(carousel, slides, index);
    });
});

document.querySelectorAll('[data-related-modal-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const modalId = trigger.getAttribute('aria-controls');
        const modal = modalId ? document.getElementById(modalId) : null;

        if (modal?.showModal) {
            modal.showModal();
        }
    });
});

document.querySelectorAll('[data-related-modal]').forEach((modal) => {
    modal.querySelectorAll('[data-related-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            modal.close();
        });
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.close();
        }
    });
});

document.querySelectorAll('[data-related-load-more]').forEach((listing) => {
    const trigger = listing.querySelector('[data-related-load-more-trigger]');
    const parsedPageSize = Number.parseInt(listing.dataset.relatedPageSize || '1', 10);
    const pageSize = Number.isNaN(parsedPageSize) ? 1 : Math.max(1, parsedPageSize);

    if (! trigger) {
        return;
    }

    const hiddenItems = () => Array.from(listing.querySelectorAll('[data-related-load-more-item][hidden]'));

    const updateTrigger = () => {
        trigger.hidden = hiddenItems().length === 0;
    };

    trigger.addEventListener('click', () => {
        hiddenItems().slice(0, pageSize).forEach((item) => {
            item.hidden = false;
        });

        updateTrigger();
    });

    updateTrigger();
});

document.querySelectorAll('[data-related-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-related-carousel-track]');
    const viewport = carousel.querySelector('[data-related-carousel-viewport]');
    const controls = carousel.querySelector('.concept-updates__carousel-controls');
    const previous = carousel.querySelector('[data-related-carousel-previous]');
    const next = carousel.querySelector('[data-related-carousel-next]');

    if (! track || ! viewport) {
        return;
    }

    const cards = () => Array.from(track.children);
    const configuredVisibleCount = Number.parseInt(carousel.dataset.relatedCarouselVisibleCount || '0', 10);

    const visibleCardCount = () => {
        const currentCards = cards();
        const firstCard = currentCards[0];

        if (! firstCard) {
            return 0;
        }

        const viewportWidth = viewport.getBoundingClientRect().width;
        const cardWidth = firstCard.getBoundingClientRect().width;
        const trackStyles = window.getComputedStyle(track);
        const gap = Number.parseFloat(trackStyles.columnGap || trackStyles.gap || '0') || 0;

        if (viewportWidth <= 0 || cardWidth <= 0) {
            return 1;
        }

        const actualVisibleCount = Math.max(1, Math.floor((viewportWidth + gap) / (cardWidth + gap)));

        if (Number.isNaN(configuredVisibleCount) || configuredVisibleCount < 1) {
            return actualVisibleCount;
        }

        return Math.min(configuredVisibleCount, actualVisibleCount);
    };

    const hasOverflow = () => cards().length > visibleCardCount();

    const updateControls = () => {
        if (controls) {
            controls.hidden = ! hasOverflow();
        }
    };

    const rotate = (direction) => {
        if (! hasOverflow()) {
            return;
        }

        const currentCards = cards();

        if (direction > 0) {
            track.append(currentCards[0]);
        } else {
            track.prepend(currentCards[currentCards.length - 1]);
        }
    };

    previous?.addEventListener('click', () => rotate(-1));
    next?.addEventListener('click', () => rotate(1));

    let pointerStart = null;

    viewport.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        pointerStart = event.clientX;
    });

    viewport.addEventListener('pointerup', (event) => {
        if (pointerStart === null) {
            return;
        }

        const delta = event.clientX - pointerStart;
        pointerStart = null;

        if (Math.abs(delta) < 36) {
            return;
        }

        rotate(delta < 0 ? 1 : -1);
    });

    viewport.addEventListener('pointercancel', () => {
        pointerStart = null;
    });

    window.addEventListener('resize', updateControls);
    updateControls();
});
