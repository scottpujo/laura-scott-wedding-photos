<?php
declare(strict_types=1);

try {
    require dirname(__DIR__) . '/includes/bootstrap.php';
    requirePost();

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_contains(strtolower($contentType), 'application/json')) {
        jsonResponse(['ok' => false, 'message' => 'Invalid request format.'], 415);
    }

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid request body.'], 400);
    }

    // Honeypot field for simple bot filtering.
    if (trim((string) ($payload['website'] ?? '')) !== '') {
        jsonResponse(['ok' => true, 'batch_token' => bin2hex(random_bytes(32))]);
    }

    $guestName = trim((string) ($payload['guest_name'] ?? ''));
    $note = trim((string) ($payload['note'] ?? ''));

    if ($guestName === '' || mb_strlen($guestName) > 120) {
        jsonResponse(['ok' => false, 'message' => 'Please enter your name.'], 422);
    }

    if (mb_strlen($note) > 500) {
        jsonResponse(['ok' => false, 'message' => 'Your note must be 500 characters or fewer.'], 422);
    }

    $pdo = db($config);

    $eventStmt = $pdo->prepare(
        'SELECT id, uploads_enabled FROM events WHERE slug = :slug LIMIT 1'
    );
    $eventStmt->execute(['slug' => $config['app']['event_slug']]);
    $event = $eventStmt->fetch();

    if (!$event) {
        jsonResponse(['ok' => false, 'message' => 'Wedding event was not found.'], 500);
    }

    if (!(bool) $event['uploads_enabled']) {
        jsonResponse(['ok' => false, 'message' => 'Photo uploads are currently closed.'], 403);
    }

    $batchToken = bin2hex(random_bytes(32));

    $insert = $pdo->prepare(
        'INSERT INTO upload_batches (event_id, guest_name, note, batch_token)
         VALUES (:event_id, :guest_name, :note, :batch_token)'
    );

    $insert->execute([
        'event_id' => $event['id'],
        'guest_name' => $guestName,
        'note' => $note !== '' ? $note : null,
        'batch_token' => $batchToken,
    ]);

    jsonResponse([
        'ok' => true,
        'batch_token' => $batchToken,
    ], 201);
} catch (Throwable $e) {
    error_log('Wedding photo create-batch error: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'message' => 'We could not start your upload. Please try again.'], 500);
}
