# Laura & Scott Wedding Photos

Mobile-first wedding photo sharing app for Laura & Scott's wedding on September 26, 2026.

Guests scan one wedding-wide QR code and use `https://photos.lauraandscottforever.com` to:

- Enter their name
- Leave an optional note
- Take a photo with their phone
- Choose one or more existing photos
- Preview their selections
- Upload without creating an account

## Production environment

Network Solutions shared hosting:

- Debian Linux
- PHP 8.4.7
- MySQL 5.7.44
- GD available
- Imagick unavailable
- ZIP extension unavailable
- `upload_max_filesize = 20M`
- `post_max_size = 20M`
- `memory_limit = 64M`

The public app is intended for `/WeddingPhotos/`.

Private credentials and uploaded originals should live outside the public directory in `/WeddingPhotoPrivate/`.

## Private configuration

Copy `config.example.php` to the sibling server directory:

`/WeddingPhotoPrivate/config.php`

Then fill in the database password and create:

- `/WeddingPhotoPrivate/uploads/originals/`

Do not commit the real private configuration file.

## Database

The application uses:

- `events`
- `upload_batches`
- `photos`
- `admins`

See `database/schema.sql`.

## v0.1 scope

This version contains the guest upload flow. The admin gallery is intentionally deferred to the next phase.
