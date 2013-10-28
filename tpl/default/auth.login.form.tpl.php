<?php
$p['pageTitle'] = "Login";
include $tplPath.'header.tpl.php';
?>

        <header><h3><?php echo $p['pageTitle']; ?></h3></header>
        <form id="loginForm" method="post" action="">
            <fieldset>
                <legend>Informations</legend>
                <label>
                    <span>Login</span>
                    <input type="text" name="login"<?php if(isset($p['values']['login'])) { ?> value="<?php echo $p['values']['login']; ?>"<?php } if(!isset($p['errors']['wrongPassword']) || $p['errors']['wrongPassword'] == false) { ?> autofocus="autofocus"<?php } ?>>
                    <span class="info">Default: admin</span><?php if(isset($p['errors']['unknownLogin']) && $p['errors']['unknownLogin']) { ?>

                    <span class="error">Unknown login</span><?php } ?>

                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password"<?php if(isset($p['errors']['wrongPassword']) && $p['errors']['wrongPassword']) { ?> autofocus="autofocus"<?php } ?>>
                    <span class="info">Default: watamelo</span><?php if(isset($p['errors']['wrongPassword']) && $p['errors']['wrongPassword']) { ?>

                    <span class="error">Incorrect password</span><?php } ?>

                </label>
                <label>
                    <input type="checkbox" name="remember" value="remember"<?php if(isset($p['values']['remember']) && $p['values']['remember'] == true) { ?> checked<?php } ?>>
                    <span>Remember me</span>
                </label>
                
                <input type="submit" name="submitLogin" id="submitLogin" value="Log in" />
            </fieldset>
        </form>
<?php include $tplPath.'footer.tpl.php'; ?>