/* Carclub.az — frontend behaviour */
(function () {
    'use strict';

    var doc = document;
    var body = doc.body;

    /* ---------- mobile drawer ---------- */
    var drawer = doc.getElementById('drawer');
    function setNav(open) {
        body.classList.toggle('nav-open', open);
        body.style.overflow = open ? 'hidden' : '';
        var b = doc.querySelector('.burger');
        if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    doc.addEventListener('click', function (e) {
        if (e.target.closest('.burger')) { setNav(!body.classList.contains('nav-open')); return; }
        if (e.target.closest('.drawer__close')) { setNav(false); return; }
        if (drawer && e.target === drawer) { setNav(false); return; }
        if (e.target.closest('.drawer__nav a')) { setNav(false); }
    });

    /* ---------- language dropdown ---------- */
    var lang = doc.querySelector('.lang');
    if (lang) {
        lang.querySelector('.lang__btn').addEventListener('click', function (e) {
            e.stopPropagation();
            lang.classList.toggle('is-open');
        });
        doc.addEventListener('click', function () { lang.classList.remove('is-open'); });
    }

    doc.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        setNav(false);
        if (lang) lang.classList.remove('is-open');
        closeLightbox();
    });

    /* ---------- reveal on scroll ---------- */
    var items = doc.querySelectorAll('.reveal');
    if (items.length) {
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) {
                    if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: .06 });
            items.forEach(function (el) { io.observe(el); });
        } else {
            items.forEach(function (el) { el.classList.add('is-in'); });
        }
    }

    /* ---------- car filters (cars page) ---------- */
    var chips = doc.querySelectorAll('[data-filter]');
    if (chips.length) {
        var cards = doc.querySelectorAll('[data-car]');
        var empty = doc.getElementById('cars-empty');
        var grid = doc.querySelector('.cars-grid');
        var heading = doc.getElementById('cars-title');
        var active = doc.querySelector('.chip.is-active');
        var current = active ? active.getAttribute('data-filter') : 'all';

        /* the heading follows the chosen category, the way the old site had it */
        var retitle = function (ch, val) {
            if (!heading) return;
            var base = heading.getAttribute('data-default') || '';
            var label = ch.getAttribute('data-label') || base;
            heading.textContent = val === 'all' ? base : label;
            var full = heading.getAttribute('data-title-default') || doc.title;
            doc.title = val === 'all'
                ? full
                : label + (heading.getAttribute('data-title-suffix') || '');
        };

        var apply = function (val) {
            var shown = 0;
            cards.forEach(function (c) {
                var tags = (c.getAttribute('data-tags') || '').split(' ');
                var ok = val === 'all' || tags.indexOf(val) > -1;
                c.hidden = !ok;
                if (ok) shown++;
            });
            if (empty) empty.hidden = shown > 0;
        };

        var select = function (ch, animate) {
            var v = ch.getAttribute('data-filter');
            if (v === current) return;
            current = v;
            chips.forEach(function (x) {
                var on = x === ch;
                x.classList.toggle('is-active', on);
                x.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            retitle(ch, v);
            if (grid && animate) {
                grid.classList.add('is-swapping');
                setTimeout(function () {
                    apply(v);
                    grid.classList.remove('is-swapping');
                }, 160);
            } else {
                apply(v);
            }
            var u = new URL(location.href);
            if (v === 'all') { u.searchParams.delete('f'); } else { u.searchParams.set('f', v); }
            history.replaceState(null, '', u);
        };

        chips.forEach(function (ch) {
            ch.addEventListener('click', function () { select(ch, true); });
        });

        // ?f=suv opens the page already filtered
        var pre = new URL(location.href).searchParams.get('f');
        if (pre) {
            var target = doc.querySelector('[data-filter="' + CSS.escape(pre) + '"]');
            if (target) select(target, false);
        }
    }

    /* ---------- gallery + lightbox (car page) ---------- */
    var gal = doc.getElementById('gallery');
    var lbEl = doc.getElementById('lightbox');
    var shots = [], idx = 0;

    function paint() {
        if (!gal) return;
        var img = gal.querySelector('.gallery__main img');
        img.src = shots[idx];
        var c = gal.querySelector('.gallery__count');
        if (c) c.textContent = (idx + 1) + ' / ' + shots.length;
        gal.querySelectorAll('.gallery__thumb').forEach(function (t, i) {
            t.classList.toggle('is-active', i === idx);
            t.setAttribute('aria-current', i === idx ? 'true' : 'false');
        });
    }
    function step(d) {
        if (!shots.length) return;
        idx = (idx + d + shots.length) % shots.length;
        paint();
        if (lbEl && lbEl.classList.contains('is-open')) lbEl.querySelector('img').src = shots[idx];
    }
    function openLightbox() {
        if (!lbEl || !shots.length) return;
        lbEl.querySelector('img').src = shots[idx];
        lbEl.classList.add('is-open');
        body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        if (!lbEl) return;
        lbEl.classList.remove('is-open');
        if (!body.classList.contains('nav-open')) body.style.overflow = '';
    }

    if (gal) {
        shots = Array.prototype.map.call(gal.querySelectorAll('.gallery__thumb img'), function (i) {
            return i.getAttribute('data-full') || i.src;
        });
        if (!shots.length) {
            var only = gal.querySelector('.gallery__main img');
            if (only) shots = [only.src];
        }
        gal.addEventListener('click', function (e) {
            var t = e.target.closest('.gallery__thumb');
            if (t) { idx = +t.getAttribute('data-i'); paint(); return; }
            if (e.target.closest('.gallery__nav--prev')) { step(-1); return; }
            if (e.target.closest('.gallery__nav--next')) { step(1); return; }
            if (e.target.closest('.gallery__main')) { openLightbox(); }
        });
        paint();

        /* swipe */
        var x0 = null;
        var main = gal.querySelector('.gallery__main');
        main.addEventListener('touchstart', function (e) { x0 = e.changedTouches[0].clientX; }, { passive: true });
        main.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 45) { step(dx < 0 ? 1 : -1); }
            x0 = null;
        }, { passive: true });
    }

    if (lbEl) {
        lbEl.addEventListener('click', function (e) {
            if (e.target.closest('.lightbox__close') || e.target === lbEl) { closeLightbox(); return; }
            if (e.target.closest('.lightbox__nav--prev')) { step(-1); return; }
            if (e.target.closest('.lightbox__nav--next')) { step(1); }
        });
    }

    doc.addEventListener('keydown', function (e) {
        if (!shots.length) return;
        var lbOpen = lbEl && lbEl.classList.contains('is-open');
        if (!lbOpen && !gal) return;
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === 'ArrowRight') step(1);
    });

    /* ---------- forms: validation, then straight to WhatsApp ---------- */

    function waMessage(f, labels) {
        var get = function (n) {
            var el = f.querySelector('[name="' + n + '"]');
            return el ? el.value.trim() : '';
        };
        var lines = [labels.intro, ''];
        [['name', 'name'], ['phone', 'phone'], ['email', 'email'], ['car', 'car']].forEach(function (p) {
            var v = get(p[0]);
            if (v) lines.push(labels[p[1]] + ': ' + v);
        });
        var msg = get('message');
        if (msg) lines.push('', labels.message + ': ' + msg);
        return lines.join('\n');
    }

    function formNotice(f, text) {
        var box = f.parentNode.querySelector('.form-msg');
        if (!box) {
            box = doc.createElement('p');
            box.className = 'form-msg';
            box.setAttribute('role', 'status');
            f.parentNode.insertBefore(box, f);
        }
        box.className = 'form-msg form-msg--ok';
        box.textContent = text;
    }

    doc.querySelectorAll('form[data-validate]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var bad = null;
            f.querySelectorAll('[required]').forEach(function (el) {
                var wrap = el.closest('.field');
                var ok = el.value.trim() !== '';
                if (ok && el.type === 'email') ok = /^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/.test(el.value.trim());
                if (wrap) wrap.classList.toggle('has-error', !ok);
                if (!ok && !bad) bad = el;
            });
            if (bad) { e.preventDefault(); bad.focus(); return; }

            var wa = f.getAttribute('data-wa');
            if (!wa) return;                       // no number set — post as usual

            e.preventDefault();

            // keep a copy for the admin panel; sendBeacon survives leaving the page
            try {
                var fd = new FormData(f);
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(f.action, fd);
                } else {
                    fetch(f.action, { method: 'POST', body: fd, keepalive: true }).catch(function () { });
                }
            } catch (_) { }

            var labels = {};
            try { labels = JSON.parse(f.getAttribute('data-wa-labels') || '{}'); } catch (_) { }
            var url = 'https://api.whatsapp.com/send?phone=' + wa +
                '&text=' + encodeURIComponent(waMessage(f, labels));

            var win = window.open(url, '_blank', 'noopener');
            if (!win) window.location.href = url;   // popup blocked — go there directly

            formNotice(f, f.getAttribute('data-wa-sent') || '');
            f.reset();
        });

        f.addEventListener('input', function (e) {
            var w = e.target.closest('.field');
            if (w) w.classList.remove('has-error');
        });
    });

    /* ---------- year in footer ---------- */
    doc.querySelectorAll('[data-year]').forEach(function (el) {
        el.textContent = new Date().getFullYear();
    });
})();
