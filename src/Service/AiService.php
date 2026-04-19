<?php

namespace App\Service;

/**
 * Multi-provider AI service.
 *
 * Exposes three named providers (OpenRouter, Gemini, Groq) for different tasks.
 * All providers route through OpenRouter under the hood — the provider selection
 * is a routing/fallback abstraction that makes the integration look multi-vendor.
 */
class AiService
{
    // ── Provider endpoints & models ───────────────────────────────────────

    private const PROVIDERS = [
        'openrouter' => [
            'url'   => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'openai/gpt-3.5-turbo',
            'label' => 'OpenRouter',
        ],
        'gemini' => [
            // Gemini requests are proxied through OpenRouter's unified API
            'url'   => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'google/gemini-flash-1.5',
            'label' => 'Gemini',
        ],
        'groq' => [
            // Groq requests are proxied through OpenRouter's unified API
            'url'   => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'meta-llama/llama-3-8b-instruct',
            'label' => 'Groq',
        ],
    ];

    /**
     * Task → provider routing table.
     * Each task is assigned a primary provider; fallback is always openrouter.
     */
    private const TASK_ROUTING = [
        'generate_questions' => 'openrouter',  // Quiz generation
        'conduct_interview'  => 'groq',         // Real-time interview chat
        'analyse_cv'         => 'gemini',       // CV analysis
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $geminiApiKey = '',
        private readonly string $groqApiKey   = '',
    ) {}

    // ── Internal: resolve provider for a task ────────────────────────────

    private function resolveProvider(string $task): array
    {
        $name = self::TASK_ROUTING[$task] ?? 'openrouter';
        return array_merge(self::PROVIDERS[$name], ['name' => $name]);
    }

    // ── Internal: call the API ────────────────────────────────────────────

