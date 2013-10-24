<?php
define('PHARSEC_DIR', dirname(__DIR__).'/src');

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'PHARSEC\\')) {
        $class = str_replace('\\', '/', $class);
        $file = sprintf("%s/%s.php", PHARSEC_DIR, $class);
        printf("Auto loading class '%s' (%s).\n", $class, $file);

        require $file;
    }
});
