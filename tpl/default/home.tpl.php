<?php
$pageTitle = "";
include $templatePath.'header.tpl.php';
?>
        <h3>Welcome to Watamelo</h3>
        <div>
            You are currently: <?php if($currentUser->level >= $userLevels['user']) { echo 'connected as "'.$currentUser->login.'"'; } else { echo 'not connected'; } ?>
        </div>
        <ul>
            <li><a href="<?php echo $baseUrl; ?>403">Show a 403 error</a></li>
            <li><a href="<?php echo $baseUrl; ?>no-route-for-this-url">Show a 404 error</a></li><?php if($currentUser->level < $userLevels['user']) { ?>

            <li><a href="<?php echo $baseUrl; ?>login">Log in</a></li><?php } else { ?>

            <li><a href="<?php echo $baseUrl.$variables['admin']; ?>">Admin</a></li>
            <li><a href="<?php echo $baseUrl; ?>logout">Log out</a></li><?php } ?>

            <li><a href="<?php echo $baseUrl; ?>feed.rss">RSS feed</a> or <a href="<?php echo $baseUrl; ?>feed.atom">Atom feed</a></li>
            <li>Export data to file: <a href="<?php echo $baseUrl; ?>export.csv">CSV</a> or <a href="<?php echo $baseUrl; ?>export.json">JSON</a></li>

        </ul>
        <hr>
        <div>
            The following variables and constants are accessible from the views by default:
        </div>
        <ul>
            <li><strong>$rootUrl =</strong> <em><?php echo $rootUrl; ?></em> (for direct links to files)</li>
            <li><strong>$baseUrl =</strong> <em><?php echo $baseUrl; ?></em> (might differ from $rootUrl if URL rewriting not activated. Used for links to pages)</li>
            <li><strong>$templateUrl =</strong> <em><?php echo $templateUrl; ?></em> (for your CSS, javascript and design related images)</li>
            <li><strong>DEVELOPMENT_ENVIRONMENT =</strong> <em><?php echo DEVELOPMENT_ENVIRONMENT; ?></em> (boolean)</li>
            <li><strong>APP_VERSION =</strong> <em><?php echo APP_VERSION; ?></em> (version number of your app)</li>
            <li><strong>WATAMELO_VERSION =</strong> <em><?php echo WATAMELO_VERSION; ?></em> (version number of the framework, used here in footer)</li>
        </ul>
        <div>
            The following variables are related to the current example and are accessible from the current view:
        </div>
        <ul>
            <li>
                <strong>$userLevels =</strong>
                <ul><?php foreach($userLevels as $key => $value) { ?>

                    <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                </ul>
            </li>
            <li>
                <strong>$currentUser =</strong>
                <ul><?php foreach($currentUser as $key => $value) { ?>

                    <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                </ul>
            </li>
            <li>
                <strong>$users =</strong> (exemple of database retrieved values)
                <ul><?php foreach($users as $num => $user) { ?>

                    <li><?php echo $num; ?> =>
                        <ul><?php foreach($user as $key => $value) { ?>

                            <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                        </ul>
                    </li><?php } //foreach ?>

                </ul>
            </li>
        </ul>
<?php include $templatePath.'footer.tpl.php'; ?>