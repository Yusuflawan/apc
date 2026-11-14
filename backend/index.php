<?php

require 'vendor/autoload.php';

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$router = new RouteCollector();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = rtrim(dirname($scriptPath), '/');
$normalizedPath = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $path, 1);
$normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;
$normalizedPath = '/' . trim($normalizedPath, '/');

setDefaultHeaders();
handlePreflight();

require_once './app/config/Database.php';
require_once './app/controllers/AuthController.php';
require_once './app/controllers/UserController.php';
// require_once './app/controllers/ProductController.php';
// require_once './app/controllers/SaleController.php';

require_once './app/middlewares/AuthMiddleware.php';

// public routes
$router->post('/auth/register', fn() => (new AuthController())->register());
$router->post('/auth/upload-photo', fn() => (new AuthController())->uploadPhoto());
$router->post('/auth/login', fn() => (new AuthController())->login());
$router->get('/states', fn() => (new UserController())->getStates());
$router->get('/state/{id}/lgas', fn($id) => (new UserController())->getLGAsByStateId($id));


// helper function for protected routes
function protectedRoute($callback)
{
    $auth = new AuthMiddleware();
    $auth->authenticate();        // Check JWT
    return $callback();
}


// // protected routes (authenticated user)
// $router->put(
//     '/cashier/{cashierId}/update-profile',
//     fn($cashierId) =>
//     protectedRoute(
//         fn() => (new AuthController())->updateCashierProfile($cashierId)
//     )
// );

// $router->put(
//     '/profile',
//     fn() =>
//     protectedRoute(
//         fn() => (new AuthController())->updateProfile()
//     )
// );

// $router->put(
//     '/update-password',
//     fn() =>
//     protectedRoute(
//         fn() => (new AuthController())->updatePassword()
//     )
// );

// $router->put(
//     '/cashier/{cashierId}/update-status',
//     fn($cashierId) =>
//     protectedRoute(
//         fn() => (new AuthController())->updateCashierStatus($cashierId)
//     )
// );

// users


// // products
// $router->get(
//     '/products',
//     fn() =>
//     protectedRoute(
//         fn() => (new ProductController())->getProducts()
//     )
// );

// $router->post(
//     '/products',
//     fn() =>
//     protectedRoute(
//         fn() => (new ProductController())->setProduct()
//     )
// );

// $router->get(
//     '/customers',
//     fn() =>
//     protectedRoute(
//         fn() => (new UserController())->getCustomers()
//     )
// );

// $router->get(
//     '/categories',
//     fn() =>
//     protectedRoute(
//         fn() => (new ProductController())->getCategories()
//     )
// );

// $router->post(
//     '/categories',
//     fn() =>
//     protectedRoute(
//         fn() => (new ProductController())->setCategory()
//     )
// );

// $router->put(
//     '/products/{productId}',
//     fn($productId) =>
//     protectedRoute(
//         fn() => (new ProductController())->updateProduct($productId)
//     )
// );

// $router->put(
//     '/categories/{categoryId}',
//     fn($categoryId) =>
//     protectedRoute(
//         fn() => (new ProductController())->updateCategory($categoryId)
//     )
// );

// $router->delete(
//     '/products/{productId}',
//     fn($productId) =>
//     protectedRoute(
//         fn() => (new ProductController())->deleteProduct($productId)
//     )
// );

// $router->delete(
//     '/categories/{categoryId}',
//     fn($categoryId) =>
//     protectedRoute(
//         fn() => (new ProductController())->deleteCategory($categoryId)
//     )
// );

// // sales
// $router->post(
//     '/sales',
//     fn() =>
//     protectedRoute(
//         fn() => (new SaleController())->setSales()
//     )
// );

// $router->put(
//     '/sales/{saleId}',
//     fn($saleId) =>
//     protectedRoute(
//         fn() => (new SaleController())->updateSaleById($saleId)
//     )
// );

// $router->get(
//     '/cashiers',
//     fn() =>
//     protectedRoute(
//         fn() => (new UserController())->getCashiers()
//     )
// );

// $router->get(
//     '/sales',
//     fn() =>
//     protectedRoute(
//         fn() => (new SaleController())->getSales()
//     )
// );

// $router->get(
//     '/sales/{saleId}',
//     fn($saleId) =>
//     protectedRoute(
//         fn() => (new SaleController())->getSaleById($saleId)
//     )
// );

// $router->get(
//     '/dashboard-cards',
//     fn() =>
//     protectedRoute(
//         fn() => (new SaleController())->dashboardCards()
//     )
// );

// $router->get(
//     '/weekly-sales-chart',
//     fn() =>
//     protectedRoute(
//         fn() => (new SaleController())->getSalesChartData()
//     )
// );


try {
    $dispatcher = new Phroute\Phroute\Dispatcher($router->getData());

    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $normalizedPath);
} catch (HttpRouteNotFoundException $e) {
    errorResponse("Route not found", 404);
} catch (HttpMethodNotAllowedException $e) {
    errorResponse("Method not allowed", 405);
} catch (Exception $e) {
    errorResponse("Internal Server Error: " . $e->getMessage(), 500);
}

