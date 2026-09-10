<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#090909">
    <meta name="description" content="Share your photos from Laura & Scott's wedding.">
    <title>Laura & Scott | Share Your Photos</title>
    <link rel="stylesheet" href="assets/css/app.css?v=1">
    <script src="assets/js/app.js?v=1" defer></script>
</head>
<body>
    <main class="page-shell">
        <section class="upload-card" aria-labelledby="page-title">
            <header class="hero">
                <div class="monogram" aria-hidden="true">L <span>&amp;</span> S</div>
                <div class="gold-rule"><span class="pink-heart">♥</span></div>
                <p class="eyebrow">September 26, 2026</p>
                <h1 id="page-title">Share Your Memories</h1>
                <p class="intro">Help us remember the day through your eyes. Add your favorite photos from our wedding.</p>
            </header>

            <form id="uploadForm" novalidate>
                <div class="field-group">
                    <label for="guestName">Your Name <span class="required">*</span></label>
                    <input id="guestName" name="guest_name" type="text" maxlength="120" autocomplete="name" placeholder="e.g., Sarah & John" required>
                    <p class="field-error" id="nameError" aria-live="polite"></p>
                </div>

                <div class="field-group">
                    <div class="label-row">
                        <label for="note">Leave a Note <span class="optional">Optional</span></label>
                        <span id="noteCount" class="counter">0 / 500</span>
                    </div>
                    <textarea id="note" name="note" maxlength="500" rows="4" placeholder="Share a message for Laura & Scott..."></textarea>
                </div>

                <input id="website" class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

                <div class="photo-actions" aria-label="Add photos">
                    <button type="button" class="action-button action-primary" id="takePhotoButton">
                        <span class="action-icon" aria-hidden="true">📷</span>
                        <span><strong>Take a Photo</strong><small>Open your camera</small></span>
                    </button>

                    <button type="button" class="action-button action-secondary" id="choosePhotosButton">
                        <span class="action-icon" aria-hidden="true">▧</span>
                        <span><strong>Choose Photos</strong><small>Select from your library</small></span>
                    </button>
                </div>

                <input id="cameraInput" class="file-input" type="file" accept="image/*" capture="environment">
                <input id="galleryInput" class="file-input" type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" multiple>

                <section id="selectionPanel" class="selection-panel" hidden aria-labelledby="selectionTitle">
                    <div class="selection-heading">
                        <div>
                            <p class="section-kicker">Ready to share</p>
                            <h2 id="selectionTitle">Selected Photos</h2>
                        </div>
                        <span id="photoCount" class="photo-count">0 / 20</span>
                    </div>
                    <div id="previewGrid" class="preview-grid"></div>
                </section>

                <div id="formMessage" class="form-message" role="status" aria-live="polite"></div>

                <button id="uploadButton" class="upload-button" type="submit" disabled>
                    <span id="uploadButtonText">Upload Photos</span>
                </button>

                <section id="progressPanel" class="progress-panel" hidden aria-live="polite">
                    <div class="progress-copy">
                        <span id="progressLabel">Uploading your memories…</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-track" aria-hidden="true">
                        <div id="progressBar" class="progress-bar"></div>
                    </div>
                    <p id="progressDetail">Preparing upload…</p>
                </section>
            </form>

            <section id="successPanel" class="success-panel" hidden>
                <div class="success-heart" aria-hidden="true">♥</div>
                <h2>Thank You!</h2>
                <p>Your photos were added to Laura & Scott's wedding memories.</p>
                <div class="success-actions">
                    <button type="button" id="addMoreButton" class="action-button action-primary compact">Add More Photos</button>
                    <button type="button" id="doneButton" class="text-button">Done</button>
                </div>
            </section>

            <footer>
                <span class="footer-rule"></span>
                <p>No account required · Up to 20 photos at a time</p>
                <p class="footer-names">Laura &amp; Scott</p>
            </footer>
        </section>
    </main>
</body>
</html>
