<?php

namespace App\Services\Media;

use RuntimeException;

/**
 * Storage no longer has the multipart upload: cleanup aborted it, a bucket
 * lifecycle rule expired it, or it was never started. Its parts are gone, and
 * the device has to begin again from a new intent.
 */
class UploadGone extends RuntimeException {}
