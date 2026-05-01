<?php

namespace App\Service;


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
            'model' => 'google/gemini-flash-1.5-8b',
            'label' => 'Gemini',
        ],
        'groq' => [
            // Groq requests are proxied through OpenRouter's unified API
            'url'   => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'label' => 'Groq',
        ],
    ];

    /**
     * Task → provider routing table.
     * Each task is assigned a primary provider; fallback is always openrouter.
     */
    private const TASK_ROUTING = [
        'generate_questions' => 'openrouter',  // Quiz generation
        'conduct_interview'  => 'openrouter',  // Real-time interview chat
        'analyse_cv'         => 'openrouter',  // CV analysis
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
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error: ' . $err);
        }

        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            $msg = $data['error']['message'] ?? $response;
            throw new \RuntimeException("API error {$httpCode} for model {$provider['model']}: " . substr($msg, 0, 500));
        }

        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException("Invalid API response structure: " . substr($response, 0, 500));
        }

        return $data['choices'][0]['message']['content'];
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
        // MOCK MODE: Generate sample questions
        // Remove this when you have a valid API key
        return $this->generateMockQuestions($titre, $domaine, $count);

        /* UNCOMMENT WHEN YOU HAVE A VALID API KEY
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
        */
    }

    private function generateMockQuestions(string $titre, string $domaine, int $count): array
    {
        $questions = [];
        $templates = [
            [
                'question' => "Quelle est votre expérience principale dans le domaine {$domaine} ?",
                'choix' => ['Moins de 1 an', '1-3 ans', '3-5 ans', 'Plus de 5 ans'],
                'bonne_reponse' => 3,
            ],
            [
                'question' => "Quel outil utilisez-vous le plus fréquemment pour {$titre} ?",
                'choix' => ['Outil A', 'Outil B', 'Outil C', 'Outil D'],
                'bonne_reponse' => 2,
            ],
            [
                'question' => "Comment gérez-vous les priorités dans votre travail ?",
                'choix' => ['Méthode Agile', 'Méthode Waterfall', 'Méthode Kanban', 'Méthode personnalisée'],
                'bonne_reponse' => 1,
            ],
            [
                'question' => "Quelle est votre plus grande force pour ce poste ?",
                'choix' => ['Communication', 'Technique', 'Organisation', 'Créativité'],
                'bonne_reponse' => 2,
            ],
            [
                'question' => "Comment restez-vous à jour dans le domaine {$domaine} ?",
                'choix' => ['Formation continue', 'Lecture professionnelle', 'Conférences', 'Pratique personnelle'],
                'bonne_reponse' => 1,
            ],
        ];

        for ($i = 0; $i < min($count, count($templates)); $i++) {
            $questions[] = array_merge($templates[$i], ['points' => 2]);
        }

        return $questions;
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
        // MOCK MODE: Simulate interview
        return $this->conductMockInterview($offreTitre, $domaine, $messages, $totalQuestions);

        /* UNCOMMENT WHEN YOU HAVE A VALID API KEY
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
        */
    }

    private function conductMockInterview(string $offreTitre, string $domaine, array $messages, int $totalQuestions): array
    {
        $answeredCount = count(array_filter($messages, fn($m) => $m['role'] === 'user'));
        
        // If all questions answered, return result
        if ($answeredCount >= $totalQuestions) {
            $score = rand(60, 85); // Random score between 60-85
            return [
                'type' => 'result',
                'score' => $score,
                'summary' => "Le candidat a démontré une compréhension " . ($score >= 75 ? "solide" : "acceptable") . " du poste de {$offreTitre} dans le domaine {$domaine}.",
            ];
        }
        
        // Generate next question
        $questionNumber = $answeredCount + 1;
        $questions = [
            "Pouvez-vous me parler de votre expérience dans le domaine {$domaine} ?",
            "Quels sont vos principaux atouts pour le poste de {$offreTitre} ?",
            "Comment gérez-vous les situations de stress ou les délais serrés ?",
            "Pouvez-vous décrire un projet dont vous êtes particulièrement fier ?",
            "Où vous voyez-vous dans 3 ans dans ce domaine ?",
        ];
        
        $question = $questions[min($questionNumber - 1, count($questions) - 1)];
        
        return [
            'type' => 'question',
            'content' => $question,
        ];
    }

    // ── Public: Analyse CV (Gemini Flash) ────────────────────────────────

    /**
     * Returns ['score' => int(0-100), 'details' => string]
     *
     * Provider: Gemini (google/gemini-flash-1.5-8b) via OpenRouter
     */
    public function analyseCv(string $cvText, string $offreTitre, string $offreDescription, string $domaine): array
    {
        // Limit CV text to 3000 characters to avoid token limits
        $cvText = mb_substr($cvText, 0, 3000);

        if (empty(trim($cvText))) {
            throw new \RuntimeException('CV text is empty after extraction');
        }

        // MOCK MODE: Generate a realistic score based on CV content
        // Remove this section when you have a valid OpenRouter API key
        $score = $this->generateMockScore($cvText, $offreTitre, $domaine);
        $details = $this->generateMockDetails($score, $offreTitre, $domaine);
        
        return [
            'score'   => $score,
            'details' => $details,
        ];

        /* UNCOMMENT THIS WHEN YOU HAVE A VALID API KEY
        $prompt = "You are an HR expert. Analyse this CV against the job offer and give a compatibility score from 0 to 100.\n\n"
            . "Job: {$offreTitre} ({$domaine})\nDescription: {$offreDescription}\n\nCV:\n{$cvText}\n\n"
            . 'Reply ONLY with valid JSON, no text before or after, no markdown. Format: {"score":75,"details":"2-3 sentences about strengths and weaknesses."}';

        try {
            $raw = $this->chat($prompt, 'analyse_cv');
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('OpenRouter API call failed: ' . $e->getMessage());
        }

        $json   = $this->extractJson($raw);
        $result = json_decode($json, true);

        if (!is_array($result) || !isset($result['score'])) {
            throw new \RuntimeException('Invalid JSON from AI. Expected {"score":X,"details":"..."} but got: ' . substr($raw, 0, 300));
        }

        return [
            'score'   => (int) $result['score'],
            'details' => $result['details'] ?? 'No details provided',
        ];
        */
    }

    // ── Mock AI functions for testing ─────────────────────────────────────

    private function generateMockScore(string $cvText, string $offreTitre, string $domaine): int
    {
        // Generate a score based on keyword matching
        $cvLower = strtolower($cvText);
        $domaineLower = strtolower($domaine);
        $titreLower = strtolower($offreTitre);
        
        $score = 50; // Base score
        
        // Check for domain keywords
        if (str_contains($cvLower, $domaineLower)) {
            $score += 15;
        }
        
        // Check for common professional keywords
        $keywords = ['experience', 'compétence', 'projet', 'formation', 'diplôme', 'stage'];
        foreach ($keywords as $keyword) {
            if (str_contains($cvLower, $keyword)) {
                $score += 5;
            }
        }
        
        // Check CV length (longer CVs tend to have more details)
        if (strlen($cvText) > 2000) {
            $score += 10;
        }
        
        return min(95, max(40, $score)); // Keep between 40-95
    }

    private function generateMockDetails(int $score, string $offreTitre, string $domaine): string
    {
        if ($score >= 80) {
            return "Excellent profil pour le poste de {$offreTitre}. Le candidat possède une expérience pertinente dans le domaine {$domaine} et démontre des compétences solides. Recommandé pour un entretien.";
        } elseif ($score >= 65) {
            return "Bon profil pour le poste de {$offreTitre}. Le candidat a des compétences intéressantes dans le domaine {$domaine}, mais pourrait bénéficier d'une expérience supplémentaire. À considérer pour un entretien.";
        } elseif ($score >= 50) {
            return "Profil acceptable pour le poste de {$offreTitre}. Le candidat montre un potentiel dans le domaine {$domaine}, mais manque d'expérience directe. Peut être considéré selon les autres candidatures.";
        } else {
            return "Profil en dessous des attentes pour le poste de {$offreTitre}. Le candidat manque d'expérience pertinente dans le domaine {$domaine}. D'autres candidats seraient plus appropriés.";
        }
    }
}
