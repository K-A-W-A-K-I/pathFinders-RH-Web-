# Trace — Tests Unitaires : Tâche Quiz de Candidature

---

## 1. Entité choisie

**Question** (`src/Entity/Question.php`)
Utilisée dans le quiz de candidature. Chaque question appartient à une offre, possède 2 à 4 choix, une bonne réponse (index 1–4) et un nombre de points.

---

## 2. Règles métier identifiées

| # | Règle métier |
|---|---|
| 1 | La liste de questions ne peut pas être vide pour calculer un score |
| 2 | Les points d'une question doivent être supérieurs à 0 |
| 3 | Le score calculé est toujours un entier entre 0 et 100 (arrondi) |
| 4 | La pondération par points est respectée dans le calcul du score |
| 5 | Un candidat est admis si et seulement si son score >= scoreMinimum |
| 6 | Le score minimum d'une offre doit être compris entre 0 et 100 |

---

## 3. Structure des fichiers créés

```
src/
└── Service/
    └── QuizScoreManager.php        ← service métier (logique de calcul)

tests/
└── Service/
    └── QuizScoreManagerTest.php    ← tests unitaires (10 tests)
```

---

## 4. Étape make:test

Commande exécutée :

```bash
php bin/console make:test
```

Choix sélectionné : **TestCase**

Nom de la classe : **QuizScoreManagerTest**

Fichier généré déplacé dans : `tests/Service/QuizScoreManagerTest.php`

---

## 5. Service métier — QuizScoreManager.php

```php
// src/Service/QuizScoreManager.php

public function calculateScore(array $questions, array $answers): int
{
    if (empty($questions)) {
        throw new \InvalidArgumentException('La liste de questions ne peut pas être vide.');
    }
    $total = 0; $maxScore = 0;
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

public function isAdmis(int $score, int $scoreMinimum): bool
{
    if ($scoreMinimum < 0 || $scoreMinimum > 100) {
        throw new \InvalidArgumentException('Le score minimum doit être entre 0 et 100.');
    }
    return $score >= $scoreMinimum;
}
```

Logique utilisée :
- `empty()` pour vérifier que la liste de questions n'est pas vide
- Comparaison `<= 0` pour valider les points
- `round()` pour arrondir le pourcentage à l'entier le plus proche
- `InvalidArgumentException` pour signaler immédiatement toute donnée invalide

---

## 6. Tests unitaires implémentés

### Fichier : `tests/Service/QuizScoreManagerTest.php`

| # | Méthode de test | Règle métier validée | Résultat attendu |
|---|---|---|---|
| 1 | `testScoreAllCorrect` | Toutes les réponses correctes | score = 100 |
| 2 | `testScoreAllWrong` | Aucune réponse correcte | score = 0 |
| 3 | `testScorePartial` | 2 bonnes sur 3 (10 pts chacune) | score = 67 |
| 4 | `testScoreWeighted` | Pondération par points différents | score = 75 |
| 5 | `testScoreThrowsOnEmptyQuestions` | Liste vide → exception | InvalidArgumentException |
| 6 | `testScoreThrowsOnZeroPoints` | Points = 0 → exception | InvalidArgumentException |
| 7 | `testIsAdmisTrue` | score >= scoreMinimum | true |
| 8 | `testIsAdmisFalse` | score < scoreMinimum | false |
| 9 | `testIsAdmisThrowsOnNegativeMinimum` | scoreMinimum < 0 → exception | InvalidArgumentException |
| 10 | `testIsAdmisThrowsOnMinimumAbove100` | scoreMinimum > 100 → exception | InvalidArgumentException |

---

## 7. Exécution des tests

Commande :

```bash
php bin/phpunit tests/Service/QuizScoreManagerTest.php
```

Résultat réel obtenu dans le terminal :

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: phpunit.dist.xml

..........                                                        10 / 10 (100%)

Time: 00:00.011, Memory: 10.00 MB

OK (10 tests, 16 assertions)
```

- Chaque `.` correspond à un test passé avec succès
- `OK (10 tests, 12 assertions)` confirme que toute la logique métier est valide
- Aucune erreur, aucun avertissement

---

## 8. Détail des assertions par test

### Test 1 — `testScoreAllCorrect`
- 3 questions × 10 pts, toutes bonnes
- `calculateScore([q1,q2,q3], [1=>1, 2=>2, 3=>1])`
- **Assertion** : `assertSame(100, $score)` ✅

### Test 2 — `testScoreAllWrong`
- 2 questions × 10 pts, toutes fausses
- `calculateScore([q1,q2], [1=>2, 2=>1])`
- **Assertion** : `assertSame(0, $score)` ✅

### Test 3 — `testScorePartial`
- 3 questions × 10 pts, 2 bonnes (q1, q2), q3 fausse
- `round(20/30 * 100)` = 67
- **Assertion** : `assertSame(67, $score)` ✅

### Test 4 — `testScoreWeighted`
- q1 = 5 pts (fausse), q2 = 15 pts (bonne)
- `round(15/20 * 100)` = 75
- **Assertion** : `assertSame(75, $score)` ✅

### Test 5 — `testScoreThrowsOnEmptyQuestions`
- `calculateScore([], [])`
- **Assertion** : `expectException(InvalidArgumentException::class)` ✅

### Test 6 — `testScoreThrowsOnZeroPoints`
- Question avec points forcés à 0 via ReflectionProperty
- **Assertion** : `expectException(InvalidArgumentException::class)` ✅

### Test 7 — `testIsAdmisTrue`
- `isAdmis(75, 50)` → true, `isAdmis(50, 50)` → true (limite incluse)
- **Assertions** : `assertTrue(...)` × 2 ✅

### Test 8 — `testIsAdmisFalse`
- `isAdmis(49, 50)` → false, `isAdmis(0, 60)` → false
- **Assertions** : `assertFalse(...)` × 2 ✅

### Test 9 — `testIsAdmisThrowsOnNegativeMinimum`
- `isAdmis(50, -1)`
- **Assertion** : `expectException(InvalidArgumentException::class)` ✅

### Test 10 — `testIsAdmisThrowsOnMinimumAbove100`
- `isAdmis(50, 101)`
- **Assertion** : `expectException(InvalidArgumentException::class)` ✅

---

## 9. Conclusion

Les 10 tests unitaires couvrent l'intégralité de la logique métier du quiz :
- Le calcul du score (cas nominal, cas limites, pondération)
- La validation des données d'entrée (exceptions)
- La décision d'admission (admis / refusé / scoreMinimum invalide)

Le service `QuizScoreManager` est entièrement découplé de Doctrine et du framework Symfony, ce qui le rend testable de manière isolée sans base de données ni conteneur.
