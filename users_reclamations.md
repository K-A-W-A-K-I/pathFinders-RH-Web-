# Users & Reclamations - Change Summary

This document tracks changes related to users, reclamations, authentication, and security integrations.
Update this file each time a related feature is added or modified.

## Last Updated
- Date: 2026-04-19

## Overview
- Added JWT-based API authentication.
- Added TOTP 2FA flow (Microsoft Authenticator compatible).
- Added pagination for users and admin reclamations lists.
- Added Cloudinary integration for user profile image and CV uploads.
- Added PDF generation for single user and all users exports.

---

## Feature 1: JWT Authentication (Lexik)

### What was implemented
- API login endpoint at `/api/login_check` using `json_login`.
- Stateless JWT firewall for `/api/**` routes.
- Protected API test endpoint `/api/me`.

### How it works
1. Client sends JSON credentials to `POST /api/login_check`.
2. On valid credentials, Lexik returns a JWT token.
3. Client sends `Authorization: Bearer <token>` to protected `/api/**` endpoints.
4. Security layer authenticates token and grants access.

### Files changed
- `composer.json`
- `composer.lock`
- `config/bundles.php`
- `config/packages/security.yaml`
- `config/packages/lexik_jwt_authentication.yaml`
- `config/routes/lexik_jwt_authentication.yaml`
- `src/Controller/Api/MeController.php`
- `.gitignore`
- `symfony.lock`

### Required config/env
- `JWT_SECRET_KEY`
- `JWT_PUBLIC_KEY`
- `JWT_PASSPHRASE`

---

## Feature 2: 2FA TOTP (Scheb)

### What was implemented
- Scheb TwoFactor integration in main firewall.
- User-level TOTP support in entity.
- 2FA setup page in profile.
- Enable/disable flow with code verification.
- Dedicated 2FA login challenge page.

### How it works
1. User opens profile 2FA setup page: `/profil/2fa`.
2. App ensures TOTP secret exists and generates QR content.
3. User scans QR with Microsoft Authenticator.
4. User submits a 6-digit code to enable 2FA.
5. On next logins, after password validation, user is redirected to `/2fa` to enter TOTP code.

### Files changed
- `composer.json`
- `composer.lock`
- `config/bundles.php`
- `config/packages/security.yaml`
- `config/packages/scheb_2fa.yaml`
- `config/routes/scheb_2fa.yaml`
- `src/Entity/Utilisateur.php`
- `src/Service/Security/TwoFactorManager.php`
- `src/Controller/User/TwoFactorController.php`
- `templates/profile/2fa.html.twig`
- `templates/security/2fa_form.html.twig`
- `templates/profile/edit.html.twig`
- `symfony.lock`

### Required config/env
- `APP_2FA_ISSUER`

### Notes
- Ensure DB schema includes fields used for TOTP secret and 2FA enabled flag in the user entity.

---

## Feature 3: Pagination (Users + Reclamations)

### What was implemented
- Replaced unbounded list loading with paginated query builder results.
- Added pagination controls in Twig templates.
- Added paginator config defaults.

### How it works
1. Controller builds Doctrine query.
2. Query is passed to KNP paginator with page number from `?page=`.
3. Template renders page items and navigation.

### Files changed
- `config/packages/knp_paginator.yaml`
- `src/Controller/User/UserController.php`
- `templates/user/index.html.twig`
- `src/Controller/Reclamation/AdminReclamationController.php`
- `templates/admin/reclamation/index.html.twig`

---

## Feature 4: Cloudinary Uploads + Users PDF

### What was implemented
- Integrated Cloudinary SDK for user profile image uploads.
- Integrated Cloudinary SDK for CV uploads (PDF-only flow).
- Added safer CV preview rendering in profile based on Cloudinary transformed preview URL.
- Added PDF generation service for:
  - Single user profile export.
  - All users list export.
- Added admin buttons to download:
  - PDF for one user.
  - PDF for all users.

### How it works
1. Profile update controller delegates file uploads to a dedicated upload service.
2. Uploaded profile image/CV URLs are stored on existing user/candidat fields.
3. CV preview in profile uses first-page transformed image URL (compatible with Cloudinary restrictions on original PDF delivery).
4. PDF generator service renders Twig templates and uses Dompdf to output downloadable PDFs.

### Files changed
- `composer.json`
- `composer.lock`
- `config/services.yaml`
- `.env`
- `src/Service/CloudinaryUploader.php`
- `src/Service/UserPdfGenerator.php`
- `src/Controller/User/ProfileController.php`
- `src/Controller/User/UserController.php`
- `templates/profile/edit.html.twig`
- `templates/user/index.html.twig`
- `templates/user/pdf/profile.html.twig`
- `templates/user/pdf/all_users.html.twig`

### Required config/env
- `CLOUDINARY_CLOUD_NAME`
- `CLOUDINARY_API_KEY`
- `CLOUDINARY_API_SECRET`
- `CLOUDINARY_USER_IMAGE_FOLDER`
- `CLOUDINARY_USER_CV_FOLDER`
- `CLOUDINARY_MAX_IMAGE_SIZE`
- `CLOUDINARY_MAX_CV_SIZE`
- `PDF_COMPANY_NAME`

