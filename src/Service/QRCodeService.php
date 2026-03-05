<?php

namespace App\Service;

/**
 * QRCodeService — équivalent PHP du QRCodeService JavaFX (ZXing)
 *
 * JavaFX utilisait ZXing pour générer le QR Code en local pixel par pixel.
 * En PHP/Symfony on génère le QR Code via qrserver.com (même résultat, sans dépendance).
 *
 * Logique identique à JavaFX :
 *   1️⃣  YouTubeService->getVideoUrl(destinationId, nomVille)  →  URL vidéo
 *   2️⃣  genererQRCode(url, taille)                            →  Image QR Code
 */
class QRCodeService
{
    public function __construct(
        private YouTubeService $youtubeService
    ) {}

    /**
     * Equivalent de genererQRCodeDestination(int destinationId, String nomVille, int taille)
     * Récupère l'URL YouTube puis génère le QR Code.
     *
     * @return string|null  URL de l'image QR Code, ou null si aucune vidéo trouvée
     */
    public function genererQRCodeDestination(int $destinationId, string $nomVille, int $taille = 180): ?string
    {
        // 1️⃣ Obtenir l'URL YouTube (depuis BDD ou API YouTube)
        $videoUrl = $this->youtubeService->getVideoUrl($destinationId, $nomVille);

        if (!$videoUrl) {
            return null;
        }

        // 2️⃣ Générer le QR Code avec l'URL
        return $this->genererQRCode($videoUrl, $taille);
    }

    /**
     * Equivalent de genererQRCode(String url, int taille)
     * 
     * JavaFX  : ZXing BitMatrix → WritableImage pixel par pixel
     * PHP     : qrserver.com API → URL image PNG (même niveau de correction H)
     *
     * @return string  URL de l'image QR Code prête à afficher dans <img src="...">
     */
    public function genererQRCode(string $url, int $taille = 180): string
    {
        return sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s&color=0d1b2a&bgcolor=f5f0e8&format=png&ecc=H',
            $taille,
            $taille,
            urlencode($url)
        );
        // ecc=H → ErrorCorrectionLevel.H  (comme dans JavaFX hints)
        // color=0d1b2a → Navy  (couleur modules QR)
        // bgcolor=f5f0e8 → Crème (fond)
    }
}