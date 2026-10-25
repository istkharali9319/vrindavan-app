<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin (ARCHITECTURE.md §8.4)
|--------------------------------------------------------------------------
|
| Loaded with the "web" middleware, the /admin prefix and the "admin." name prefix.
| Staff sign in at /admin/login (the guest login at /login refuses staff, and this one refuses guests).
|
*/

Route::get('/login', Admin\Auth\LoginController::class)->middleware('guest')->name('login');

$staffOnly = ['auth', 'verified', 'role:'.implode('|', Role::staffValues())];

/*
| Every admin page needs a staff role plus the module permission (ARCHITECTURE.md §9).
*/
Route::middleware($staffOnly)->group(function () {
    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile');

    Route::get('/notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [Admin\NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/', Admin\DashboardController::class)
        ->middleware('permission:'.Permission::DashboardView->value)
        ->name('dashboard');

    Route::middleware('permission:'.Permission::UsersView->value)->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show')->withTrashed();
    });

    Route::middleware('permission:'.Permission::UsersManage->value)->group(function () {
        Route::post('/users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reactivate', [Admin\UserController::class, 'reactivate'])->name('users.reactivate');
    });

    Route::middleware('permission:'.Permission::BookingsView->value)->group(function () {
        Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking:reference}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::get('/bookings/{booking:reference}/invoice', [Admin\BookingController::class, 'invoice'])->name('bookings.invoice');
    });

    Route::post('/bookings/{booking:reference}/cancel', [Admin\BookingController::class, 'cancel'])
        ->middleware('permission:'.Permission::BookingsManage->value)
        ->name('bookings.cancel');

    Route::middleware('permission:'.Permission::InventoryView->value)->group(function () {
        Route::get('/rooms', [Admin\RoomController::class, 'index'])->name('rooms.index');
    });

    Route::middleware('permission:'.Permission::InventoryManage->value)->group(function () {
        Route::get('/rooms/create', [Admin\RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms', [Admin\RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}/edit', [Admin\RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [Admin\RoomController::class, 'update'])->name('rooms.update');
    });

    Route::middleware('permission:'.Permission::CatalogManage->value)->group(function () {
        Route::get('/room-types', [Admin\RoomTypeController::class, 'index'])->name('room-types.index');
        Route::get('/room-types/create', [Admin\RoomTypeController::class, 'create'])->name('room-types.create');
        Route::post('/room-types', [Admin\RoomTypeController::class, 'store'])->name('room-types.store');
        Route::get('/room-types/{roomType}/edit', [Admin\RoomTypeController::class, 'edit'])->name('room-types.edit');
        Route::put('/room-types/{roomType}', [Admin\RoomTypeController::class, 'update'])->name('room-types.update');

        Route::get('/experiences', [Admin\ExperienceController::class, 'index'])->name('experiences.index');
        Route::get('/experiences/create', [Admin\ExperienceController::class, 'create'])->name('experiences.create');
        Route::post('/experiences', [Admin\ExperienceController::class, 'store'])->name('experiences.store');
        Route::get('/experiences/{experience}/edit', [Admin\ExperienceController::class, 'edit'])->name('experiences.edit');
        Route::put('/experiences/{experience}', [Admin\ExperienceController::class, 'update'])->name('experiences.update');
        Route::delete('/experiences/{experience}', [Admin\ExperienceController::class, 'destroy'])->name('experiences.destroy');
    });

    Route::middleware('permission:'.Permission::EnquiriesManage->value)->group(function () {
        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::post('/enquiries/{enquiry}/messages', [Admin\EnquiryController::class, 'storeMessage'])->name('enquiries.messages.store');
        Route::delete('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'destroy'])->name('enquiries.destroy');
    });

    Route::middleware('permission:'.Permission::ContentManage->value)->prefix('website')->name('website.')->group(function () {
        Route::get('/', [Admin\WebsitePageController::class, 'index'])->name('index');
        Route::post('/test-email', [Admin\WebsitePageController::class, 'testEmail'])->middleware('throttle:5,1')->name('test-email');
        Route::get('/{page}', [Admin\WebsitePageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [Admin\WebsitePageController::class, 'update'])->name('update');

        Route::get('/{page}/{collection}', [Admin\WebsiteItemController::class, 'index'])->name('items.index');
        Route::get('/{page}/{collection}/create', [Admin\WebsiteItemController::class, 'create'])->name('items.create');
        Route::post('/{page}/{collection}', [Admin\WebsiteItemController::class, 'store'])->name('items.store');
        Route::get('/{page}/{collection}/{item}/edit', [Admin\WebsiteItemController::class, 'edit'])->name('items.edit');
        Route::put('/{page}/{collection}/{item}', [Admin\WebsiteItemController::class, 'update'])->name('items.update');
        Route::delete('/{page}/{collection}/{item}', [Admin\WebsiteItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/{page}/{collection}/{item}/move', [Admin\WebsiteItemController::class, 'move'])->name('items.move');
    });

    Route::middleware('permission:'.Permission::RolesManage->value)->group(function () {
        Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [Admin\StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{user}/edit', [Admin\StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{user}', [Admin\StaffController::class, 'update'])->name('staff.update');
    });
});
