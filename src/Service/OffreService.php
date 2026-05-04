<?php

namespace App\Service;

use App\Entity\Offre;
use Doctrine\ORM\EntityManagerInterface;

class OffreService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getAllIntegratedOffres(): array
    {
        $connection = $this->em->getConnection();

        // LA REQUÊTE MAÎTRESSE : Elle lie ton module à tous les autres
        $sql = "SELECT o.*, 
                d.nom as city_name, d.pays as country_name, d.image_url as dest_images,
                v.prix as price_v,
                h.name as name_h, h.price_per_night as prix_h, hi.image_url as hotel_web_img,
                vol.arrivee as dest_vol, vol.prix as prix_vol, vol.image_url as vol_img,
                veh.type as type_veh, veh.ville as ville_veh, veh.prix as prix_veh, veh.image as veh_img
                FROM offre o
                LEFT JOIN voyages v ON o.id_voyage = v.id
                LEFT JOIN destination d ON v.destination_id = d.id 
                LEFT JOIN hotels h ON o.id_hotel = h.id
                LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.display_order = 1
                LEFT JOIN vols vol ON o.id_vol = vol.id
                LEFT JOIN vehicule veh ON o.id_vehicule = veh.idVehicule";

        $results = $connection->fetchAllAssociative($sql);
        $offresObjects = [];

        foreach ($results as $data) {
            $o = new Offre();
            $o->setId($data['id_offre']); 
            // On remplit l'objet Offre avec les données brutes
            $o->setTitre($data['titre']);
            $o->setDescription($data['description']);
            $o->setTauxRemise($data['taux_remise']);
            $o->setCategory($data['category']);
            $o->setIsLocalSupport($data['is_local_support']);

            // LOGIQUE D'INTÉGRATION : On détermine le label et le prix selon la catégorie
            if ($data['category'] === 'VOYAGE' && $data['city_name']) {
                $o->setDestination($data['city_name'] . " (" . $data['country_name'] . ")");
                $o->setPrixInitial((float)$data['price_v']);
                $img = $data['dest_images'] ?? $data['image_url'] ?? 'default.jpg';
                $o->setImageUrl($this->extractFirstImage($data['dest_images']));
            } 
            elseif ($data['category'] === 'HOTEL') {
                $o->setDestination($data['name_h']);
                $o->setPrixInitial((float)$data['prix_h']);
                $o->setImageUrl($data['hotel_web_img'] ?? 'default.jpg');
            }
            elseif ($data['category'] === 'VOL') {
                $o->setDestination("Vers " . $data['dest_vol']);
                $o->setPrixInitial((float)$data['prix_vol']);
                $o->setImageUrl($data['vol_img'] ?? 'plane.jpg');
            }
            elseif ($data['category'] === 'TRANSPORT') {
                $o->setDestination($data['type_veh'] . " (" . $data['ville_veh'] . ")");
                $o->setPrixInitial((float)$data['prix_veh']);
                $o->setImageUrl($data['veh_img'] ?? 'bus.png');
            }

            $offresObjects[] = $o;
        }

        return $offresObjects;
    }

    // Petit outil pour prendre la 1ère image de la galerie du copain
    private function extractFirstImage(?string $gallery): string {
        if (!$gallery) return 'default.jpg';
        $imgs = preg_split('/[;|]/', $gallery);
        return $imgs[0];
    }
}