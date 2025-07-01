<?php

namespace App\Services\Chatbot;

class ChatbotService {

    public function __construct (){
    }

    public function containsSensitiveDataTextOnly(string $text): array
    {
        $patterns = [
            'DNI o NIE' => '/\b\d{8}[A-Z]\b|\b[XYZ]\d{7}[A-Z]\b/i',
            'nombres completos' => '/\b([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)\s([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)\b/u',
            'tarjetas de crédito/débito' => '/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{4}\b/',
            'emails' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z]{2,}\b/i',
            'teléfonos' => '/\b(\+?\d{1,3}[-.\s]?)?(\(?\d{2,4}\)?[-.\s]?)?\d{3,4}[-.\s]?\d{3,4}\b/',
            'IBAN' => '/\b[A-Z]{2}[0-9]{2}(?:\s?[0-9]{4}){4,5}\b/',
            'Seguridad Social (España)' => '/\b\d{2}\/?\d{8}\/?\d{2}\b/',
            'URLs' => '/\bhttps?:\/\/[^\s]+\b/',
            'direcciones postales' => '/\b(calle|avda|avenida|plaza|cll|paseo|psj|pasaje|c\/)\s+[a-záéíóúñ\s]+\d*/i',
            'códigos postales' => '/\b(0[1-9]|[1-4][0-9]|5[0-2])\d{3}\b/',
        ];

        foreach ($patterns as $tipo => $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'containsSensitiveData' => true,
                    'message' => "Por seguridad, no puedo procesar $tipo.",
                ];
            }
        }

        $sensitiveKeywords = [
            "dni",
            "nie",
            "pasaporte",
            "carnet",
            "identidad",
            "tarjeta crédito",
            "tarjeta debito",
            "número cuenta",
            "contraseña",
            "password",
            "credenciales",
            "seguridad social",
            "tarjeta sanitaria",
            "historial médico",
            "dirección",
            "domicilio",
            "fecha nacimiento",
            "nacido el",
            "nombre completo",
        ];

        $lowerText = mb_strtolower($text);
        foreach ($sensitiveKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return [
                    'containsSensitiveData' => true,
                    'message' => "He detectado que podrías estar compartiendo información sensible ('$keyword'). Por seguridad, no puedo procesar este tipo de datos.",
                ];
            }
        }

        return [
            'containsSensitiveData' => false,
            'message' => null, 
        ];
    }
}