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
        $portfolioData = [
            'user_id'     => $user->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ];

        $portfolio = $this->portfolioRepo->create($portfolioData);
        if (isset($data['media']) && is_array($data['media'])) {
            foreach ($data['media'] as $item) {
                if (isset($item['file'])) {
                    $path = uploadImage($item['file'], 'portfolios');

                    if ($path) {
                        $mediaData = [
                            'media_url'  => $path,
                            'media_type' => $item['media_type'],
                            'title'      => $item['title'] ?? null,
                        ];

                        $this->portfolioRepo->addMedia($portfolio->id, $mediaData);
                    }
                }
            }
        }

        return $portfolio->load('media');
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
            'portfolio.media',
            'portfolio.user:id,name,role'
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
        $portfolio = $this->portfolioRepo->getById($id);
        if (!$portfolio) {

            throw new \Exception("Portfolio not found.", 404);
        }
        if ((int) $portfolio->user_id !== (int) $user->id) {
            throw new \Exception("You are not authorized to edit this portfolio.", 403);
        }


        $this->portfolioRepo->update($id, [
            'title'       => $data['title'],
            'description' => $data['description'] ?? $portfolio->description,
        ]);

        if (isset($data['delete_media_ids'])) {
            foreach ($data['delete_media_ids'] as $mediaId) {
                $this->portfolioRepo->deleteMedia($mediaId);
            }
        }

        if (isset($data['new_media']) && is_array($data['new_media'])) {
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

        return $portfolio->load('media');
    }
}
