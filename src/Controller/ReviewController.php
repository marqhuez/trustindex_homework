<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\Dto\CreateReviewRequest;
use App\Form\ReviewType;
use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{
    #[Route('/', name: 'app_review_index')]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        // pagination logic kept in the controller since it's the only consumer.
        // if an API endpoint or console command needed the same listing, this would move into ReviewRepository (e.g. returning a PaginatedResult) to avoid duplication
        $limit = 10;
        $totalPages = max(1, (int) ceil($reviewRepository->count(['flagged' => false]) / $limit));

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
    public function new(
        Request $request,
        ReviewService $reviewService,
        CompanyRepository $companyRepository,
        LoggerInterface $logger,
    ): Response {
        $dto = new CreateReviewRequest();

        $companyId = $request->query->getInt('company');
        if ($companyId > 0) {
            $company = $companyRepository->find($companyId);
            if (null !== $company) {
                $dto->companyName = $company->getName();
            }
        }

        $form = $this->createForm(ReviewType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $review = $reviewService->createFromRequest($dto);
                $reviewService->saveNew($review);

                $this->addFlash('success', $review->isFlagged()
                    ? 'Thanks! Your review is pending moderation.'
                    : 'Review submitted successfully!');

                return $this->redirectToRoute('app_review_index');
            } catch (\Throwable $e) {
                $logger->error('Error saving review', ['exception' => $e]);
                $this->addFlash('error', 'An error occurred while saving the review');
            }
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reviews/{id}', name: 'app_review_show', requirements: ['id' => '\d+'])]
    public function show(Review $review, Request $request): Response
    {
        // flagged reviews are hidden from the listing and company stats pending moderation —
        // reachable-by-id would defeat that, so treat them as not found here too
        if ($review->isFlagged()) {
            throw $this->createNotFoundException();
        }

        $fromPage = max(1, $request->query->getInt('fromPage', 1));

        return $this->render('review/show.html.twig', [
            'review' => $review,
            'fromPage' => $fromPage,
        ]);
    }
}
