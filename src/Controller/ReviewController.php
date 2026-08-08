<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Service\ReviewService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review')]
    public function index(Request $request, ReviewService $reviewService, LoggerInterface $logger): Response
    {
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reviewService->saveNew($review);
                $this->addFlash('success', 'Review submitted successfully!');

                return $this->redirectToRoute('app_review');
            } catch (Throwable $e) {
                $logger->error('Error saving review: ' . $e->getMessage());
                $this->addFlash('error', 'An error occurred while saving the review');
            }
        }

        return $this->render('review/index.html.twig', [
            'controller_name' => 'ReviewController',
            'form' => $form,
        ]);
    }
}
