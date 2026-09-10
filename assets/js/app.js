(() => {
    'use strict';

    const MAX_FILES = 20;
    const MAX_FILE_SIZE = 18 * 1024 * 1024;
    const acceptedTypes = new Set([
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/heic',
        'image/heif'
    ]);

    const form = document.getElementById('uploadForm');
    const guestName = document.getElementById('guestName');
    const note = document.getElementById('note');
    const website = document.getElementById('website');
    const noteCount = document.getElementById('noteCount');
    const nameError = document.getElementById('nameError');
    const formMessage = document.getElementById('formMessage');
    const takePhotoButton = document.getElementById('takePhotoButton');
    const choosePhotosButton = document.getElementById('choosePhotosButton');
    const cameraInput = document.getElementById('cameraInput');
    const galleryInput = document.getElementById('galleryInput');
    const selectionPanel = document.getElementById('selectionPanel');
    const previewGrid = document.getElementById('previewGrid');
    const photoCount = document.getElementById('photoCount');
    const uploadButton = document.getElementById('uploadButton');
    const uploadButtonText = document.getElementById('uploadButtonText');
    const progressPanel = document.getElementById('progressPanel');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressDetail = document.getElementById('progressDetail');
    const successPanel = document.getElementById('successPanel');
    const addMoreButton = document.getElementById('addMoreButton');
    const doneButton = document.getElementById('doneButton');

    let selectedFiles = [];
    let busy = false;

    takePhotoButton.addEventListener('click', () => cameraInput.click());
    choosePhotosButton.addEventListener('click', () => galleryInput.click());

    cameraInput.addEventListener('change', () => {
        addFiles(cameraInput.files);
        cameraInput.value = '';
    });

    galleryInput.addEventListener('change', () => {
        addFiles(galleryInput.files);
        galleryInput.value = '';
    });

    guestName.addEventListener('input', () => {
        nameError.textContent = '';
        updateSubmitState();
    });

    note.addEventListener('input', () => {
        noteCount.textContent = `${note.value.length} / 500`;
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;

        const name = guestName.value.trim();
        if (!name) {
            nameError.textContent = 'Please enter your name.';
            guestName.focus();
            return;
        }

        if (selectedFiles.length === 0) {
            showMessage('Please take or choose at least one photo.');
            return;
        }

        await uploadAll(name);
    });

    addMoreButton.addEventListener('click', () => {
        successPanel.hidden = true;
        form.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    doneButton.addEventListener('click', () => {
        successPanel.querySelector('p').textContent = 'Thank you for sharing part of the day with us. ♥';
        doneButton.hidden = true;
        addMoreButton.hidden = true;
    });

    function addFiles(fileList) {
        if (!fileList || busy) return;
        clearMessage();

        const incoming = Array.from(fileList);
        const remainingSlots = MAX_FILES - selectedFiles.length;

        if (remainingSlots <= 0) {
            showMessage(`You can upload up to ${MAX_FILES} photos at a time.`);
            return;
        }

        let rejected = 0;
        let tooLarge = 0;
        let duplicates = 0;

        for (const file of incoming.slice(0, remainingSlots)) {
            const lowerName = file.name.toLowerCase();
            const extensionOkay = /\.(jpe?g|png|webp|heic|heif)$/.test(lowerName);
            const typeOkay = acceptedTypes.has(file.type) || (file.type === '' && extensionOkay);

            if (!typeOkay) {
                rejected += 1;
                continue;
            }

            if (file.size > MAX_FILE_SIZE) {
                tooLarge += 1;
                continue;
            }

            const key = `${file.name}:${file.size}:${file.lastModified}`;
            if (selectedFiles.some(item => item.key === key)) {
                duplicates += 1;
                continue;
            }

            selectedFiles.push({ key, file, previewUrl: null });
        }

        if (incoming.length > remainingSlots) {
            showMessage(`Only the first ${remainingSlots} additional photos were added. The limit is ${MAX_FILES}.`);
        } else if (tooLarge) {
            showMessage(`${tooLarge} photo${tooLarge === 1 ? ' was' : 's were'} over 18 MB and could not be added.`);
        } else if (rejected) {
            showMessage(`${rejected} file${rejected === 1 ? ' was' : 's were'} not a supported photo format.`);
        } else if (duplicates) {
            showMessage(`${duplicates} duplicate photo${duplicates === 1 ? ' was' : 's were'} skipped.`);
        }

        renderPreviews();
    }

    function renderPreviews() {
        for (const item of selectedFiles) {
            if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
            item.previewUrl = null;
        }

        previewGrid.innerHTML = '';
        selectionPanel.hidden = selectedFiles.length === 0;
        photoCount.textContent = `${selectedFiles.length} / ${MAX_FILES}`;

        selectedFiles.forEach((item, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'preview-item';

            const canPreview = item.file.type.startsWith('image/') && !/hei[cf]$/i.test(item.file.name);

            if (canPreview) {
                const image = document.createElement('img');
                item.previewUrl = URL.createObjectURL(item.file);
                image.src = item.previewUrl;
                image.alt = `Selected photo ${index + 1}`;
                wrapper.appendChild(image);
            } else {
                const fallback = document.createElement('div');
                fallback.className = 'preview-fallback';
                fallback.textContent = item.file.name.split('.').pop()?.toUpperCase() || 'PHOTO';
                wrapper.appendChild(fallback);
            }

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-photo';
            remove.setAttribute('aria-label', `Remove photo ${index + 1}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                if (busy) return;
                selectedFiles.splice(index, 1);
                renderPreviews();
            });

            wrapper.appendChild(remove);
            previewGrid.appendChild(wrapper);
        });

        uploadButtonText.textContent = selectedFiles.length === 1
            ? 'Upload 1 Photo'
            : `Upload ${selectedFiles.length || ''} Photos`.replace('  ', ' ');

        updateSubmitState();
    }

    function updateSubmitState() {
        uploadButton.disabled = busy || guestName.value.trim() === '' || selectedFiles.length === 0;
    }

    async function uploadAll(name) {
        setBusy(true);
        clearMessage();
        progressPanel.hidden = false;
        setProgress(0, 'Starting your upload…');

        try {
            const batchToken = await createBatch(name, note.value.trim());
            const total = selectedFiles.length;

            for (let index = 0; index < total; index += 1) {
                const item = selectedFiles[index];
                await uploadOne(item.file, batchToken, index, total);
            }

            selectedFiles = [];
            renderPreviews();
            progressPanel.hidden = true;
            form.hidden = true;
            successPanel.hidden = false;
            doneButton.hidden = false;
            addMoreButton.hidden = false;
            successPanel.querySelector('p').textContent = "Your photos were added to Laura & Scott's wedding memories.";
            successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (error) {
            progressPanel.hidden = true;
            showMessage(error instanceof Error ? error.message : 'Upload failed. Please try again.');
        } finally {
            setBusy(false);
        }
    }

    async function createBatch(name, message) {
        const response = await fetch('api/create-batch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                guest_name: name,
                note: message,
                website: website.value
            })
        });

        const data = await readJson(response);
        if (!response.ok || !data.ok || !data.batch_token) {
            throw new Error(data.message || 'We could not start your upload.');
        }
        return data.batch_token;
    }

    function uploadOne(file, batchToken, index, total) {
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('batch_token', batchToken);
            formData.append('photo', file, file.name);

            request.open('POST', 'api/upload.php');
            request.responseType = 'json';

            request.upload.addEventListener('progress', (event) => {
                const fileProgress = event.lengthComputable ? event.loaded / event.total : 0;
                const overall = ((index + fileProgress) / total) * 100;
                setProgress(overall, `Uploading photo ${index + 1} of ${total}…`);
            });

            request.addEventListener('load', () => {
                const data = request.response || {};
                if (request.status >= 200 && request.status < 300 && data.ok) {
                    setProgress(((index + 1) / total) * 100, `Uploaded photo ${index + 1} of ${total}.`);
                    resolve(data);
                } else {
                    reject(new Error(data.message || `Photo ${index + 1} could not be uploaded.`));
                }
            });

            request.addEventListener('error', () => reject(new Error('A network error interrupted the upload. Please try again.')));
            request.addEventListener('abort', () => reject(new Error('The upload was cancelled.')));
            request.send(formData);
        });
    }

    async function readJson(response) {
        try {
            return await response.json();
        } catch {
            return {};
        }
    }

    function setBusy(value) {
        busy = value;
        guestName.disabled = value;
        note.disabled = value;
        takePhotoButton.disabled = value;
        choosePhotosButton.disabled = value;
        updateSubmitState();
    }

    function setProgress(percent, detail) {
        const safe = Math.max(0, Math.min(100, Math.round(percent)));
        progressBar.style.width = `${safe}%`;
        progressPercent.textContent = `${safe}%`;
        progressDetail.textContent = detail;
    }

    function showMessage(message) {
        formMessage.textContent = message;
    }

    function clearMessage() {
        formMessage.textContent = '';
    }

    updateSubmitState();
})();
