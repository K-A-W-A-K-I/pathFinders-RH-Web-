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
        
        // Format A4 portrait ou un format personnalisé plus grand pour voir la marge autour
        $dompdf->setPaper('A4', 'landscape');
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
        $dateDebut = $evenement->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin   = $evenement->getDateFin()?->format('d/m/Y') ?? '';
        
        $prix      = $evenement->getPrix() && (float)$evenement->getPrix() > 0
                        ? number_format((float)$evenement->getPrix(), 0) . ' TND'
                        : 'GRATUIT';
                        
        $fullName  = htmlspecialchars(ucwords(strtolower($prenom . ' ' . $nom)));
        $emailHtml = htmlspecialchars($email);
        $ticketRef = 'PF-' . str_pad((string)$inscriptionId, 6, '0', STR_PAD_LEFT);
        $type      = htmlspecialchars(ucfirst(strtolower($evenement->getTypeEvenement() ?? '')));

        $paiementLabel = match($statutPaiement) {
            'PAYE'       => 'PAYÉ',
            'NON_REQUIS' => 'GRATUIT',
            default      => 'EN ATTENTE',
        };
        
        // Couleur dynamique pour le bouton PAYÉ / EN ATTENTE
        $paiementColor = match($statutPaiement) {
            'PAYE'       => '#10b981', // Vert
            'NON_REQUIS' => '#10b981',
            default      => '#f59e0b', // Orange
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    background: #f5f5f5; /* Fond clair pour faire ressortir le ticket */
    font-family: DejaVu Sans, Arial, sans-serif; 
}

.page { 
    padding: 60px; 
    text-align: center;
}

/* Conteneur principal avec bordure noire */
.ticket {
    width: 760px;
    height: 240px;
    margin: 0 auto;
    display: table;
    table-layout: fixed;
    border-radius: 12px;
    border: 3px solid #1a1a1a;
    background: #1a1a1a;
    overflow: hidden;
    text-align: left;
}

/* Partie gauche du ticket */
.ticket-main {
    display: table-cell;
    width: 68%;
    background: #272051;
    vertical-align: top;
    padding: 24px 28px;
    position: relative;
    overflow: hidden;
}

/* Cercles décoratifs (Gradients/Formes) */
.circle-top-right {
    position: absolute;
    top: -120px;
    right: -40px;
    width: 300px;
    height: 300px;
    background: #372e6b;
    border-radius: 50%;
    z-index: 1;
}
.circle-bottom-left {
    position: absolute;
    bottom: -100px;
    left: -80px;
    width: 250px;
    height: 250px;
    background: #372e6b;
    border-radius: 50%;
    z-index: 1;
}

.content-layer {
    position: relative;
    z-index: 2;
}

/* Badge supérieur */
.badge-pill {
    display: inline-block;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 20px;
    padding: 5px 12px;
    margin-bottom: 16px;
}
.badge-pill-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #8b85c1;
    margin-right: 6px;
    vertical-align: middle;
}
.badge-pill-text {
    font-size: 8px;
    font-weight: 700;
    color: rgba(255,255,255,.8);
    letter-spacing: .1em;
    text-transform: uppercase;
    vertical-align: middle;
}

.event-title {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 18px;
}

/* Grille d'informations */
.meta-row {
    display: table;
    width: 100%;
    margin-bottom: 22px;
}
.meta-cell {
    display: table-cell;
    padding-right: 10px;
    vertical-align: top;
}
.meta-label {
    font-size: 8px;
    font-weight: 700;
    color: rgba(255,255,255,.5);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 6px;
    line-height: 1.3;
}
.meta-value {
    font-size: 11px;
    font-weight: 800;
    color: #fff;
    line-height: 1.3;
}

/* Pied du ticket (Titulaire / Prix) */
.bottom-row {
    display: table;
    width: 100%;
}
.bottom-left {
    display: table-cell;
    vertical-align: bottom;
}
.bottom-right {
    display: table-cell;
    vertical-align: bottom;
    text-align: right;
}

