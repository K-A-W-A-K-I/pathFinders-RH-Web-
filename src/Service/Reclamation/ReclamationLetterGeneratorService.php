<?php

namespace App\Service\Reclamation;

class ReclamationLetterGeneratorService
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
        private readonly bool $letterGenerationEnabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->letterGenerationEnabled && $this->geminiClient->isEnabled();
    }

    /**
     * @param array{titre:string,description:string,date_incident?:string,lieu?:string,details?:string} $input
     */
    public function generate(array $input): string
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('La generation IA est desactivee.');
        }

        $titre = trim((string) ($input['titre'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $dateIncident = trim((string) ($input['date_incident'] ?? ''));
        $lieu = trim((string) ($input['lieu'] ?? ''));
        $details = trim((string) ($input['details'] ?? ''));

        if ($titre === '' || $description === '') {
            throw new \InvalidArgumentException('Le titre et la description sont obligatoires pour generer une description.');
        }

        $prompt = "Tu es un assistant RH.\n"
            . "Reecris la description d'une reclamation en francais simple, claire et directe.\n"
            . "Contraintes:\n"
            . "- ton naturel et professionnel\n"
            . "- texte court: 4 a 7 phrases maximum\n"
            . "- pas de formule d'email (pas de Bonjour, Cordialement, etc.)\n"
            . "- decrire les faits, l'impact et la demande de resolution\n"
            . "- ne pas inventer d'informations\n"
            . "- retourner UNIQUEMENT un JSON valide: {\"letter\":\"...\"}\n\n"
            . "Informations utilisateur:\n"
            . "Objet: {$titre}\n"
            . "Description: {$description}\n"
            . "Date de l'incident: " . ($dateIncident !== '' ? $dateIncident : 'Non precisee') . "\n"
            . "Lieu: " . ($lieu !== '' ? $lieu : 'Non precise') . "\n"
            . "Details complementaires: " . ($details !== '' ? $details : 'Aucun') . "\n";

        $result = $this->geminiClient->generateJson($prompt);
        $letter = trim((string) ($result['letter'] ?? ''));

        if ($letter === '') {
            throw new \RuntimeException('La generation de description a retourne un contenu vide.');
        }

        return $letter;
    }
}
