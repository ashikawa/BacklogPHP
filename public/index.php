<?php
// todo state check
require_once '../vendor/autoload.php';

function escape($str)
{
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

session_start();

if (getenv('BACKLOG_CLIENT_ID')) {
    $config = array(
        'baseUri'      => getenv('BACKLOG_BASE_URI'),
        'clientId'     => getenv('BACKLOG_CLIENT_ID'),
        'clientSecret' => getenv('BACKLOG_CLIENT_SECRET'),
    );

    $oauth = new Backlog\OAuth2\Consumer($config);

    if (isset($_GET['callback'])) {
        $oauth->requestAccessToken();

        header("Location: /");
        exit(0);
    }

    if (isset($_GET['logout'])) {
        $oauth->removeAccessToken();
    }

    if ($accessToken = $oauth->getAccessToken()) {
        $space = $oauth->getClient()
            ->space->get()
            ->getBody();

        $user = $oauth->getClient()
            ->users->myself->get()
            ->getBody();
    } else {
        $authorizeUrl = $oauth->getAuthorizeUrl();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backlog OAuth Sample</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <style>
    body {
        padding-top: 50px;
    }
    .starter-template {
        padding: 40px 15px;
        text-align: center;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <span class="navbar-brand">Backlog OAuth</span>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
<?php if ($accessToken) : ?>
                    <li ><a href="/?logout">Logout</a></li>
<?php endif; ?>
                </ul>
            </div><!--/.nav-collapse -->
        </div>
      </div>
    </nav>
    <div class="container">
<?php if (!getenv('BACKLOG_CLIENT_ID')) : ?>
        <div class="starter-template">
            <h1>Configuration.</h1>
            <h2>First.</h2>
            <p>
                Go to <a href="https://www.backlog.jp/developer/applications/" target="_blank">Backlog Developers Site</a> and create new application.
                <dl>
                  <dt>Redirect URL</dt>
                  <dd>http://xxx.example.com/?callback=1</dd>
                </dl>
            </p>
            <h2>Second.</h2>
            <p>
                Set ENV.
                <pre>BACKLOG_SPACE=xxxxx
BACKLOG_CLIENT_ID=xxxxx
BACKLOG_CLIENT_SECRET=xxxxx</pre>
            </p>
        </div>
<?php else: ?>
        <div class="starter-template">
<?php   if (!$accessToken) : ?>
            <p><a href="<?php echo escape($authorizeUrl); ?>" class="btn btn-primary">Login</a></p>
<?php   else: ?>
            <h2>Your Space Info.</h1>
            <div>
                <table class="table">
                    <tr><th>Space</th><td><?php echo escape($space->spaceKey); ?></td></tr>
                    <tr><th>Name</th><td><?php echo escape($space->name); ?></td></tr>
                    <tr><th>Timezone</th><td><?php echo escape($space->timezone); ?></td></tr>
                </table>
            </div>

            <h2>Your Info.</h1>
            <div>
                <table class="table">
                    <tr><th>UserId</th><td><?php echo escape($user->userId); ?></td></tr>
                    <tr><th>Name</th><td><?php echo escape($user->name); ?></td></tr>
                    <tr><th>Lang</th><td><?php echo escape($user->lang); ?></td></tr>
                    <tr><th>MailAddress</th><td><?php echo escape($user->mailAddress); ?></td></tr>
                </table>
            </div>

<?php   endif; ?>
        </div>
<?php endif; ?>
    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
</body>
</html>
