<?php

namespace App\Services;

/**
 * 100% ISO/IEC 18004 Compliant QR Code Generator.
 *
 * Implements standard QR Code Model 2 with:
 * - Galois Field GF(256) arithmetic
 * - Reed-Solomon error correction with block interleaving
 * - Error correction levels (Low, Medium, Quartile, High)
 * - Automatic Error Correction boosting when capacity allows
 * - Standard Finder, Alignment, Timing, and Dark module placement
 * - ISO BCH(15, 5) Format Information encoding with exact coordinate mapping
 * - Full 4-rule (N1..N4) penalty evaluation for optimal mask selection
 * - Crisp SVG rendering with standard 4-module quiet zone
 */
class QrCodeService
{
    public const ECC_LOW = 0;

    public const ECC_MEDIUM = 1;

    public const ECC_QUARTILE = 2;

    public const ECC_HIGH = 3;

    private static array $exp = [];

    private static array $log = [];

    private static bool $tablesInitialized = false;

    // Error correction codewords per block: [level => [v0, v1, v2, ... v40]]
    private const ECC_CODEWORDS_PER_BLOCK = [
        0 => [-1,  7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        1 => [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],
        2 => [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        3 => [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
    ];

    // Number of error correction blocks: [level => [v0, v1, v2, ... v40]]
    private const NUM_ERROR_CORRECTION_BLOCKS = [
        0 => [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4,  4,  4,  4,  4,  6,  6,  6,  6,  7,  8,  8,  9,  9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],
        1 => [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5,  5,  8,  9,  9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],
        2 => [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8,  8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],
        3 => [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],
    ];

    private static function initTables(): void
    {
        if (self::$tablesInitialized) {
            return;
        }

        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $val;
            self::$log[$val] = $i;
            $val <<= 1;
            if ($val & 0x100) {
                $val ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }

        self::$tablesInitialized = true;
    }

    private static function rsMultiply(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    private static function rsComputeDivisor(int $degree): array
    {
        $result = array_fill(0, $degree - 1, 0);
        $result[] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::rsMultiply($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::rsMultiply($root, 0x02);
        }

        return $result;
    }

    private static function rsComputeRemainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::rsMultiply($coef, $factor);
            }
        }

        return $result;
    }

    private static function getNumRawDataModules(int $ver): int
    {
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = intdiv($ver, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($ver >= 7) {
                $result -= 36;
            }
        }

        return $result;
    }

    private static function getNumDataCodewords(int $ver, int $ecl): int
    {
        return intdiv(self::getNumRawDataModules($ver), 8)
            - self::ECC_CODEWORDS_PER_BLOCK[$ecl][$ver]
            * self::NUM_ERROR_CORRECTION_BLOCKS[$ecl][$ver];
    }

    /**
     * Generate an ISO/IEC 18004 compliant SVG QR Code.
     *
     * @param  string  $data  Content payload (e.g. Wi-Fi connection string)
     * @param  int  $width  SVG display width/height in px
     * @param  string  $color  Dark module hex color
     * @param  int  $ecl  Requested base error correction level (default Medium)
     * @param  bool  $boostEcl  Whether to boost error correction if data fits in the same version
     * @param  int  $forcedMask  Forced mask number (0..7), or -1 for automatic selection
     */
    public static function generateSvg(
        string $data,
        int $width = 240,
        string $color = '#2C221E',
        int $ecl = self::ECC_MEDIUM,
        bool $boostEcl = true,
        int $forcedMask = -1
    ): string {
        self::initTables();

        $bytes = array_values(unpack('C*', $data));
        $dataLen = count($bytes);

        // Find smallest suitable QR version (1 to 40)
        $version = 0;
        for ($v = 1; $v <= 40; $v++) {
            $charCountBits = ($v <= 9) ? 8 : 16;
            $dataUsedBits = 4 + $charCountBits + ($dataLen * 8);
            $capacityBits = self::getNumDataCodewords($v, $ecl) * 8;
            if ($dataUsedBits <= $capacityBits) {
                $version = $v;
                break;
            }
        }

        if ($version === 0) {
            $version = 40;
        }

        $charCountBits = ($version <= 9) ? 8 : 16;
        $dataUsedBits = 4 + $charCountBits + ($dataLen * 8);

        // Boost error correction level while data still fits in this version
        if ($boostEcl) {
            foreach ([self::ECC_MEDIUM, self::ECC_QUARTILE, self::ECC_HIGH] as $newEcl) {
                if ($dataUsedBits <= self::getNumDataCodewords($version, $newEcl) * 8) {
                    $ecl = $newEcl;
                }
            }
        }

        $dataCapacityBits = self::getNumDataCodewords($version, $ecl) * 8;

        // Construct bit buffer in Byte mode (0100)
        $bits = [];
        $appendBits = function (int $val, int $len) use (&$bits) {
            for ($i = $len - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };

        // Mode indicator 0100
        $appendBits(0b0100, 4);
        // Character count indicator
        $appendBits($dataLen, $charCountBits);
        // Data bytes
        foreach ($bytes as $b) {
            $appendBits($b, 8);
        }

        // Terminator up to 4 zero bits
        $termLen = min(4, $dataCapacityBits - count($bits));
        for ($i = 0; $i < $termLen; $i++) {
            $bits[] = 0;
        }

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Pad alternating codewords 0xEC and 0x11
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $dataCapacityBits) {
            $appendBits($padBytes[$padIdx % 2], 8);
            $padIdx++;
        }

        // Convert bits to data codewords
        $dataCodewords = [];
        $totalBitsCount = count($bits);
        for ($i = 0; $i < $totalBitsCount; $i += 8) {
            $byte = 0;
            for ($b = 0; $b < 8; $b++) {
                $byte = ($byte << 1) | $bits[$i + $b];
            }
            $dataCodewords[] = $byte;
        }

        // Split into error correction blocks & compute Reed-Solomon codewords
        $numBlocks = self::NUM_ERROR_CORRECTION_BLOCKS[$ecl][$version];
        $blockEccLen = self::ECC_CODEWORDS_PER_BLOCK[$ecl][$version];
        $rawCodewords = intdiv(self::getNumRawDataModules($version), 8);
        $numShortBlocks = $numBlocks - ($rawCodewords % $numBlocks);
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);

        $dataBlocks = [];
        $eccBlocks = [];
        $rsDiv = self::rsComputeDivisor($blockEccLen);
        $k = 0;

        for ($i = 0; $i < $numBlocks; $i++) {
            $datLen = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($dataCodewords, $k, $datLen);
            $k += $datLen;
            $ecc = self::rsComputeRemainder($dat, $rsDiv);
            $dataBlocks[] = $dat;
            $eccBlocks[] = $ecc;
        }

        // Interleave data codewords across all blocks
        $allCodewords = [];
        $maxDataLen = $shortBlockLen - $blockEccLen + 1;
        for ($col = 0; $col < $maxDataLen; $col++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                if ($col < count($dataBlocks[$b])) {
                    $allCodewords[] = $dataBlocks[$b][$col];
                }
            }
        }