### Notes
- CV flow is currently PDF-only for reliable Cloudinary delivery.
- Some Cloudinary accounts can block original PDF delivery (`show_original_customer_untrusted`); transformed preview URLs are used as fallback for UX.

---

## Feature 5: Reclamations Geolocation + Gemini AI

### What was implemented
- Added geolocation fields to reclamations (incident date, place, latitude, longitude).
- Added map preview support in reclamation creation form with:
  - Browser geolocation (`Ma position`).
  - Address geocoding via external Nominatim service.
- Added Gemini moderation before saving a reclamation.
- Added Gemini letter assistant to generate a professional French reclamation letter.
- Kept generated letter editable before final submission (fills description textarea only).

### How it works
1. User fills title/description and optional date/location/details.
2. User may localize the place on map via browser location or address search.
3. User may click AI generation to produce a professional French letter draft.
4. On submission, server validates fields and runs Gemini moderation.
5. If moderation accepts, reclamation is saved with optional geolocation metadata.

### Files changed
- `.env`
- `config/services.yaml`
- `src/Entity/Reclamation.php`
- `src/Controller/Reclamation/ReclamationController.php`
- `src/Service/Reclamation/GeminiClient.php`
- `src/Service/Reclamation/ReclamationModerationService.php`
- `src/Service/Reclamation/ReclamationLetterGeneratorService.php`
- `src/Service/Reclamation/GeocodingService.php`
- `templates/reclamation/form.html.twig`
- `templates/reclamation/index.html.twig`
- `templates/admin/reclamation/index.html.twig`
- `migrations/Version20260419000002.php`

### Required config/env
- `GEMINI_API_KEY`
- `GEMINI_MODEL`
- `GEMINI_TIMEOUT_SECONDS`
- `RECLAMATION_GEMINI_MODERATION_ENABLED`
- `RECLAMATION_AI_LETTER_ENABLED`
- `RECLAMATION_GEOCODING_ENABLED`
- `RECLAMATION_GEOCODING_USER_AGENT`
- `RECLAMATION_GEOCODING_TIMEOUT_SECONDS`

### Notes
- Gemini moderation and AI letter generation are toggleable by env flags.
- Geocoding failures do not block valid reclamation submission.

---

## Additional Related Changes
- Removed debug `dump()/die()` from post-login redirect flow:
  - `src/Controller/User/AuthController.php`
- Added forgot-password and secure reset-password flow with Brevo email delivery and token expiry handling.
- Added Google reCAPTCHA validation on registration and reclamation submission forms.

---

## Validation Status (latest run)
- Container lint: OK
- Twig lint: OK
- New routes resolved: OK
- Unauthorized access to `/api/me` without token: returns 401 (expected)
- Invalid credentials on `/api/login_check`: returns 401 (expected)

---

## Update Rules (for future edits)
When a users/reclamations/security-related change is made, update this file by adding:
1. Date of change.
2. Feature name.
3. What changed.
4. Exact files touched.
5. Short runtime behavior explanation.
6. Any required env/config/migration step.

## Change Log

### 2026-04-19
- Created this Markdown summary document.
- Documented JWT, 2FA, and pagination integrations with file-level detail.
- Fixed 2FA login bypass where verification was skipped after password login.
- Enforced `IS_AUTHENTICATED_FULLY` on `/redirect-after-login` in security access control.
- Added explicit runtime guard in `AuthController::redirectAfterLogin` to redirect 2FA-in-progress tokens to `/2fa`.
- Expanded Scheb `security_tokens` compatibility to include `UsernamePasswordToken`.
- Fixed JWT login response override: `LoginSubscriber` now skips `api_login`/`/api/*` so Lexik returns JSON token instead of HTML redirect.
- Implemented password reset routes `/mot-de-passe/oubli` and `/mot-de-passe/reset` with secure token generation, hashing, expiry checks, and one-time invalidation.
- Implemented Brevo API sender service for password reset emails using env API key.
- Implemented server-side reCAPTCHA verification service and integrated it into register and reclamation POST handlers.
- Added CSRF validation to forgot-password, reset-password, and reclamation submission forms.
- Integrated Cloudinary uploader service for profile images and CV uploads.
- Switched CV flow to PDF-only and improved validation for upload reliability.
- Added LinkedIn-style CV picker UI with selected-file card and current-CV preview block.
- Added Cloudinary transformed preview fallback for current CV display when original PDF delivery is blocked.
- Kept users PDF generation service on Dompdf after rollback from Snappy.
- Added admin single-user PDF route `/admin/utilisateurs/{id}/pdf` and admin all-users PDF route `/admin/utilisateurs/pdf/all`.
- Added admin UI actions for per-user PDF and all-users PDF export.
- Added geolocation and map preview integration for reclamations with browser location and address geocoding.
- Added Gemini moderation before reclamation persistence (bad/abusive/spam/irrelevant filtering).
- Added Gemini AI assistant for generating professional French reclamation letters editable before submission.
