<?php

namespace App\Controller\Admin;

use App\Entity\Hotel;
use App\Entity\HotelImage;
use App\Form\HotelFormType;
use App\Repository\HotelRepository;
use App\Service\HotelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hotels', name: 'admin_hotel_')]
#[IsGranted('ROLE_ADMIN')]
class HotelController extends AbstractController
{
    public function __construct(
        private HotelRepository      $hotelRepo,
        private EntityManagerInterface $em,
        private HotelService         $hotelService,
    ) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $query  = $request->query->get('q', '');
        $hotels = $query
            ? $this->hotelRepo->searchHotels($query)
            : $this->hotelRepo->findAllWithImages();

        return $this->render('admin/hotel/index.html.twig', [
            'hotels' => $hotels,
            'query'  => $query,
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $hotel = new Hotel();
        $form  = $this->createForm(HotelFormType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amenitiesRaw  = $form->get('amenitiesInput')->getData() ?? '';
            $uploadedFiles = $form->get('photos')->getData() ?? [];

            $this->hotelService->saveHotel($hotel, $amenitiesRaw, $uploadedFiles);

            $this->addFlash('success', "Hotel «{$hotel->getName()}» created successfully.");
            return $this->redirectToRoute('admin_hotel_index');
        }

        return $this->render('admin/hotel/form.html.twig', [
            'form'  => $form,
            'hotel' => $hotel,
            'title' => 'Add New Hotel',
        ]);
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $hotel = $this->hotelRepo->findOneWithDetails($id);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }

        // Pre-fill amenities input with current amenity names
        $currentAmenities = implode(', ', $hotel->getAmenityNames());

        $form = $this->createForm(HotelFormType::class, $hotel);
        $form->get('amenitiesInput')->setData($currentAmenities);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amenitiesRaw  = $form->get('amenitiesInput')->getData() ?? '';
            $uploadedFiles = $form->get('photos')->getData() ?? [];

            $this->hotelService->saveHotel($hotel, $amenitiesRaw, $uploadedFiles);

            $this->addFlash('success', "Hotel «{$hotel->getName()}» updated successfully.");
            return $this->redirectToRoute('admin_hotel_index');
        }

        return $this->render('admin/hotel/form.html.twig', [
            'form'  => $form,
            'hotel' => $hotel,
            'title' => 'Edit Hotel: ' . $hotel->getName(),
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    #[Route('/{id}', name: 'show')]
    public function show(int $id): Response
    {
        $hotel = $this->hotelRepo->findOneWithDetails($id);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }
        return $this->render('admin/hotel/show.html.twig', ['hotel' => $hotel]);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $hotel = $this->hotelRepo->find($id);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }

        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_hotel_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_hotel_index');
        }

        $name = $hotel->getName();
        $this->em->remove($hotel);
        $this->em->flush();

        $this->addFlash('success', "Hotel «{$name}» deleted.");
        return $this->redirectToRoute('admin_hotel_index');
    }

    // ─── Delete single image (AJAX-friendly) ──────────────────────────────────

    #[Route('/image/{id}/delete', name: 'image_delete', methods: ['POST'])]
    public function deleteImage(int $id, Request $request): Response
    {
        $image = $this->em->getRepository(HotelImage::class)->find($id);
        if (!$image) {
            throw $this->createNotFoundException('Image not found.');
        }

        $hotelId = $image->getHotel()->getId();

        if (!$this->isCsrfTokenValid('delete_image_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_hotel_edit', ['id' => $hotelId]);
        }

        $this->hotelService->deleteImage($image);
        $this->addFlash('success', 'Image removed.');
        return $this->redirectToRoute('admin_hotel_edit', ['id' => $hotelId]);
    }
}