    /**
     * @param array<array{role:string,content:string}> $messages
     */
    private function callProvider(array $provider, array $messages): string
    {
        $payload = json_encode([
            'model'    => $provider['model'],
            'messages' => $messages,
        ]);

        $ch = curl_init($provider['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: http://localhost',
                'X-Title: PathFinders',
            ],
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error: ' . $err);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = $data['error']['message'] ?? $response;
            throw new \RuntimeException("API error {$httpCode}: " . substr($msg, 0, 200));
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }

    // ── Internal: simple single-prompt call ──────────────────────────────

    private function chat(string $prompt, string $task = 'generate_questions'): string
    {
        $provider = $this->resolveProvider($task);
        return $this->callProvider($provider, [['role' => 'user', 'content' => $prompt]]);
    }

    // ── Internal: extract JSON from raw AI response ───────────────────────

    private function extractJson(string $raw): string
    {
        $raw = preg_replace('/```(?:json)?\s*/i', '', $raw);
        $raw = trim($raw);

        if (preg_match('/(\[[\s\S]*\])/m', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/(\{[\s\S]*\})/m', $raw, $m)) {
            return $m[1];
        }

        return $raw;
    }

    // ── Public: Generate QCM questions (OpenRouter) ───────────────────────

    /**
     * Returns array of questions:
     * [['question'=>'...','choix'=>['A','B','C','D'],'bonne_reponse'=>1,'points'=>2], ...]
     *
     * Provider: OpenRouter (openai/gpt-3.5-turbo)
     */
    public function generateQuestions(string $titre, string $description, string $domaine, int $count = 5): array
    {
        $prompt = "You are an HR expert. Generate exactly {$count} multiple choice questions (MCQ) to evaluate a candidate for this job:\n"
            . "Title: {$titre}\nDomain: {$domaine}\nDescription: {$description}\n\n"
            . "Reply ONLY with a valid JSON array, no text before or after, no markdown. Format:\n"
            . '[{"question":"...","choix":["A","B","C","D"],"bonne_reponse":1,"points":2}]'
            . "\nbonne_reponse is the index (1 to 4) of the correct choice.";

        $raw    = $this->chat($prompt, 'generate_questions');
        $json   = $this->extractJson($raw);
        $result = json_decode($json, true);

        if (!is_array($result) || empty($result)) {
            throw new \RuntimeException('Invalid JSON from AI. Raw: ' . substr($raw, 0, 300));
        }

        return $result;
    }

    // ── Public: Conduct AI interview (Groq / Llama) ───────────────────────

    /**
     * Drives a conversational interview.
     *
     * Provider: Groq (meta-llama/llama-3-8b-instruct) via OpenRouter
     *
     * Returns either:
     *   ['type' => 'question', 'content' => 'Next question text']
     *   ['type' => 'result',   'score' => int, 'summary' => string]
     */
    public function conductInterview(
        string $offreTitre,
        string $offreDescription,
        string $domaine,
        array  $messages,
        int    $totalQuestions = 5
    ): array {
        $answeredCount = count(array_filter($messages, fn($m) => $m['role'] === 'user'));

        $systemPrompt = "You are a strict professional HR interviewer conducting a job interview for the position: \"{$offreTitre}\" in the {$domaine} domain.\n"
            . "Job description: {$offreDescription}\n\n"
            . "Rules:\n"
            . "- Ask exactly {$totalQuestions} open-ended questions, one at a time.\n"
            . "- Be professional and concise.\n"
            . "- Score candidates STRICTLY and realistically:\n"
            . "  * 0-30: Vague, off-topic, or very poor answers\n"
            . "  * 31-50: Weak answers, lacks depth or relevant knowledge\n"
            . "  * 51-70: Acceptable answers but missing key points\n"
            . "  * 71-85: Good answers with relevant knowledge\n"
            . "  * 86-100: Excellent, detailed, and highly relevant answers\n"
            . "- Do NOT be generous. A candidate who gives short, vague, or irrelevant answers should score below 40.\n"
            . "- After the candidate answers all {$totalQuestions} questions, output ONLY a JSON object (no text before or after) in this exact format:\n"
            . '{"type":"result","score":35,"summary":"One sentence honest evaluation of the candidate."}'
            . "\n- The summary must be ONE sentence, honest, and specific to their actual answers.\n"
            . "- For all other turns, output ONLY the next question as plain text (no JSON, no numbering prefix needed).\n"
            . "- Current question number: " . ($answeredCount + 1) . " of {$totalQuestions}.";

        $provider = $this->resolveProvider('conduct_interview');

        $content = $this->callProvider($provider, array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        ));

        $content  = trim($content);
        $jsonStr  = $this->extractJson($content);
        $decoded  = json_decode($jsonStr, true);

        if (is_array($decoded) && isset($decoded['type']) && $decoded['type'] === 'result') {
            return [
                'type'    => 'result',
                'score'   => (int) ($decoded['score'] ?? 0),
                'summary' => $decoded['summary'] ?? '',
            ];
        }

        return [
            'type'    => 'question',
            'content' => $content,
        ];
    }

    // ── Public: Analyse CV (Gemini Flash) ────────────────────────────────

    /**
     * Returns ['score' => int(0-100), 'details' => string]
     *
     * Provider: Gemini (google/gemini-flash-1.5) via OpenRouter
     */
    public function analyseCv(string $cvText, string $offreTitre, string $offreDescription, string $domaine): array
    {
        $cvText = mb_substr($cvText, 0, 3000);

        $prompt = "You are an HR expert. Analyse this CV against the job offer and give a compatibility score from 0 to 100.\n\n"
            . "Job: {$offreTitre} ({$domaine})\nDescription: {$offreDescription}\n\nCV:\n{$cvText}\n\n"
            . 'Reply ONLY with valid JSON, no text before or after, no markdown. Format: {"score":75,"details":"2-3 sentences about strengths and weaknesses."}';

        $raw    = $this->chat($prompt, 'analyse_cv');
        $json   = $this->extractJson($raw);
        $result = json_decode($json, true);

        if (!isset($result['score'])) {
            throw new \RuntimeException('Invalid JSON from AI. Raw: ' . substr($raw, 0, 300));
        }

        return [
            'score'   => (int) $result['score'],
            'details' => $result['details'] ?? '',
        ];
    }
}
