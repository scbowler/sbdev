<?php

echo shell_exec( 'cd /var/www/dev.sbdev && git pull origin dev' );

echo '<h1>code ran</h1>';