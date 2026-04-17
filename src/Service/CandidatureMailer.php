<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Entretien;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CandidatureMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $fromEmail = 'noreply@pathfinders.tn'
    ) {}

    /** Envoyé au candidat après soumission du quiz */
    public function sendCandidatureRecue(Candidature $candidature, string $toEmail, string $toName): void
    {
        $offre = $candidature->getOffre();
        $score = $candidature->getScore();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('✅ Candidature reçue — ' . $offre->getTitre())
            ->html($this->templateRecue($toName, $offre->getTitre(), $score));

        $this->mailer->send($email);
    }

    /** Envoyé au candidat quand le RH accepte */
    public function sendCandidatureAcceptee(Candidature $candidature, string $toEmail, string $toName): void
    {
        $offre = $candidature->getOffre();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('🎉 Félicitations — ' . $offre->getTitre())
            ->html($this->templateAcceptee($toName, $offre->getTitre()));

        $this->mailer->send($email);
    }

    /** Envoyé au candidat quand le RH refuse */
    public function sendCandidatureRefusee(Candidature $candidature, string $toEmail, string $toName): void
    {
        $offre = $candidature->getOffre();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Résultat de votre candidature — ' . $offre->getTitre())
            ->html($this->templateRefusee($toName, $offre->getTitre()));

        $this->mailer->send($email);
    }

    /** Envoyé au candidat quand l'entretien est confirmé */
    public function sendEntretienConfirme(Entretien $entretien, string $toEmail, string $toName): void
    {
        $offre = $entretien->getOffre();
        $date  = $entretien->getDateEntretien();
        $notes = $entretien->getNotes();
        $token = $entretien->getInterviewToken();

        $interviewUrl = $token
            ? $this->urlGenerator->generate('interview_chat', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL)
            : null;

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('📅 Entretien confirmé — ' . $offre->getTitre())
            ->html($this->templateEntretienConfirme($toName, $offre->getTitre(), $date, $notes, $interviewUrl));

        $this->mailer->send($email);
    }

    // ── Templates HTML ────────────────────────────────────────────────────

    private function templateRecue(string $nom, string $titre, int $score): string
    {
        return "
        <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px;background:#f9f9ff;border-radius:12px'>
            <h2 style='color:#6C63FF;margin-bottom:8px'>✅ Candidature reçue</h2>
            <p style='color:#333'>Bonjour <strong>{$nom}</strong>,</p>
            <p style='color:#555'>Votre candidature pour le poste <strong>{$titre}</strong> a bien été reçue.</p>
            <div style='background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#555'>Score obtenu au quiz : <strong style='color:#6C63FF'>{$score}%</strong></p>
            </div>
            <p style='color:#555'>Notre équipe RH examinera votre dossier et vous contactera prochainement.</p>
            <p style='color:#999;font-size:12px;margin-top:24px'>PathFinders — Plateforme de recrutement</p>
        </div>";
    }

    private function templateAcceptee(string $nom, string $titre): string
    {
        return "
        <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px;background:#f0fdf4;border-radius:12px'>
            <h2 style='color:#16a34a;margin-bottom:8px'>🎉 Félicitations !</h2>
            <p style='color:#333'>Bonjour <strong>{$nom}</strong>,</p>
            <p style='color:#555'>Nous avons le plaisir de vous informer que votre candidature pour le poste <strong>{$titre}</strong> a été <strong style='color:#16a34a'>acceptée</strong>.</p>
            <p style='color:#555'>Notre équipe vous contactera très prochainement pour les prochaines étapes.</p>
            <p style='color:#999;font-size:12px;margin-top:24px'>PathFinders — Plateforme de recrutement</p>
        </div>";
    }

    private function templateRefusee(string $nom, string $titre): string
    {
        return "
        <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px;background:#fff7f7;border-radius:12px'>
            <h2 style='color:#dc2626;margin-bottom:8px'>Résultat de votre candidature</h2>
            <p style='color:#333'>Bonjour <strong>{$nom}</strong>,</p>
            <p style='color:#555'>Après examen de votre dossier, nous avons le regret de vous informer que votre candidature pour le poste <strong>{$titre}</strong> n'a pas été retenue.</p>
            <p style='color:#555'>Nous vous encourageons à postuler à d'autres offres correspondant à votre profil.</p>
            <p style='color:#999;font-size:12px;margin-top:24px'>PathFinders — Plateforme de recrutement</p>
        </div>";
    }

    private function templateEntretienConfirme(string $nom, string $titre, \DateTimeInterface $date, ?string $notes, ?string $interviewUrl = null): string
    {
        $dateFormatee = $date->format('d/m/Y à H:i');
        $notesHtml = $notes
            ? "<div style='background:#f0fdf4;border:1px solid rgba(22,163,74,.2);border-radius:8px;padding:12px 16px;margin:16px 0;font-size:13px;color:#166534'><strong>Note :</strong> {$notes}</div>"
            : '';

        $interviewHtml = $interviewUrl
            ? "<div style='background:#f0f4ff;border:1px solid #c7d2fe;border-radius:10px;padding:18px;margin:20px 0'>
                <p style='margin:0 0 10px;font-size:14px;color:#3730a3;font-weight:600'>🤖 Entretien en ligne</p>
                <p style='margin:0 0 14px;font-size:13px;color:#4338ca'>Un entretien virtuel avec notre assistant IA est prévu à l'heure de votre rendez-vous. Cliquez sur le bouton ci-dessous pour y accéder :</p>
                <a href='{$interviewUrl}' style='display:inline-block;background:#6c63ff;color:#fff;padding:11px 24px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600'>
                    💬 Démarrer l'entretien IA
                </a>
                <p style='margin:12px 0 0;font-size:11px;color:#6366f1'>Le lien sera actif 15 minutes avant l'heure prévue.</p>
               </div>"
            : '';

        return "
        <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px;background:#f0f9ff;border-radius:12px'>
            <h2 style='color:#0369a1;margin-bottom:8px'>📅 Entretien confirmé</h2>
            <p style='color:#333'>Bonjour <strong>{$nom}</strong>,</p>
            <p style='color:#555'>Votre entretien pour le poste <strong>{$titre}</strong> a été <strong style='color:#0369a1'>confirmé</strong>.</p>
            <div style='background:#fff;border:1px solid #e0f2fe;border-radius:10px;padding:18px;margin:20px 0'>
                <p style='margin:0;font-size:15px;color:#0c4a6e'>
                    🗓 <strong>Date :</strong> {$dateFormatee}
                </p>
            </div>
            {$notesHtml}
            {$interviewHtml}
            <p style='color:#555'>Merci de vous présenter à l'heure. En cas d'empêchement, contactez-nous dès que possible.</p>
            <p style='color:#999;font-size:12px;margin-top:24px'>PathFinders — Plateforme de recrutement</p>
        </div>";
    }
}
