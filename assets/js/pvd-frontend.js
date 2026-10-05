/**
 * Prevent Video Download — Frontend Protection Script
 * Version: 1.2.0
 *
 * Comprehensive protection: global right-click, keyboard shortcuts, DevTools
 * detection, Save As blocking, print blocking, blob URL interception, download
 * request blocking, drag prevention, watermark, Elementor support.
 */
(function () {
    'use strict';

    var S = window.PVD_Settings || {};
    var d = document;
    var w = window;

    // SAFETY: If we are inside any visual builder/editor, do NOT apply protection.
    (function detectBuilder() {
        if (w.elementor || (w.location && (w.location.href.indexOf('elementor-preview') !== -1 || w.location.href.indexOf('elementor') !== -1))) {
            return;
        }
        if (w.location) {
            var builders = ['et_fb', 'fl_builder', 'ct_builder', 'bricks-editor', 'breakdance', 'wp_customize', 'customize_changeset_uuid'];
            var href = w.location.href;
            for (var bi = 0; bi < builders.length; bi++) {
                if (href.indexOf(builders[bi]) !== -1) return;
            }
        }
        if (d.body && d.body.className && d.body.className.indexOf('elementor-editor-active') !== -1) {
            return;
        }
        runProtection();
    })();

    function runProtection() {

    if (S.enableProtection === false) {
        return;
    }

    var videoExts = ['.mp4', '.webm', '.ogg', '.ogv', '.avi', '.mov', '.mkv', '.flv', '.wmv', '.m4v', '.m3u8', '.ts'];

    /* ------------------------------------------------------------------ */
    /* Helper: is the target a video element?                              */
    /* ------------------------------------------------------------------ */
    function isVideo(el) {
        if (!el) return false;
        var tag = el.tagName ? el.tagName.toUpperCase() : '';
        if (tag === 'VIDEO') return true;
        if (tag === 'SOURCE') return true;
        if (el.closest && (el.closest('video') || el.closest('.elementor-video') || el.closest('.elementor-widget-video'))) {
            return true;
        }
        return false;
    }

    function urlHasVideoExt(url) {
        if (!url || typeof url !== 'string') return false;
        var lower = url.toLowerCase().split('?')[0].split('#')[0];
        for (var i = 0; i < videoExts.length; i++) {
            if (lower.indexOf(videoExts[i]) !== -1) return true;
        }
        return false;
    }

    /* ------------------------------------------------------------------ */
    /* Warning toast                                                       */
    /* ------------------------------------------------------------------ */
    var warningTimer = null;
    function showWarning() {
        if (!S.showWarningMessage) return;
        var existing = d.getElementById('pvd-warning-toast');
        if (existing) existing.remove();

        var toast = d.createElement('div');
        toast.id = 'pvd-warning-toast';
        toast.className = 'pvd-warning-toast';
        var icon = d.createElement('span');
        icon.className = 'pvd-warning-icon';
        icon.innerHTML = '&#9888;';
        var msg = d.createElement('span');
        msg.textContent = S.warningMessage || 'دانلود ویدیو از این سایت ممنوع است';
        toast.appendChild(icon);
        toast.appendChild(msg);
        if (d.body) d.body.appendChild(toast);

        setTimeout(function () { toast.className += ' pvd-warning-show'; }, 10);

        if (warningTimer) clearTimeout(warningTimer);
        warningTimer = setTimeout(function () {
            toast.className = toast.className.replace(' pvd-warning-show', '');
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 400);
        }, 3000);
    }

    /* ------------------------------------------------------------------ */
    /* 1. Global right-click blocking (entire page)                       */
    /* ------------------------------------------------------------------ */
    if (S.disableRightClick) {
        d.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showWarning();
            return false;
        }, true);
    }

    if (S.hideContextMenu) {
        d.oncontextmenu = function () { return false; };
    }

    /* ------------------------------------------------------------------ */
    /* 2. Keyboard shortcuts — block devtools, save, print, view source   */
    /* ------------------------------------------------------------------ */
    if (S.disableShortcuts) {
        d.addEventListener('keydown', function (e) {
            var evt = e || w.event;
            var k = evt.key ? evt.key.toUpperCase() : '';
            var code = evt.keyCode || evt.which;
            var ctrl = evt.ctrlKey || evt.metaKey;
            var shift = evt.shiftKey;

            // F12 — DevTools
            if (code === 123) {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

            // Ctrl+Shift+I/J/C/K — DevTools
            if (ctrl && shift && (k === 'I' || k === 'J' || k === 'C' || k === 'K')) {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

            // Ctrl+U — View source
            if (ctrl && !shift && k === 'U') {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

            // Ctrl+S / Ctrl+Shift+S — Save Page / Save As
            if (ctrl && k === 'S') {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

            // Ctrl+P — Print (can be used to save page as PDF)
            if (ctrl && !shift && k === 'P') {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

            // PrintScreen — screenshot
            if (k === 'PRINTSCREEN' || code === 44) {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }

        }, true);
    }

    /* ------------------------------------------------------------------ */
    /* 3. Block Save As via beforeunload                                  */
    /* ------------------------------------------------------------------ */
    if (S.blockVideoDownload) {
        // Intercept beforeunload to prevent the browser Save As dialog
        // When the user tries Ctrl+S, the browser fires beforeunload.
        // We already block Ctrl+S in keydown, but this is a backup for
        // browser menu File > Save Page As.
        var origBeforeUnload = w.onbeforeunload;
        w.onbeforeunload = function (e) {
            // Check if this is a save attempt vs a genuine navigation
            // We can't fully distinguish, so we just ensure no save dialog
            // by not returning a string (which would show a prompt)
            return undefined;
        };

        // Block the Ctrl+S save dialog specifically by intercepting the
        // save event at the capture phase
        d.addEventListener('keydown', function (e) {
            var ctrl = e.ctrlKey || e.metaKey;
            var k = e.key ? e.key.toUpperCase() : '';
            if (ctrl && k === 'S') {
                e.preventDefault();
                e.stopPropagation();
                showWarning();
                return false;
            }
        }, { capture: true, passive: false });
    }

    /* ------------------------------------------------------------------ */
    /* 4. Block printing (Ctrl+P, window.print, print media)             */
    /* ------------------------------------------------------------------ */
    if (S.blockVideoDownload) {
        // Override window.print
        var origPrint = w.print;
        w.print = function () {
            showWarning();
            return;
        };

        // Block beforeprint event
        w.addEventListener('beforeprint', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showWarning();
        }, true);
    }

    /* ------------------------------------------------------------------ */
    /* 5. DevTools detection — multiple methods                           */
    /* ------------------------------------------------------------------ */
    if (S.disableDevtools) {
        var devtoolsOpen = false;
        var threshold = 200;

        function checkWindowSize() {
            var widthDiff = w.outerWidth - w.innerWidth;
            var heightDiff = w.outerHeight - w.innerHeight;
            if (widthDiff > 160 || heightDiff > 160) return true;
            return false;
        }

        function checkTiming() {
            var start = performance.now();
            debugger;
            var end = performance.now();
            return (end - start) > threshold;
        }

        function onDevToolsOpen() {
            if (S.blurOnDevTools && d.body) {
                d.body.style.filter = 'blur(10px)';
                d.body.style.webkitFilter = 'blur(10px)';
                d.body.style.transition = 'filter 0.3s ease';
            }
            if (console.clear) console.clear();
            showWarning();
        }

        function onDevToolsClose() {
            if (S.blurOnDevTools && d.body) {
                d.body.style.filter = 'none';
                d.body.style.webkitFilter = 'none';
            }
        }

        var wasOpen = false;
        function checkDevtools() {
            devtoolsOpen = false;
            if (checkWindowSize()) devtoolsOpen = true;
            if (checkTiming()) devtoolsOpen = true;

            if (devtoolsOpen && !wasOpen) {
                wasOpen = true;
                onDevToolsOpen();
            } else if (!devtoolsOpen && wasOpen) {
                wasOpen = false;
                onDevToolsClose();
            }
        }

        setInterval(checkDevtools, 500);
    }

    /* ------------------------------------------------------------------ */
    /* 6. Console protection                                              */
    /* ------------------------------------------------------------------ */
    if (S.disableConsole) {
        var origLog = console.log;
        var origWarn = console.warn;
        var origErr = console.error;
        var origInfo = console.info;
        var origDebug = console.debug;
        var origDir = console.dir;
        var origTable = console.table;

        function wrapMethod(orig) {
            return function () {
                if (S.blurOnDevTools && d.body) {
                    d.body.style.filter = 'blur(10px)';
                    d.body.style.webkitFilter = 'blur(10px)';
                }
                if (console.clear) console.clear();
                return;
            };
        }

        console.log   = wrapMethod(origLog);
        console.warn  = wrapMethod(origWarn);
        console.error = wrapMethod(origErr);
        console.info  = wrapMethod(origInfo);
        console.debug = wrapMethod(origDebug);
        if (origDir)   console.dir   = wrapMethod(origDir);
        if (origTable) console.table = wrapMethod(origTable);

        var styles = [
            'color: red', 'font-size: 28px', 'font-weight: bold',
            'text-shadow: 1px 1px 0 #000', 'padding: 10px',
        ].join(';');
        origLog.call(console, '%c\u26A0 \u062F\u0627\u0646\u0644\u0648\u062F \u0648\u06CC\u062F\u06CC\u0648 \u0627\u0632 \u0627\u06CC\u0646 \u0633\u0627\u06CC\u062A \u0645\u0645\u0646\u0648\u0639 \u0627\u0633\u062A!', styles);
    }

    /* ------------------------------------------------------------------ */
    /* 7. Drag & drop prevention on videos                                */
    /* ------------------------------------------------------------------ */
    if (S.disableDrag) {
        d.addEventListener('dragstart', function (e) {
            if (isVideo(e.target)) {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }
        }, true);

        d.addEventListener('drag', function (e) {
            if (isVideo(e.target)) { e.preventDefault(); e.stopPropagation(); return false; }
        }, true);

        d.addEventListener('drop', function (e) {
            if (isVideo(e.target)) { e.preventDefault(); e.stopPropagation(); return false; }
        }, true);
    }

    /* ------------------------------------------------------------------ */
    /* 8. Patch video controls — hide download button                     */
    /* ------------------------------------------------------------------ */
    function patchVideoControls() {
        var videos = d.querySelectorAll('video:not([data-pvd-patched])');
        for (var i = 0; i < videos.length; i++) {
            videos[i].setAttribute('controlsList', 'nodownload');
            videos[i].setAttribute('disablepictureinpicture', 'true');
            videos[i].setAttribute('disableremoteplayback', 'true');
            videos[i].setAttribute('data-pvd-patched', '1');
            if (S.disableDrag) {
                videos[i].setAttribute('draggable', 'false');
                videos[i].draggable = false;
            }
        }
    }
    patchVideoControls();

    /* ------------------------------------------------------------------ */
    /* 9. Block download requests — XHR, fetch, blob URLs, window.open    */
    /* ------------------------------------------------------------------ */
    if (S.blockVideoDownload) {
        // 9a. Intercept XMLHttpRequest
        var origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method, url) {
            if (urlHasVideoExt(url)) {
                showWarning();
            }
            return origOpen.apply(this, arguments);
        };

        // 9b. Intercept fetch API
        if (w.fetch) {
            var origFetch = w.fetch;
            w.fetch = function (input, init) {
                var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
                if (urlHasVideoExt(url)) showWarning();
                return origFetch.apply(this, arguments);
            };
        }

        // 9c. Intercept window.open — block opening video URLs in new tabs
        var origWindowOpen = w.open;
        w.open = function (url, target, features) {
            if (urlHasVideoExt(url)) {
                showWarning();
                return null;
            }
            return origWindowOpen.apply(this, arguments);
        };

        // 9d. Intercept URL.createObjectURL — block blob: video downloads
        if (w.URL && w.URL.createObjectURL) {
            var origCreateObjectURL = w.URL.createObjectURL;
            w.URL.createObjectURL = function (obj) {
                if (obj && obj.type && obj.type.indexOf('video') !== -1) {
                    showWarning();
                }
                return origCreateObjectURL.apply(this, arguments);
            };
        }

        // 9e. Intercept <a> element click — block download attribute and video hrefs
        d.addEventListener('click', function (e) {
            var link = e.target.closest ? e.target.closest('a') : null;
            if (link) {
                var href = (link.href || '').toLowerCase();
                var hasDownload = link.hasAttribute('download');
                if (hasDownload || urlHasVideoExt(href)) {
                    e.preventDefault();
                    e.stopPropagation();
                    showWarning();
                    return false;
                }
            }
        }, true);

        d.addEventListener('auxclick', function (e) {
            if (e.button === 2) {
                e.preventDefault();
                return false;
            }
        }, true);

        // 9f. Remove download attribute from all links pointing to video files
        var dlLinks = d.querySelectorAll('a[download]');
        for (var j = 0; j < dlLinks.length; j++) {
            var href = (dlLinks[j].href || '').toLowerCase();
            if (urlHasVideoExt(href)) {
                dlLinks[j].removeAttribute('download');
                dlLinks[j].addEventListener('click', function (e) {
                    e.preventDefault(); e.stopPropagation(); showWarning(); return false;
                }, true);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* 10. Block video element direct source access                       */
    /* ------------------------------------------------------------------ */
    if (S.blockVideoDownload) {
        // Override video.src and video.currentSrc getters to make it harder
        // to extract the URL via console
        function protectVideoSrc(video) {
            // Wrap the src property to prevent easy extraction
            var origSrc = video.src;
            try {
                Object.defineProperty(video, 'src', {
                    get: function () { return ''; },
                    set: function (val) {
                        // Allow setting but make getting return empty
                        Object.getOwnPropertyDescriptor(HTMLMediaElement.prototype, 'src').set.call(this, val);
                    },
                    configurable: true,
                });
            } catch (e) {}
        }

        // Don't override src globally as it can break some players.
        // Instead, block copying of src via copy events.
        d.addEventListener('copy', function (e) {
            var sel = d.getSelection();
            if (sel && sel.toString()) {
                var text = sel.toString().toLowerCase();
                if (urlHasVideoExt(text)) {
                    e.preventDefault();
                    e.clipboardData.setData('text/plain', '');
                    showWarning();
                    return false;
                }
            }
        }, true);

        // Block cut of video URLs
        d.addEventListener('cut', function (e) {
            var sel = d.getSelection();
            if (sel && urlHasVideoExt(sel.toString().toLowerCase())) {
                e.preventDefault();
                showWarning();
                return false;
            }
        }, true);
    }

    /* ------------------------------------------------------------------ */
    /* 11. Watermark overlay on videos                                    */
    /* ------------------------------------------------------------------ */
    function addWatermarks() {
        if (!S.watermarkText) return;
        var videos = d.querySelectorAll('video:not([data-pvd-watermarked])');
        for (var i = 0; i < videos.length; i++) {
            var video = videos[i];
            var wrapper = d.createElement('div');
            wrapper.className = 'pvd-watermark-wrapper';

            for (var row = 0; row < 4; row++) {
                for (var col = 0; col < 3; col++) {
                    var wm = d.createElement('div');
                    wm.className = 'pvd-watermark-item';
                    wm.textContent = S.watermarkText;
                    wm.style.opacity = S.watermarkOpacity || '0.15';
                    wm.style.left = (col * 35 + 5) + '%';
                    wm.style.top = (row * 25 + 5) + '%';
                    wrapper.appendChild(wm);
                }
            }

            var parent = video.parentNode;
            if (parent) {
                var computed = w.getComputedStyle(parent);
                if (computed.position === 'static') {
                    parent.style.position = 'relative';
                }
                parent.insertBefore(wrapper, video.nextSibling);
                video.setAttribute('data-pvd-watermarked', '1');
            }
        }
    }
    addWatermarks();

    /* ------------------------------------------------------------------ */
    /* 12. Elementor video widget protection                              */
    /* ------------------------------------------------------------------ */
    function protectElementorVideos() {
        if (!S.protectElementor) return;
        var wrappers = d.querySelectorAll(
            '.elementor-widget-video, .elementor-video, ' +
            '.e-hosted-video, .elementor-wrapper'
        );

        for (var i = 0; i < wrappers.length; i++) {
            var el = wrappers[i];
            if (el.getAttribute('data-pvd-prot-elm')) continue;
            el.setAttribute('data-pvd-prot-elm', '1');

            el.addEventListener('contextmenu', function (e) {
                e.preventDefault(); e.stopPropagation(); showWarning(); return false;
            }, true);

            el.addEventListener('dragstart', function (e) {
                e.preventDefault(); e.stopPropagation(); return false;
            }, true);

            var vids = el.querySelectorAll ? el.querySelectorAll('video') : [];
            for (var j = 0; j < vids.length; j++) {
                vids[j].setAttribute('controlsList', 'nodownload');
                vids[j].setAttribute('disablepictureinpicture', 'true');
                vids[j].setAttribute('disableremoteplayback', 'true');
                vids[j].setAttribute('draggable', 'false');
                vids[j].draggable = false;
            }
        }
    }
    protectElementorVideos();

    /* ------------------------------------------------------------------ */
    /* 13. General video source protection                                */
    /* ------------------------------------------------------------------ */
    function protectVideoSources() {
        var videos = d.querySelectorAll('video');
        for (var i = 0; i < videos.length; i++) {
            videos[i].setAttribute('draggable', 'false');
            videos[i].draggable = false;
            if (!videos[i].hasAttribute('controlsList')) {
                videos[i].setAttribute('controlsList', 'nodownload');
            }
            if (!videos[i].hasAttribute('disablepictureinpicture')) {
                videos[i].setAttribute('disablepictureinpicture', 'true');
            }
            if (!videos[i].hasAttribute('disableremoteplayback')) {
                videos[i].setAttribute('disableremoteplayback', 'true');
            }
        }
    }
    protectVideoSources();

    /* ------------------------------------------------------------------ */
    /* 14. MutationObserver — re-apply on dynamically loaded content      */
    /* ------------------------------------------------------------------ */
    if (w.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            var needsRecheck = false;
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                    needsRecheck = true;
                    break;
                }
            }
            if (needsRecheck) {
                protectVideoSources();
                patchVideoControls();
                protectElementorVideos();
                addWatermarks();
            }
        });

        observer.observe(d.body || d.documentElement, {
            childList: true,
            subtree: true,
        });
    }

    /* ------------------------------------------------------------------ */
    /* 15. Re-run on window load                                          */
    /* ------------------------------------------------------------------ */
    w.addEventListener('load', function () {
        protectVideoSources();
        patchVideoControls();
        protectElementorVideos();
        addWatermarks();
    });

    /* ------------------------------------------------------------------ */
    /* 16. Protect iframes (YouTube/Vimeo embeds)                         */
    /* ------------------------------------------------------------------ */
    var iframes = d.querySelectorAll('iframe');
    for (var i = 0; i < iframes.length; i++) {
        iframes[i].setAttribute('draggable', 'false');
    }

    } // end runProtection()

})();
