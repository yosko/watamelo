<?php
$p['pageTitle'] = "";
include $tplPath.'header.tpl.php';
?>
        <h3>Welcome to Watamelo</h3>
        <div>
            You are currently: <?php if($p['currentUser']['level'] >= $p['userLevels']['user']) { echo 'connected as "'.$p['currentUser']['login'].'"'; } else { echo 'not connected'; } ?>
        </div>
        <ul>
            <li><a href="<?php echo $p['baseUrl']; ?>403">Show a 403 error</a></li>
            <li><a href="<?php echo $p['baseUrl']; ?>no-route-for-this-url">Show a 404 error</a></li><?php if($p['currentUser']['level'] < $p['userLevels']['user']) { ?>

            <li><a href="<?php echo $p['baseUrl']; ?>login">Log in</a></li><?php } else { ?>

            <li><a href="<?php echo $p['baseUrl']; ?>logout">Log out</a></li><?php } ?>

            <li><a href="<?php echo $p['baseUrl']; ?>feed.rss">RSS feed</a> or <a href="<?php echo $p['baseUrl']; ?>feed.atom">Atom feed</a></li>
            <li>Export data to file: <a href="<?php echo $p['baseUrl']; ?>export.csv">CSV</a> or <a href="<?php echo $p['baseUrl']; ?>export.json">JSON</a></li>

        </ul>
        <hr>
        <div>
            The following variables and constants are accessible from the views by default:
        </div>
        <ul>
            <li><strong>$p['rootUrl'] =</strong> <em><?php echo $p['rootUrl']; ?></em> (for direct links to files)</li>
            <li><strong>$p['baseUrl'] =</strong> <em><?php echo $p['baseUrl']; ?></em> (might differ from $rootUrl if URL rewriting not activated. Used for links to pages)</li>
            <li><strong>$p['templateUrl'] =</strong> <em><?php echo $p['templateUrl']; ?></em> (for your CSS, javascript and design related images)</li>
            <li><strong>DEVELOPMENT_ENVIRONMENT =</strong> <em><?php echo DEVELOPMENT_ENVIRONMENT; ?></em> (boolean)</li>
            <li><strong>VERSION =</strong> <em><?php echo VERSION; ?></em> (used in footer)</li>
        </ul>
        <div>
            The following variables are related to the current example and are accessible from the current view:
        </div>
        <ul>
            <li>
                <strong>$userLevels =</strong>
                <ul><?php foreach($p['userLevels'] as $key => $value) { ?>
                    
                    <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                </ul>
            </li>
            <li>
                <strong>$currentUser =</strong>
                <ul><?php foreach($p['currentUser'] as $key => $value) { ?>
                    
                    <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                </ul>
            </li>
            <li>
                <strong>$users =</strong> (exemple of database retrieved values)
                <ul><?php foreach($p['users'] as $num => $user) { ?>
                    
                    <li><?php echo $num; ?> =>
                        <ul><?php foreach($user as $key => $value) { ?>
                            
                            <li><?php echo $key.' => '.$value; ?></li><?php } //foreach ?>

                        </ul>
                    </li><?php } //foreach ?>

                </ul>
            </li>
        </ul>
<?php include $tplPath.'footer.tpl.php'; ?>