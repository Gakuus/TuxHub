<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$token = $input['csrf_token'] ?? '';
$stored = $_SESSION['csrf_token'] ?? '';
if (empty($token) || empty($stored) || !hash_equals($stored, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'CSRF inválido']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Mensaje vacío']);
    exit;
}

if (strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Mensaje demasiado largo (máx 2000 caracteres)']);
    exit;
}

if (!rate_limit_check($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'chat', 20, 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'msg' => 'Demasiadas solicitudes. Intenta de nuevo en un minuto.']);
    exit;
}

$provider = env('CHAT_PROVIDER', 'gemini');
$api_key = env('GEMINI_API_KEY', '');
$model = env('GEMINI_MODEL', 'gemini-2.0-flash');

if (empty($api_key)) {
    echo json_encode(['ok' => false, 'msg' => 'API key de Gemini no configurada. Revisa el archivo .env']);
    exit;
}

$context_file = __DIR__ . '/../ANALISIS.md';
$project_context = '';
if (file_exists($context_file)) {
    $project_context = file_get_contents($context_file);
}

$system_prompt = <<<PROMPT
Eres un asistente de IA integrado en el **Sistema Agora**, un sistema PHP de gestión educativa.
Tu función es responder preguntas sobre el proyecto: su arquitectura, funcionalidades, seguridad, base de datos, y cómo usar cada módulo.

Contexto completo del proyecto:
{$project_context}

Reglas:
1. Responde siempre en español, claro y conciso.
2. Si no sabes la respuesta, di que no tienes esa información.
3. Cuando hables de código o consultas SQL, usa bloques de código Markdown.
4. Mantén un tono profesional pero amigable.
5. Si te preguntan sobre seguridad, da recomendaciones específicas basadas en el contexto real del proyecto.
6. NO ejecutes código ni accedas a la base de datos. Solo das información basada en el contexto.
7. Máximo 500 caracteres por respuesta para mantener agilidad.
PROMPT;

// ---------------------------------------------------------------------------
// Gemini API
// ---------------------------------------------------------------------------
if ($provider === 'gemini') {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => $message]],
            ],
        ],
        'systemInstruction' => [
            'parts' => [['text' => $system_prompt]],
        ],
        'generationConfig' => [
            'maxOutputTokens' => 600,
            'temperature' => 0.7,
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        app_log('ERROR', 'Chat Gemini curl error', ['error' => $curl_error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error de conexión con Gemini']);
        exit;
    }

    $data = json_decode($response, true);

    if ($http_code !== 200 || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $error_msg = $data['error']['message'] ?? "Error HTTP $http_code";
        app_log('ERROR', 'Chat Gemini API error', ['http_code' => $http_code, 'error' => $error_msg]);

        if ($http_code === 429) {
            $msg = 'Límite de uso de la API excedido. Esperá un momento y volvé a intentar.';
        } elseif ($http_code === 403 || $http_code === 401) {
            $msg = 'Error de autenticación con la API. Revisá la API key en .env';
        } elseif ($http_code === 400) {
            $msg = 'Error en la solicitud a Gemini. Revisá el modelo o el formato.';
        } else {
            $msg = 'Gemini no está disponible';
        }

        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $msg]);
        exit;
    }

    $reply = trim($data['candidates'][0]['content']['parts'][0]['text']);

// ---------------------------------------------------------------------------
// OpenAI / compatible
// ---------------------------------------------------------------------------
} else {
    $api_url = env('CHAT_API_URL', 'https://api.openai.com/v1/chat/completions');
    $openai_key = env('CHAT_API_KEY', '');
    $model = env('CHAT_MODEL', 'gpt-4o-mini');

    if (empty($openai_key) && str_contains($api_url, 'api.openai.com')) {
        echo json_encode(['ok' => false, 'msg' => 'API key de OpenAI no configurada']);
        exit;
    }

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $message],
        ],
        'max_tokens' => 600,
        'temperature' => 0.7,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            $openai_key ? "Authorization: Bearer $openai_key" : '',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        app_log('ERROR', 'Chat OpenAI curl error', ['error' => $curl_error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error de conexión con el servicio de IA']);
        exit;
    }

    $data = json_decode($response, true);

    if ($http_code !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $error_msg = $data['error']['message'] ?? "Error HTTP $http_code";
        app_log('ERROR', 'Chat API error', ['http_code' => $http_code, 'error' => $error_msg]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'El servicio de IA no está disponible']);
        exit;
    }

    $reply = trim($data['choices'][0]['message']['content']);
}

$reply = mb_substr($reply, 0, 2000);

app_log('INFO', 'Chat completado', [
    'user_id' => $_SESSION['user_id'],
    'msg_len' => strlen($message),
    'reply_len' => strlen($reply),
    'provider' => $provider,
]);

echo json_encode(['ok' => true, 'response' => $reply]);
