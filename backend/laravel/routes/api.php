<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\NationController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\WsTokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::patch('/auth/password', [AuthController::class, 'changeOwnPassword']);
    Route::post('/ws/token', [WsTokenController::class, 'issue']);

    Route::get('/meta/about', [MetaController::class, 'about']);

    Route::get('/me/dashboard', [MeController::class, 'dashboard']);
    Route::get('/me/resources', [MeController::class, 'resources']);
    Route::patch('/me/about', [MeController::class, 'updateAbout']);
    Route::get('/me/settings', [MeController::class, 'settings']);
    Route::patch('/me/settings', [MeController::class, 'updateSettings']);
    Route::get('/me/units', [MeController::class, 'units']);
    Route::get('/me/buildings', [MeController::class, 'buildings']);
    Route::get('/me/terrain-square-miles', [MeController::class, 'terrainSquareMiles']);
    Route::get('/players', [MeController::class, 'players']);

    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('role:admin');

    Route::get('/maps/layers', [MapController::class, 'index']);

    Route::get('/nations', [NationController::class, 'index']);
    Route::get('/nations/{nationId}', [NationController::class, 'show']);

    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::get('/chats/{chatId}/messages', [ChatController::class, 'messages']);
    Route::patch('/chats/{chatId}/read', [ChatController::class, 'markRead']);
    Route::patch('/chats/{chatId}/unread', [ChatController::class, 'markUnread']);
    Route::post('/chats/{chatId}/messages', [ChatController::class, 'send']);
    Route::patch('/chats/{chatId}/archive', [ChatController::class, 'archive']);
    Route::patch('/chats/{chatId}/unarchive', [ChatController::class, 'unarchive']);
    Route::delete('/chats/{chatId}', [ChatController::class, 'removeForUser']);

    Route::get('/shop/categories', [ShopController::class, 'categories']);
    Route::get('/shop/items', [ShopController::class, 'items']);
    Route::post('/shop/buy', [ShopController::class, 'buy']);

    Route::middleware('role:admin')->prefix('/admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/nations', [AdminController::class, 'nations']);
        Route::post('/users', [AdminController::class, 'createManagedAccount']);
        Route::delete('/users/{userId}', [AdminController::class, 'deleteManagedAccount']);
        Route::post('/nations', [AdminController::class, 'createPlaceholderNation']);
        Route::put('/nations/{nationId}', [AdminController::class, 'updateNation']);
        Route::post('/nations/{nationId}/units', [AdminController::class, 'addUnitToNation']);
        Route::get('/unit-catalog', [AdminController::class, 'unitCatalog']);
        Route::get('/new-account-defaults', [AdminController::class, 'newAccountDefaults']);
        Route::patch('/new-account-defaults', [AdminController::class, 'updateNewAccountDefaults']);
        Route::patch('/users/{userId}/password', [AuthController::class, 'adminResetPassword']);
        Route::get('/time-tracker', [AdminController::class, 'timeTracker']);
        Route::patch('/time-tracker', [AdminController::class, 'updateTimeTracker']);
        Route::post('/time-tracker/next-year', [AdminController::class, 'advanceYear']);
        Route::get('/notifications', [AdminController::class, 'notifications']);
        Route::delete('/notifications/{notificationId}', [AdminController::class, 'deleteNotification']);
        Route::post('/maps/layers/{layerType}', [MapController::class, 'uploadLayer']);
        Route::post('/chats', [AdminController::class, 'createChat']);
        Route::delete('/chats/{chatId}', [AdminController::class, 'deleteChat']);
        Route::post('/chats/{chatId}/members', [AdminController::class, 'addMembers']);
        Route::delete('/chats/{chatId}/members/{userId}', [AdminController::class, 'removeMember']);
        Route::post('/shop/items', [AdminController::class, 'createShopItem']);
        Route::get('/shop/item-templates', [AdminController::class, 'shopItemTemplates']);
        Route::put('/shop/items/{itemId}', [AdminController::class, 'updateShopItem']);
        Route::delete('/shop/items/{itemId}', [AdminController::class, 'deleteShopItem']);
        Route::get('/visibility/fields', [AdminController::class, 'visibilityFields']);
        Route::get('/visibility/rules', [AdminController::class, 'visibilityRules']);
        Route::put('/visibility/rules', [AdminController::class, 'updateVisibilityRules']);
        Route::get('/game-documents', [AdminController::class, 'gameDocuments']);
        Route::get('/game-documents/{code}', [AdminController::class, 'gameDocument']);
        Route::put('/game-documents/{code}', [AdminController::class, 'updateGameDocument']);
    });
});
