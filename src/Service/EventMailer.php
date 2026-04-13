<?php
 
namespace App\Service;
 
use App\Entity\Evenement;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
 
class EventMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TicketGenerator $ticketGenerator,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $appBaseUrl,
    ) {}
 
    public function sendInscriptionConfirmation(
        string $toEmail,
        string $nom,
        string $prenom,
        Evenement $evenement,
        string $statutPaiement,
        int $inscriptionId = 0,
    ): void {
        $eventUrl  = $this->appBaseUrl . '/user/evenements/' . $evenement->getId();
        $ticketRef = 'PF-' . str_pad((string)$inscriptionId, 6, '0', STR_PAD_LEFT);
 
        // Generate PDF ticket
        $pdfContent = $this->ticketGenerator->generateTicketPdf(
            $nom, $prenom, $toEmail,
            $evenement, $statutPaiement, $inscriptionId
        );
 
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($toEmail, $prenom . ' ' . $nom))
            ->subject('🎟️ Votre ticket — ' . $evenement->getTitre())
            ->htmlTemplate('emails/inscription_confirmation.html.twig')
            ->context([
                'evenement'      => $evenement,
                'nom'            => $nom,
                'prenom'         => $prenom,
                'statutPaiement' => $statutPaiement,
                'eventUrl'       => $eventUrl,
                'ticketRef'      => $ticketRef,
            ])
            ->addPart(
                new DataPart(
                    $pdfContent,
                    'ticket-' . $ticketRef . '.pdf',
                    'application/pdf'
                )
            );
 
        $this->mailer->send($email);
    }
}