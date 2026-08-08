<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/companies/search', name: 'app_company_search', methods: ['GET'])]
    public function search(Request $request, CompanyRepository $companyRepository): JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        if ($query === '') {
            return $this->json([]);
        }

        return $this->json(array_map(
            static fn (Company $company) => ['id' => $company->getId(), 'name' => $company->getName()],
            $companyRepository->search($query),
        ));
    }
}
