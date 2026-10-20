<?php

use App\Http\Controllers\PlatoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PaymentController;

// Rutas de Administración
Route::get('/', function () {
    return redirect()->route('menu.index');
});
Route::get('/platos', [PlatoController::class, 'index'])->name('platos.index');
Route::get('/platos/crear', [PlatoController::class, 'create'])->name('platos.create');
Route::post('/platos', [PlatoController::class, 'store'])->name('platos.store');
Route::get('/platos/{id}/editar', [PlatoController::class, 'edit'])->name('platos.edit');
Route::put('/platos/{id}', [PlatoController::class, 'update'])->name('platos.update');
Route::delete('/platos/{id}', [PlatoController::class, 'destroy'])->name('platos.destroy');

// Rutas Públicas de la Carta
Route::get('/menu', [PlatoController::class, 'menu'])->name('menu.index');
Route::get('/menu/{id}', [PlatoController::class, 'menuDetalle'])->name('menu.show');
Route::get('/email', [EmailController::class, 'index'])->name('email.index');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');
// Rutas de Stripe
Route::get('/checkout', [PaymentController::class, 'showCheckout'])->name('checkout.show');
Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent'])->name('checkout.intent');
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

// SendGrid Routes
Route::get('/email', [EmailController::class, 'index'])->name('email.index');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');