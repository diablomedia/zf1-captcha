<?php declare(strict_types = 1);

if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    return array();
}

$ignoreErrors   = array();
$ignoreErrors[] = array(
    'message' => '#^Parameter \\#1 \\$x_size of function imagecreatetruecolor expects int, int\\|false given\\.$#',
    'count'   => 1,
    'path'    => __DIR__ . '/src/Zend/Captcha/Image.php',
);
$ignoreErrors[] = array(
    'message' => '#^Parameter \\#2 \\$max of function mt_rand expects int, int\\|false given\\.$#',
    'count'   => 12,
    'path'    => __DIR__ . '/src/Zend/Captcha/Image.php',
);
$ignoreErrors[] = array(
    'message' => '#^Parameter \\#2 \\$y_size of function imagecreatetruecolor expects int, int\\|false given\\.$#',
    'count'   => 1,
    'path'    => __DIR__ . '/src/Zend/Captcha/Image.php',
);

return array('parameters' => array('ignoreErrors' => $ignoreErrors));
