#!/usr/bin/env pwsh
# Script de test de performance pour preuves

Write-Host "=== TEST DE PERFORMANCE - GÉNÉRATION DE PREUVES ===" -ForegroundColor Cyan
Write-Host ""

# Configuration
$baseUrl = "http://localhost:8000"
$pages = @(
    @{Name="Page d'accueil"; Url="/"},
    @{Name="Liste des offres"; Url="/offre"},
    @{Name="Candidatures (N+1 test)"; Url="/offre/candidatures"}
)

Write-Host "IMPORTANT: Assurez-vous que le serveur est lancé avec:" -ForegroundColor Yellow
Write-Host "  php -S localhost:8000 -t public" -ForegroundColor Yellow
Write-Host ""
Read-Host "Appuyez sur Entrée pour continuer..."

# Créer un dossier pour les résultats
$resultFolder = "performance_results_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $resultFolder -Force | Out-Null

Write-Host "Résultats seront sauvegardés dans: $resultFolder" -ForegroundColor Green
Write-Host ""

# Tester chaque page
foreach ($page in $pages) {
    Write-Host "Test: $($page.Name)" -ForegroundColor Cyan
    Write-Host "URL: $baseUrl$($page.Url)" -ForegroundColor Gray
    
    $times = @()
    $memories = @()
    
    # Faire 10 requêtes
    for ($i = 1; $i -le 10; $i++) {
        try {
            $result = Measure-Command {
                $response = Invoke-WebRequest -Uri "$baseUrl$($page.Url)" -UseBasicParsing -ErrorAction Stop
            }
            
            $timeMs = [math]::Round($result.TotalMilliseconds, 2)
            $times += $timeMs
            
            Write-Host "  Requête $i : $timeMs ms" -ForegroundColor Gray
        }
        catch {
            Write-Host "  Erreur sur requête $i : $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Start-Sleep -Milliseconds 100
    }
    
    # Calculer les statistiques
    if ($times.Count -gt 0) {
        $avg = [math]::Round(($times | Measure-Object -Average).Average, 2)
        $min = [math]::Round(($times | Measure-Object -Minimum).Minimum, 2)
        $max = [math]::Round(($times | Measure-Object -Maximum).Maximum, 2)
        
        Write-Host ""
        Write-Host "  Résultats pour $($page.Name):" -ForegroundColor Green
        Write-Host "    - Temps moyen: $avg ms" -ForegroundColor White
        Write-Host "    - Temps min: $min ms" -ForegroundColor White
        Write-Host "    - Temps max: $max ms" -ForegroundColor White
        
        # Sauvegarder dans un fichier
        $resultFile = "$resultFolder/$($page.Name -replace ' ', '_').txt"
        @"
=== $($page.Name) ===
URL: $baseUrl$($page.Url)
Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

Temps moyen: $avg ms
Temps minimum: $min ms
Temps maximum: $max ms

Détails des 10 requêtes:
$($times | ForEach-Object { "  - $_ ms" } | Out-String)
"@ | Out-File -FilePath $resultFile -Encoding UTF8
        
        Write-Host "    Sauvegardé dans: $resultFile" -ForegroundColor Gray
    }
    
    Write-Host ""
}

Write-Host "=== INSTRUCTIONS POUR LES CAPTURES ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Ouvrez votre navigateur sur: $baseUrl/offre/candidatures" -ForegroundColor Yellow
Write-Host "2. Cliquez sur la barre debug Symfony en bas de page" -ForegroundColor Yellow
Write-Host "3. Allez dans l'onglet 'Doctrine'" -ForegroundColor Yellow
Write-Host "4. PRENEZ UNE CAPTURE montrant le nombre de requêtes SQL" -ForegroundColor Yellow
Write-Host ""
Write-Host "5. Allez dans l'onglet 'Performance'" -ForegroundColor Yellow
Write-Host "6. PRENEZ UNE CAPTURE montrant le temps de réponse" -ForegroundColor Yellow
Write-Host ""
Write-Host "Tous les résultats sont dans le dossier: $resultFolder" -ForegroundColor Green
Write-Host ""
Write-Host "=== TEST TERMINÉ ===" -ForegroundColor Cyan
