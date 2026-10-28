<?php
/**
 * Document download - payment.
 *
 * FIX C4 - this file used to stream any file in its directory to any caller.
 * All checks (session, ownership, path containment, safe filename) now live in
 * download.php.
 */
require_once __DIR__ . '/download.php';

spark_download('payment');
