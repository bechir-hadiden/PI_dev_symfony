<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/register', name: 'register')]
class RegisterController extends AbstractController
{

    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        HttpClientInterface $http
    ): Response {

        if ($request->isMethod('POST')) {

            $email = $request->request->get('email');
            $name = $request->request->get('name');
            $password = $request->request->get('password');
            $imageData = $request->request->get('face_image');


            // vérifier champs obligatoires
            if (!$email || !$name || !$password) {

                $this->addFlash('error', 'Tous les champs sont obligatoires');

                return $this->redirectToRoute('register');
            }


            // création utilisateur
            $user = new User();

            $user->setEmail($email);
            $user->setName($name);

            $hashedPassword = $hasher->hashPassword($user, $password);

            $user->setPassword($hashedPassword);

            $user->setFaceRegistered(false);


            $em->persist($user);
            $em->flush(); // génère ID utilisateur


            // ─────────────────────────────
            // Envoi image Face ID vers Python
            // ─────────────────────────────

            if ($imageData) {

                try {

                    // nettoyer base64
                    $imageData = preg_replace(
                        '/^data:image\/\w+;base64,/',
                        '',
                        $imageData
                    );

                    $binary = base64_decode($imageData);


                    // appel API Flask
                    $response = $http->request(
                        'POST',
                        "http://127.0.0.1:5000/register/" . $user->getId(),
                        [
                            'headers' => [
                                'Content-Type' => 'application/octet-stream'
                            ],
                            'body' => $binary
                        ]
                    );


                    $result = $response->toArray();


                    if ($result['success']) {

                        $user->setFaceRegistered(true);

                        $em->flush();
                    }

                } catch (\Exception $e) {

                    $this->addFlash(
                        'warning',
                        'Face ID non enregistré (service indisponible)'
                    );
                }
            }


            $this->addFlash(
                'success',
                'Compte créé avec succès'
            );


            return $this->redirectToRoute('app_login');
        }


        return $this->render('register/index.html.twig');
    }
}