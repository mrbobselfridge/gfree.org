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

const relatedLoadMoreSetups = new WeakMap();

const setupRelatedLoadMore = (listing) => {
    if (relatedLoadMoreSetups.has(listing)) {
        return relatedLoadMoreSetups.get(listing);
    }

    const trigger = listing.querySelector('[data-related-load-more-trigger]');
    const parsedPageSize = Number.parseInt(listing.dataset.relatedPageSize || '1', 10);
    const pageSize = Number.isNaN(parsedPageSize) ? 1 : Math.max(1, parsedPageSize);

    const hiddenItems = () => Array.from(listing.querySelectorAll('[data-related-load-more-item][hidden]'));

    const updateTrigger = () => {
        if (trigger) {
            trigger.hidden = hiddenItems().length === 0;
        }
    };

    const restoreInitialItems = () => {
        listing.querySelectorAll('[data-related-search-item]').forEach((item) => {
            item.hidden = item.dataset.relatedInitialHidden === 'true';
        });

        updateTrigger();
    };

    if (trigger) {
        trigger.addEventListener('click', () => {
            hiddenItems().slice(0, pageSize).forEach((item) => {
                item.hidden = false;
            });

            updateTrigger();
        });
    }

    const setup = {
        hideTrigger: () => {
            if (trigger) {
                trigger.hidden = true;
            }
        },
        restoreInitialItems,
        updateTrigger,
    };

    relatedLoadMoreSetups.set(listing, setup);

    updateTrigger();

    return setup;
};

document.querySelectorAll('[data-related-load-more]').forEach((listing) => {
    setupRelatedLoadMore(listing);
});

