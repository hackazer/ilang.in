<?php
/**
 * =======================================================================================
 *                           GemFramework (c) GemPixel
 * ---------------------------------------------------------------------------------------
 *  This software is packaged with an exclusive framework as such distribution
 *  or modification of this framework is not allowed before prior consent from
 *  GemPixel. If you find that this framework is packaged in a software not distributed
 *  by GemPixel or authorized parties, you must not use this software and contact GemPixel
 *  at https://gempixel.com/contact to inform them of this misuse.
 * =======================================================================================
 *
 * @package GemPixel\Premium-URL-Shortener
 * @author GemPixel (https://gempixel.com)
 * @license https://gempixel.com/licenses
 * @link https://gempixel.com
 */

namespace Helpers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRFpdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QRGd {
    private const MIME_TYPES = [
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'svg' => 'image/svg+xml',
    ];

    private string $data;
    private int $size;
    private int $margin;
    private ?array $logo = null;
    private ?string $extension = null;
    private array $foregroundColor = [0, 0, 0];
    private array $backgroundColor = [255, 255, 255];

    /**
     * Generate QR Code.
     */
    public function __construct($data, $size = 200, $margin = 10){
        $this->data = (string) $data;
        $this->size = max(1, (int) $size);
        $this->margin = max(0, (int) $margin);

        return $this;
    }

    /**
     * Add a centered logo.
     */
    public function withLogo($path, $size = 50){
        $this->logo = [$path, max(1, (int) $size)];

        return $this;
    }

    /**
     * Select PNG, PDF, or SVG output. Unknown formats retain the PNG fallback.
     */
    public function format($format = 'png'){
        $format = strtolower((string) $format);
        $this->extension = isset(self::MIME_TYPES[$format]) ? $format : 'png';

        return $this;
    }

    /**
     * Set foreground and background colors from CSS rgb() or hex notation.
     */
    public function color($fg, $bg){
        $this->foregroundColor = $this->parseColor($fg, [0, 0, 0]);
        $this->backgroundColor = $this->parseColor($bg, [255, 255, 255]);

        return $this;
    }

    /**
     * Emit the selected output and terminate, matching the legacy download API.
     */
    public function string(){
        echo $this->render();
        exit;
    }

    /**
     * Return the selected file extension.
     */
    public function extension(){
        return $this->extension;
    }

    /**
     * Generate raw, file, or data URI output.
     */
    public function create($output = 'raw', $file = null){
        $contents = $this->render();

        if($output === 'file'){
            $this->writeFileAtomically($file, $contents);

            return true;
        }

        if($output === 'uri'){
            return 'data:'.$this->mimeType().';base64,'.base64_encode($contents);
        }

        header('Content-Type: '.$this->mimeType());
        echo $contents;
        exit;
    }

    private function render(): string{
        if($this->extension === null){
            throw new \LogicException('Select a QR format before rendering.');
        }

        return match($this->extension){
            'pdf' => $this->renderPdf(),
            'svg' => $this->renderSvg(),
            default => $this->renderPng(),
        };
    }

    private function renderPng(): string{
        [$options, $matrix] = $this->createMatrix(QRGdImagePNG::class, false);
        $options->scale = max(4, (int) ceil($this->size / $matrix->moduleCount));
        $options->moduleValues = $this->moduleValues(false);
        $options->bgColor = $this->backgroundColor;

        $png = (new QRGdImagePNG($options, $matrix))->dump();

        if(!is_string($png)){
            throw new \RuntimeException('Unable to render the QR code as PNG.');
        }

        $source = @imagecreatefromstring($png);

        if(!$source instanceof \GdImage){
            throw new \RuntimeException('Unable to decode the generated QR code image.');
        }

        $length = $this->size + ($this->margin * 2);
        $canvas = imagecreatetruecolor($length, $length);

        if(!$canvas instanceof \GdImage){
            throw new \RuntimeException('Unable to allocate the QR code image.');
        }

        $background = imagecolorallocate($canvas, ...$this->backgroundColor);
        imagefill($canvas, 0, 0, $background);
        imagecopyresized(
            $canvas,
            $source,
            $this->margin,
            $this->margin,
            0,
            0,
            $this->size,
            $this->size,
            imagesx($source),
            imagesy($source)
        );

        if($this->logo !== null){
            $this->overlayLogoOnImage($canvas);
        }

        ob_start();
        $written = imagepng($canvas);
        $contents = ob_get_clean();

        if($written !== true || !is_string($contents)){
            throw new \RuntimeException('Unable to encode the QR code image.');
        }

        return $contents;
    }

