<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FaceLoginController extends AbstractController
{
    #[Route('/face-login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepo,
        HttpClientInterface $http
    ): JsonResponse {

     $email     = $request->request->get('email');
$imageData = $request->request->get('face_image');

// 1. Trouver l'utilisateur
$user = $userRepo->findOneBy(['email' => $email]);
if (!$user) {
    return new JsonResponse(['success' => false, 'error' => 'Email non trouvé'], 404);
}

// 2. Nettoyer le base64 correctement
$base64 = $imageData;
if (str_contains($base64, ',')) {
    $base64 = explode(',', $base64)[1]; // ← prend uniquement la partie après la virgule
}
$base64 = trim($base64);

// 3. Vérifier que Python tourne
try {
    $http->request('GET', 'http://127.0.0.1:5000/health', ['timeout' => 2]);
} catch (\Exception $e) {
    return new JsonResponse([
        'success' => false,
        'error'   => 'Service Python indisponible'
    ], 503);
}

// 4. Envoyer en JSON
try {
    $response = $http->request('POST',
        "http://127.0.0.1:5000/verify/{$user->getId()}",
        [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body' => json_encode(['image_base64' => $base64])
        ]
    );
    $result = $response->toArray();

} catch (\Exception $e) {
    return new JsonResponse([
        'success' => false,
        'error'   => 'Erreur Python : ' . $e->getMessage()
    ], 503);
}

// 5. Résultat
if (!empty($result['match']) && $result['confidence'] > 75) {
    $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
        $user, 'main', $user->getRoles()
    );
    $request->getSession()->set('_security_main', serialize($token));

    return new JsonResponse([
        'success'    => true,
        'confidence' => $result['confidence'],
        'redirect'   => $this->generateUrl('app_home')
    ]);
}

return new JsonResponse([
    'success'    => false,
    'confidence' => $result['confidence'] ?? 0,
    'error'      => 'Visage non reconnu'
], 401);
}}