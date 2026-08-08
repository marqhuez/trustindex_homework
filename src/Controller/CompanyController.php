<?php

namespace App\Controller;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyController extends AbstractController
{
    #[Route('/companies', name: 'app_company')]
    public function index(CompanyRepository $companyRepository): Response
    {
        $stats = $companyRepository->findAllWithReviewStats();

        return $this->render('company/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
