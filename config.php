<?php
// config.php - Database Configuration
// EDIT THESE VALUES FOR YOUR SERVER

define('DB_HOST', 'localhost');
define('DB_NAME', 'viscom_maintenance');
define('DB_USER', 'root');       // Change to your DB username
define('DB_PASS', '');           // Change to your DB password
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Viscom Maintenance Tracking');
define('APP_VERSION', '2.0');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="color:red;padding:20px;font-family:sans-serif;">
                <h2>Database Connection Error</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Please check your config.php settings.</p>
            </div>');
        }
    }
    return $pdo;
}

// Period helpers
function getMonthlyPeriods() {
    return ['January','February','March','April','May','June',
            'July','August','September','October','November','December'];
}
function getQuarterlyPeriods() {
    return ['March','June','September','December'];
}
function getSemiAnnualPeriods() {
    return ['June','December'];
}
function getAnnualPeriods() {
    return ['Year'];
}

function getPeriodsByType($type) {
    switch($type) {
        case 'monthly':     return getMonthlyPeriods();
        case 'quarterly':   return getQuarterlyPeriods();
        case 'semi_annual': return getSemiAnnualPeriods();
        case 'annual':      return getAnnualPeriods();
    }
    return [];
}

function getTypeLabel($type) {
    switch($type) {
        case 'monthly':     return 'Monthly Maintenance';
        case 'quarterly':   return '3 Monthly Maintenance';
        case 'semi_annual': return '6 Monthly Maintenance';
        case 'annual':      return 'Yearly Maintenance';
    }
    return $type;
}
