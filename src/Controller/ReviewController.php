<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ReviewController extends AbstractController
{
    #[Route('/', name: 'app_review_index')]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $limit = 10;
        $totalPages = max(1, (int) ceil($reviewRepository->count([]) / $limit));

        $page = $request->query->getInt('page', 1);
        $page = max(1, min($page, $totalPages));

        $reviews = $reviewRepository->findPageNewestFirst($limit, offset: ($page - 1) * $limit);

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/reviews/new', name: 'app_review_new')]
    public function new(Request $request, ReviewService $reviewService, LoggerInterface $logger): Response
    {
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reviewService->saveNew($review);
                $this->addFlash('success', 'Review submitted successfully!');

                return $this->redirectToRoute('app_review_index');
            } catch (Throwable $e) {
                $logger->error('Error saving review: ' . $e->getMessage());
                $this->addFlash('error', 'An error occurred while saving the review');
            }
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reviews/{id}', name: 'app_review_show', requirements: ['id' => '\d+'])]
    public function show(Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }
}