        // Interleave error correction codewords across all blocks
        for ($col = 0; $col < $blockEccLen; $col++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $allCodewords[] = $eccBlocks[$b][$col];
            }
        }

        // Matrix dimension
        $size = 17 + 4 * $version;
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $isFunction = array_fill(0, $size, array_fill(0, $size, false));

        $setFunction = function (int $x, int $y, bool $isDark) use (&$modules, &$isFunction, $size) {
            if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                $modules[$y][$x] = $isDark;
                $isFunction[$y][$x] = true;
            }
        };

        // 1. Draw 3 Finder Patterns with Separators (9x9 around center)
        $drawFinder = function (int $cx, int $cy) use ($setFunction) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $dist = max(abs($dx), abs($dy));
                    $isDark = ($dist !== 2 && $dist !== 4);
                    $setFunction($cx + $dx, $cy + $dy, $isDark);
                }
            }
        };

        $drawFinder(3, 3);
        $drawFinder($size - 4, 3);
        $drawFinder(3, $size - 4);

        // 2. Draw Alignment Patterns
        if ($version >= 2) {
            $numAlign = intdiv($version, 7) + 2;
            $step = intdiv($version * 8 + $numAlign * 3 + 5, $numAlign * 4 - 4) * 2;
            $alignPatPos = [];
            $pos = $size - 7;
            for ($i = 0; $i < $numAlign - 1; $i++) {
                array_unshift($alignPatPos, $pos);
                $pos -= $step;
            }
            array_unshift($alignPatPos, 6);

            $lastIdx = count($alignPatPos) - 1;
            foreach ($alignPatPos as $ix => $ax) {
                foreach ($alignPatPos as $iy => $ay) {
                    if (($ix === 0 && $iy === 0) || ($ix === 0 && $iy === $lastIdx) || ($ix === $lastIdx && $iy === 0)) {
                        continue;
                    }
                    for ($dy = -2; $dy <= 2; $dy++) {
                        for ($dx = -2; $dx <= 2; $dx++) {
                            $dist = max(abs($dx), abs($dy));
                            $setFunction($ax + $dx, $ay + $dy, $dist !== 1);
                        }
                    }
                }
            }
        }

        // 3. Timing Patterns
        for ($i = 0; $i < $size; $i++) {
            if (! $isFunction[6][$i]) {
                $setFunction($i, 6, $i % 2 === 0);
            }
            if (! $isFunction[$i][6]) {
                $setFunction(6, $i, $i % 2 === 0);
            }
        }

        // 4. Dark Module
        $setFunction(8, $size - 8, true);

        // 5. Reserve Format Bits
        for ($i = 0; $i <= 8; $i++) {
            if (! $isFunction[$i][8]) {
                $isFunction[$i][8] = true;
            }
            if (! $isFunction[8][$i]) {
                $isFunction[8][$i] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $isFunction[8][$size - 1 - $i] = true;
            $isFunction[$size - 1 - $i][8] = true;
        }

        // 6. Reserve and draw Version Information (Versions >= 7)
        if ($version >= 7) {
            $rem = $version;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
            }
            $vBits = ($version << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($vBits >> $i) & 1) === 1;
                $a = $size - 11 + ($i % 3);
                $b = intdiv($i, 3);
                $setFunction($a, $b, $bit);
                $setFunction($b, $a, $bit);
            }
        }

        // 7. Place Codeword Bits in 2-column Zig-Zag pattern
        $bitIdx = 0;
        $totalCodewordBits = count($allCodewords) * 8;
        for ($right = $size - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? ($size - 1 - $vert) : $vert;

                    if (! $isFunction[$y][$x] && $bitIdx < $totalCodewordBits) {
                        $byte = $allCodewords[$bitIdx >> 3];
                        $bit = ($byte >> (7 - ($bitIdx & 7))) & 1;
                        $modules[$y][$x] = ($bit === 1);
                        $bitIdx++;
                    }
                }
            }
        }

        // 8. Masking & Format Information
        $eclFormatBits = [
            self::ECC_LOW => 1,
            self::ECC_MEDIUM => 0,
            self::ECC_QUARTILE => 3,
            self::ECC_HIGH => 2,
        ];

        $drawFormatBits = function (int $mask) use (&$modules, $eclFormatBits, $ecl, $size) {
            $data = ($eclFormatBits[$ecl] << 3) | $mask;
            $rem = $data;
            for ($i = 0; $i < 10; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
            }
            $fBits = (($data << 10) | $rem) ^ 0x5412;

            $getBit = fn (int $n) => (($fBits >> $n) & 1) === 1;

            // First copy (around top-left finder)
            for ($i = 0; $i < 6; $i++) {
                $modules[$i][8] = $getBit($i);
            }
            $modules[7][8] = $getBit(6);
            $modules[8][8] = $getBit(7);
            $modules[8][7] = $getBit(8);
            for ($i = 9; $i < 15; $i++) {
                $modules[8][14 - $i] = $getBit($i);
            }

            // Second copy (around top-right & bottom-left finders)
            for ($i = 0; $i < 8; $i++) {
                $modules[8][$size - 1 - $i] = $getBit($i);
            }
            for ($i = 8; $i < 15; $i++) {
                $modules[$size - 15 + $i][8] = $getBit($i);
            }
            $modules[$size - 8][8] = true;
        };

        $maskPatterns = [
            0 => fn (int $x, int $y) => ($x + $y) % 2 === 0,
            1 => fn (int $x, int $y) => $y % 2 === 0,
            2 => fn (int $x, int $y) => $x % 3 === 0,
            3 => fn (int $x, int $y) => ($x + $y) % 3 === 0,
            4 => fn (int $x, int $y) => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
            5 => fn (int $x, int $y) => (($x * $y) % 2 + ($x * $y) % 3) === 0,
            6 => fn (int $x, int $y) => ((($x * $y) % 2 + ($x * $y) % 3) % 2) === 0,
            7 => fn (int $x, int $y) => ((($x + $y) % 2 + ($x * $y) % 3) % 2) === 0,
        ];

        $applyMask = function (int $mask) use (&$modules, $isFunction, $size, $maskPatterns) {
            $fn = $maskPatterns[$mask];
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    if (! $isFunction[$y][$x] && $fn($x, $y)) {
                        $modules[$y][$x] = ! $modules[$y][$x];
                    }
                }
            }
        };

        // Full ISO 18004 penalty calculation (Rules N1, N2, N3, N4)
        $finderPenaltyCountPatterns = function (array $history) use ($size): int {
            $n = $history[1];
            if ($n <= 0 || $n > $size * 3) {
                return 0;
            }
            $core = ($history[2] === $n && $history[4] === $n && $history[5] === $n && $history[3] === $n * 3);
            $count = 0;
            if ($core && $history[0] >= $n * 4 && $history[6] >= $n) {
                $count++;
            }
            if ($core && $history[6] >= $n * 4 && $history[0] >= $n) {
                $count++;
            }

            return $count;
        };

        $finderPenaltyAddHistory = function (int $runLength, array &$history) use ($size): void {
            if ($history[0] === 0) {
                $runLength += $size;
            }
            array_unshift($history, $runLength);
            if (count($history) > 7) {
                array_pop($history);
            }
        };

        $finderPenaltyTerminateAndCount = function (
            bool $currentRunColor,
            int $currentRunLength,
            array &$history
        ) use ($size, $finderPenaltyAddHistory, $finderPenaltyCountPatterns): int {
            if ($currentRunColor) {
                $finderPenaltyAddHistory($currentRunLength, $history);
                $currentRunLength = 0;
            }
            $currentRunLength += $size;
            $finderPenaltyAddHistory($currentRunLength, $history);

            return $finderPenaltyCountPatterns($history);
        };

        $getPenaltyScore = function () use (
            &$modules,
            $size,
            $finderPenaltyCountPatterns,
            $finderPenaltyAddHistory,
            $finderPenaltyTerminateAndCount
        ): int {
            $score = 0;

            // Rule 1 & Rule 3: Rows
            for ($y = 0; $y < $size; $y++) {
                $runColor = false;
                $runLength = 0;
                $history = array_fill(0, 7, 0);
                for ($x = 0; $x < $size; $x++) {
                    if ($modules[$y][$x] === $runColor) {
                        $runLength++;
                        if ($runLength === 5) {
                            $score += 3;
                        } elseif ($runLength > 5) {
                            $score += 1;
                        }
                    } else {
                        $finderPenaltyAddHistory($runLength, $history);
                        if (! $runColor) {
                            $score += $finderPenaltyCountPatterns($history) * 40;
                        }
                        $runColor = $modules[$y][$x];
                        $runLength = 1;
                    }
                }
                $score += $finderPenaltyTerminateAndCount($runColor, $runLength, $history) * 40;
            }

            // Rule 1 & Rule 3: Columns
            for ($x = 0; $x < $size; $x++) {
                $runColor = false;
                $runLength = 0;
                $history = array_fill(0, 7, 0);
                for ($y = 0; $y < $size; $y++) {
                    if ($modules[$y][$x] === $runColor) {
                        $runLength++;
                        if ($runLength === 5) {
                            $score += 3;
                        } elseif ($runLength > 5) {
                            $score += 1;
                        }
                    } else {
                        $finderPenaltyAddHistory($runLength, $history);
                        if (! $runColor) {
                            $score += $finderPenaltyCountPatterns($history) * 40;
                        }
                        $runColor = $modules[$y][$x];
                        $runLength = 1;
                    }
                }
                $score += $finderPenaltyTerminateAndCount($runColor, $runLength, $history) * 40;
            }

            // Rule 2: 2x2 blocks of same color
            for ($y = 0; $y < $size - 1; $y++) {
                for ($x = 0; $x < $size - 1; $x++) {
                    $c = $modules[$y][$x];
                    if ($c === $modules[$y][$x + 1] && $c === $modules[$y + 1][$x] && $c === $modules[$y + 1][$x + 1]) {
                        $score += 3;
                    }
                }
            }

            // Rule 4: Balance of dark / light modules
            $dark = 0;
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    if ($modules[$y][$x]) {
                        $dark++;
                    }
                }
            }
            $total = $size * $size;
            $k = intdiv(abs($dark * 20 - $total * 10) + $total - 1, $total) - 1;
            $score += $k * 10;

            return $score;
        };

        // Select mask with minimum penalty score
        if ($forcedMask >= 0 && $forcedMask <= 7) {
            $bestMask = $forcedMask;
        } else {
            $bestMask = 0;
            $minPenalty = PHP_INT_MAX;
            for ($m = 0; $m < 8; $m++) {
                $applyMask($m);
                $drawFormatBits($m);
                $penalty = $getPenaltyScore();
                if ($penalty < $minPenalty) {
                    $minPenalty = $penalty;
                    $bestMask = $m;
                }
                $applyMask($m); // Revert mask (XOR property)
            }
        }

        $applyMask($bestMask);
        $drawFormatBits($bestMask);

        // 9. Render standard SVG with 4-module quiet zone
        $quietZone = 4;
        $totalViewSize = $size + $quietZone * 2;
        $pathD = '';

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($modules[$y][$x]) {
                    $px = $x + $quietZone;
                    $py = $y + $quietZone;
                    $pathD .= "M{$px},{$py}h1v1h-1z ";
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$totalViewSize.' '.$totalViewSize.'" width="'.$width.'" height="'.$width.'" shape-rendering="crispEdges" aria-label="Wi-Fi QR Code">'
            .'<rect width="100%" height="100%" fill="#ffffff"/>'
            .'<path d="'.rtrim($pathD).'" fill="'.htmlspecialchars($color, ENT_QUOTES).'"/>'
            .'</svg>';
    }
}
