<?php

namespace App\Console\Commands;

use App\Models\LoginCarouselImage;
use Illuminate\Console\Command;

class GenerateLoginCarouselThumbnails extends Command
{
    protected $signature = 'login-carousel:thumbnails';

    protected $description = 'Generate admin preview thumbnails for existing login carousel images';

    public function handle(): int
    {
        if (! LoginCarouselImage::tableExists()) {
            $this->error('Table login_carousel_images does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $images = LoginCarouselImage::query()->select(['id', 'image_path'])->get();
        $bar = $this->output->createProgressBar($images->count());
        $bar->start();

        foreach ($images as $image) {
            $image->ensureThumbnail();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done. Generated thumbnails for '.$images->count().' image(s).');

        return self::SUCCESS;
    }
}
