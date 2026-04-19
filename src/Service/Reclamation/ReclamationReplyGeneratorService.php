<?php

namespace App\Service\Reclamation;

use App\Entity\Reclamation;

class ReclamationReplyGeneratorService
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->geminiClient->isEnabled();
    }

    public function generate(Reclamation $reclamation): string
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Generation IA indisponible.');
        }

        $prompt = "Tu es un agent support RH.\n"
            . "Redige une reponse professionnelle a une reclamation en francais.\n"
            . "Contraintes:\n"
            . "- ton poli, rassurant et professionnel\n"
            . "- longueur courte a moyenne (6 a 10 phrases)\n"
            . "- adaptee au contenu de la reclamation\n"
            . "- ne pas promettre de resultat garanti\n"
            . "- ne pas reconnaitre une faute legale automatiquement\n"
            . "- proposer une prochaine etape concrete\n"
            . "- retourner UNIQUEMENT un JSON valide: {\"reply\":\"...\"}\n\n"
            . "Titre: " . $reclamation->getTitre() . "\n"
            . "Description: " . $reclamation->getDescription() . "\n"
            . "Date incident: " . ($reclamation->getDateIncident() ? $reclamation->getDateIncident()->format('Y-m-d') : 'Non precisee') . "\n"
            . "Lieu: " . ($reclamation->getLieu() ?? 'Non precise') . "\n"
            . "Moderation summary: " . ($reclamation->getModerationSummary() ?? 'N/A') . "\n";

        $result = $this->geminiClient->generateJson($prompt);
        $reply = trim((string) ($result['reply'] ?? ''));

        if ($reply === '') {
            throw new \RuntimeException('La generation IA a retourne un contenu vide.');
        }

        return $reply;
    }
}
