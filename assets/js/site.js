/*
 * itflow.org - the menu on small screens, the menu item for the section on
 * screen, and "ITFlow in action": on a wide screen, pick a step (or use the
 * arrows) to see its screenshot. On a phone every step is open with its own
 * screenshot, so there is nothing to do.
 */
(function () {
    'use strict';

    // The menu button on small screens
    var masthead = document.querySelector('[data-masthead]');
    var menuButton = document.querySelector('[data-menu-button]');
    if (masthead && menuButton) {
        var setOpen = function (open) {
            masthead.classList.toggle('is-open', open);
            menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        menuButton.addEventListener('click', function () {
            setOpen(!masthead.classList.contains('is-open'));
        });
        // A link in the menu closes it - most go somewhere on this page
        masthead.querySelectorAll('.menu a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && masthead.classList.contains('is-open')) {
                setOpen(false);
                menuButton.focus();
            }
        });
    }

    // The menu item for the section on screen - or the one just clicked, while the page scrolls to it
    var spyLinks = Array.prototype.slice.call(document.querySelectorAll('.menu a[href^="#"]'));
    var spyItems = spyLinks.map(function (link) {
        return { link: link, section: document.getElementById(link.getAttribute('href').slice(1)) };
    }).filter(function (item) { return item.section; });
    if (spyItems.length) {
        var clicked = null;
        var settle = null;
        var mark = function (item) {
            spyItems.forEach(function (other) {
                var on = other === item;
                other.link.classList.toggle('is-active', on);
                if (on) {
                    other.link.setAttribute('aria-current', 'location');
                } else {
                    other.link.removeAttribute('aria-current');
                }
            });
        };
        // The last section whose top has passed a line a third of the way down; at the very bottom, the last one
        var spy = function () {
            if (clicked) {
                return;
            }
            var line = window.innerHeight * 0.35;
            var found = null;
            spyItems.forEach(function (item) {
                var box = item.section.getBoundingClientRect();
                if (box.top <= line && box.bottom > 0) {
                    found = item;
                }
            });
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
                var last = spyItems[spyItems.length - 1];
                if (last.section.getBoundingClientRect().top < window.innerHeight) {
                    found = last;
                }
            }
            mark(found);
        };
        spyItems.forEach(function (item) {
            item.link.addEventListener('click', function () {
                clicked = item;
                mark(item);
                // Let go even if the page had nowhere to scroll
                clearTimeout(settle);
                settle = setTimeout(function () { clicked = null; spy(); }, 1000);
            });
        });
        var ticking = false;
        window.addEventListener('scroll', function () {
            // A click holds its item until the scroll it started has stopped
            if (clicked) {
                clearTimeout(settle);
                settle = setTimeout(function () { clicked = null; spy(); }, 200);
                return;
            }
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(function () { ticking = false; spy(); });
            }
        }, { passive: true });
        window.addEventListener('resize', spy);
        // Arriving at /#section, or the page restoring its scroll
        window.addEventListener('load', spy);
        spy();
    }

    // ITFlow in action
    var tour = document.querySelector('[data-tour]');
    if (!tour) {
        return;
    }
    var steps = Array.prototype.slice.call(tour.querySelectorAll('[data-tour-step]'));
    var shots = Array.prototype.slice.call(tour.querySelectorAll('[data-tour-shot]'));
    var heads = Array.prototype.slice.call(tour.querySelectorAll('[data-tour-go]'));
    var count = tour.querySelector('[data-tour-count]');
    var wide = window.matchMedia('(min-width: 1000px)');
    var current = 0;

    function show(index) {
        current = (index + steps.length) % steps.length;
        steps.forEach(function (step, i) { step.classList.toggle('is-active', i === current); });
        shots.forEach(function (shot, i) { shot.classList.toggle('is-active', i === current); });
        // On a phone every step is open
        heads.forEach(function (head, i) { head.setAttribute('aria-expanded', !wide.matches || i === current ? 'true' : 'false'); });
        if (count) {
            count.textContent = (current + 1) + ' of ' + steps.length;
        }
    }

    tour.addEventListener('click', function (e) {
        var go = e.target.closest('[data-tour-go]');
        if (go && wide.matches) {
            show(parseInt(go.getAttribute('data-tour-go'), 10));
            return;
        }
        var by = e.target.closest('[data-tour-step-by]');
        if (by) {
            show(current + parseInt(by.getAttribute('data-tour-step-by'), 10));
        }
    });

    // Up and down move between the steps
    tour.addEventListener('keydown', function (e) {
        var head = e.target.closest('[data-tour-go]');
        if (!head || !wide.matches || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp')) {
            return;
        }
        e.preventDefault();
        var next = (parseInt(head.getAttribute('data-tour-go'), 10) + (e.key === 'ArrowDown' ? 1 : -1) + heads.length) % heads.length;
        show(next);
        heads[next].focus();
    });

    if (wide.addEventListener) {
        wide.addEventListener('change', function () { show(current); });
    }
    show(0);
})();
