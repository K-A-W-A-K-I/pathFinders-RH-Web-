<?php

namespace App\Service;

use App\Entity\Question;

/**
 * Service métier pour la logique du quiz de candidature.
 *
 * Règles métier validées :
 *  1. Le score minimum d'une offre doit être entre 0 et 100.
 *  2. Les points d'une question doivent être supérieurs à 0.
 *  3. Le score calculé est toujours un entier entre 0 et 100.
 *  4. Un candidat est admis si et seulement si son score >= scoreMinimum.
 */
class QuizScoreManager
{
    /**
     * Calcule le score en pourcentage (0–100) à partir des réponses soumises.
     *
     * @param Question[] $questions
     * @param array<int, int> $answers  clé = id_question, valeur = index réponse choisie
     *
     * @throws \InvalidArgumentException si la liste de questions est vide
     * @throws \InvalidArgumentException si une question a des points <= 0
     */
    public function calculateScore(array $questions, array $answers): int
    {
        if (empty($questions)) {
            throw new \InvalidArgumentException('La liste de questions ne peut pas être vide.');
        }

        $total    = 0;
        $maxScore = 0;

        foreach ($questions as $q) {
            if ($q->getPoints() <= 0) {
                throw new \InvalidArgumentException('Les points d\'une question doivent être supérieurs à 0.');
            }
            $maxScore += $q->getPoints();
            $userAnswer = (int) ($answers[$q->getId()] ?? 0);
            if ($userAnswer === $q->getBonneReponse()) {
                $total += $q->getPoints();
            }
        }

        return (int) round(($total / $maxScore) * 100);
    }

    /**
     * Détermine si un candidat est admis selon le score minimum de l'offre.
     *
     * @throws \InvalidArgumentException si scoreMinimum est hors de [0, 100]
     */
    public function isAdmis(int $score, int $scoreMinimum): bool
    {
        if ($scoreMinimum < 0 || $scoreMinimum > 100) {
            throw new \InvalidArgumentException('Le score minimum doit être entre 0 et 100.');
        }

        return $score >= $scoreMinimum;
    }
}
