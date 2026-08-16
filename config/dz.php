<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (!str_contains($line, '=')) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
loadEnv();

function getDbConnection() {
    $host = getenv('DB_HOST') ?: 'aws-0-eu-north-1.pooler.supabase.com';
    $port = getenv('DB_PORT') ?: '6543';
    $dbname = getenv('DB_NAME') ?: 'postgres';
    $user = getenv('DB_USER') ?: 'postgres.potpocgevsyoxxopwtaq';
    $password = getenv('DB_PASSWORD') ?: 'Mahenge2004#';

    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
        $db = new PDO($dsn, $user, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    } catch (PDOException $e) {
        $dbPath = __DIR__ . '/../../fastnet_backed/database/database.sqlite';
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    }
}

$CurrentPage = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);

$allowed_pages = ['page-login', 'page-register', 'page-forgot-password', 'page-lock-screen', 'page-error-400', 'page-error-403', 'page-error-404', 'page-error-500', 'page-error-503', 'local-api-reset'];

if (!isset($_SESSION['user_role']) && !in_array($CurrentPage, $allowed_pages)) {
    header('Location: page-login.php');
    exit();
}

$DexignZoneSettings = [
    'site_level' => [
        'site_title' => 'FastNet Stays — Admin & Owner Portal',
        'favicon' => 'assets/images/favicon.png',
        'seo' => [
            'page_title' => 'Dashboard Overview',
            'meta' => [
                ['name' => 'keywords', 'content' => 'FastNet Stays, Lodge Management, Owner Portal, Hotel Dashboard, Accommodation Control Center'],
                ['name' => 'author', 'content' => 'FastNet Stays'],
                ['name' => 'robots', 'content' => 'noindex, nofollow'],
                ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, minimal-ui, viewport-fit=cover'],
                ['name' => 'description', 'content' => 'FastNet Stays Admin & Lodge Owner Control Center for real-time lodge management, reservations, and financial payouts.'],
                ['property' => 'og:title', 'content' => 'FastNet Stays — Admin & Owner Portal'],
                ['property' => 'og:description', 'content' => 'FastNet Stays Admin & Lodge Owner Control Center for real-time lodge management, reservations, and financial payouts.'],
                ['name' => 'format-detection', 'content' => 'telephone=no'],
            ],
        ],
        'fonts' => [
            'google' => [
                'families' => [
                    'Poppins:300,400,500,600,700',
                ]
            ]
        ],
        'asset_url' => 'assets/',
        'support_button' => true,
        'theme_option' => true,
    ],
    'global' => [
        'css' => [
            'css/style.css',
        ],
        'js' => [
            'top' => [
                'vendor/global/global.min.js',
            ],
            'bottom' => [
                'js/custom.js',
                'js/dlabnav-init.js',
            ],
        ],
    ],
    'pagelevel' => [
        'index' => [
            'css' => [
                'vendor/owl-carousel/owl.carousel.css',
            ],
            'js' => [
                'vendor/chart.js/Chart.bundle.min.js',
                'vendor/owl-carousel/owl.carousel.js',
            ],
            'seo' => [
                'page_title' => 'Dashboard Overview',
            ],
        ],
        'guest-list' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Guest Directory',
            ],
        ],
        'guest-details' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Guest Profile Details',
            ],
        ],
        'concierge-list' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Concierge Directory',
            ],
        ],
        'room-list' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Room Inventory',
            ],
        ],
        'add-room' => [
            'css' => [
                'vendor/dropzone/dropzone.min.css',
            ],
            'js' => [
                'vendor/dropzone/dropzone.min.js',
            ],
            'seo' => [
                'page_title' => 'Add New Room',
            ],
        ],
        'edit-lodge' => [
            'css' => [
                'vendor/dropzone/dropzone.min.css',
            ],
            'js' => [
                'vendor/dropzone/dropzone.min.js',
            ],
            'seo' => [
                'page_title' => 'Edit Property Details',
            ],
        ],
        'onboarding' => [
            'css' => [
                'vendor/dropzone/dropzone.min.css',
            ],
            'js' => [
                'vendor/dropzone/dropzone.min.js',
            ],
            'seo' => [
                'page_title' => 'Onboard New Lodge',
            ],
        ],
        'reviews' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Customer Reviews',
            ],
        ],
        'ecom-customers' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Verify Lodges & Owners',
            ],
        ],
        'chart-chartist' => [
            'css' => [],
            'js' => [
                'vendor/chart.js/Chart.bundle.min.js',
            ],
            'seo' => [
                'page_title' => 'Financial Analytics',
            ],
        ],
        'chart-flot' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Owner Payout Operations',
            ],
        ],
        'chart-chartjs' => [
            'css' => [
                'vendor/datatables/css/jquery.dataTables.min.css',
            ],
            'js' => [
                'vendor/datatables/js/jquery.dataTables.min.js',
            ],
            'seo' => [
                'page_title' => 'Financial Transaction Ledger',
            ],
        ],
        'ecom-invoice' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Payout Invoices',
            ],
        ],
        'app-profile' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Property Profile',
            ],
        ],
        'app-calender' => [
            'css' => [
                'vendor/fullcalendar/css/main.min.css',
            ],
            'js' => [
                'vendor/fullcalendar/js/main.min.js',
            ],
            'seo' => [
                'page_title' => 'Availability Calendar',
            ],
        ],
        'email-compose' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Messages & Admin Support',
            ],
        ],
        'form-element' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'System Configurations',
            ],
        ],
        'page-login' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Portal Login',
            ],
        ],
        'page-register' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Register Account',
            ],
        ],
        'page-forgot-password' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Forgot Password',
            ],
        ],
        'page-lock-screen' => [
            'css' => [],
            'js' => [],
            'seo' => [
                'page_title' => 'Session Locked',
            ],
        ],
    ],
];

$pageTitle = !empty($DexignZoneSettings['pagelevel'][$CurrentPage]['seo']['page_title']) ? $DexignZoneSettings['pagelevel'][$CurrentPage]['seo']['page_title'] : $DexignZoneSettings['site_level']['seo']['page_title'];

global $AdminTitle;
global $CurrentPage;
global $pageTitle;
global $DexignZoneSettings;
