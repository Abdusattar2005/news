<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * Отображение главной страницы с новостями
     */
    public function index()
    {
        return view('news.index');
    }

    /**
     * API для получения новостей
     */
    public function getNews(Request $request): JsonResponse
    {
        $news = $this->newsService->fetchNews();


        if ($request->has('date') && !empty($request->date)) {
            $news = $this->newsService->filterByDate($news, $request->date);
        }


        if ($request->has('search') && !empty($request->search)) {
            $news = $this->newsService->searchByTitle($news, $request->search);
        }


        $newsArray = array_map(function ($item) {
            return $item->toArray();
        }, $news);

        return response()->json([
            'success' => true,
            'data' => array_values($newsArray)
        ]);
    }

    /**
     * API для поиска новостей
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'date' => 'nullable|date_format:Y-m-d'
        ]);

        $news = $this->newsService->fetchNews();


        if ($request->has('date') && !empty($request->date)) {
            $news = $this->newsService->filterByDate($news, $request->date);
        }


        $filteredNews = $this->newsService->searchByTitle($news, $request->query);

        $newsArray = array_map(function ($item) {
            return $item->toArray();
        }, $filteredNews);

        return response()->json([
            'success' => true,
            'data' => array_values($newsArray),
            'query' => $request->query,
            'date' => $request->date
        ]);
    }
}
