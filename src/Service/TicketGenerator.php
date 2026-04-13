<?php
 
namespace App\Service;
 
use App\Entity\Evenement;
use Dompdf\Dompdf;
use Dompdf\Options;
 
class TicketGenerator
{
    public function generateTicketPdf(
        string $nom,
        string $prenom,
        string $email,
        Evenement $evenement,
        string $statutPaiement,
        int $inscriptionId,
    ): string {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
 
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($nom, $prenom, $email, $evenement, $statutPaiement, $inscriptionId));
        $dompdf->setPaper([0, 0, 842, 300], 'landscape');
        $dompdf->render();
 
        return $dompdf->output();
    }
 
    private function buildHtml(
        string $nom,
        string $prenom,
        string $email,
        Evenement $evenement,
        string $statutPaiement,
        int $inscriptionId,
    ): string {
        $titre     = htmlspecialchars($evenement->getTitre());
        $lieu      = htmlspecialchars($evenement->getLieu() ?? 'Non précisé');
        $dateDebut = $evenement->getDateDebut()?->format('d') ?? '';
        $moisDebut = $evenement->getDateDebut()?->format('m') ?? '';
        $anneeDebut= $evenement->getDateDebut()?->format('Y') ?? '';
        $dateFin   = $evenement->getDateFin()?->format('d/m/Y') ?? '';
        $prix      = $evenement->getPrix() && (float)$evenement->getPrix() > 0
                        ? number_format((float)$evenement->getPrix(), 2) . ' TND'
                        : 'GRATUIT';
        $fullName  = htmlspecialchars(strtoupper($prenom . ' ' . $nom));
        $emailHtml = htmlspecialchars($email);
        $ticketRef = 'PF-' . str_pad((string)$inscriptionId, 8, '0', STR_PAD_LEFT);
        $type      = htmlspecialchars($evenement->getTypeEvenement() ?? '');
        $categorie = htmlspecialchars($evenement->getCategorie()?->getNomCategorie() ?? 'Général');
 
        $paiementLabel = match($statutPaiement) {
            'PAYE'       => 'PAYÉ',
            'NON_REQUIS' => 'GRATUIT',
            default      => 'EN ATTENTE',
        };
        $paiementBg = match($statutPaiement) {
            'PAYE'       => '#16a34a',
            'NON_REQUIS' => '#16a34a',
            default      => '#d97706',
        };
 
        // Perforated tear-off dots pattern
        $dots = '';
        for ($i = 0; $i <= 18; $i++) {
            $dots .= '<div style="width:7px;height:7px;border-radius:50%;background:#f4f5fb;margin:5px 0;"></div>';
        }
 
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f0f5; font-family: DejaVu Sans, Arial, sans-serif; }
 
.page { padding: 28px 32px; }
 
.ticket {
    width: 760px;
    height: 228px;
    display: flex;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(108,99,255,.25);
    position: relative;
}
 
/* ── LEFT: hero image area ──────────────────────────────── */
.ticket-hero {
    width: 440px;
    background: linear-gradient(135deg, #0f0c1a 0%, #1a1040 35%, #3a1f6e 65%, #6C63FF 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 0;
}
 
/* Animated orb overlays */
.hero-orb-1 {
    position: absolute; top: -60px; right: -40px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(108,99,255,.35);
    filter: blur(40px);
}
.hero-orb-2 {
    position: absolute; bottom: -40px; left: -20px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(157,107,255,.3);
    filter: blur(35px);
}
.hero-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,.07) 1px, transparent 1px);
    background-size: 22px 22px;
}
.hero-veil {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(9,9,15,.85) 0%, rgba(9,9,15,.3) 50%, transparent 100%);
}
 
