<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Chatbot\ChatbotController;


// CHATBOT
Route::get('/chatbot', [ChatbotController::class, 'chatBot'])->name('chatBot');
Route::post('/chatbot/get-response', [ChatbotController::class, 'queryGroqAI'])->name('queryGroqAI');
