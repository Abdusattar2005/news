<?php

namespace App\Services;

use App\Classes\News;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

class NewsService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://kaktus.media/',
            'timeout' => 10.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
            ]
        ]);
    }

    public function parseNewsByDate(string $date): array
    {
        try {
            $dateFormatted = date('d.m.Y', strtotime($date));
            $response = $this->client->get("?lable=8&date={$dateFormatted}&order=time");

            $html = $response->getBody()->getContents();

            // Для отладки можно сохранить HTML
            // file_put_contents(storage_path('last_page.html'), $html);

            $crawler = new Crawler($html);
            $news = [];

            // Новые селекторы для Kaktus.media
            $crawler->filter('.Tag--articles .ArticleItem')->each(function (Crawler $node) use (&$news, $date) {
                try {
                    $titleNode = $node->filter('.ArticleItem--name');
                    if ($titleNode->count() === 0) {
                        return;
                    }

                    $title = $titleNode->text();
                    $url = $titleNode->attr('href');

                    $image = null;
                    $imageNode = $node->filter('.ArticleItem--image img');
                    if ($imageNode->count() > 0) {
                        $image = $imageNode->attr('src');
                        // Если URL изображения относительный, преобразуем в абсолютный
                        if ($image && !preg_match('/^https?:\/\//', $image)) {
                            $image = 'https://kaktus.media' . ltrim($image, '/');
                        }
                    }

                    $news[] = new News(
                        date: $date,
                        title: trim($title),
                        url: $url,
                        image: $image
                    );
                } catch (\Exception $e) {
                    // Пропускаем новость, если возникла ошибка при парсинге
                    logger()->error('Error parsing news item: ' . $e->getMessage());
                }
            });

            return $news;
        } catch (RequestException $e) {
            logger()->error('Request failed: ' . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            logger()->error('Error parsing news: ' . $e->getMessage());
            return [];
        }
    }
}
