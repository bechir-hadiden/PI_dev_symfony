<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository              $userRepo,
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $users = $query
            ? $this->userRepo->searchUsers($query)
            : $this->userRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'query' => $query,
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserFormType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', "User «{$user->getUsername()}» created.");
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', [
            'form'  => $form,
            'user'  => $user,
            'title' => 'Add New User',
        ]);
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $form = $this->createForm(UserFormType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            // Only re-hash if a new password was entered
            if ($plain) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
            }

            $this->em->flush();
            $this->addFlash('success', "User «{$user->getUsername()}» updated.");
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', [
            'form'  => $form,
            'user'  => $user,
            'title' => 'Edit User: ' . $user->getUsername(),
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    #[Route('/{id}', name: 'show')]
    public function show(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
        return $this->render('admin/user/show.html.twig', ['user' => $user]);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_user_index');
        }

        if (!$this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_user_index');
        }

        $username = $user->getUsername();
        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', "User «{$username}» deleted.");
        return $this->redirectToRoute('admin_user_index');
    }

    // ─── Block / Unblock ──────────────────────────────────────────────────────

    #[Route('/{id}/toggle-block', name: 'toggle_block', methods: ['POST'])]
    public function toggleBlock(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot block your own account.');
            return $this->redirectToRoute('admin_user_index');
        }

        if (!$this->isCsrfTokenValid('toggle_block_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user->setIsBlocked(!$user->isBlocked());
        $this->em->flush();

        $status = $user->isBlocked() ? 'blocked' : 'unblocked';
        $this->addFlash('success', "User «{$user->getUsername()}» has been {$status}.");

        return $this->redirectToRoute('admin_user_index');
    }
}
