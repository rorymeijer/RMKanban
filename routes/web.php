<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardListController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardLinkController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MyWorkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SavedFilterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureRegistrationOpen;
use Illuminate\Support\Facades\Route;

/*
 * Installer. Bereikbaar zolang de app niet geïnstalleerd is; daarna blokkeert
 * de EnsureInstalled-middleware deze routes.
 */
Route::get('/install', [InstallController::class, 'show'])->name('install.show');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');
Route::post('/install/test-mail', [InstallController::class, 'testMail'])->name('install.test-mail');

/*
 * Authenticatie.
 */
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallenge'])
        ->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);

    // Zelfregistratie (standaard dicht, via env te openen).
    Route::middleware(EnsureRegistrationOpen::class)->group(function (): void {
        Route::get('/register', [RegisterController::class, 'show'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
 * Applicatie (vereist authenticatie).
 */
Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Boards.
    Route::post('/workspaces/{workspace}/boards', [BoardController::class, 'store'])->name('boards.store');
    Route::get('/boards/{board:slug}', [BoardController::class, 'show'])->name('boards.show');
    Route::patch('/boards/{board}', [BoardController::class, 'update'])->name('boards.update');
    Route::post('/boards/{board}/archive', [BoardController::class, 'archive'])->name('boards.archive');
    Route::post('/boards/{board}/restore', [BoardController::class, 'restore'])->name('boards.restore');

    // Lijsten.
    Route::post('/boards/{board}/lists', [BoardListController::class, 'store'])->name('lists.store');
    Route::patch('/lists/{list}', [BoardListController::class, 'update'])->name('lists.update');
    Route::post('/lists/{list}/move', [BoardListController::class, 'move'])->name('lists.move');
    Route::post('/lists/{list}/archive', [BoardListController::class, 'archive'])->name('lists.archive');
    Route::post('/lists/{list}/restore', [BoardListController::class, 'restore'])->name('lists.restore');

    // Kaarten.
    Route::post('/lists/{list}/cards', [CardController::class, 'store'])->name('cards.store');
    Route::patch('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::post('/cards/{card}/move', [CardController::class, 'move'])->name('cards.move');
    Route::post('/cards/{card}/archive', [CardController::class, 'archive'])->name('cards.archive');
    Route::post('/cards/{card}/restore', [CardController::class, 'restore'])->name('cards.restore');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');

    // Kaartdetails (Fase 3).
    Route::post('/cards/{card}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('/cards/{card}/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    Route::post('/checklists/{checklist}/items', [ChecklistController::class, 'addItem'])->name('checklists.items.store');
    Route::post('/checklist-items/{item}/toggle', [ChecklistController::class, 'toggleItem'])->name('checklists.items.toggle');

    Route::post('/boards/{board}/labels', [LabelController::class, 'store'])->name('labels.store');
    Route::post('/cards/{card}/labels', [LabelController::class, 'attach'])->name('cards.labels.attach');
    Route::delete('/cards/{card}/labels/{label}', [LabelController::class, 'detach'])->name('cards.labels.detach');

    Route::post('/cards/{card}/links', [CardLinkController::class, 'store'])->name('cards.links.store');
    Route::delete('/cards/{card}/links/{link}', [CardLinkController::class, 'destroy'])->name('cards.links.destroy');

    Route::post('/boards/{board}/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
    Route::post('/cards/{card}/custom-fields/{field}', [CustomFieldController::class, 'setValue'])->name('cards.custom-fields.set');

    // Prullenbak.
    Route::get('/boards/{board}/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/boards/{board}/trash/cards/{card}/restore', [TrashController::class, 'restoreCard'])
        ->name('trash.cards.restore');
    Route::delete('/boards/{board}/trash/cards/{card}', [TrashController::class, 'forceDeleteCard'])
        ->name('trash.cards.force');

    // Weergaves & zoeken (Fase 5).
    Route::get('/my-work', [MyWorkController::class, 'index'])->name('my-work');
    Route::get('/search', SearchController::class)->name('search');
    Route::post('/cards/{card}/assignees', [AssignmentController::class, 'store'])->name('cards.assignees.store');
    Route::delete('/cards/{card}/assignees/{user}', [AssignmentController::class, 'destroy'])->name('cards.assignees.destroy');
    Route::get('/boards/{board}/filters', [SavedFilterController::class, 'index'])->name('filters.index');
    Route::post('/boards/{board}/filters', [SavedFilterController::class, 'store'])->name('filters.store');
    Route::delete('/filters/{filter}', [SavedFilterController::class, 'destroy'])->name('filters.destroy');

    // Automations, webhooks, API-tokens, import/export (Fase 6).
    Route::post('/boards/{board}/automations', [AutomationController::class, 'store'])->name('automations.store');
    Route::post('/automations/{automation}/run', [AutomationController::class, 'run'])->name('automations.run');
    Route::post('/boards/{board}/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::delete('/boards/{board}/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/user/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/user/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
    Route::get('/boards/{board}/export.json', [ExportController::class, 'json'])->name('boards.export.json');
    Route::get('/boards/{board}/calendar.ics', [ExportController::class, 'ical'])->name('boards.ical');
    Route::post('/workspaces/{workspace}/import/trello', [ImportController::class, 'trello'])->name('import.trello');

    // Notificaties (Fase 4).
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/user/notification-preferences', [NotificationController::class, 'updatePreferences'])
        ->name('notifications.preferences');

    // Tweestapsverificatie.
    Route::post('/user/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/user/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Workspace-leden & uitnodigingen.
    Route::post('/workspaces/{workspace}/invitations', [WorkspaceMemberController::class, 'invite'])
        ->name('workspaces.invite');
    Route::get('/invitations/{token}/accept', [WorkspaceMemberController::class, 'accept'])
        ->name('invitations.accept');
    Route::delete('/workspaces/{workspace}/members/{member}', [WorkspaceMemberController::class, 'destroy'])
        ->name('workspaces.members.destroy');

    // Adminpaneel.
    Route::middleware(EnsureAdmin::class)->group(function (): void {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    });
});
