<?php

namespace App\Services;

use App\Repositories\PortfolioRepository;
use Illuminate\Support\Facades\Storage;

class PortfolioService
{
    protected $portfolioRepo;

    public function __construct(PortfolioRepository $portfolioRepo)
    {
        $this->portfolioRepo = $portfolioRepo;
    }

    public function storePortfolio($user, array $data)
    {
        $targetUserId = $user->id;
        $createdBy = null;

        if (!empty($data['user_id']) && ($user->isAgency() || $user->isBusinessManager())) {
            $requestedUserId = (int) $data['user_id'];

            if ($user->id !== $requestedUserId) {
                if (!$user->clients()->where('user_id', $requestedUserId)->exists()) {
                    throw new \Exception("Unauthorized: You are not the manager of this influencer.");
                }
                $targetUserId = $requestedUserId;
                $createdBy = $user->id;
            }
        }
        $portfolioData = [
            'user_id'     => $targetUserId,
            'created_by'  => $createdBy,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ];

        $portfolio = $this->portfolioRepo->create($portfolioData);

        if (isset($data['media']) && is_array($data['media'])) {
            foreach ($data['media'] as $item) {
                if (isset($item['file'])) {
                    $path = uploadImage($item['file'], 'portfolios');
                    if ($path) {
                        $this->portfolioRepo->addMedia($portfolio->id, [
                            'media_url'  => $path,
                            'media_type' => $item['media_type'],
                            'title'      => $item['title'] ?? null,
                        ]);
                    }
                }
            }
        }

        return $portfolio->load(['media', 'creator']);
    }
    public function toggleBookmark($user, $portfolioId)
    {
        $exists = $this->portfolioRepo->isBookmarked($user->id, $portfolioId);

        if ($exists) {
            $this->portfolioRepo->removeBookmark($user->id, $portfolioId);
            return ['status' => 'removed', 'message' => 'Portfolio removed from bookmarks'];
        }

        $this->portfolioRepo->bookmarkPortfolio($user->id, $portfolioId);
        return ['status' => 'added', 'message' => 'Portfolio bookmarked successfully'];
    }

    public function getBookmarkedPortfolios($user)
    {
        return \App\Models\BookmarkedPortfolio::where('user_id', $user->id)
            ->with([
                'portfolio' => function ($query) {
                    $query->with(['media', 'user:id,name,role,avatar'])
                        ->withCount([
                            'interactions as views_count' => function ($q) {
                                $q->where('interaction_type', 'view');
                            },
                            'interactions as likes_count' => function ($q) {
                                $q->where('interaction_type', 'like');
                            }
                        ]);
                }
            ])
            ->latest()
            ->get();
    }
    public function getUserPortfolios($user)
    {
        return $this->portfolioRepo->getAllPortfolioForUser($user->id);
    }
    public function getFilteredPortfolios($role = null, $excludeUserId = null)
    {
        return $this->portfolioRepo->getAllWithFilters($role, $excludeUserId);
    }

    public function getPortfolioDetails($id)
    {
        $portfolio = $this->portfolioRepo->getByIdWithMedia($id);

        if (!$portfolio) {
            throw new \Exception("Portfolio not found.");
        }

        return $portfolio;
    }
    public function updatePortfolio($user, $id, array $data)
    {
        // Eager load user to check manager relationship efficiently
        $portfolio = $this->portfolioRepo->getById($id);

        if (!$portfolio) {
            throw new \Exception("Portfolio not found.", 404);
        }

        // Professional Authorization Check
        $isOwner = (int) $portfolio->user_id === (int) $user->id;
        $isManager = (int) $portfolio->user->parent_id === (int) $user->id;

        if (!$isOwner && !$isManager) {
            throw new \Exception("You are not authorized to edit this portfolio.", 403);
        }

        return \DB::transaction(function () use ($portfolio, $id, $data) {
            // 1. Update Portfolio Details
            $this->portfolioRepo->update($id, [
                'title'       => $data['title'],
                'description' => $data['description'] ?? $portfolio->description,
            ]);

            // 2. Update Existing Media Titles
            if (!empty($data['update_media'])) {
                foreach ($data['update_media'] as $item) {
                    $this->portfolioRepo->updateMedia($item['id'], ['title' => $item['title']]);
                }
            }

            // 3. Delete Requested Media (Should also delete files from storage)
            if (!empty($data['delete_media_ids'])) {
                foreach ($data['delete_media_ids'] as $mediaId) {
                    $this->portfolioRepo->deleteMedia($mediaId);
                }
            }

            // 4. Handle New Media Uploads
            if (!empty($data['new_media'])) {
                foreach ($data['new_media'] as $item) {
                    if (isset($item['file'])) {
                        $path = uploadImage($item['file'], 'portfolios');
                        $this->portfolioRepo->addMedia($portfolio->id, [
                            'media_url'  => $path,
                            'media_type' => $item['media_type'],
                            'title'      => $item['title'] ?? null,
                        ]);
                    }
                }
            }
            return $portfolio->fresh('media');
        });
    }
}
