<?php
$pageTitle = "Error : page not found";
include $templatePath.'header.tpl.php';
?>

        <header><h2><?php echo $pageTitle; ?></h2></header>
        <p>The page or file you are looking for doesn't exist or has been moved.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $baseUrl; ?>">the homepage</a>.</p>
<?php include $templatePath.'footer.tpl.php'; ?>
