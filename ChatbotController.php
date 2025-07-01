<?php


namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\GroqService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ChatbotController extends Controller
{
    protected $chatbotService;
    protected $groqService;

    public function __construct(ChatbotService $chatbotService, GroqService $groqService){
        $this->chatbotService = $chatbotService;
        $this->groqService = $groqService;
    }

    public function chatBot()
    {
        $usuario = $this->getUserLoged();

        Inertia::share('pageTitle', 'Dinobot');

        return Inertia::render('ChatBot', [
            'user' => $usuario,
        ]);
    }

    public function queryGroqAI(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'temperature' => 'sometimes|numeric|between:0,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $model = config('services.groq.model'); 
        $apiKey = config('services.groq.key');
        $timeout = config('services.groq.timeout', 60);
        $retryTimes = config('services.groq.retry_times', 3);
        $retryDelay = config('services.groq.retry_delay', 500);
        $messages = $request->input('messages');
        $temperature = $request->input('temperature', 0.3);

        $userMessages = array_filter($messages, fn($msg) => $msg['role'] === 'user');
        $lastUserMessage = end($userMessages);

        $text = $lastUserMessage['content'] ?? null;

        if ($text) {
            $containsSensitiveData = $this->chatbotService->containsSensitiveDataTextOnly($text); 
        
            if ($containsSensitiveData['containsSensitiveData']) {
                return response()->json([
                    'containsSensitiveData' => true,
                    'message' => $containsSensitiveData['message'],
                ]);
            }
        }        

        try {
            $params = [
                'timeout' => $timeout,
                'retryTimes' => $retryTimes,
                'retryDelay' => $retryDelay,
                'apiKey' => $apiKey,
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];

            $responseMessage = $this->groqService->askAI($params);

            if (isset($responseMessage['error'])) {
                return response()->json([
                    'error' => $responseMessage['error'],
                    'code' => $responseMessage['code'],
                    'details' => $responseMessage['details'] ?? null
                ], $responseMessage['status'] ?? 500);
            }
            
            return response()->json([
                'response' => $responseMessage['message'],
                'usage' => $responseMessage['usage'],
                'model' => $responseMessage['model']
            ]);
        } catch (ConnectionException $e) {
            return response()->json([
                'error' => 'No se pudo conectar con Groq. Verifica tu conexión a internet.',
                'code' => 'connection_error'
            ], 504);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Error al comunicarse con la API de Groq.',
                'code' => 'request_exception'
            ], 500);
        } catch (Exception $e) {
            Log::error('Error en getAIResponse: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => 'Ocurrió un error interno al procesar tu solicitud.',
                'code' => 'internal_error'
            ], 500);
        }
    }
}
