<?php

namespace App\Services\Chatbot;

use Exception;
use Illuminate\Support\Facades\Http;

class GroqService {
    public function __construct(){

    }

    public function askAI(array $params){

        $systemPrompt = <<<EOT
        Eres un asistente técnico, especializado EXCLUSIVAMENTE en ayudar a usuarios con pocos conocimientos en los siguientes temas:

        - Informática (hardware, software)
        - Ofimática (Word, Excel, PowerPoint, etc)
        - Teléfonos móviles (Android, Apple)
        - Sistemas operativos (especialmente Windows)
        - Impresoras y periféricos (configuración, instalación, solución de problemas)
        - Conectividad: wifi, Internet, redes y seguridad informática

        
        Tu función es ayudar a resolver dudas técnicas de forma clara y sencilla, incluso si el usuario se expresa de forma informal o usa lenguaje coloquial.
        
        NUNCA respondas sobre:
        - Temas no técnicos (cocina, deportes, medicina, tiempo, etc.)
        - Preguntas sobre esta aplicación

        Si la pregunta es poco clara o no proporciona suficientes detalles, pídele educadamente al usuario que brinde más información para poder ayudarle mejor a encontrar una solución e incluso guiarle paso a paso.
        
        Si te saludan, responde amable y educadamente, pero no sigas la conversación más allá del saludo.
        
        Si te preguntan cómo te llamas, responde amable y educadamente que te llamas Dinobot, pero no sigas más allá de un saludo.
        
        Si la pregunta no tiene nada que ver con los temas técnicos mencionados, responde EXACTAMENTE:
        "⚠️ Lo siento, solo puedo responder preguntas sobre informática y ofimática. Por favor, formula tu duda técnica."
        EOT;        

        $messages = $params['messages'] ?? [];
        array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);

        $responseAI = Http::timeout($params['timeout'])
            ->retry($params['retryTimes'], $params['retryDelay'])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $params['apiKey'],
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $params['model'],
                'messages' => $messages,
                'temperature' => $params['temperature'],
                'max_tokens' => 1000,
            ]);
        
        if ($responseAI->failed()){
            return $this->handleError($responseAI);
        }

        $responseAiData = $responseAI->json();

        if (!isset($responseAiData['choices'][0]['message']['content'])){
            return [
                'error' => 'La API no devolvió una respuesta válida',
                'code' => 'invalid_response_format',
                'status' => 502,
            ];
        }

        return [
            'message' => $responseAiData['choices'][0]['message']['content'],
            'usage' => $responseAiData['usage'] ?? null,
            'model' => $responseAiData['model'] ?? null,
        ];
    }

    private function handleError($response){
        $errorBody = $response->json();
        $statusCode = $response->status();

        $errorMessages = [
            429 => ['Límite de tasa excedido. Por favor, espera un momento antes de intentar nuevamente.', 'rate_limit_exceeded'],
            401 => ['Error de autenticación con Groq.', 'authentication_error'],
            400 => [$errorBody['error']['message'] ?? 'Solicitud mal formada.', 'bad_request'],
        ];

        $default = ['Error en la API de Groq', 'api_error'];

        [$message, $code] = $errorMessages[$statusCode] ?? $default;

        return [
            'error' => $message,
            'code' => $code,
            'details' => $errorBody['error'] ?? null,
            'status' => $statusCode
        ];
    }
}