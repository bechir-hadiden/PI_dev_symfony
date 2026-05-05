<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Entity\HotelAmenity;
use App\Entity\HotelImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Handles hotel persistence + file upload logic.
 * Keeping this out of controllers keeps them thin and clean.
 */
class HotelService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private string $uploadDir,  // injected via services.yaml
    ) {}

    /**
     * Save hotel with amenities (from comma-separated string) and uploaded files.
     */
    public function saveHotel(Hotel $hotel, string $amenitiesRaw, array $uploadedFiles): void
    {
        // 1. Replace amenities
        foreach ($hotel->getAmenities() as $existing) {
            $hotel->removeAmenity($existing);
            $this->em->remove($existing);
        }

        $names = array_filter(array_map('trim', explode(',', $amenitiesRaw)));
        foreach ($names as $name) {
            if ($name !== '') {
                $amenity = new HotelAmenity();
                $amenity->setAmenityName($name);
                $hotel->addAmenity($amenity);
            }
        }

        // 2. Handle uploaded photos
        $order = $hotel->getImages()->count();
        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile) {
                $filename = $this->uploadFile($file);
                $image = new HotelImage();
                $image->setImageUrl('/uploads/hotels/' . $filename);
                $image->setDisplayOrder(++$order);
                $hotel->addImage($image);
            }
        }

        $this->em->persist($hotel);
        $this->em->flush();
    }

    /**
     * Moves the uploaded file to the public uploads directory and returns the filename.
     */
    public function uploadFile(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadDir, $newFilename);

        return $newFilename;
    }

    /**
     * Delete a specific image from a hotel.
     */
    public function deleteImage(HotelImage $image): void
    {
        $filePath = $this->uploadDir . '/' . basename($image->getImageUrl());
        if (file_exists($filePath) && !str_starts_with($image->getImageUrl(), 'http')) {
            unlink($filePath);
        }
        $this->em->remove($image);
        $this->em->flush();
    }
}
