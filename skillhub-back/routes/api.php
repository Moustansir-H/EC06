<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\AtelierController;
use App\Http\Controllers\InternalUserSyncController;

// Catalogue & logs d'activité (équivalent skillhub : atelierControl/recup_liste_atelier, log_activity)
Route::get('/ateliers', [AtelierController::class, 'liste']);
Route::get('/ateliers/{id}', [AtelierController::class, 'detail']);
Route::post('/activity-log', [AtelierController::class, 'logActivity']);

// Route interne: synchronisation des utilisateurs depuis skillhub-auth
Route::post('/internal/users/sync', [InternalUserSyncController::class, 'sync']);

// Routes protégées (authentification gérée par skillhub-auth)
Route::middleware(\App\Http\Middleware\JwtTokenMiddleware::class)->group(function () {

    // Formations du formateur connecté
    Route::get('/formations',       [FormationController::class, 'index']);
    Route::post('/formations',      [FormationController::class, 'store']);
    Route::delete('/formations/{id}', [FormationController::class, 'destroy']);
    Route::put('/formations/{id}', [FormationController::class, 'update']);
    Route::get('/formations/{id}/modules', [FormationController::class, 'modulesIndex']);
    Route::post('/formations/{id}/modules', [FormationController::class, 'modulesStore']);

    // Catégories
    Route::get('/categories', [FormationController::class, 'categories']);

    // Journaux d'activité MongoDB (utilisateur connecté)
    Route::get('/activity-logs', [AtelierController::class, 'activityLogs']);
    Route::get('/mes-inscriptions', [AtelierController::class, 'mesInscriptions']);

    // Inscription apprenant à une formation
    Route::post('/ateliers/{id}/inscription', [AtelierController::class, 'inscrire']);
    Route::delete('/ateliers/{id}/inscription', [AtelierController::class, 'desinscrire']);
});
