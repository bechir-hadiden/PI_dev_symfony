<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvisController extends AbstractController
{
    #[Route('/avis', name: 'app_avis_index')]
    public function index(): Response
    {
        return new Response('
            <html>
            <head><title>Avis (Bypass)</title></head>
            <body style="font-family:sans-serif; text-align:center; padding:50px;">
                <h1 style="color:#d9534f">⚠️ Route Inexistante sur cette branche</h1>
                <p>Le module des Avis n\'est pas présent sur la branche <b>gestionpaiement</b>.</p>
                <p>Si vous cliquez sur un lien "Avis", c\'est normal qu\'il ne fonctionne pas ici.</p>
                <a href="/mes-paiements" style="display:inline-block; margin-top:20px; padding:10px 20px; background:#0d1b2a; color:#c9a84c; text-decoration:none; border-radius:5px;">Retour à Mes Paiements</a>
            </body>
            </html>
        ');
    }
}
