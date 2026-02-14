<?php

namespace App\Repositories;

use App\Models\Portfolio;
use App\Models\PortfolioMedia;

class PortfolioRepository
{
    public function create(array $data)
    {
        return Portfolio::create($data);
    }

    public function addMedia($portfolioId, array $mediaItem)
    {
        return PortfolioMedia::create([
            'portfolio_id' => $portfolioId,
            'media_url'    => $mediaItem['media_url'],
            'media_type'   => $mediaItem['media_type'],
            'title'        => $mediaItem['title'] ?? null,
        ]);
    }
    public function bookmarkPortfolio($userId, $portfolioId)
    {
        return \App\Models\BookmarkedPortfolio::firstOrCreate([
            'user_id'      => $userId,
            'portfolio_id' => $portfolioId
        ]);
    }

    public function removeBookmark($userId, $portfolioId)
    {
        return \App\Models\BookmarkedPortfolio::where('user_id', $userId)
            ->where('portfolio_id', $portfolioId)
            ->delete();
    }

    public function isBookmarked($userId, $portfolioId)
    {
        return \App\Models\BookmarkedPortfolio::where('user_id', $userId)
            ->where('portfolio_id', $portfolioId)
            ->exists();
    }
    public function getAllPortfolioForUser($userId)
    {
        return \App\Models\Portfolio::where('user_id', $userId)
            ->with('media')
            ->withCount([
                'interactions as views_count' => function ($query) {
                    $query->where('interaction_type', 'view');
                },
                'interactions as likes_count' => function ($query) {
                    $query->where('interaction_type', 'like');
                }
            ])
            ->latest()
            ->get();
    }
    public function getAllWithFilters($role = null, $excludeUserId = null)
    {
        $query = \App\Models\Portfolio::with(['media', 'user:id,name,role'])
            ->withCount([
                'interactions as views_count' => function ($query) {
                    $query->where('interaction_type', 'view');
                },
                'interactions as likes_count' => function ($query) {
                    $query->where('interaction_type', 'like');
                }
            ]);
        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }
        if ($role) {
            $query->whereHas('user', function ($q) use ($role) {
                $q->where('role', $role);
            });
        }

        return $query->latest()->get();
    }
    public function getByIdWithMedia($id)
    {
        return \App\Models\Portfolio::with('media')
            ->withCount([
                'interactions as views_count' => function ($query) {
                    $query->where('interaction_type', 'view');
                },
                'interactions as likes_count' => function ($query) {
                    $query->where('interaction_type', 'like');
                }
            ])->find($id);
    }
    // update part
    public function getById($id)
    {
        return \App\Models\Portfolio::findOrFail($id);
    }

    public function update($id, array $data)
    {
        return \App\Models\Portfolio::where('id', $id)->update($data);
    }
    public function updateMedia($mediaId, array $data)
    {
        return \App\Models\PortfolioMedia::where('id', $mediaId)->update($data);
    }
    public function deleteMedia($mediaId)
    {
        $media = \App\Models\PortfolioMedia::find($mediaId);
        if ($media) {
            if (file_exists(public_path($media->media_url))) {
                unlink(public_path($media->media_url));
            }
            $media->delete();
        }
    }
}
