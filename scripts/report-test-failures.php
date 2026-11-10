<?php

// Keep CI failures visible in check annotations, including when log access is restricted.
$path = $argv[1] ?? '';
if (!is_file($path)) {
    echo "::warning::PHPUnit did not produce a test report; inspect the test step for setup errors.\n";
    exit(0);
}

libxml_use_internal_errors(true);
$report = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NONET);
if ($report === false) {
    echo "::warning::Could not read the PHPUnit test report.\n";
    exit(0);
}

$failures = $report->xpath('//testcase[failure or error]') ?: [];
foreach (array_slice($failures, 0, 10) as $test) {
    $detail = isset($test->failure) ? (string) $test->failure : (string) $test->error;
    $message = $test['class'].'::'.$test['name']."\n".substr($detail, 0, 3500);
    $escaped = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $message);
    echo "::error title=PHPUnit test failed::{$escaped}\n";
}

echo count($failures)." failing test(s) in the PHPUnit report.\n";
