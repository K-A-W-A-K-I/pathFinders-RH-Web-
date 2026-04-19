# Advanced Analytics Functionalities (avances)

This document explains the 3 advanced analytics features implemented for Users and Reclamations, including inputs, formulas, persistence, and display.

## Overview

The application now computes and stores:

1. Reclamation Urgency/Priority Score
2. Reclamation Anomaly Detection Score
3. User Trust/Health Score

These analytics are recalculated from admin list pages and persisted in database fields.

## Architecture

### Core services

- src/Service/Analytics/ReclamationUrgencyScoringService.php
- src/Service/Analytics/ReclamationAnomalyDetectionService.php
- src/Service/Analytics/UserTrustScoringService.php
- src/Service/Analytics/DashboardAnalyticsCoordinator.php

### Data providers

- src/Repository/ReclamationRepository.php

### Controllers that trigger recalculation

- src/Controller/Reclamation/AdminReclamationController.php
- src/Controller/User/UserController.php

### UI display

- templates/admin/reclamation/index.html.twig
- templates/user/index.html.twig

### Persistence entities

- src/Entity/Reclamation.php
- src/Entity/Utilisateur.php

### Migration

- migrations/Version20260419000004.php

## 1) Reclamation Urgency/Priority

### Purpose

Prioritize reclamations based on moderation risk, urgency, sentiment, and SLA delay.

### Main inputs

- moderationScore (0.0 to 1.0)
- moderationUrgency (low, medium, high)
- moderationSentiment (neutral, mixed, negative)
- statut (En attente, En cours, etc.)
- openAgeHours (hours since creation)
- manualReviewRequired (true/false)
- SLA = 24 hours

### Calculation logic

Starting score = 0

- + moderationScore * 35
- +22 if moderationUrgency = high
- +10 if moderationUrgency = medium
- +14 if sentiment = negative
- +6 if sentiment = mixed
- +6 if statut is En attente or En cours
- + min(28, (openAgeHours - 24) * 1.5) when openAgeHours > 24 and status is open
- +10 if manualReviewRequired = true

Final score is clamped to [0, 100].

### Priority mapping

- 80 to 100: Critique
- 60 to 79.99: Haute
- 40 to 59.99: Moyenne
- 0 to 39.99: Basse

### Stored fields (Reclamation)

- analyticsUrgencyScore
- analyticsPriorityLevel

## 2) Reclamation Anomaly Detection

### Purpose

Detect suspicious or unusual complaint patterns (duplicates, bursts, sensitive moderation patterns).

### Main inputs

- sameTitleRecentCount (same title in recent 48h)
- userBurstCount (same user reclamations in recent 24h)
- manualReviewRequired
- moderationDecision (review/rejected)
- description length

### Calculation logic

Starting score = 0

- + min(45, sameTitleRecentCount * 18) if duplicates exist
- + min(35, (userBurstCount - 2) * 10) if userBurstCount >= 3
- +12 if manualReviewRequired = true
- +12 if moderationDecision is review or rejected
- +6 if description length < 35 characters

Final score is clamped to [0, 100].

### Anomaly flag

- true if score >= 60
- false otherwise

### Stored fields (Reclamation)

- analyticsAnomalyScore
- analyticsAnomalyFlag
- analyticsAnomalyReasons

## 3) User Trust/Health Score

### Purpose

Measure user reliability based on recent complaint behavior and account status.

### Aggregation window

- Last 90 days

### Main inputs per user

- total reclamations
- open reclamations
- rejected moderation count
- review moderation count
- resolved count
- repondu count
- user statut
- user role

### Ratios

- openRatio = open / total
- rejectedRatio = rejected / total
- reviewRatio = review / total
- closedRatio = (resolved + repondu) / total

### Calculation logic

Starting score = 100

- - (openRatio * 25)
- - (rejectedRatio * 30)
- - (reviewRatio * 18)
- + (closedRatio * 8)
- - min(20, (total - 7) * 2.5) if total >= 8
- -15 if statut is not actif
- +3 if role is ROLE_ADMIN

Final score is clamped to [0, 100].

### Level mapping

- 75 to 100: Eleve
- 45 to 74.99: Moyen
- 0 to 44.99: Faible

### Stored fields (Utilisateur)

- analyticsTrustScore
- analyticsTrustLevel
- analyticsTrustFlags

## Execution flow

1. Admin opens reclamation list page:
   - coordinator computes urgency + anomaly for visible paginated rows
   - values are persisted

2. Admin opens users list page:
   - coordinator computes trust score for visible paginated users
   - values are persisted

3. analyticsLastCalculatedAt is updated on each recalculation.

## Repository helper methods used

In ReclamationRepository:

- findOpenAgeHoursByIds(...)
- countRecentByUserIds(...)
- countRecentByExactTitle(...)
- findUserReclamationStats(...)

## Displayed in dashboard

### Reclamation list

- Urgency score (/100)
- Priority badge (Basse/Moyenne/Haute/Critique)
- Anomaly score (/100)
- Anomaly status and reasons

### Users list

- Trust score (/100)
- Trust level badge (Faible/Moyen/Eleve)
- Trust flags

## Notes

- Recalculation is page-bounded (current pagination items) to keep performance predictable.
- Scores are persisted for visibility and reporting.
- SLA rule for urgency currently fixed to 24 hours.
