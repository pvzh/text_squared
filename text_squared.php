<?php

declare(strict_types=1);

if (!function_exists('imagettftext')) {
    echo 'Для создания изображений необходимо включить PHP-расширение "GD"', PHP_EOL;
    exit;
}

$size = 460; //px

// $text = 'Hello_Wor1d!';
$text = 'Каждый разработчик, имеющий сколько-нибудь значительный опыт работы, знает, что предыдущий беспорядок замедляет его работу. Но при этом все разработчики под давлением творят беспорядок в своем коде для соблюдения графика. Короче, у них нет времени, чтобы работать быстро!';
$text .= '...Единственный способ работать быстро — заключается в том, чтобы постоянно поддерживать чистоту в коде.';

// Моноширинный шрифт
// https://www.fonts-online.ru/font/MODERN-TYPEWRITER-RUS-LAT
$fontfile = './fonts/18832.otf';

/** Убрать лишние пробелы, запятые, точки и т.п. */
function filter_chars(string $str): string
{
    return trim(preg_replace('/[^\w-]+/u', ' ', $str));
}

/** Делители, от 3 до (1/3 числа) */
function calc_dividers(int $num): array
{
    $result = [];
    $max = ceil($num / 3);
    for ($i = 3; $i <= $max; $i++) {
        $remainder = $num % $i;
        if ($remainder === 0) {
            $result[] = $i;
        }
        echo "При делении {$num} на {$i} остаток {$remainder}", PHP_EOL;
    }
    return $result;
}

/** Наибольший размер шрифта, при котором символ умещается в прямоугольник */
function calc_char_size(float $width, float $height, string $fontfile): array
{
    $pt = 5;
    $angle = 0;
    // $format = "(%4d,%4d) #### (%4d,%4d)";
    while (true) {
        $pt++;
        $box = imagettfbbox($pt, $angle, $fontfile, 'ы');
        // echo PHP_EOL, $pt, 'pt', PHP_EOL;
        // echo sprintf($format, $box[6], $box[7], $box[4], $box[5]), PHP_EOL;
        // echo sprintf($format, $box[0], $box[1], $box[2], $box[3]), PHP_EOL;

        $w = $box[2] - $box[0];
        $h = $box[1] - $box[7];
        if ($w > $width or $h > $height) break;
    }
    return [$w, $h, $pt];
}

function make_image(int $size, string $text, int $cols, string $fontfile): void
{
    // размеры клетки
    $rows = mb_strlen($text) / $cols;
    $sx = intdiv($size, $cols);
    $sy = intdiv($size, $rows);
    echo "Клетка {$sx}x{$sy}", PHP_EOL;
    if (max($sx, $sy) / min($sx, $sy) > 3) {
        echo 'Большое отличие ширины и высоты клетки', PHP_EOL;
        return;
    }

    // размеры символа и смещения внутри клетки
    $scale = 0.67;
    list($cx, $cy, $pt) = calc_char_size($sx * $scale, $sy * $scale, $fontfile);
    if ($cx > $sx or $cy > $sy) {
        echo "Символ {$cx}x{$cy}, {$pt}pt - не умещается в клетку", PHP_EOL;
        return;
    }

    // смещение символа внутри клетки
    $dx = intdiv($sx - $cx, 2);
    $dy = intdiv($sy - $cy, 2);
    echo "Символ {$cx}x{$cy}, {$pt}pt, смещение {$dx}x{$dy}", PHP_EOL;

    $im = imagecreatetruecolor($size, $size) or exit('Невозможно создать изображение');
    $fg = imagecolorallocate($im, 0x00, 0xFF, 0xFF);
    $bg = imagecolorallocate($im, 0x33, 0x33, 0x33);
    imagefilledrectangle($im, 0, 0, $size, $size, $bg);

    for ($j = 0; $j < $rows; $j++) {
        $y = ($j + 1) * $sy - $dy; // нижний угол
        for ($i = 0; $i < $cols; $i++) {
            $x = $i * $sx + $dx;
            $char = mb_substr($text, $i + $j * $cols, 1);
            imagettftext($im, $pt, 0, $x, $y, $fg, $fontfile, $char);
        }
    }

    // сглаживание
    imagefilter($im, IMG_FILTER_SMOOTH, 1);

    $filename = md5($text . $cols . $size) . '.png';
    echo "{$rows} строк, {$cols} столбцов: {$filename}", PHP_EOL;
    imagepng($im, __DIR__ . "/images/{$filename}");
    imagedestroy($im);
}

if (!file_exists($fontfile)) {
    echo 'Файл шрифта не найден', PHP_EOL;
    exit;
}

$text = filter_chars($text);
echo $text, PHP_EOL;
echo 'Длина текста: ', mb_strlen($text), PHP_EOL;

$divs = calc_dividers(mb_strlen($text));
if (empty($divs)) {
    echo 'Текст не делится на строки без остатка', PHP_EOL;
    exit;
}

// удалить созданные ранее изображения
array_map('unlink', glob(__DIR__ . '/images/*.png'));

foreach ($divs as $div) {
    make_image($size, $text, $div, $fontfile);
}
