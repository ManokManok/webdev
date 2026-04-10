<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OthersController extends AbstractController
{
    #[Route('/others', name: 'app_others', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('others/index.html.twig');
    }
}
