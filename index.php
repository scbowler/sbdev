<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scott's Test Page</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
    <h1>Hello my name is Scott</h1>

    <h3>Your IP Address: <?= $_SERVER['REMOTE_ADDR'] ?> </h3>

    <a class="btn btn-primary btn-lg" href="http://localhost/lfz-work/lf-main-site/" target="_blank">Goto Localhost LF</a>
    <a class="btn btn-primary btn-lg" href="http://localhost/lfz-work/lf-main-site/info-session" target="_blank">Goto Localhost LF Info Session</a>
    <hr>
    <a class="btn btn-primary btn-lg" href="http://dev.learningfuze.com" target="_blank">Goto Dev LF</a>
    <a class="btn btn-primary btn-lg" href="http://dev.learningfuze.com/info-session" target="_blank">Goto Dev LF Info Session</a>
    <hr>
    <a class="btn btn-primary btn-lg" href="http://learningfuze.com" target="_blank">Goto Live LF</a>
    <a class="btn btn-primary btn-lg" href="http://learningfuze.com/info-session" target="_blank">Goto Live LF Info Session</a>
</body>
</html>
