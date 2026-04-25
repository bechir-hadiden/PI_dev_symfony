<?php
// src/Service/ContactService.php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class ContactService
{
     public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail    // injecté via services.yaml
    ) {}

    public function envoyerContact(array $data): void
    {
        // ── Email 1 : notification à l'agence ──────────────────
        $notif = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip'))  // ✅ votre vraie adresse
            ->to($this->adminEmail)
            ->replyTo(new Address($data['email'], $data['prenom'].' '.$data['nom']))
            ->subject('📩 Nouvelle demande — '.$data['prenom'].' '.$data['nom'])
            ->html($this->buildAgenceHtml($data));

        $this->mailer->send($notif);

        // ── Email 2 : confirmation au client ───────────────────
        $confirm = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip'))  // ✅ votre vraie adresse
            ->to(new Address($data['email'], $data['prenom'].' '.$data['nom']))
            ->subject('✅ SmartTrip — Votre demande a bien été reçue')
            ->html($this->buildClientHtml($data));

        $this->mailer->send($confirm);
    }

    // ── Template email agence ──────────────────────────────────
    private function buildAgenceHtml(array $d): string
    {
        $tel  = $d['telephone']          ?? 'Non renseigné';
        $dest = $d['destinationSouhaitee'] ?? 'Non renseignée';
        $bud  = $d['budget']             ?? 'Non renseigné';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="margin:0;padding:0;background:#f5f0e8;font-family:'Segoe UI',sans-serif;">
          <div style="max-width:580px;margin:2rem auto;background:#0d1b2a;border-radius:16px;overflow:hidden;">

            <div style="background:linear-gradient(135deg,#c9a84c,#e8c97a);padding:2rem 2.5rem;">
              <h1 style="margin:0;font-size:1.4rem;color:#0d1b2a;font-family:Georgia,serif;">
                📩 Nouvelle demande de devis
              </h1>
              <p style="margin:.4rem 0 0;font-size:.82rem;color:rgba(13,27,42,0.7);">
                Reçue le {$this->now()}
              </p>
            </div>

            <div style="padding:2rem 2.5rem;">
              <table style="width:100%;border-collapse:collapse;">
                {$this->row('👤 Nom complet', $d['prenom'] . ' ' . $d['nom'])}
                {$this->row('📧 Email', $d['email'])}
                {$this->row('📞 Téléphone', $tel)}
                {$this->row('🌍 Destination', $dest)}
                {$this->row('💰 Budget', $bud)}
              </table>

              <div style="margin-top:1.5rem;padding:1.2rem 1.5rem;
                          background:rgba(201,168,76,0.08);
                          border-left:3px solid #c9a84c;border-radius:4px;">
                <p style="margin:0 0 .5rem;font-size:.72rem;text-transform:uppercase;
                           letter-spacing:.1em;color:#c9a84c;font-weight:700;">
                  Message
                </p>
                <p style="margin:0;color:rgba(255,255,255,0.8);font-size:.88rem;line-height:1.65;">
                  {$this->esc($d['message'])}
                </p>
              </div>

              <div style="margin-top:1.8rem;text-align:center;">
                <a href="mailto:{$d['email']}"
                   style="display:inline-block;padding:.8rem 2rem;
                          background:linear-gradient(135deg,#c9a84c,#e8c97a);
                          color:#0d1b2a;text-decoration:none;border-radius:8px;
                          font-weight:700;font-size:.85rem;">
                  ↩ Répondre à {$d['prenom']}
                </a>
              </div>
            </div>

            <div style="padding:1.2rem 2.5rem;border-top:1px solid rgba(201,168,76,0.1);
                        text-align:center;font-size:.72rem;color:rgba(255,255,255,0.25);">
              SmartTrip · Avenue Habib Bourguiba, Tunis · contact@smarttrip.tn
            </div>

          </div>
        </body>
        </html>
        HTML;
    }

    // ── Template email client (confirmation) ───────────────────
    private function buildClientHtml(array $d): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="margin:0;padding:0;background:#f5f0e8;font-family:'Segoe UI',sans-serif;">
          <div style="max-width:580px;margin:2rem auto;background:#0d1b2a;border-radius:16px;overflow:hidden;">

            <div style="background:linear-gradient(135deg,#c9a84c,#e8c97a);padding:2.5rem;text-align:center;">
              <h1 style="margin:0;font-family:Georgia,serif;font-size:1.6rem;color:#0d1b2a;">
                SmartTrip
              </h1>
              <p style="margin:.3rem 0 0;font-size:.78rem;color:rgba(13,27,42,0.65);">
                Agence de voyage premium
              </p>
            </div>

            <div style="padding:2.5rem;text-align:center;">
              <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
              <h2 style="font-family:Georgia,serif;font-weight:300;color:#ffffff;
                          font-size:1.5rem;margin:0 0 .8rem;">
                Merci, {$this->esc($d['prenom'])} !
              </h2>
              <p style="color:rgba(255,255,255,0.55);font-size:.9rem;line-height:1.7;margin:0 0 2rem;">
                Votre demande a bien été reçue.<br>
                Un de nos conseillers vous contactera <strong style="color:#c9a84c;">sous 24h</strong>
                pour construire votre voyage idéal.
              </p>

              <div style="background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2);
                          border-radius:12px;padding:1.4rem;text-align:left;margin-bottom:2rem;">
                <p style="margin:0 0 .8rem;font-size:.72rem;text-transform:uppercase;
                           letter-spacing:.12em;color:#c9a84c;font-weight:700;">
                  Récapitulatif de votre demande
                </p>
                <p style="margin:.3rem 0;color:rgba(255,255,255,0.65);font-size:.84rem;">
                  <strong style="color:#fff;">Destination :</strong>
                  {$this->esc($d['destinationSouhaitee'] ?? 'Non précisée')}
                </p>
                <p style="margin:.3rem 0;color:rgba(255,255,255,0.65);font-size:.84rem;">
                  <strong style="color:#fff;">Budget :</strong>
                  {$this->esc($d['budget'] ?? 'Non précisé')}
                </p>
              </div>

              <div style="font-size:.78rem;color:rgba(255,255,255,0.25);line-height:1.6;">
                SmartTrip · +216 71 234 567<br>
                contact@smarttrip.tn · Ave Habib Bourguiba, Tunis
              </div>
            </div>

          </div>
        </body>
        </html>
        HTML;
    }

    private function row(string $label, string $value): string
    {
        return <<<HTML
        <tr>
          <td style="padding:.55rem 0;border-bottom:1px solid rgba(201,168,76,0.08);
                     font-size:.78rem;color:rgba(255,255,255,0.4);width:35%;vertical-align:top;">
            {$label}
          </td>
          <td style="padding:.55rem 0;border-bottom:1px solid rgba(201,168,76,0.08);
                     font-size:.84rem;color:#fff;font-weight:500;">
            {$value}
          </td>
        </tr>
        HTML;
    }

    private function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    private function now(): string
    {
        return (new \DateTime())->format('d/m/Y à H:i');
    }
}