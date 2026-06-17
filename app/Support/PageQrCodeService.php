<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageQrCode as PageQrCodeModel;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

class PageQrCodeService
{
    private const DIRECTORY = 'page-qr-codes';

    public function regenerate(Page $page): ?PageQrCodeModel
    {
        if (! $page->exists || blank($page->publicUrl())) {
            return null;
        }

        $url = (string) $page->publicUrl();
        $pngPath = self::pngPath($page);
        $svgPath = self::svgPath($page);

        Storage::disk('public')->put($pngPath, $this->render($url, QROutputInterface::GDIMAGE_PNG));
        Storage::disk('public')->put($svgPath, $this->render($url, QROutputInterface::MARKUP_SVG));

        return PageQrCodeModel::query()->updateOrCreate(
            ['page_id' => $page->getKey()],
            [
                'url' => $url,
                'png_path' => $pngPath,
                'svg_path' => $svgPath,
                'generated_at' => now(),
            ],
        );
    }

    public function delete(Page $page): void
    {
        Storage::disk('public')->delete([
            self::pngPath($page),
            self::svgPath($page),
        ]);
    }

    public static function pngPath(Page $page): string
    {
        return self::path($page, 'png');
    }

    public static function svgPath(Page $page): string
    {
        return self::path($page, 'svg');
    }

    private static function path(Page $page, string $extension): string
    {
        return self::DIRECTORY.'/page-'.$page->getKey().'.'.$extension;
    }

    private function render(string $url, string $outputType): string
    {
        return (string) (new QRCode(new QROptions([
            'eccLevel' => EccLevel::M,
            'outputBase64' => false,
            'outputType' => $outputType,
            'scale' => 10,
        ])))->render($url);
    }
}