    private function renderSvg(): string{
        [$options, $matrix] = $this->createMatrix(QRMarkupSVG::class, false);
        $options->moduleValues = $this->moduleValues(true);
        $options->bgColor = $this->cssColor($this->backgroundColor);
        $options->svgAddXmlHeader = false;

        $svg = (new QRMarkupSVG($options, $matrix))->dump();

        if(!preg_match('~<svg[^>]*>(.*)</svg>~s', $svg, $match)){
            throw new \RuntimeException('Unable to render the QR code as SVG.');
        }

        $length = $this->size + ($this->margin * 2);
        $moduleCount = $matrix->moduleCount;
        $logo = $this->svgLogo($moduleCount);
        $background = htmlspecialchars($this->cssColor($this->backgroundColor), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d">'.PHP_EOL.
            '<rect width="100%%" height="100%%" fill="%2$s"/>'.PHP_EOL.
            '<svg x="%3$d" y="%3$d" width="%4$d" height="%4$d" viewBox="0 0 %5$d %5$d" preserveAspectRatio="xMidYMid meet">'.PHP_EOL.
            '%6$s%7$s</svg>'.PHP_EOL.'</svg>'.PHP_EOL,
            $length,
            $background,
            $this->margin,
            $this->size,
            $moduleCount,
            $match[1],
            $logo
        );
    }

    private function renderPdf(): string{
        [$options, $matrix] = $this->createMatrix(QRFpdf::class, true);
        $options->scale = max(1, (int) round($this->size / max(1, $matrix->moduleCount - ($options->quietzoneSize * 2))));
        $options->moduleValues = $this->moduleValues(false);
        $options->bgColor = $this->backgroundColor;
        $options->returnResource = true;
        $options->fpdfMeasureUnit = 'pt';

        $pdf = (new QRFpdf($options, $matrix))->dump();

        if(!$pdf instanceof \FPDF){
            throw new \RuntimeException('Unable to render the QR code as PDF.');
        }

        if($this->logo !== null){
            [$path, $logoSize] = $this->validatedLogo();
            $logoSize = min((float) $logoSize, $pdf->GetPageWidth() * 0.45);
            $pdf->Image(
                $path,
                ($pdf->GetPageWidth() - $logoSize) / 2,
                ($pdf->GetPageHeight() - $logoSize) / 2,
                $logoSize,
                $logoSize
            );
        }

        return $pdf->Output('S');
    }

    private function createMatrix(string $outputInterface, bool $includeMargin): array{
        $eccLevel = $this->logo === null ? EccLevel::M : EccLevel::H;
        $baseOptions = new QROptions([
            'eccLevel' => $eccLevel,
            'addQuietzone' => false,
            'outputBase64' => false,
        ]);
        $baseQr = new QRCode($baseOptions);
        $baseQr->addByteSegment($this->data);
        $baseMatrix = $baseQr->getQRMatrix();
        $moduleCount = $baseMatrix->moduleCount;
        $scale = max(1, (int) floor($this->size / max(1, $moduleCount)));
        $quietzoneSize = $includeMargin ? max(0, (int) round($this->margin / $scale)) : 0;

        $settings = [
            'eccLevel' => $eccLevel,
            'addQuietzone' => $includeMargin && $quietzoneSize > 0,
            'quietzoneSize' => $quietzoneSize,
            'outputBase64' => false,
            'outputInterface' => $outputInterface,
        ];

        if($this->logo !== null){
            $logoModules = max(1, (int) round(($this->logo[1] / $this->size) * $moduleCount));
            $logoModules = min($logoModules, max(1, (int) floor($moduleCount * 0.45)));
            $settings['addLogoSpace'] = true;
            $settings['logoSpaceWidth'] = $logoModules;
            $settings['logoSpaceHeight'] = $logoModules;
        }

        $options = new QROptions($settings);
        $qrCode = new QRCode($options);
        $qrCode->addByteSegment($this->data);

        return [$options, $qrCode->getQRMatrix()];
    }

    private function moduleValues(bool $css): array{
        $foreground = $css ? $this->cssColor($this->foregroundColor) : $this->foregroundColor;
        $background = $css ? $this->cssColor($this->backgroundColor) : $this->backgroundColor;
        $values = [];

        foreach(QROutputInterface::DEFAULT_MODULE_VALUES as $type => $dark){
            $values[$type] = $dark ? $foreground : $background;
        }

        return $values;
    }

