<?php

echo shell_exec( 'cd /var/www/dev.sbdev && sudo git fetch --all && sudo git checkout dev && sudo git pull origin dev' );

echo '<h1>code ran</h1>';