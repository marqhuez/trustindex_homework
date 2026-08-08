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
        $offset = max(0, $request->query->getInt('offset', 0));

        $reviews = $reviewRepository->findPageNewestFirst($offset, $limit + 1);
        $hasNextPage = count($reviews) > $limit;
        $reviews = array_slice($reviews, 0, $limit);

        return $this->render('main_page/index.html.twig', [
            'reviews' => $reviews,
            'offset' => $offset,
            'limit' => $limit,
            'hasNextPage' => $hasNextPage,
        ]);
    }
}
