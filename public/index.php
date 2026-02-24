<?php
/**
 * Front Controller
 *
 * All requests route through here via .htaccess
 */

// Error reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load core files
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/router.php';
require_once __DIR__ . '/../app/models/Specimen.php';
require_once __DIR__ . '/../app/models/CustomField.php';
require_once __DIR__ . '/../app/models/Photo.php';
require_once __DIR__ . '/../app/controllers/PublicController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

// Set timezone
$config = require __DIR__ . '/../app/config.php';
date_default_timezone_set($config['site']['timezone']);

// Start session
Auth::init();

// Initialize router
$router = new Router();

// ============================================
// Public Routes
// ============================================
$router->get('/', function () {
    PublicController::gallery();
});

$router->get('/search', function () {
    PublicController::search();
});

$router->get('/specimen/{slug}', function ($params) {
    PublicController::detail($params['slug']);
});

// ============================================
// Admin Routes
// ============================================
$router->get('/admin/login', function () {
    AdminController::loginForm();
});

$router->post('/admin/login', function () {
    AdminController::login();
});

$router->get('/admin/logout', function () {
    AdminController::logout();
});

$router->get('/admin', function () {
    AdminController::dashboard();
});

// Specimens
$router->get('/admin/specimens', function () {
    AdminController::specimenList();
});

$router->get('/admin/specimens/create', function () {
    AdminController::specimenForm();
});

$router->post('/admin/specimens/create', function () {
    AdminController::specimenSave();
});

$router->get('/admin/specimens/{id}/edit', function ($params) {
    AdminController::specimenForm((int)$params['id']);
});

$router->post('/admin/specimens/{id}/edit', function ($params) {
    AdminController::specimenSave((int)$params['id']);
});

$router->post('/admin/specimens/{id}/delete', function ($params) {
    AdminController::specimenDelete((int)$params['id']);
});

// Photos
$router->get('/admin/specimens/{id}/photos', function ($params) {
    AdminController::photoManager((int)$params['id']);
});

$router->post('/admin/specimens/{id}/photos/upload', function ($params) {
    AdminController::photoUpload((int)$params['id']);
});

$router->post('/admin/photos/{id}/primary', function ($params) {
    AdminController::photoSetPrimary((int)$params['id']);
});

$router->post('/admin/photos/{id}/caption', function ($params) {
    AdminController::photoUpdateCaption((int)$params['id']);
});

$router->post('/admin/photos/{id}/delete', function ($params) {
    AdminController::photoDelete((int)$params['id']);
});

$router->post('/admin/photos/reorder', function () {
    AdminController::photoReorder();
});

// Custom Fields
$router->get('/admin/fields', function () {
    AdminController::fieldList();
});

$router->get('/admin/fields/create', function () {
    AdminController::fieldForm();
});

$router->post('/admin/fields/create', function () {
    AdminController::fieldSave();
});

$router->get('/admin/fields/{id}/edit', function ($params) {
    AdminController::fieldForm((int)$params['id']);
});

$router->post('/admin/fields/{id}/edit', function ($params) {
    AdminController::fieldSave((int)$params['id']);
});

$router->post('/admin/fields/{id}/toggle', function ($params) {
    AdminController::fieldToggle((int)$params['id']);
});

$router->post('/admin/fields/reorder', function () {
    AdminController::fieldReorder();
});

// Setup (one-time)
$router->get('/admin/setup', function () {
    AdminController::setup();
});

$router->post('/admin/setup', function () {
    AdminController::setupSave();
});

// Dispatch!
$router->dispatch();
