<?php

namespace App\Tests\Service;

use App\Entity\Question;
use App\Service\QuizScoreManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour QuizScoreManager.
 *
 * Règles métier testées :
 *  1. Score calculé correctement quand toutes les réponses sont bonnes  → 100%
 *  2. Score calculé correctement quand aucune réponse n'est bonne       → 0%
 *  3. Score calculé correctement pour une réponse partielle             → valeur arrondie
 *  4. Exception levée si la liste de questions est vide
 *  5. Exception levée si une question a des points <= 0
 *  6. isAdmis() retourne true  quand score >= scoreMinimum
 *  7. isAdmis() retourne false quand score <  scoreMinimum
 *  8. Exception levée si scoreMinimum est hors de [0, 100]
 */
class QuizScoreManagerTest extends TestCase
{
    private QuizScoreManager $manager;

    protected function setUp(): void
    {
        $this->manager = new QuizScoreManager();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Crée une Question factice sans passer par Doctrine.
     */
    private function makeQuestion(int $id, int $points, int $bonneReponse): Question
    {
        $q = new Question();

        // On utilise la réflexion pour injecter l'id (propriété privée sans setter)
        $ref = new \ReflectionProperty(Question::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($q, $id);

        $q->setPoints($points);
        $q->setBonneReponse($bonneReponse);
        $q->setQuestion('Question de test ' . $id);
        $q->setChoix1('A');
        $q->setChoix2('B');

        return $q;
    }

    // ---------------------------------------------------------------
    // Tests : calculateScore
    // ---------------------------------------------------------------

    /** Test 1 — Toutes les réponses correctes → 100 % */
    public function testScoreAllCorrect(): void
    {
        $q1 = $this->makeQuestion(1, 10, 1);
        $q2 = $this->makeQuestion(2, 10, 2);
        $q3 = $this->makeQuestion(3, 10, 1);

        $answers = [1 => 1, 2 => 2, 3 => 1]; // toutes bonnes

        $score = $this->manager->calculateScore([$q1, $q2, $q3], $answers);

        $this->assertSame(100, $score);
    }

    /** Test 2 — Aucune réponse correcte → 0 % */
    public function testScoreAllWrong(): void
    {
        $q1 = $this->makeQuestion(1, 10, 1);
        $q2 = $this->makeQuestion(2, 10, 2);

        $answers = [1 => 2, 2 => 1]; // toutes fausses

        $score = $this->manager->calculateScore([$q1, $q2], $answers);

        $this->assertSame(0, $score);
    }

    /** Test 3 — Réponse partielle : 2 bonnes sur 3 questions de 10 pts → 67 % */
    public function testScorePartial(): void
    {
        $q1 = $this->makeQuestion(1, 10, 1);
        $q2 = $this->makeQuestion(2, 10, 2);
        $q3 = $this->makeQuestion(3, 10, 1);

        $answers = [1 => 1, 2 => 2, 3 => 2]; // q3 fausse

        $score = $this->manager->calculateScore([$q1, $q2, $q3], $answers);

        $this->assertSame(67, $score); // round(20/30 * 100) = 67
    }

    /** Test 4 — Questions avec points différents : pondération correcte */
    public function testScoreWeighted(): void
    {
        $q1 = $this->makeQuestion(1, 5,  1); //  5 pts
        $q2 = $this->makeQuestion(2, 15, 2); // 15 pts — bonne réponse

        $answers = [1 => 2, 2 => 2]; // q1 fausse, q2 bonne

        $score = $this->manager->calculateScore([$q1, $q2], $answers);

        $this->assertSame(75, $score); // 15/20 * 100 = 75
    }

    /** Test 5 — Exception si liste de questions vide */
    public function testScoreThrowsOnEmptyQuestions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La liste de questions ne peut pas être vide.');

        $this->manager->calculateScore([], []);
    }

    /** Test 6 — Exception si une question a des points <= 0 */
    public function testScoreThrowsOnZeroPoints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les points d\'une question doivent être supérieurs à 0.');

        // On force points = 0 via réflexion pour contourner le setter
        $q = $this->makeQuestion(1, 1, 1);
        $ref = new \ReflectionProperty(Question::class, 'points');
        $ref->setAccessible(true);
        $ref->setValue($q, 0);

        $this->manager->calculateScore([$q], []);
    }

    // ---------------------------------------------------------------
    // Tests : isAdmis
    // ---------------------------------------------------------------

    /** Test 7 — Candidat admis : score >= scoreMinimum */
    public function testIsAdmisTrue(): void
    {
        $this->assertTrue($this->manager->isAdmis(75, 50));
        $this->assertTrue($this->manager->isAdmis(50, 50)); // exactement égal
    }

    /** Test 8 — Candidat non admis : score < scoreMinimum */
    public function testIsAdmisFalse(): void
    {
        $this->assertFalse($this->manager->isAdmis(49, 50));
        $this->assertFalse($this->manager->isAdmis(0, 60));
    }

    /** Test 9 — Exception si scoreMinimum < 0 */
    public function testIsAdmisThrowsOnNegativeMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le score minimum doit être entre 0 et 100.');

        $this->manager->isAdmis(50, -1);
    }

    /** Test 10 — Exception si scoreMinimum > 100 */
    public function testIsAdmisThrowsOnMinimumAbove100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le score minimum doit être entre 0 et 100.');

        $this->manager->isAdmis(50, 101);
    }
}
