<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainPageController extends AbstractController
{
    #[Route('/', name: 'app_main_page')]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $limit = 10;
        $totalPages = max(1, (int) ceil($reviewRepository->count([]) / $limit));

        $page = $request->query->getInt('page', 1);
        $page = max(1, min($page, $totalPages));

        $reviews = $reviewRepository->findPageNewestFirst($limit, offset: ($page - 1) * $limit);

        return $this->render('main_page/index.html.twig', [
            'reviews' => $reviews,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }
}
