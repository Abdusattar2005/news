<?php
namespace App\Classes;

class News
{
    public string $date;
    public string $title;
    public string $url;
    public ?string $image;

    public function __construct(string $date, string $title, string $url, ?string $image = null)
    {
        $this->date = $date;
        $this->title = $title;
        $this->url = $url;
        $this->image = $image;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'title' => $this->title,
            'url' => $this->url,
            'image' => $this->image
        ];
    }
}
