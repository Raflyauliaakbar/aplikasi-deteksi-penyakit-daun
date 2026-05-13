<?php
/**
 * File bootstrap. Di-include di setiap entry-point.
 * Urutan penting: config -> helpers -> auth -> database.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
