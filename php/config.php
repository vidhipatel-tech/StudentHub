<?php
declare(strict_types=1);

/*
 * StudentHub settings. These defaults match a fresh XAMPP install.
 * Change them here once; both database connection files read from this file.
 */
date_default_timezone_set('Asia/Kolkata');

const DB_HOST = 'localhost';
const DB_NAME = 'studenthub';
const DB_USER = 'root';
const DB_PASS = 'sqlv23';

/** Seconds of inactivity before a signed-in session expires (15 minutes). */
const SESSION_TIMEOUT = 900;
