<?php

namespace App\Controller\Admin;

use App\Repository\HotelRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(
        UserRepository  $userRepo,
        HotelRepository $hotelRepo,
    ): Response {
        $totalUsers   = count($userRepo->findAll());
        $totalClients = count($userRepo->findAllWithRole('CLIENT'));
        $totalAdmins  = count($userRepo->findAllWithRole('ADMIN'));
        $totalHotels  = count($hotelRepo->findAll());
        $recentUsers  = array_slice($userRepo->findBy([], ['createdAt' => 'DESC']), 0, 5);
        $recentHotels = array_slice($hotelRepo->findAll(), 0, 5);

        return $this->render('admin/dashboard.html.twig', [
            'total_users'   => $totalUsers,
            'total_clients' => $totalClients,
            'total_admins'  => $totalAdmins,
            'total_hotels'  => $totalHotels,
            'recent_users'  => $recentUsers,
            'recent_hotels' => $recentHotels,
        ]);
    }
}
