<?php

namespace App\Service\Reclamation;

use App\Repository\ReclamationRepository;

class ReclamationModerationService
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
        private readonly ReclamationRepository $reclamationRepository,
        private readonly bool $moderationEnabled,
    ) {
    }

    /**
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    public function moderate(int $userId, string $title, string $description): array
    {
        $title = trim($title);
        $description = trim($description);

        if (!$this->moderationEnabled) {
            return $this->acceptedFallback();
        }

        $text = $this->normalize($title . ' ' . $description);
        $flags = [];
        $score = 0.0;

        if ($description === '' || mb_strlen($description) < 10) {
            $flags[] = 'empty_or_low_value';
            $score += 0.65;
        }

        if (mb_strlen($description) < 25) {
            $flags[] = 'very_short_complaint';
            $score += 0.25;
        }

        if ($this->containsAny($text, ['con', 'salope', 'pute', 'encule', 'batard', 'fdp', 'merde', 'nique', 'abruti'])) {
            $flags[] = 'insults_or_offensive_language';
            $score += 0.8;
        }

        if ($this->containsAny($text, ['haine', 'raciste', 'menace', 'je vais te tuer', 'harceler', 'terroriste'])) {
            $flags[] = 'hate_harassment_or_threats';
            $score += 0.9;
        }

        if ($this->containsAny($text, ['promo', 'promotion', 'cliquez', 'abonnez-vous', 'http://', 'https://', 'code promo'])) {
            $flags[] = 'spam_or_promotional_content';
            $score += 0.65;
        }

        if ($this->looksLikeGibberish($description)) {
            $flags[] = 'nonsense_or_gibberish';
            $score += 0.6;
        }

        if ($this->hasSensitiveDataExcess($description)) {
            $flags[] = 'excess_sensitive_data';
            $score += 0.45;
        }

        if ($this->isOverlyAggressive($description)) {
            $flags[] = 'overly_aggressive_language';
            $score += 0.4;
        }

        if ($this->isVerySimilarToRecent($userId, $text)) {
            $flags[] = 'duplicate_recent_complaint';
            $score += 0.5;
        }

        $local = $this->classifyFromLocal($flags, $score);

        if (!$this->geminiClient->isEnabled()) {
            return $local;
        }

        $ai = $this->callAiModeration($title, $description);

        return $this->mergeResults($local, $ai);
    }

    /**
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    private function callAiModeration(string $title, string $description): array
    {
        try {
            $prompt = "Tu es un moteur de moderation de reclamations en francais.\n"
                . "Reponds UNIQUEMENT en JSON valide avec ce schema:\n"
                . "{\"status\":\"accepted|review|rejected\",\"reason\":\"...\",\"score\":0.0,\"flags\":[\"...\"],\"summary\":\"...\",\"urgency\":\"low|medium|high\",\"sentiment\":\"negative|neutral|mixed\"}\n"
                . "Regles: detecte insultes, haine/harcelement/menaces, spam, texte vide ou faible, incoherent, agressivite excessive, donnees sensibles excessives.\n"
                . "Titre: {$title}\n"
                . "Description: {$description}";

            $result = $this->geminiClient->generateJson($prompt);

            $status = strtolower(trim((string) ($result['status'] ?? 'review')));
            if (!in_array($status, ['accepted', 'review', 'rejected'], true)) {
                $status = 'review';
            }

            $flags = array_values(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                is_array($result['flags'] ?? null) ? $result['flags'] : []
            )));

            return [
                'status' => $status,
                'reason' => trim((string) ($result['reason'] ?? 'Contenu sensible detecte.')),
                'score' => max(0.0, min(1.0, (float) ($result['score'] ?? 0.5))),
                'flags' => $flags,
                'summary' => trim((string) ($result['summary'] ?? 'Analyse automatique effectuee.')),
                'urgency' => $this->sanitizeUrgency((string) ($result['urgency'] ?? 'medium')),
                'sentiment' => $this->sanitizeSentiment((string) ($result['sentiment'] ?? 'neutral')),
            ];
        } catch (\Throwable) {
            return $this->reviewFallback('Analyse IA indisponible, verification manuelle recommandee.');
        }
    }

    /**
     * @param array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string} $local
     * @param array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string} $ai
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    private function mergeResults(array $local, array $ai): array
    {
        $priority = ['accepted' => 0, 'review' => 1, 'rejected' => 2];
        $status = ($priority[$ai['status']] ?? 1) >= ($priority[$local['status']] ?? 1) ? $ai['status'] : $local['status'];
        $flags = array_values(array_unique(array_merge($local['flags'], $ai['flags'])));
        $score = max($local['score'], $ai['score']);

        return [
            'status' => $status,
            'reason' => $status === $ai['status'] ? $ai['reason'] : $local['reason'],
            'score' => max(0.0, min(1.0, $score)),
            'flags' => $flags,
            'summary' => $ai['summary'] !== '' ? $ai['summary'] : $local['summary'],
            'urgency' => $ai['urgency'],
            'sentiment' => $ai['sentiment'],
        ];
    }

    /**
     * @param array<int,string> $flags
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    private function classifyFromLocal(array $flags, float $score): array
    {
        $score = max(0.0, min(1.0, $score));
        $status = 'accepted';
        $reason = 'Contenu accepte.';

        $rejectFlags = ['insults_or_offensive_language', 'hate_harassment_or_threats', 'spam_or_promotional_content', 'nonsense_or_gibberish'];
        $reviewFlags = ['duplicate_recent_complaint', 'excess_sensitive_data', 'overly_aggressive_language', 'very_short_complaint', 'empty_or_low_value'];

        if (array_intersect($flags, $rejectFlags) !== [] || $score >= 0.8) {
            $status = 'rejected';
            $reason = 'Le contenu ne respecte pas les regles de qualite et de respect.';
        } elseif (array_intersect($flags, $reviewFlags) !== [] || $score >= 0.45) {
            $status = 'review';
            $reason = 'Votre reclamation sera verifiee manuellement avant traitement.';
        }

        return [
            'status' => $status,
            'reason' => $reason,
            'score' => $score,
            'flags' => array_values(array_unique($flags)),
            'summary' => $status === 'accepted' ? 'Analyse locale: contenu acceptable.' : 'Analyse locale: contenu sensible detecte.',
            'urgency' => 'medium',
            'sentiment' => 'neutral',
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param array<int,string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeGibberish(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return true;
        }

        $letters = preg_match_all('/[\p{L}]/u', $trimmed, $m);
        $length = max(1, mb_strlen($trimmed));
        $ratio = (float) $letters / (float) $length;

        $repeatedPattern = preg_match('/(.)\1{5,}/u', $trimmed) === 1;

        return $ratio < 0.45 || $repeatedPattern;
    }

    private function hasSensitiveDataExcess(string $text): bool
    {
        $count = 0;
        $patterns = [
            '/\b[\w.%-]+@[\w.-]+\.[A-Za-z]{2,}\b/u',
            '/\b(?:\+?\d{1,3}[\s.-]?)?(?:\d[\s.-]?){8,12}\b/u',
            '/\b(?:\d[ -]?){14,19}\b/u',
            '/\b(?:cin|mot de passe|password|code secret|cvv|iban)\b/ui',
        ];

        foreach ($patterns as $pattern) {
            $matches = preg_match_all($pattern, $text, $m);
            $count += is_int($matches) ? $matches : 0;
        }

        return $count >= 3;
    }

    private function isOverlyAggressive(string $text): bool
    {
        $exclamations = substr_count($text, '!');
        $upper = preg_match_all('/[A-Z]/', $text, $mUpper);
        $letters = preg_match_all('/[A-Za-z]/', $text, $mLetters);
        $upperRatio = $letters > 0 ? ((float) $upper / (float) $letters) : 0.0;

        return $exclamations >= 4 || $upperRatio > 0.55;
    }

    private function isVerySimilarToRecent(int $userId, string $text): bool
    {
        $recent = $this->reclamationRepository->findRecentByUser($userId);

        foreach ($recent as $reclamation) {
            $candidate = $this->normalize($reclamation->getTitre() . ' ' . $reclamation->getDescription());
            if ($candidate === '') {
                continue;
            }

            similar_text($text, $candidate, $similarity);
            if ($similarity >= 88.0) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeUrgency(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['low', 'medium', 'high'], true) ? $value : 'medium';
    }

    private function sanitizeSentiment(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['negative', 'neutral', 'mixed'], true) ? $value : 'neutral';
    }

    /**
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    private function acceptedFallback(): array
    {
        return [
            'status' => 'accepted',
            'reason' => 'Moderation desactivee.',
            'score' => 0.0,
            'flags' => [],
            'summary' => 'Moderation desactivee.',
            'urgency' => 'medium',
            'sentiment' => 'neutral',
        ];
    }

    /**
     * @return array{status:string,reason:string,score:float,flags:array<int,string>,summary:string,urgency:string,sentiment:string}
     */
    private function reviewFallback(string $summary): array
    {
        return [
            'status' => 'review',
            'reason' => 'Verification manuelle requise.',
            'score' => 0.55,
            'flags' => ['ai_unavailable'],
            'summary' => $summary,
            'urgency' => 'medium',
            'sentiment' => 'neutral',
        ];
    }
}
