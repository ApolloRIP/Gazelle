<?php

use Gazelle\Exception\BonusException;

enforce_login();
authorize();

if (!preg_match('/^(token|other)-[1-4]$/', $Label, $match)) {
    error(403);
}

if ($match[1] === 'token') {
    try {
        $Bonus->purchaseToken($LoggedUser['ID'], $Label);
    } catch (BonusException $e) {
        $message = $e->getMessage();
        error("Purchase not concluded ($message).");
    }
}
else {
    if (empty($_GET['user'])) {
        error('You have to enter a username to give tokens to.');
    }
    $user = (new Gazelle\Manager\User)->findByUsername(urldecode($_GET['user']));
    if (is_null($user)) {
        error('Nobody with that name found at ' . SITE_NAME . '. Are you certain the spelling is right?');
    } elseif ($user->id() == $LoggedUser['ID']) {
        error('You cannot gift yourself tokens, they are cheaper to buy directly.');
    }
    try {
        $Bonus->purchaseTokenOther($LoggedUser['ID'], $user->id(), $Label);
    } catch (BonusException $e) {
        if ($e->getMessage() == 'otherToken:no-gift-funds') {
            error('Purchase for other not concluded. Either you lacked funds or they have chosen to decline FL tokens.');
        } else {
            error(0);
        }
    }
}

header('Location: bonus.php?complete=' . urlencode($Label));
