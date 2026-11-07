<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Frame</title>
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #000; cursor: none; }
        .artwork {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: contain; opacity: 0;
            -webkit-transition: opacity 1.5s ease-in-out; transition: opacity 1.5s ease-in-out;
        }
        .artwork.is-visible { opacity: 1; }
        .caption {
            position: absolute; left: 24px; bottom: 20px; max-width: 60%;
            color: rgba(255, 255, 255, .85); font: 14px/1.4 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            text-shadow: 0 1px 3px rgba(0, 0, 0, .9);
            opacity: 0; -webkit-transition: opacity 1.5s ease-in-out; transition: opacity 1.5s ease-in-out;
        }
        .caption.is-visible { opacity: 1; }
        .caption-title { font-style: italic; }
    </style>
</head>
<body>
    <img class="artwork" alt="">
    <img class="artwork" alt="">
    <div class="caption"><div class="caption-title"></div><div class="caption-meta"></div></div>

    <script>
        (function () {
            var NEXT_URL = @json($nextUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            var RETRY_MS = 60 * 1000;
            var RELOAD_MS = 24 * 60 * 60 * 1000;

            var layers = document.querySelectorAll('.artwork');
            var caption = document.querySelector('.caption');
            var captionTitle = document.querySelector('.caption-title');
            var captionMeta = document.querySelector('.caption-meta');
            var front = 0;
            var currentId = null;

            function schedule(ms) {
                window.setTimeout(next, ms);
            }

            function show(artwork, src) {
                var back = 1 - front;

                layers[back].src = src;
                layers[back].className = 'artwork is-visible';
                layers[front].className = 'artwork';
                front = back;

                captionTitle.textContent = artwork.title || '';
                captionMeta.textContent = [artwork.artist, artwork.date].filter(Boolean).join(', ');
                caption.className = 'caption is-visible';

                currentId = artwork.id;
                schedule(Math.max(5, artwork.interval_seconds || 60) * 1000);
            }

            function preload(artwork) {
                var image = new Image();

                image.onload = function () { show(artwork, artwork.image_url); };
                image.onerror = function () { schedule(RETRY_MS); };
                image.src = artwork.image_url;
            }

            function next() {
                var request = new XMLHttpRequest();
                var url = NEXT_URL + (currentId ? '?current=' + encodeURIComponent(currentId) : '');

                request.open('GET', url, true);
                request.setRequestHeader('Accept', 'application/json');
                request.timeout = 30000;
                request.onload = function () {
                    if (request.status !== 200) {
                        schedule(RETRY_MS);

                        return;
                    }

                    try {
                        preload(JSON.parse(request.responseText));
                    } catch (error) {
                        schedule(RETRY_MS);
                    }
                };
                request.onerror = request.ontimeout = function () { schedule(RETRY_MS); };
                request.send();
            }

            window.setTimeout(function () { window.location.reload(); }, RELOAD_MS);
            next();
        })();
    </script>
</body>
</html>
