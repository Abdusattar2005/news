<?php
namespace App\Classes;

class News
{
    public function __construct(
        public string $date,
        public string $title,
        public string $url,
        public ?string $image = null
    ) {}

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
