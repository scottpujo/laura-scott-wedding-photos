<?php
declare(strict_types=1);

try {
    require dirname(__DIR__) . '/includes/bootstrap.php';
    requirePost();

    $batchToken = trim((string) ($_POST['batch_token'] ?? ''));

    if (!preg_match('/^[a-f0-9]{64}$/', $batchToken)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid upload session.'], 422);
    }

    if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
        jsonResponse(['ok' => false, 'message' => 'No photo was received.'], 422);
    }

    $file = $_FILES['photo'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ((int) $file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'This photo is too large for the server.',
            UPLOAD_ERR_PARTIAL => 'The photo upload was interrupted. Please try again.',
            default => 'The photo could not be uploaded.',
        };
        jsonResponse(['ok' => false, 'message' => $message], 422);
    }

    $maxFileSize = (int) $config['uploads']['max_file_size'];
    $fileSize = (int) ($file['size'] ?? 0);

    if ($fileSize < 1 || $fileSize > $maxFileSize) {
        jsonResponse(['ok' => false, 'message' => 'Each photo must be smaller than 18 MB.'], 422);
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        jsonResponse(['ok' => false, 'message' => 'The uploaded file could not be verified.'], 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    $allowed = $config['uploads']['allowed_mime_types'];

    if (!isset($allowed[$mimeType])) {
        jsonResponse(['ok' => false, 'message' => 'Please choose a JPG, PNG, WEBP, HEIC, or HEIF image.'], 415);
    }

    $pdo = db($config);

    $batchStmt = $pdo->prepare(
        'SELECT ub.id
         FROM upload_batches ub
         INNER JOIN events e ON e.id = ub.event_id
         WHERE ub.batch_token = :batch_token
           AND e.slug = :event_slug
           AND e.uploads_enabled = 1
         LIMIT 1'
    );
    $batchStmt->execute([
        'batch_token' => $batchToken,
        'event_slug' => $config['app']['event_slug'],
    ]);
    $batch = $batchStmt->fetch();

    if (!$batch) {
        jsonResponse(['ok' => false, 'message' => 'This upload session is no longer available.'], 403);
    }

    $baseStorage = rtrim((string) $config['uploads']['original_path'], '/\\');
    $relativeDirectory = date('Y') . DIRECTORY_SEPARATOR . date('m');
    $targetDirectory = $baseStorage . DIRECTORY_SEPARATOR . $relativeDirectory;

    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $extension = $allowed[$mimeType];
    $randomName = bin2hex(random_bytes(24)) . '.' . $extension;
    $storedRelative = str_replace(DIRECTORY_SEPARATOR, '/', $relativeDirectory . DIRECTORY_SEPARATOR . $randomName);
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $randomName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Unable to move uploaded photo into storage.');
    }

    @chmod($targetPath, 0640);

    $width = null;
    $height = null;
    $imageSize = @getimagesize($targetPath);
    if (is_array($imageSize)) {
        $width = isset($imageSize[0]) ? (int) $imageSize[0] : null;
        $height = isset($imageSize[1]) ? (int) $imageSize[1] : null;
    }

    $originalFilename = basename((string) ($file['name'] ?? 'photo.' . $extension));
    $originalFilename = mb_substr($originalFilename, 0, 255);
    $sha256 = hash_file('sha256', $targetPath) ?: null;

    try {
        $insert = $pdo->prepare(
            'INSERT INTO photos
                (batch_id, original_filename, stored_filename, thumbnail_filename,
                 mime_type, file_extension, file_size, width, height, sha256)
             VALUES
                (:batch_id, :original_filename, :stored_filename, NULL,
                 :mime_type, :file_extension, :file_size, :width, :height, :sha256)'
        );

        $insert->execute([
            'batch_id' => $batch['id'],
            'original_filename' => $originalFilename,
            'stored_filename' => $storedRelative,
            'mime_type' => $mimeType,
            'file_extension' => $extension,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'sha256' => $sha256,
        ]);
    } catch (Throwable $e) {
        @unlink($targetPath);
        throw $e;
    }

    jsonResponse([
        'ok' => true,
        'photo_id' => (int) $pdo->lastInsertId(),
    ], 201);
} catch (Throwable $e) {
    error_log('Wedding photo upload error: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'message' => 'This photo could not be saved. Please try again.'], 500);
}
