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
    public function index(Request $request, CompanyRepository $companyRepository): Response
    {
        $search = trim($request->query->get('q', ''));
        $context = [
            'stats' => $companyRepository->findAllWithReviewStats($search !== '' ? $search : null),
            'search' => $search,
        ];

        // live search fetches this same route with X-Requested-With set — render just the results fragment so the JS can swap it in without a full page reload
        if ($request->isXmlHttpRequest()) {
            return $this->render('company/_results.html.twig', $context);
        }

        return $this->render('company/index.html.twig', $context);
    }

    #[Route('/companies/search', name: 'app_company_search', methods: ['GET'])]
    public function search(Request $request, CompanyRepository $companyRepository): JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        if ($query === '') {
            return $this->json([]);
        }

        return $this->json(array_map(
            static fn(Company $company) => ['id' => $company->getId(), 'name' => $company->getName()],
            $companyRepository->search($query),
        ));
    }
}
