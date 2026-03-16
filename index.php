<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false, // Ubah ke false jika tidak pakai HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Define base URL
define('BASE_URL', '/NXORA_grup');

// Tambahkan kondisi: && $page !== 'login'
if (isset($_SESSION['fingerprint']) && $page !== 'login') { 
    $fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    if ($_SESSION['fingerprint'] !== $fingerprint) {
        $_SESSION = [];
        session_destroy();
        header("Location: /NXORA_grup/?page=login");
        exit;
    }
}

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();



// Load configuration and helpers
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';

// Load controllers
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/ConsultationController.php';

// Load models
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/Service.php';
require_once __DIR__ . '/app/models/Setting.php';
require_once __DIR__ . '/app/models/Trust.php';
require_once __DIR__ . '/app/models/Why.php';
require_once __DIR__ . '/app/models/Consultation.php';
require_once __DIR__ . '/app/models/About.php';


function abort(int $code = 404): void
{
    http_response_code($code);
    require __DIR__ . "/app/views/error/{$code}.php";
    exit;
}

function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}


function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isGuest(): bool
{
    return !isAuthenticated();
}


$page = $_GET['page'] ?? 'landingpage';


$routes = [

    'landingpage' => [
        'view' => 'landingpage.php',
        'auth' => false,
        'title' => 'Home'
    ],

    'register' => [
        'view' => 'register.php',
        'auth' => false,
        'title' => 'Register'
    ],

    'login' => [
        'view' => 'login.php',
        'auth' => false,
        'title' => 'Login'
    ],

    'consultation' => [
        'view' => 'consultation.php',
        'auth' => false,
        'title' => 'Consultation'
    ],

    'dashboard' => [
        'view' => 'users/dashboard.php',
        'auth' => true,
        'title' => 'Dashboard'
    ],

    'adminDashboard' => [
        'view' => 'admin/adminDashboard.php',
        'auth' => true,
        'title' => 'Admin Dashboard'
    ],

    'manage' => [
        'view' => 'manage.php',
        'auth' => true,
        'title' => 'Manage'
    ],

    'orders' => [
        'view' => 'order.php',
        'auth' => true,
        'title' => 'Orders'
    ],

    'transactions' => [
        'view' => 'transactions.php',
        'auth' => true,
        'title' => 'Transactions'
    ],

    'agriculture' => [
        'view' => 'agriculture.php',
        'auth' => true,
        'title' => 'Agriculture'
    ],

    'store' => [
        'view' => 'store.php',
        'auth' => true,
        'title' => 'Store'
    ],


    'admin' => [
        'view' => 'admin.php',
        'auth' => true,
        'role' => 'admin',
        'title' => 'Admin Panel'
    ],

    'logout' => [
        'auth' => true,
        'title' => 'Logout'
    ]
];


if (!array_key_exists($page, $routes)) {
    abort(404);
}

$route = $routes[$page];

$publicRoutes = ['login', 'register', 'landingpage', 'consultation'];

$authRequired = $route['auth'] ?? false;

// Global Auth Guard

if ($authRequired && !isAuthenticated()) {
    if (!in_array($page, $publicRoutes)) {
        redirect(BASE_URL . '/?page=login');
    }
}

// Role Guard
if (isset($route['role']) && !isAdmin()) {
    abort(403);
}

if (isset($route['role']) && !isAdmin()) {
    abort(403);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if ($page === 'login') {
        $controller = new AuthController();
        $controller->login();
        exit;
    }

    if ($page === 'register') {
        $controller = new AuthController();
        $controller->register();
        exit;
    }

    if ($page === 'consultation') {
        $controller = new ConsultationController();
        $controller->send();
        exit;
    }
}


if ($page === 'logout') {
    $controller = new AuthController();
    $controller->logout();
    exit;
}

if ($page === 'landingpage') {
    $serviceModel = new Service();
    $settingModel = new Setting();
    $trustModel = new Trust();
    $whyModel = new Why();
    $aboutModel = new About();

    $services = $serviceModel->getActive();
    $heroTitle = $settingModel->get('hero_title') ?? 'Welcome to NXORA';
    $heroSubtitle = $settingModel->get('hero_subtitle') ?? 'Your trusted partner for business solutions';
    $aboutContent = $settingModel->get('about_content') ?? '';
    $trustItems = $trustModel->getAll();
    $whyItems = $whyModel->getActive();
    $aboutImages = $aboutModel->getImages();
}


$pageTitle = $route['title'] ?? 'NXORA';

function getFlash(string $type): ?string
{
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}


require __DIR__ . '/app/views/layout/header.php';


require __DIR__ . '/app/views/' . $route['view'];


require __DIR__ . '/app/views/layout/footer.php';