.hero-content {
    position: relative; z-index: 2;
    padding: 22px 26px;
}
.hero-eyebrow {
    font-size: 9px; font-weight: 700; letter-spacing: .18em;
    text-transform: uppercase; color: rgba(255,255,255,.6);
    margin-bottom: 6px;
}
.hero-title {
    font-size: 21px; font-weight: 900; color: #fff;
    letter-spacing: -.02em; line-height: 1.1;
    text-shadow: 0 2px 16px rgba(0,0,0,.6);
    margin-bottom: 10px;
}
.hero-meta {
    display: flex; gap: 14px;
}
.hero-meta-item {
    display: flex; align-items: center; gap: 5px;
}
.hero-meta-icon {
    width: 16px; height: 16px; border-radius: 4px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; color: rgba(255,255,255,.8);
}
.hero-meta-text {
    font-size: 10px; color: rgba(255,255,255,.75); font-weight: 600;
}
 
/* ── TEAR LINE ───────────────────────────────────────────── */
.tear {
    width: 22px;
    background: #f0f0f5;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 5;
}
.tear::before {
    content: '';
    position: absolute; top: 0; left: 10px; right: 10px;
    height: 100%;
    background: repeating-linear-gradient(
        to bottom,
        transparent 0px, transparent 10px,
        rgba(108,99,255,.15) 10px, rgba(108,99,255,.15) 12px
    );
}
.tear-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #f0f0f5;
    margin: 4px 0;
    position: relative; z-index: 2;
    border: 1px solid rgba(108,99,255,.15);
}
/* Half-circles on edges */
.tear::after {
    content: '';
    position: absolute;
    left: -8px; right: -8px;
    top: 0; bottom: 0;
}
 
