<?php
$pageTitle = "Login";
include $templatePath.'header.tpl.php';
?>

        <header><h2><?php echo $pageTitle; ?></h2></header>
        <form id="loginForm" method="post" action="">
            <fieldset>
                <legend>Informations</legend>
                <label>
                    <span>Login</span>
                    <input type="text" name="login"<?php if (isset($_POST['login'])) { ?> value="<?php echo $_POST['login']; ?>"<?php } if (!isset($currentUser->errors['wrongPassword']) || $currentUser->errors['wrongPassword'] == false) { ?> autofocus="autofocus"<?php } ?>>
                    <span class="info">Default: admin</span><?php if (isset($currentUser->errors['unknownLogin']) && $currentUser->errors['unknownLogin']) { ?>

                    <span class="error">Unknown login</span><?php } ?>

                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password"<?php if (isset($currentUser->errors['wrongPassword']) && $currentUser->errors['wrongPassword']) { ?> autofocus="autofocus"<?php } ?>>
                    <span class="info">Default: watamelo</span><?php if (isset($currentUser->errors['wrongPassword']) && $currentUser->errors['wrongPassword']) { ?>

                    <span class="error">Incorrect password</span><?php } ?>

                </label>
                <label>
                    <input type="checkbox" name="remember" value="remember"<?php if (isset($_POST['remember']) && $_POST['remember'] == true) { ?> checked<?php } ?>>
                    <span>Remember me</span>
                </label>

                <input type="submit" name="submitLogin" id="submitLogin" value="Log in" />
            </fieldset>
        </form>
<?php include $templatePath.'footer.tpl.php'; ?>
