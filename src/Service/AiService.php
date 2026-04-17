<?php

namespace App\Service;

class AiService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL   = 'openai/gpt-3.5-turbo';

    public function __construct(private string $apiKey) {}

    // ── Generic chat call ──────────────────────────────────────────────────

    private function chat(string $prompt): string
    {
        $payload = json_encode([
            'model'    => self::MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init(self::API_URL);
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
            CURLOPT_TIMEOUT        => 45,
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

    // ── Extract JSON from raw AI response ────────────────────────────────

    private function extractJson(string $raw): string
    {
        // Strip markdown code fences
        $raw = preg_replace('/```(?:json)?\s*/i', '', $raw);
        $raw = trim($raw);

        // Try to extract first JSON array [...] or object {...}
        if (preg_match('/(\[[\s\S]*\])/m', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/(\{[\s\S]*\})/m', $raw, $m)) {
            return $m[1];
        }

        return $raw;
    }

    // ── Generate QCM questions for an offre ───────────────────────────────

    /**
     * Returns array of questions:
     * [['question'=>'...','choix'=>['A','B','C','D'],'bonne_reponse'=>1,'points'=>2], ...]
     */
    public function generateQuestions(string $titre, string $description, string $domaine, int $count = 5): array
    {
        $prompt = "You are an HR expert. Generate exactly {$count} multiple choice questions (MCQ) to evaluate a candidate for this job:\n"
            . "Title: {$titre}\nDomain: {$domaine}\nDescription: {$description}\n\n"
            . "Reply ONLY with a valid JSON array, no text before or after, no markdown. Format:\n"
            . '[{"question":"...","choix":["A","B","C","D"],"bonne_reponse":1,"points":2}]'
            . "\nbonne_reponse is the index (1 to 4) of the correct choice.";

        $raw    = $this->chat($prompt);
        $json   = $this->extractJson($raw);
        $result = json_decode($json, true);

        if (!is_array($result) || empty($result)) {
            throw new \RuntimeException('Invalid JSON from AI. Raw: ' . substr($raw, 0, 300));
        }

        return $result;
    }

    // ── Conduct AI interview ──────────────────────────────────────────────

    /**
     * Drives a conversational interview.
     *
     * $messages = [['role'=>'user'|'assistant', 'content'=>'...'], ...]
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

        $payload = json_encode([
            'model'    => self::MODEL,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
        ]);

        $ch = curl_init(self::API_URL);
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

        $content = trim($data['choices'][0]['message']['content'] ?? '');

        // Try to detect a result JSON
        $jsonStr = $this->extractJson($content);
        $decoded = json_decode($jsonStr, true);

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

    // ── Analyse CV text against an offre ──────────────────────────────────

    /**
     * Returns ['score' => int(0-100), 'details' => string]
     */
    public function analyseCv(string $cvText, string $offreTitre, string $offreDescription, string $domaine): array
    {
        // Truncate CV text to avoid token limits
        $cvText = mb_substr($cvText, 0, 3000);

        $prompt = "You are an HR expert. Analyse this CV against the job offer and give a compatibility score from 0 to 100.\n\n"
            . "Job: {$offreTitre} ({$domaine})\nDescription: {$offreDescription}\n\nCV:\n{$cvText}\n\n"
            . 'Reply ONLY with valid JSON, no text before or after, no markdown. Format: {"score":75,"details":"2-3 sentences about strengths and weaknesses."}';

        $raw    = $this->chat($prompt);
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
