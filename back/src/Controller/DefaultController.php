<?php

namespace App\Controller;

use App\Repository\EtapeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EtapeRepository $etapeRepository): Response
    {
        $etapes = $etapeRepository->findAll();
        return $this->json($etapes);
    }
}
