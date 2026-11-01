import './bootstrap';

/**
 * Share helpers for the wedding detail page. The server renders the links and
 * the WhatsApp fallback; this only adds clipboard and native-share conveniences
 * when the browser supports them (docs/PRD.md: no delivery tracking claimed).
 */
function announce(button, message) {
    const original = button.dataset.originalLabel ?? button.textContent.trim();

    button.dataset.originalLabel = original;
    button.textContent = message;

    window.setTimeout(() => {
        button.textContent = original;
    }, 1600);
}

document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (target === null) {
        return;
    }

    const copy = target.closest('[data-copy-text]');

    if (copy !== null) {
        try {
            await navigator.clipboard.writeText(copy.dataset.copyText ?? '');
            announce(copy, copy.dataset.copyLabel ?? 'Tautan tersalin');
        } catch {
            window.prompt(copy.dataset.copyLabel ?? 'Salin teks berikut:', copy.dataset.copyText ?? '');
        }

        return;
    }

    const share = target.closest('[data-share-url]');

    if (share !== null && typeof navigator.share === 'function') {
        try {
            await navigator.share({
                title: share.dataset.shareTitle ?? document.title,
                url: share.dataset.shareUrl ?? window.location.href,
            });
        } catch {
            // The user dismissed the sheet; nothing to report.
        }
    }
});

document.querySelectorAll('[data-share-url]').forEach((button) => {
    if (typeof navigator.share === 'function') {
        button.hidden = false;
    }
});

/**
 * Draft autosave. Without JavaScript the form still submits normally; with it,
 * edits are saved after a pause and the page warns before being left dirty.
 * Validation failures keep the draft readable and the operator can still press
 * "Simpan draft" to see field errors.
 */
document.querySelectorAll('form[data-autosave-url]').forEach((form) => {
    const url = form.dataset.autosaveUrl;
    const status = form.dataset.autosaveStatus
        ? document.querySelector(form.dataset.autosaveStatus)
        : null;
    const token = form.querySelector('input[name="_token"]')?.value ?? '';

    if (!url) {
        return;
    }

    let timer = null;
    let saving = false;
    let pending = false;
    let dirty = false;

    const setStatus = (text, tone = 'text-stone-500') => {
        if (status === null) {
            return;
        }

        status.textContent = text;
        status.className = `text-xs ${tone}`;
    };

    const save = async () => {
        if (saving) {
            pending = true;
            return;
        }

        saving = true;
        pending = false;
        setStatus('Menyimpan…');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            if (response.status === 422) {
                const payload = await response.json().catch(() => ({}));
                const first = Object.values(payload.errors ?? {})[0];
                setStatus(
                    'Belum tersimpan: ' + (Array.isArray(first) ? first[0] : payload.message ?? 'periksa isian.'),
                    'text-amber-700',
                );
                dirty = true;

                return;
            }

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const payload = await response.json();
            dirty = false;
            setStatus(
                payload.saved_at ? `Tersimpan ${payload.saved_at}` : 'Tersimpan',
                'text-emerald-700',
            );
        } catch {
            dirty = true;
            setStatus('Gagal menyimpan otomatis. Tekan "Simpan draft" atau periksa koneksi.', 'text-rose-700');
        } finally {
            saving = false;

            if (pending) {
                schedule();
            }
        }
    };

    const schedule = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(save, 1200);
    };

    form.addEventListener('input', () => {
        dirty = true;
        setStatus('Belum disimpan…');
        schedule();
    });

    form.addEventListener('change', () => {
        dirty = true;
        setStatus('Belum disimpan…');
        schedule();
    });

    form.addEventListener('submit', () => {
        dirty = false;
        window.clearTimeout(timer);
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });
});

/**
 * Payment deadline countdown. The server renders the absolute deadline, so the
 * text is readable without JavaScript; this only ticks it down and swaps in the
 * expired message once the window closes.
 */
document.querySelectorAll('[data-countdown-to]').forEach((element) => {
    const deadline = new Date(element.dataset.countdownTo ?? '');
    const label = element.querySelector('[data-countdown-label]');

    if (Number.isNaN(deadline.getTime()) || label === null) {
        return;
    }

    const pad = (value) => String(value).padStart(2, '0');

    const render = () => {
        const remaining = deadline.getTime() - Date.now();

        if (remaining <= 0) {
            label.textContent = element.dataset.countdownExpired ?? 'Waktu pembayaran habis';
            element.classList.add('text-rose-800');
            window.clearInterval(timer);

            return;
        }

        const totalSeconds = Math.floor(remaining / 1000);

        label.textContent = `Sisa ${Math.floor(totalSeconds / 3600)} jam ${pad(Math.floor((totalSeconds % 3600) / 60))} menit ${pad(totalSeconds % 60)} detik`;
    };

    const timer = window.setInterval(render, 1000);
    render();
});

/**
 * Upload guard. When a request body is larger than PHP's post_max_size the
 * server answers with a bare 413 before validation runs.
 */