    private function parseColor(mixed $value, array $fallback): array{
        if(!is_string($value)){
            return $fallback;
        }

        $value = trim($value);

        if(preg_match('/^#([\da-f]{3}|[\da-f]{6})$/i', $value, $match)){
            $hex = strlen($match[1]) === 3
                ? $match[1][0].$match[1][0].$match[1][1].$match[1][1].$match[1][2].$match[1][2]
                : $match[1];

            return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        }

        if(!preg_match('/^rgba?\(\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $value, $match)){
            return $fallback;
        }

        return [
            $this->colorChannel($match[1]),
            $this->colorChannel($match[2]),
            $this->colorChannel($match[3]),
        ];
    }

    private function colorChannel(string $value): int{
        return max(0, min(255, (int) round((float) $value)));
    }

    private function cssColor(array $color): string{
        return sprintf('rgb(%d,%d,%d)', ...$color);
    }

    private function overlayLogoOnImage(\GdImage $canvas): void{
        [$path, $logoSize] = $this->validatedLogo();
        $contents = file_get_contents($path);
        $logo = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if(!$logo instanceof \GdImage){
            throw new \InvalidArgumentException('The QR logo must be a supported raster image.');
        }

        $logoSize = min($logoSize, (int) floor(min(imagesx($canvas), imagesy($canvas)) * 0.45));
        $ratio = min($logoSize / imagesx($logo), $logoSize / imagesy($logo));
        $width = max(1, (int) round(imagesx($logo) * $ratio));
        $height = max(1, (int) round(imagesy($logo) * $ratio));
        $x = (int) floor((imagesx($canvas) - $width) / 2);
        $y = (int) floor((imagesy($canvas) - $height) / 2);

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $logo, $x, $y, 0, 0, $width, $height, imagesx($logo), imagesy($logo));
    }

    private function svgLogo(int $moduleCount): string{
        if($this->logo === null){
            return '';
        }

        [$path, $logoSize] = $this->validatedLogo();
        $contents = file_get_contents($path);

        if(!is_string($contents)){
            throw new \InvalidArgumentException('Unable to read the QR logo.');
        }

        $image = @imagecreatefromstring($contents);

        if(!$image instanceof \GdImage){
            throw new \InvalidArgumentException('The QR logo must be a supported raster image.');
        }

        $imageInfo = getimagesizefromstring($contents);
        $mime = is_array($imageInfo) && isset($imageInfo['mime']) ? $imageInfo['mime'] : 'image/png';
        $logoModules = min(($logoSize / $this->size) * $moduleCount, $moduleCount * 0.45);
        $position = ($moduleCount - $logoModules) / 2;
        $uri = 'data:'.$mime.';base64,'.base64_encode($contents);

        return sprintf(
            '<image x="%1$.4F" y="%1$.4F" width="%2$.4F" height="%2$.4F" href="%3$s" preserveAspectRatio="xMidYMid meet"/>'.PHP_EOL,
            $position,
            $logoModules,
            htmlspecialchars($uri, ENT_QUOTES | ENT_XML1, 'UTF-8')
        );
    }

    private function validatedLogo(): array{
        [$path, $size] = $this->logo;

        if(!is_string($path) || $path === '' || str_contains($path, "\0") || !is_file($path) || !is_readable($path)){
            throw new \InvalidArgumentException('The QR logo path must reference a readable file.');
        }

        return [$path, $size];
    }

    private function writeFileAtomically(mixed $file, string $contents): void{
        if(!is_string($file) || $file === '' || str_contains($file, "\0")){
            throw new \InvalidArgumentException('A valid QR output file path is required.');
        }

        $directory = dirname($file);

        if(!is_dir($directory) || !is_writable($directory) || is_dir($file)){
            throw new \InvalidArgumentException('The QR output directory must exist and be writable.');
        }

        $temporary = tempnam($directory, '.qr-');

        if(!is_string($temporary)){
            throw new \RuntimeException('Unable to create a temporary QR output file.');
        }

        try {
            $bytes = file_put_contents($temporary, $contents, LOCK_EX);

            if($bytes !== strlen($contents)){
                throw new \RuntimeException('Unable to write the complete QR output file.');
            }

            if(!rename($temporary, $file)){
                throw new \RuntimeException('Unable to finalize the QR output file.');
            }
        } finally {
            if(is_file($temporary)){
                unlink($temporary);
            }
        }
    }

    private function mimeType(): string{
        return self::MIME_TYPES[$this->extension ?? 'png'];
    }
}
