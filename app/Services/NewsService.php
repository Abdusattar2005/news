<?php

namespace App\Services;

use App\Classes\News;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class NewsService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }

    /**
     * Получить новости с сайта kaktus.media
     */
    public function fetchNews(): array
    {
        try {
            $response = $this->client->get('https://kaktus.media');
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);
            $news = [];

            // Парсим новости с главной страницы
            $crawler->filter('.news-item, .main-news-item, .item')->each(function (Crawler $node) use (&$news) {
                $title = $this->extractTitle($node);
                $url = $this->extractUrl($node);
                $image = $this->extractImage($node);
                $date = $this->extractDate($node);

                if ($title && $url) {
                    $news[] = new News($date, $title, $url, $image);
                }
            });

            // Если основной селектор не сработал, попробуем альтернативные
            if (empty($news)) {
                $crawler->filter('article, .post, .entry')->each(function (Crawler $node) use (&$news) {
                    $title = $this->extractTitle($node);
                    $url = $this->extractUrl($node);
                    $image = $this->extractImage($node);
                    $date = $this->extractDate($node);

                    if ($title && $url) {
                        $news[] = new News($date, $title, $url, $image);
                    }
                });
            }

            return $news;
        } catch (\Exception $e) {
            \Log::error('Ошибка при парсинге новостей: ' . $e->getMessage());
            return $this->getFallbackNews();
        }
    }

    /**
     * Извлечь заголовок новости
     */
    private function extractTitle(Crawler $node): ?string
    {
        $selectors = ['h1', 'h2', 'h3', '.title', '.headline', 'a'];

        foreach ($selectors as $selector) {
            $titleNode = $node->filter($selector)->first();
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
                if (!empty($title)) {
                    return $title;
                }
            }
        }

        return null;
    }

    /**
     * Извлечь URL новости
     */
    private function extractUrl(Crawler $node): ?string
    {
        $linkNode = $node->filter('a')->first();
        if ($linkNode->count() > 0) {
            $href = $linkNode->attr('href');
            if ($href) {
                return $this->normalizeUrl($href);
            }
        }

        return null;
    }

    /**
     * Извлечь изображение новости
     */
    private function extractImage(Crawler $node): ?string
    {
        $imageNode = $node->filter('img')->first();
        if ($imageNode->count() > 0) {
            $src = $imageNode->attr('src') ?: $imageNode->attr('data-src');
            if ($src) {
                return $this->normalizeUrl($src);
            }
        }

        return null;
    }

    /**
     * Извлечь дату новости
     */
    private function extractDate(Crawler $node): string
    {
        $dateSelectors = ['.date', '.time', '.published', 'time'];

        foreach ($dateSelectors as $selector) {
            $dateNode = $node->filter($selector)->first();
            if ($dateNode->count() > 0) {
                $dateText = $dateNode->text();
                $parsedDate = $this->parseDate($dateText);
                if ($parsedDate) {
                    return $parsedDate;
                }
            }
        }

        return Carbon::now()->format('Y-m-d');
    }

    /**
     * Парсить дату из текста
     */
    private function parseDate(string $dateText): ?string
    {
        try {
            // Попытка распарсить разные форматы даты
            $date = Carbon::parse($dateText);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Нормализовать URL
     */
    private function normalizeUrl(string $url): string
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        return 'https://kaktus.media' . (strpos($url, '/') === 0 ? $url : '/' . $url);
    }

    /**
     * Фильтровать новости по дате
     */
    public function filterByDate(array $news, string $date): array
    {
        return array_filter($news, function ($item) use ($date) {
            return $item->date === $date;
        });
    }

    /**
     * Поиск новостей по заголовку
     */
    public function searchByTitle(array $news, string $query): array
    {
        return array_filter($news, function ($item) use ($query) {
            return stripos($item->title, $query) !== false;
        });
    }

    /**
     * Резервные новости на случай ошибки парсинга
     */
    private function getFallbackNews(): array
    {
        return [
            new News(
                Carbon::now()->format('Y-m-d'),
                'Новости временно недоступны',
                'https://kaktus.media',
                null
            )
        ];
    }
}
