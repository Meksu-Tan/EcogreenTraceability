<?php

$content = file_get_contents(__DIR__.'/Modules/ts-stock/app/Repositories/StockRepository.php');
// The query starts at around line 481 and ends at 636.
$lines = explode("\n", $content);
$query = implode("\n", array_slice($lines, 481 - 1, 636 - 481 + 1));
$matches = [];
$linesWithQuestionMarks = explode("\n", $query);
foreach ($linesWithQuestionMarks as $i => $line) {
    if (strpos($line, '?') !== false) {
        echo ($i + 481).': '.trim($line)."\n";
    }
}
