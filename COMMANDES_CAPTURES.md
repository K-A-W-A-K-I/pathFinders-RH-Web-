# 📸 GUIDE RAPIDE - COMMANDES POUR CAPTURES

## 🚀 DÉMARRAGE RAPIDE

### 1. Lancer le serveur
```powershell
php -S localhost:8000 -t public
```

### 2. Générer le rapport automatique
```powershell
.\generate_performance_report.ps1
```

---

## 📊 COMMANDES PAR SECTION

### 1️⃣ PHPSTAN - Analyse Statique

```powershell
# Installer PHPStan (si pas déjà fait)
composer require --dev phpstan/phpstan

# Exécuter l'analyse niveau 5
vendor\bin\phpstan analyse src --level=5

# Sauvegarder les résultats
vendor\bin\phpstan analyse src --level=5 > phpstan_results.txt
```

**CAPTURE À PRENDRE:**
- Terminal montrant "0 errors" ✅

---

### 2️⃣ TESTS UNITAIRES

```powershell
# Exécuter les tests avec format lisible
.\bin\phpunit tests/Controller/QuizControllerTest.php --testdox

# Exécuter avec détails
.\bin\phpunit tests/Controller/QuizControllerTest.php --verbose

# Sauvegarder les résultats
.\bin\phpunit tests/Controller/QuizControllerTest.php --testdox > tests_results.txt
```

**CAPTURE À PRENDRE:**
- Terminal montrant "4 tests, 8 assertions" ✅

---

### 3️⃣ DOCTRINE N+1 - Profiler Symfony

```powershell
# 1. Lancer le serveur
php -S localhost:8000 -t public

# 2. Dans le navigateur, ouvrir:
# http://localhost:8000/offre/candidatures

# 3. Cliquer sur la barre debug Symfony en bas
# 4. Aller dans l'onglet "Doctrine"
```

**CAPTURES À PRENDRE:**

**CAPTURE 1 - Nombre de requêtes:**
- Onglet "Doctrine" → Nombre total de requêtes
- Devrait montrer ~3-5 requêtes (au lieu de 20+)

**CAPTURE 2 - Détail des requêtes:**
- Cliquer sur une requête pour voir le SQL
- Montrer les JOIN dans la requête

---

### 4️⃣ PERFORMANCE - Temps de Réponse

#### Option A: Profiler Symfony (Recommandé)

```powershell
# 1. Lancer le serveur
php -S localhost:8000 -t public

# 2. Ouvrir dans le navigateur:
# http://localhost:8000/

# 3. Cliquer sur la barre debug → Onglet "Performance"
```

