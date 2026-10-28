<?php
require_once('./tcpdf_include.php');

$fontFile = '../fonts/arial-unicode-ms.ttf'; // Path to your TTF file
$fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 96);

echo "Font added: " . $fontName;
