/**
 * Progressive enhancement for invitation pages.
 *
 * Nothing here is required to read the invitation: the countdown is already
 * correct in the HTML and the gift accounts open through a native <details>
 * toggle, so the clipboard button is only a shortcut. The server serves this
 * file under the strict `script-src 'self'` policy, so it must never be
 * replaced by an inline script.
 */
(function () {
    'use strict';

    var pad = function (value) {
        return value < 10 ? '0' + value : String(value);
    };

    var startCountdowns = function () {
        var grids = document.querySelectorAll('[data-countdown]');

        Array.prototype.forEach.call(grids, function (grid) {
            var target = Date.parse(grid.getAttribute('data-target') || '');

            if (isNaN(target)) {
                return;
            }

            var cells = {
                days: grid.querySelector('[data-unit="days"]'),
                hours: grid.querySelector('[data-unit="hours"]'),
                minutes: grid.querySelector('[data-unit="minutes"]'),
                seconds: grid.querySelector('[data-unit="seconds"]')
            };

            var timer = null;

            var render = function () {
                var remaining = Math.floor((target - Date.now()) / 1000);

                if (remaining <= 0) {
                    grid.setAttribute('data-finished', '');
                    Object.keys(cells).forEach(function (key) {
                        if (cells[key]) {
                            cells[key].textContent = '00';
                        }
                    });
                    if (timer !== null) {
                        window.clearInterval(timer);
                    }
                    return;
                }

                if (cells.days) {
                    cells.days.textContent = pad(Math.floor(remaining / 86400));
                }
                if (cells.hours) {
                    cells.hours.textContent = pad(Math.floor((remaining % 86400) / 3600));
                }
                if (cells.minutes) {
                    cells.minutes.textContent = pad(Math.floor((remaining % 3600) / 60));
                }
                if (cells.seconds) {
                    cells.seconds.textContent = pad(remaining % 60);
                }
            };

            render();
            timer = window.setInterval(render, 1000);
        });
    };

    var startCopyButtons = function () {
        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            return;
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest ? event.target.closest('[data-copy]') : null;

            if (!button) {
                return;
            }

            var value = String(button.getAttribute('data-copy') || '').replace(/[\s-]/g, '');
            var original = button.textContent;

            navigator.clipboard.writeText(value).then(function () {
                button.textContent = 'Tersalin ✓';
                button.setAttribute('data-copied', 'true');

                window.setTimeout(function () {
                    button.textContent = original;
                    button.removeAttribute('data-copied');
                }, 2000);
            }).catch(function () {
                // Permission denied fallback
            });
        });
    };

    var startMusicPlayer = function () {
        var audio = document.getElementById('wedly-audio');
        var player = document.getElementById('wedly-music-player');
        var toggle = document.getElementById('wedly-music-toggle');
        var coverSwitch = document.querySelector('.pw-cover-switch');

        if (!audio || !player) {
            return;
        }

        var playMusic = function () {
            audio.play().then(function () {
                player.classList.add('is-playing');
            }).catch(function () {
                // Autoplay blocked by browser policy until next interaction
            });
        };

        var pauseMusic = function () {
            audio.pause();
            player.classList.remove('is-playing');
        };

        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                if (audio.paused) {
                    playMusic();
                } else {
                    pauseMusic();
                }
            });
        }

        // Ketika cover dibuka
        if (coverSwitch) {
            coverSwitch.addEventListener('change', function () {
                if (coverSwitch.checked) {
                    playMusic();
                }
            });
        }

        // Listener klik pada label pembuka cover
        document.addEventListener('click', function (e) {
            var openBtn = e.target.closest ? e.target.closest('.cover__open, [for="emerald-cover"], [for="celestial-cover"], [for="pw-cover-switch"], .pw-cover__btn') : null;
            if (openBtn) {
                setTimeout(playMusic, 150);
            }
        });
    };

    // Smooth Scroll Reveal Animation (inviee.id style)
    var startScrollReveal = function () {
        if (!('IntersectionObserver' in window)) {
            // Fallback for older browsers: show all immediately
            var elements = document.querySelectorAll('.reveal-on-scroll');
            for (var i = 0; i < elements.length; i++) {
                elements[i].classList.add('is-revealed');
            }
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        var targets = document.querySelectorAll('.card, .event, .person, .story, .qr, .pw-count, .pw-gift, .pw-wishes, .reveal-on-scroll');
        targets.forEach(function (el) {
            el.classList.add('reveal-on-scroll');
            observer.observe(el);
        });
    };

    startCountdowns();
    startCopyButtons();
    startMusicPlayer();
    startScrollReveal();
})();
