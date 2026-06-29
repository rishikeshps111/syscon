<?php

namespace App\Support;

use InvalidArgumentException;

class SimpleQrCode
{
    private const SIZE = 21;
    private const DATA_CODEWORDS = 19;
    private const ERROR_CODEWORDS = 7;

    /**
     * Generate a QR Code Model 2 Version 1-L SVG for short ASCII text.
     */
    public static function svg(string $text, int $scale = 8, int $quietZone = 4): string
    {
        if ($text === '' || strlen($text) > 17 || ! mb_check_encoding($text, 'ASCII')) {
            throw new InvalidArgumentException('QR data must be 1 to 17 ASCII characters.');
        }

        $modules = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));
        $reserved = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));

        self::drawFunctionPatterns($modules, $reserved);
        self::drawCodewords($modules, $reserved, self::codewords($text));
        self::drawFormatBits($modules, $reserved);

        $size = (self::SIZE + ($quietZone * 2)) * $scale;
        $paths = [];

        for ($y = 0; $y < self::SIZE; $y++) {
            for ($x = 0; $x < self::SIZE; $x++) {
                if ($modules[$y][$x]) {
                    $paths[] = 'M' . (($x + $quietZone) * $scale) . ' ' . (($y + $quietZone) * $scale) . 'h' . $scale . 'v' . $scale . 'h-' . $scale . 'z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '" role="img" aria-label="QR code" shape-rendering="crispEdges">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . '<path fill="#000" d="' . implode('', $paths) . '"/>'
            . '</svg>';
    }

    private static function codewords(string $text): array
    {
        $bits = '0100' . str_pad(decbin(strlen($text)), 8, '0', STR_PAD_LEFT);

        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $bits .= str_repeat('0', min(4, (self::DATA_CODEWORDS * 8) - strlen($bits)));
        $bits .= str_repeat('0', (8 - (strlen($bits) % 8)) % 8);

        $data = [];
        foreach (str_split($bits, 8) as $byte) {
            $data[] = bindec($byte);
        }

        for ($pad = 0; count($data) < self::DATA_CODEWORDS; $pad++) {
            $data[] = $pad % 2 === 0 ? 0xEC : 0x11;
        }

        return array_merge($data, self::errorCorrection($data));
    }

    private static function errorCorrection(array $data): array
    {
        $generator = self::reedSolomonGenerator(self::ERROR_CODEWORDS);
        $remainder = array_fill(0, self::ERROR_CODEWORDS, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;

            foreach ($generator as $index => $coefficient) {
                $remainder[$index] ^= self::gfMultiply($coefficient, $factor);
            }
        }

        return $remainder;
    }

    private static function reedSolomonGenerator(int $degree): array
    {
        if ($degree === self::ERROR_CODEWORDS) {
            return [127, 122, 154, 164, 11, 68, 117];
        }

        $result = [1];

        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($result) + 1, 0);
            $root = 1;

            for ($j = 0; $j < $i; $j++) {
                $root = self::gfMultiply($root, 2);
            }

            foreach ($result as $index => $coefficient) {
                $next[$index] ^= self::gfMultiply($coefficient, $root);
                $next[$index + 1] ^= $coefficient;
            }

            $result = $next;
        }

        return array_slice($result, 0, $degree);
    }

    private static function gfMultiply(int $x, int $y): int
    {
        $result = 0;

        for ($i = 0; $i < 8; $i++) {
            if (($y & 1) !== 0) {
                $result ^= $x;
            }

            $carry = ($x & 0x80) !== 0;
            $x = ($x << 1) & 0xFF;

            if ($carry) {
                $x ^= 0x1D;
            }

            $y >>= 1;
        }

        return $result;
    }

    private static function drawFunctionPatterns(array &$modules, array &$reserved): void
    {
        self::drawFinder($modules, $reserved, 0, 0);
        self::drawFinder($modules, $reserved, self::SIZE - 7, 0);
        self::drawFinder($modules, $reserved, 0, self::SIZE - 7);

        for ($i = 8; $i < self::SIZE - 8; $i++) {
            self::setFunction($modules, $reserved, 6, $i, $i % 2 === 0);
            self::setFunction($modules, $reserved, $i, 6, $i % 2 === 0);
        }

        self::setFunction($modules, $reserved, 8, self::SIZE - 8, true);

        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $reserved[8][$i] = true;
                $reserved[$i][8] = true;
            }
        }

        for ($i = self::SIZE - 8; $i < self::SIZE; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
    }

    private static function drawFinder(array &$modules, array &$reserved, int $x, int $y): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $xx = $x + $dx;
                $yy = $y + $dy;

                if ($xx < 0 || $xx >= self::SIZE || $yy < 0 || $yy >= self::SIZE) {
                    continue;
                }

                $dark = ($dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6)
                    && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));

                self::setFunction($modules, $reserved, $xx, $yy, $dark);
            }
        }
    }

    private static function drawCodewords(array &$modules, array $reserved, array $codewords): void
    {
        $bits = '';
        foreach ($codewords as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $bitIndex = 0;
        $upward = true;

        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right--;
            }

            for ($vert = 0; $vert < self::SIZE; $vert++) {
                $y = $upward ? self::SIZE - 1 - $vert : $vert;

                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;

                    if ($reserved[$y][$x]) {
                        continue;
                    }

                    $dark = $bitIndex < strlen($bits) && $bits[$bitIndex] === '1';
                    if (($x + $y) % 2 === 0) {
                        $dark = ! $dark;
                    }

                    $modules[$y][$x] = $dark;
                    $bitIndex++;
                }
            }

            $upward = ! $upward;
        }
    }

    private static function drawFormatBits(array &$modules, array &$reserved): void
    {
        $bits = self::formatBits();
        $positions1 = [[8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8], [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]];
        $positions2 = [[self::SIZE - 1, 8], [self::SIZE - 2, 8], [self::SIZE - 3, 8], [self::SIZE - 4, 8], [self::SIZE - 5, 8], [self::SIZE - 6, 8], [self::SIZE - 7, 8], [self::SIZE - 8, 8], [8, self::SIZE - 7], [8, self::SIZE - 6], [8, self::SIZE - 5], [8, self::SIZE - 4], [8, self::SIZE - 3], [8, self::SIZE - 2], [8, self::SIZE - 1]];

        for ($i = 0; $i < 15; $i++) {
            [$x1, $y1] = $positions1[$i];
            [$x2, $y2] = $positions2[$i];
            self::setFunction($modules, $reserved, $x1, $y1, (($bits >> $i) & 1) !== 0);
            self::setFunction($modules, $reserved, $x2, $y2, (($bits >> $i) & 1) !== 0);
        }
    }

    private static function formatBits(): int
    {
        $data = 0b01000; // Error correction L (01), mask pattern 0 (000).
        $remainder = $data;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }

        return (($data << 10) | $remainder) ^ 0x5412;
    }

    private static function setFunction(array &$modules, array &$reserved, int $x, int $y, bool $dark): void
    {
        $modules[$y][$x] = $dark;
        $reserved[$y][$x] = true;
    }
}