**CAPTURE À PRENDRE:**
- Temps total de réponse (~90-110ms pour page d'accueil)

#### Option B: PowerShell (Mesure précise)

```powershell
# Test page d'accueil (10 fois)
1..10 | ForEach-Object { 
    Measure-Command { 
        Invoke-WebRequest -Uri "http://localhost:8000/" -UseBasicParsing 
    } | Select-Object -ExpandProperty TotalMilliseconds 
}

# Test page offres
1..10 | ForEach-Object { 
    Measure-Command { 
        Invoke-WebRequest -Uri "http://localhost:8000/offre" -UseBasicParsing 
    } | Select-Object -ExpandProperty TotalMilliseconds 
}

# Test page candidatures
1..10 | ForEach-Object { 
    Measure-Command { 
        Invoke-WebRequest -Uri "http://localhost:8000/offre/candidatures" -UseBasicParsing 
    } | Select-Object -ExpandProperty TotalMilliseconds 
}
```

**CAPTURE À PRENDRE:**
- Terminal montrant les temps de réponse

#### Option C: Outils Développeur Navigateur

```
1. Appuyer sur F12 (outils développeur)
2. Onglet "Network"
3. Rafraîchir la page (F5)
4. Regarder en bas: temps total de chargement
```

**CAPTURE À PRENDRE:**
- Onglet Network montrant le temps total

---

### 5️⃣ MÉMOIRE - Utilisation

#### Via Profiler Symfony

```powershell
# 1. Lancer le serveur
php -S localhost:8000 -t public

# 2. Ouvrir: http://localhost:8000/offre/candidatures
# 3. Cliquer sur la barre debug
# 4. Chercher "Peak Memory Usage" (en haut à droite)
```

**CAPTURE À PRENDRE:**
- Peak Memory Usage (~10-12 MB)

---

### 6️⃣ CODE SOURCE - Optimisations

**CAPTURES À PRENDRE:**

**CAPTURE 1 - QuizController (PHPStan fixes):**
```powershell
# Ouvrir dans l'éditeur:
code src/Controller/Offre/QuizController.php
```
- Montrer les vérifications `if (!$offre)` ajoutées
- Montrer le typage `@var array<int, mixed>`

**CAPTURE 2 - QuestionRepository (JOIN):**
```powershell
code src/Repository/QuestionRepository.php
```
- Montrer `addSelect('o')` et `leftJoin('q.offre', 'o')`

**CAPTURE 3 - CandidatRepository (Batch):**
```powershell
code src/Repository/CandidatRepository.php
```
- Montrer la requête batch avec `IN()`

---

## 🎯 SCRIPT TOUT-EN-UN

Pour générer TOUTES les preuves automatiquement:

```powershell
# Créer un dossier pour les résultats
$folder = "preuves_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $folder

# 1. PHPStan
vendor\bin\phpstan analyse src --level=5 > "$folder\phpstan.txt"

# 2. Tests
.\bin\phpunit tests/Controller/QuizControllerTest.php --testdox > "$folder\tests.txt"

# 3. Performance (si serveur lancé)
1..10 | ForEach-Object { 
    Measure-Command { 
        Invoke-WebRequest -Uri "http://localhost:8000/" -UseBasicParsing 
    } | Select-Object -ExpandProperty TotalMilliseconds 
} > "$folder\performance_home.txt"

1..10 | ForEach-Object { 
    Measure-Command { 
        Invoke-WebRequest -Uri "http://localhost:8000/offre/candidatures" -UseBasicParsing 
    } | Select-Object -ExpandProperty TotalMilliseconds 
} > "$folder\performance_candidatures.txt"

Write-Host "✅ Tous les résultats sont dans: $folder\"
```

---

## 📋 CHECKLIST DES CAPTURES

### PHPStan
- [ ] Terminal montrant "0 errors"
- [ ] Fichier phpstan_results.txt

### Tests Unitaires
- [ ] Terminal montrant "4 tests, 8 assertions"
- [ ] Tous les tests en vert ✅

### Doctrine N+1
- [ ] Profiler Symfony → Onglet Doctrine → ~3-5 requêtes
- [ ] Détail d'une requête montrant les JOIN

### Performance
- [ ] Page d'accueil: ~90-110ms
- [ ] Page candidatures: ~130-160ms
- [ ] Comparaison avant/après

### Mémoire
- [ ] Peak Memory Usage: ~10-12 MB

### Code Source
- [ ] QuizController avec vérifications nullité
- [ ] QuestionRepository avec JOIN
- [ ] CandidatRepository avec batch query

---

## 🆘 DÉPANNAGE

### Problème: "Could not open input file: bin/console"

**Solution:**
```powershell
# Utiliser le chemin Windows
php .\bin\console --version

# OU naviguer dans le bon dossier
cd "C:\Users\boule\Desktop\project git\pathFinders-RH-Web-"
```

### Problème: "PHPStan not found"

**Solution:**
```powershell
composer require --dev phpstan/phpstan
```

### Problème: "Connection refused" lors des tests

**Solution:**
```powershell
# Vérifier que le serveur est lancé
php -S localhost:8000 -t public
```

---

## ✅ VALIDATION FINALE

Après avoir pris toutes les captures, vérifiez:

- [ ] PHPStan: 0 erreurs
- [ ] Tests: 4/4 réussis
- [ ] Requêtes SQL: ~3-5 (au lieu de 20+)
- [ ] Temps de réponse: ~50% plus rapide
- [ ] Mémoire: ~27% moins utilisée

**Si tout est ✅, votre rapport est complet!**
