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

    $imageData = $request->request->get('face_image');

    // 1. Extraire le base64
    $base64 = $imageData;
    if (str_contains($base64, ',')) {
        $base64 = explode(',', $base64)[1];
    }
    $base64 = trim($base64);

    if (empty($base64)) {
        return new JsonResponse(['success' => false, 'error' => 'Image vide'], 400);
    }

    // 2. Vérifier que Python tourne
    try {
        $http->request('GET', 'http://127.0.0.1:5000/health', ['timeout' => 2]);
    } catch (\Exception $e) {
        return new JsonResponse(['success' => false, 'error' => 'Service Python indisponible'], 503);
    }

    // 3. Envoyer à /identify — Python trouve le user tout seul
    try {
        $response = $http->request('POST',
            "http://127.0.0.1:5000/identify",
            ['json' => ['image_base64' => $base64]]
        );
        $result = $response->toArray();

    } catch (\Exception $e) {
        return new JsonResponse(['success' => false, 'error' => 'Erreur Python : ' . $e->getMessage()], 503);
    }

    // 4. Connecter si match
    if (!empty($result['match']) && !empty($result['user_id'])) {
        $user = $userRepo->find($result['user_id']);

        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
        }

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
        'error'      => $result['error'] ?? 'Visage non reconnu'
    ], 401);
}}