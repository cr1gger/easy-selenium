<?php
echo "chromedriver starts...\n\n";
$handle = popen('chromedriver --url-base=/wd/hub --port=4444 --no-sandbox', 'rb');
echo fread($handle, 2096);
echo "\n\nchromedriver started...";