document.querySelectorAll('form[data-upload-form]').forEach((form) => {
    const input = form.querySelector('input[type="file"]');
    const error = form.querySelector('[data-upload-error]');
    const summary = form.querySelector('[data-upload-summary]');

    if (input === null) {
        return;
    }

    const maxFiles = Number(form.dataset.maxFiles ?? 10);
    const maxFileBytes = Number(form.dataset.maxFileBytes ?? 0);
    const maxTotalBytes = Number(form.dataset.maxTotalBytes ?? 0);
    const format = (bytes) =>
        new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(bytes / 1_048_576);

    let problems = [];

    const render = () => {
        const files = Array.prototype.slice.call(input.files ?? []);
        const total = files.reduce((sum, file) => sum + file.size, 0);
        const oversized = files.filter((file) => file.size > maxFileBytes);

        problems = [];

        if (files.length > maxFiles) {
            problems.push(`Pilih maksimal ${maxFiles} foto sekali unggah (sekarang ${files.length}).`);
        }

        if (oversized.length > 0) {
            problems.push(
                `${oversized.map((file) => file.name).join(', ')} melebihi ${format(maxFileBytes)} MB per foto.`,
            );
        }

        if (total > maxTotalBytes) {
            problems.push(
                `Total ${format(total)} MB melebihi batas ${format(maxTotalBytes)} MB sekali kirim; unggah lebih sedikit foto sekaligus.`,
            );
        }

        if (summary !== null) {
            summary.textContent = files.length > 0 ? `${files.length} foto, ${format(total)} MB` : '';
        }

        if (error !== null) {
            error.textContent = problems.join(' ');
            error.classList.toggle('hidden', problems.length === 0);
        }
    };

    input.addEventListener('change', render);

    form.addEventListener('submit', (event) => {
        render();

        if (problems.length > 0) {
            event.preventDefault();
        }
    });
});

/**
 * The no-JavaScript mobile menu closes on outside tap or Escape.
 */
document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (target === null) {
        return;
    }

    document.querySelectorAll('details[data-mobile-menu]').forEach((menu) => {
        if (!menu.open) {
            return;
        }

        if (menu.contains(target) && target.closest('a') === null) {
            return;
        }

        menu.open = false;
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('details[data-mobile-menu]').forEach((menu) => {
        menu.open = false;
    });
});

/**
 * Catalogue load-more.
 */
let catalogLoading = false;

document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (target === null) {
        return;
    }

    const button = target.closest('[data-load-more]');

    if (button === null || catalogLoading) {
        return;
    }

    event.preventDefault();

    const scope = button.closest('[data-catalog-results]');
    const url = button.dataset.loadMoreUrl;
    const label = button.textContent.trim();

    if (scope === null || !url) {
        return;
    }

    catalogLoading = true;
    button.textContent = button.dataset.loadMoreBusy ?? 'Memuat…';

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        const incoming = parsed.querySelector('[data-catalog-grid]');
        const grid = scope.querySelector('[data-catalog-grid]');
        let appended = 0;

        if (grid !== null && incoming !== null) {
            Array.prototype.forEach.call(incoming.children, (card) => {
                grid.appendChild(card);
                appended += 1;
            });
        }

        const shown = scope.querySelector('[data-catalog-shown]');

        if (shown !== null) {
            shown.textContent = String(Number(shown.textContent ?? '0') + appended);
        }

        const pager = parsed.querySelector('[data-catalog-pager]');
        const current = scope.querySelector('[data-catalog-pager]');

        if (pager !== null && current !== null) {
            current.replaceWith(pager);
        }
    } catch {
        delete button.dataset.loadMore;
        button.textContent = label;
    } finally {
        catalogLoading = false;
    }
});

/**
 * Scroll Reveal Animations
 */
(function initReveal() {
    if (typeof IntersectionObserver === 'undefined') {
        document.querySelectorAll('.reveal, .reveal-stagger').forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0, rootMargin: '0px 0px -20px 0px' });

    document.querySelectorAll('.reveal, .reveal-stagger').forEach((el) => {
        // Immediately reveal elements already in or near the viewport on page load
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight * 1.5 && rect.bottom > 0) {
            el.classList.add('is-visible');
        } else {
            observer.observe(el);
        }
    });
})();

/**
 * Hero Mockup Subtle Tilt Effect
 */
(function initTilt() {
    const card = document.querySelector('[data-mockup-tilt]');
    if (!card) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if ('ontouchstart' in window) return;

    let ticking = false;

    document.addEventListener('mousemove', (e) => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - (rect.left + rect.width / 2);
                const y = e.clientY - (rect.top + rect.height / 2);
                const rotateX = Math.max(-10, Math.min(10, -(y / 35)));
                const rotateY = Math.max(-10, Math.min(10, (x / 35)));
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                ticking = false;
            });
            ticking = true;
        }
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
        card.style.transition = 'transform 0.5s ease';
        setTimeout(() => { card.style.transition = ''; }, 500);
    });
})();
