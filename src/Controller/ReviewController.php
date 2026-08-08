<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Service\ReviewService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review')]
    public function index(Request $request, ReviewService $reviewService): Response
    {
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reviewService->saveNew($review);
                $this->addFlash('success', 'Review submitted successfully!');

                return $this->redirectToRoute('app_review');
            } catch (Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the review: ' . $e->getMessage());
            }
        }

        return $this->render('review/index.html.twig', [
            'controller_name' => 'ReviewController',
            'form' => $form,
        ]);
    }
}
