<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return view('news.index');
    }

    public function getNews(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));

        $parser = new NewsService();
        $news = $parser->parseNewsByDate($date);

        return response()->json([
            'success' => true,
            'data' => array_values($news)
        ]);
    }

    public function searchNews(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $query = $request->input('query');

        $parser = new NewsService();
        $news = $parser->parseNewsByDate($date);

        $filteredNews = array_filter($news, function ($item) use ($query) {
            return stripos($item->title, $query) !== false;
        });

        return response()->json([
            'success' => true,
            'data' => array_values($filteredNews)
        ]);
    }
}