/* ── MIDDLE: details ────────────────────────────────────── */
.ticket-body {
    flex: 1;
    background: #fff;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.body-top {}
.body-label {
    font-size: 8.5px; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: #9ca3af; margin-bottom: 3px;
}
.body-value {
    font-size: 13px; font-weight: 800; color: #1a1d2e;
}
.body-grid {
    display: flex; gap: 0; border-top: 1px solid #f0eeff; padding-top: 12px; margin-top: 12px;
}
.body-cell {
    flex: 1;
    border-right: 1px dashed #e5e7ef;
    padding-right: 12px;
    margin-right: 12px;
}
.body-cell:last-child { border-right: none; padding-right: 0; margin-right: 0; }
 
.date-big {
    font-size: 26px; font-weight: 900; color: #6C63FF;
    line-height: 1; letter-spacing: -.03em;
}
.date-sub {
    font-size: 9px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .1em; margin-top: 2px;
}
 
.price-tag {
    display: inline-block;
    background: linear-gradient(135deg, #6C63FF, #9D6BFF);
    color: #fff;
    font-size: 14px; font-weight: 900;
    padding: 4px 12px; border-radius: 8px;
    letter-spacing: -.01em;
}
 
.status-pill {
    display: inline-block;
    font-size: 9px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
    letter-spacing: .08em; text-transform: uppercase;
    color: #fff;
    background: {$paiementBg};
    margin-top: 4px;
}
 
/* ── RIGHT STUB ─────────────────────────────────────────── */
.ticket-stub {
    width: 120px;
    background: linear-gradient(160deg, #6C63FF 0%, #9D6BFF 100%);
    padding: 18px 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.stub-orb {
    position: absolute; top: -30px; right: -30px;
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255,255,255,.1); filter: blur(20px);
}
.stub-brand {
    font-size: 8px; font-weight: 700; color: rgba(255,255,255,.65);
    letter-spacing: .14em; text-transform: uppercase;
    position: relative; z-index: 2;
}
.stub-logo {
    font-size: 13px; font-weight: 900; color: #fff;
    letter-spacing: -.01em;
    position: relative; z-index: 2;
    margin-top: 2px;
}
.stub-divider {
    width: 100%; height: 1px;
    background: rgba(255,255,255,.2);
    margin: 8px 0;
}
.stub-ref-label {
    font-size: 7px; font-weight: 700; color: rgba(255,255,255,.55);
    letter-spacing: .1em; text-transform: uppercase;
    position: relative; z-index: 2;
}
.stub-ref {
    font-size: 9px; font-weight: 800; color: #fff;
    letter-spacing: .06em; margin-top: 3px;
    position: relative; z-index: 2;
    word-break: break-all;
}
.stub-admit {
    font-size: 7px; font-weight: 700; color: rgba(255,255,255,.55);
    text-transform: uppercase; letter-spacing: .1em;
    position: relative; z-index: 2;
}
.stub-admit-val {
    font-size: 18px; font-weight: 900; color: #fff;
    position: relative; z-index: 2;
}
 
/* barcode-like decoration */
.stub-barcode {
    display: flex; gap: 2px; margin: 6px 0;
    position: relative; z-index: 2;
}
.stub-bar {
    background: rgba(255,255,255,.5);
    border-radius: 1px;
}
</style>
</head>
<body>
<div class="page">
<div class="ticket">
 
    <!-- HERO -->
    <div class="ticket-hero">
        <div class="hero-orb-1"></div>
        <div class="hero-orb-2"></div>
        <div class="hero-dots"></div>
        <div class="hero-veil"></div>
        <div class="hero-content">
            <div class="hero-eyebrow">PathFinder RH &nbsp;·&nbsp; Ticket d'entrée</div>
            <div class="hero-title">{$titre}</div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <div class="hero-meta-icon">&#128197;</div>
                    <div class="hero-meta-text">{$dateDebut}/{$moisDebut}/{$anneeDebut}</div>
                </div>
                <div class="hero-meta-item">
                    <div class="hero-meta-icon">&#128205;</div>
                    <div class="hero-meta-text">{$lieu}</div>
                </div>
                <div class="hero-meta-item">
                    <div class="hero-meta-icon">&#127981;</div>
                    <div class="hero-meta-text">{$type}</div>
                </div>
            </div>
        </div>
    </div>
 
    <!-- TEAR LINE -->
    <div class="tear">
        {$dots}
    </div>
 
    <!-- BODY -->
    <div class="ticket-body">
        <div class="body-top">
            <div class="body-label">Titulaire du billet</div>
            <div class="body-value">{$fullName}</div>
            <div style="font-size:10px;color:#6b7280;margin-top:2px;">{$emailHtml}</div>
        </div>
        <div class="body-grid">
            <div class="body-cell">
                <div class="body-label">Date</div>
                <div class="date-big">{$dateDebut}</div>
                <div class="date-sub">{$moisDebut} / {$anneeDebut}</div>
            </div>
            <div class="body-cell">
                <div class="body-label">Fin</div>
                <div class="date-big" style="font-size:18px;">{$dateFin}</div>
            </div>
            <div class="body-cell">
                <div class="body-label">Catégorie</div>
                <div class="body-value" style="font-size:11px;">{$categorie}</div>
            </div>
            <div class="body-cell" style="border-right:none;">
                <div class="body-label">Tarif</div>
                <div class="price-tag">{$prix}</div>
                <div class="status-pill">{$paiementLabel}</div>
            </div>
        </div>
    </div>
 
    <!-- STUB -->
    <div class="ticket-stub">
        <div class="stub-orb"></div>
        <div>
            <div class="stub-brand">PathFinder</div>
            <div class="stub-logo">RH</div>
        </div>
        <div>
            <div class="stub-divider"></div>
            <div class="stub-admit">Admit</div>
            <div class="stub-admit-val">1</div>
            <div class="stub-divider"></div>
        </div>
        <div class="stub-barcode">
            <div class="stub-bar" style="width:2px;height:32px;"></div>
            <div class="stub-bar" style="width:1px;height:32px;"></div>
            <div class="stub-bar" style="width:3px;height:32px;"></div>
            <div class="stub-bar" style="width:1px;height:32px;"></div>
            <div class="stub-bar" style="width:2px;height:32px;"></div>
            <div class="stub-bar" style="width:1px;height:32px;"></div>
            <div class="stub-bar" style="width:3px;height:32px;"></div>
            <div class="stub-bar" style="width:2px;height:32px;"></div>
            <div class="stub-bar" style="width:1px;height:32px;"></div>
            <div class="stub-bar" style="width:2px;height:32px;"></div>
        </div>
        <div>
            <div class="stub-ref-label">Réf. billet</div>
            <div class="stub-ref">{$ticketRef}</div>
        </div>
    </div>
 
</div>
</div>
</body>
</html>
HTML;
    }
}