document.querySelectorAll('[data-related-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-related-carousel-track]');
    const viewport = carousel.querySelector('[data-related-carousel-viewport]');
    const controls = carousel.querySelector('.concept-updates__carousel-controls');
    const previous = carousel.querySelector('[data-related-carousel-previous]');
    const next = carousel.querySelector('[data-related-carousel-next]');
    const isAuto = carousel.hasAttribute('data-related-carousel-auto');
    const interval = Number.parseInt(carousel.dataset.relatedCarouselInterval || '10000', 10);
    const autoInterval = Number.isNaN(interval) || interval < 1 ? 10000 : interval;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let autoTimer = null;
    let isAnimating = false;
    const autoPauseReasons = new Set();

    if (! track || ! viewport) {
        return;
    }

    const cards = () => Array.from(track.children).filter((card) => ! card.hidden);
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

    const gapSize = () => {
        const trackStyles = window.getComputedStyle(track);

        return Number.parseFloat(trackStyles.columnGap || trackStyles.gap || '0') || 0;
    };

    const slideDistance = (card) => card.getBoundingClientRect().width + gapSize();

    const setTrackTransition = (enabled) => {
        track.style.transition = enabled ? 'transform 360ms ease' : 'none';
    };

    const resetTrackPosition = () => {
        setTrackTransition(false);
        track.style.transform = 'translateX(0)';
        track.offsetHeight;
        track.style.transition = '';
    };

    const updateControls = () => {
        if (controls) {
            controls.hidden = ! hasOverflow();
        }

        syncAuto();
    };

    const stopAuto = () => {
        if (! autoTimer) {
            return;
        }

        window.clearInterval(autoTimer);
        autoTimer = null;
    };

    const startAuto = () => {
        if (! isAuto || autoPauseReasons.size > 0 || ! hasOverflow() || autoTimer) {
            return;
        }

        autoTimer = window.setInterval(() => {
            if (! hasOverflow()) {
                updateControls();

                return;
            }

            rotate(1, false);
        }, autoInterval);
    };

    function syncAuto() {
        if (! isAuto) {
            return;
        }

        if (autoPauseReasons.size > 0 || ! hasOverflow()) {
            stopAuto();

            return;
        }

        startAuto();
    }

    const restartAuto = () => {
        stopAuto();
        startAuto();
    };

    const pauseAuto = (reason) => {
        autoPauseReasons.add(reason);
        stopAuto();
    };

    const resumeAuto = (reason) => {
        autoPauseReasons.delete(reason);
        syncAuto();
    };

    const rotate = (direction, shouldRestartAuto = true) => {
        if (isAnimating || ! hasOverflow()) {
            syncAuto();

            return;
        }

        const currentCards = cards();

        if (currentCards.length < 2) {
            syncAuto();

            return;
        }

        if (prefersReducedMotion) {
            if (direction > 0) {
                track.append(currentCards[0]);
            } else {
                track.prepend(currentCards[currentCards.length - 1]);
            }

            if (shouldRestartAuto) {
                restartAuto();
            }

            return;
        }

        isAnimating = true;

        const finishRotation = (event) => {
            if (event && event.target !== track) {
                return;
            }

            track.removeEventListener('transitionend', finishRotation);

            if (direction > 0) {
                track.append(currentCards[0]);
            }

            resetTrackPosition();
            isAnimating = false;

            if (shouldRestartAuto) {
                restartAuto();
            }
        };

        if (direction > 0) {
            setTrackTransition(true);
            track.style.transform = `translateX(-${slideDistance(currentCards[0])}px)`;
        } else {
            track.prepend(currentCards[currentCards.length - 1]);
            setTrackTransition(false);
            track.style.transform = `translateX(-${slideDistance(currentCards[currentCards.length - 1])}px)`;
            track.offsetHeight;
            setTrackTransition(true);
            track.style.transform = 'translateX(0)';
        }

        track.addEventListener('transitionend', finishRotation);
        window.setTimeout(() => {
            if (! isAnimating) {
                return;
            }

            finishRotation();
        }, 420);
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
    carousel.addEventListener('related-search-updated', updateControls);

    if (isAuto) {
        carousel.addEventListener('mouseenter', () => {
            pauseAuto('hover');
        });

        carousel.addEventListener('mouseleave', () => {
            resumeAuto('hover');
        });

        carousel.addEventListener('focusin', () => {
            pauseAuto('focus');
        });

        carousel.addEventListener('focusout', () => {
            window.requestAnimationFrame(() => {
                if (carousel.contains(document.activeElement)) {
                    return;
                }

                resumeAuto('focus');
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                pauseAuto('visibility');

                return;
            }

            resumeAuto('visibility');
        });
    }

    updateControls();
});

document.querySelectorAll('[data-related-search-section]').forEach((section) => {
    const input = section.querySelector('[data-related-search-input]');
    const listing = section.querySelector('[data-related-search-listing]');
    const emptyMessage = section.querySelector('[data-related-search-empty]');

    if (! input || ! listing) {
        return;
    }

    const items = Array.from(listing.querySelectorAll('[data-related-search-item]'));
    const loadMore = setupRelatedLoadMore(listing);

    const normalize = (value) => (value || '').toLocaleLowerCase();

    const notifyCarousel = () => {
        section.querySelectorAll('[data-related-carousel]').forEach((carousel) => {
            carousel.dispatchEvent(new CustomEvent('related-search-updated'));
        });
    };

    const updateSearch = () => {
        const query = normalize(input.value.trim());

        if (query === '') {
            loadMore.restoreInitialItems();

            if (emptyMessage) {
                emptyMessage.hidden = true;
            }

            notifyCarousel();

            return;
        }

        let matchCount = 0;

        items.forEach((item) => {
            const isMatch = normalize(item.dataset.relatedSearch).includes(query);
            item.hidden = ! isMatch;

            if (isMatch) {
                matchCount++;
            }
        });

        loadMore.hideTrigger();

        if (emptyMessage) {
            emptyMessage.hidden = matchCount > 0;
        }

        notifyCarousel();
    };

    input.addEventListener('input', updateSearch);
    updateSearch();
});
