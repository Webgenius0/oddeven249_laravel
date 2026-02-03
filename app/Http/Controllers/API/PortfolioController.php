<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PortfolioService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    use ApiResponse;

    protected $portfolioService;

    public function __construct(PortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }
    public function index(Request $request)
    {
        $role = $request->query('role');
        $authUserId = auth()->id();
        try {
            $portfolios = $this->portfolioService->getFilteredPortfolios($role, $authUserId);
            return $this->success($portfolios, 'Portfolios retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'media'               => 'required|array|min:1',
            'media.*.file'        => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:20480',
            'media.*.media_type'  => 'required|in:photo,video',
            'media.*.title'       => 'nullable|string|max:255',
        ]);

        try {
            $portfolio = $this->portfolioService->storePortfolio(Auth::user(), $request->all());
            return $this->success($portfolio, 'Portfolio created successfully!', 201);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function toggleBookmark(Request $request)
    {
        $request->validate([
            'portfolio_id' => 'required|exists:portfolios,id'
        ]);

        try {
            $result = $this->portfolioService->toggleBookmark(Auth::user(), $request->portfolio_id);
            return $this->success(null, $result['message']);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function show(Request $request)
    {
        $request->validate([
        'portfolio_id' => 'required|exists:portfolios,id'
        ]);
        $id = $request->portfolio_id;
        try {
            $portfolio = $this->portfolioService->getPortfolioDetails($id);
            return $this->success($portfolio, 'Portfolio details retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 404);
        }
    }
    public function update(Request $request)
    {
        $request->validate([
            'id'                     => 'required|exists:portfolios,id',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            // Existing media title update
            'update_media'           => 'nullable|array',
            'update_media.*.id'      => 'required|exists:portfolio_media,id',
            'update_media.*.title'   => 'nullable|string|max:255',
            // New media upload
            'new_media'              => 'nullable|array',
            'new_media.*.file'       => 'required_with:new_media|file|mimes:jpg,jpeg,png,mp4,mov|max:20480',
            'new_media.*.media_type' => 'required_with:new_media|in:photo,video',
            // Media deletion
            'delete_media_ids'       => 'nullable|array',
            'delete_media_ids.*'     => 'exists:portfolio_media,id',
        ]);

        try {
            $portfolio = $this->portfolioService->updatePortfolio(
                $request->user(),
                $request->id,
                $request->all()
            );
            return $this->success($portfolio, 'Portfolio updated successfully!');
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return $this->error(null, $e->getMessage(), $code);
        }
    }
    public function myBookmarks()
    {
        try {
            $bookmarks = $this->portfolioService->getBookmarkedPortfolios(Auth::user());
            return $this->success($bookmarks, 'Bookmarked portfolios retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function myPortfolios()
    {
        try {
            $portfolios = $this->portfolioService->getUserPortfolios(Auth::user());
            return $this->success($portfolios, 'Your portfolios retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

}