.holder-label {
    font-size: 8px;
    font-weight: 700;
    color: rgba(255,255,255,.4);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.holder-name {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
}
.holder-email {
    font-size: 9.5px;
    color: rgba(255,255,255,.5);
    margin-top: 3px;
}

.price-badge {
    display: inline-block;
    background: #6358c5;
    border-radius: 14px;
    padding: 7px 16px;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 6px;
}

.status-pill {
    display: inline-block;
    border-radius: 12px;
    padding: 3px 12px;
    font-size: 8.5px;
    font-weight: 800;
    color: {$paiementColor};
    border: 1px solid {$paiementColor};
    letter-spacing: .08em;
}

/* Souche (Partie détachable à droite) */
.ticket-stub {
    display: table-cell;
    width: 32%;
    background: #3b328a;
    vertical-align: top;
    position: relative;
    text-align: center;
    border-left: 2px dashed #1a1a1a;
    padding: 24px 15px;
    overflow: hidden;
}

.circle-stub-top {
    position: absolute;
    top: -50px;
    right: -50px;
    width: 160px;
    height: 160px;
    background: #463c9b;
    border-radius: 50%;
    z-index: 1;
}

/* Effets de trous de découpe haut et bas */
.stub-cutout-top {
    position: absolute;
    top: -12px;
    left: -12px;
    width: 20px;
    height: 20px;
    background: #f5f5f5; /* Même couleur que le body */
    border: 3px solid #1a1a1a;
    border-radius: 50%;
    z-index: 10;
}
.stub-cutout-bottom {
    position: absolute;
    bottom: -12px;
    left: -12px;
    width: 20px;
    height: 20px;
    background: #f5f5f5; /* Même couleur que le body */
    border: 3px solid #1a1a1a;
    border-radius: 50%;
    z-index: 10;
}

.stub-brand {
    font-size: 9px;
    font-weight: 700;
    color: rgba(255,255,255,.6);
    letter-spacing: .12em;
    text-transform: uppercase;
}
.stub-logo {
    font-size: 18px;
    font-weight: 900;
    color: #fff;
    margin-top: 2px;
}
.stub-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 14px 20px;
}
.stub-admit-label {
    font-size: 8px;
    font-weight: 700;
    color: rgba(255,255,255,.5);
    letter-spacing: .12em;
    text-transform: uppercase;
}
.stub-admit-val {
    font-size: 32px;
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin-top: 4px;
}
.stub-barcode {
    display: table;
    margin: 16px auto 12px;
}
.stub-bar-wrap {
    display: table-cell;
    vertical-align: bottom;
    padding: 0 1px;
}
.stub-bar {
    background: rgba(255,255,255,.6);
    border-radius: 1px;
    display: block;
}
.stub-ref-label {
    font-size: 8px;
    font-weight: 700;
    color: rgba(255,255,255,.5);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.stub-ref {
    font-size: 10px;
    font-weight: 800;
    color: #fff;
    letter-spacing: .05em;
}
</style>
</head>
<body>
<div class="page">
    <div class="ticket">

        <div class="ticket-main">
            <div class="circle-top-right"></div>
            <div class="circle-bottom-left"></div>

            <div class="content-layer">
                <div class="badge-pill">
                    <span class="badge-pill-dot"></span>
                    <span class="badge-pill-text">PATHFINDER RH &nbsp;·&nbsp; TICKET D'ENTRÉE</span>
                </div>

                <div class="event-title">{$titre}</div>

                <div class="meta-row">
                    <div class="meta-cell" style="width: 18%;">
                        <div class="meta-label">DATE<br>DÉBUT</div>
                        <div class="meta-value">{$dateDebut}</div>
                    </div>
                    <div class="meta-cell" style="width: 22%;">
                        <div class="meta-label">DATE FIN<br>&nbsp;</div>
                        <div class="meta-value">{$dateFin}</div>
                    </div>
                    <div class="meta-cell" style="width: 38%;">
                        <div class="meta-label">LIEU<br>&nbsp;</div>
                        <div class="meta-value">{$lieu}</div>
                    </div>
                    <div class="meta-cell" style="width: 22%;">
                        <div class="meta-label">TYPE<br>&nbsp;</div>
                        <div class="meta-value">{$type}</div>
                    </div>
                </div>

                <div class="bottom-row">
                    <div class="bottom-left">
                        <div class="holder-label">TITULAIRE</div>
                        <div class="holder-name">{$fullName}</div>
                        <div class="holder-email">{$emailHtml}</div>
                    </div>
                    <div class="bottom-right">
                        <div class="price-badge">{$prix}</div><br>
                        <div class="status-pill">{$paiementLabel}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-stub">
            <div class="stub-cutout-top"></div>
            <div class="stub-cutout-bottom"></div>
            <div class="circle-stub-top"></div>

            <div class="content-layer">
                <div class="stub-brand">PATHFINDER</div>
                <div class="stub-logo">RH</div>
                
                <div class="stub-divider"></div>
                
                <div class="stub-admit-label">ADMIT</div>
                <div class="stub-admit-val">1</div>
                
                <div class="stub-divider" style="margin-top:20px;"></div>
                
                <div class="stub-barcode">
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:2px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:1px;height:24px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:3px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:1px;height:18px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:2px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:1px;height:26px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:3px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:2px;height:22px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:1px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:2px;height:28px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:3px;height:32px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:1px;height:16px;"></div></div>
                    <div class="stub-bar-wrap"><div class="stub-bar" style="width:2px;height:32px;"></div></div>
                </div>
                
                <div class="stub-ref-label">RÉFÉRENCE</div>
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