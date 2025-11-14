<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Blogs;
use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class SitemapAction
{
    private const BASE_URL = 'https://pdfclicks.com';

    public function generate(): void
    {
        $sitemap = Sitemap::create();

        // Add static pages
        $this->addStaticPages($sitemap);

        // Add blog posts
        $this->addBlogPosts($sitemap);

        // Add file converter pages
        $this->addConverterPages($sitemap);

        // Write sitemap to public directory
        $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    private function addStaticPages(Sitemap $sitemap): void
    {
        $staticPages = [
            ['url' => '/', 'priority' => 1.0, 'frequency' => Url::CHANGE_FREQUENCY_DAILY],
            ['url' => '/tools', 'priority' => 0.8, 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/contact-us', 'priority' => 0.7, 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/blogs', 'priority' => 0.9, 'frequency' => Url::CHANGE_FREQUENCY_DAILY],
            ['url' => '/faqs', 'priority' => 0.9, 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY],
            ['url' => '/privacy-policy', 'priority' => 0.9, 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create(self::BASE_URL.$page['url'])
                    ->setPriority($page['priority'])
                    ->setChangeFrequency($page['frequency'])
                    ->setLastModificationDate(Carbon::now())
            );
        }
    }

    private function addBlogPosts(Sitemap $sitemap): void
    {
        // Use chunk to handle large number of posts efficiently
        Blogs::published()
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        Url::create(self::BASE_URL."/blogs/{$post->slug}")
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.8)
                    );
                }
            });
    }

    private function addConverterPages(Sitemap $sitemap): void
    {
        $converterTools = [
            'word-to-pdf',
            'pdf-to-word',
            'merge-pdf',
            'split-pdf',
            'jpg-to-pdf',
            'pdf-to-jpg',
            'powerpoint-to-pdf',
            'pdf-to-powerpoint',
            'compress-pdf',
            'pdf-to-excel',
            'png-to-pdf',
            'pdf-to-png',
        ];

        foreach ($converterTools as $tool) {
            $sitemap->add(
                Url::create(self::BASE_URL."/tools/{$tool}")
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setLastModificationDate(Carbon::now())
            );
        }
    }
}
