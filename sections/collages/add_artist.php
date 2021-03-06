<?php

authorize();

if (!($_REQUEST['action'] == 'add_artist' || $_REQUEST['action'] == 'add_artist_batch')) {
    error(403);
}

if (isset($_POST['collage_combo']) && (int)$_POST['collage_combo']) {
    // From artist page
    $collageId = (int)$_POST['collage_combo'];
} elseif (isset($_POST['collage_ref']) && preg_match('@' . SITE_URL . '.*?(?:id=)?(\d+)(?:&|\s*$)?@', $_POST['collage_ref'], $match)) {
    // From artist page
    $collageId = $match[1];
} else {
    // From collage page
    $collageId = (int)$_POST['collageid'];
}
$collageMan = new Gazelle\Manager\Collage;
$collage = $collageMan->findById($collageId);
if (!$collage) {
    error(404);
}

if (!check_perms('site_collages_delete')) {
    if ($collage->isLocked()) {
        error('This collage is locked');
    }
    if ($collage->categoryId() == 0 && !$collage->isOwner($LoggedUser['ID'])) {
        error("You cannot edit someone else's personal collage.");
    }
    if ($collage->maxGroups() > 0 && $collage->numEntries() >= $collage->maxGroups()) {
        error('This collage already holds its maximum allowed number of entries.');
    }
}

/* grab the URLs (single or many) from the form */
$URL = [];
if ($_REQUEST['action'] == 'add_artist') {
    if (isset($_POST['url'])) {
        // From a collage page
        $URL[] = trim($_POST['url']);
    } elseif (isset($_POST['artistid'])) {
        // From an artist page
        $URL[] = SITE_URL . '/artist.php?id=' . (int)$_POST['artistid'];
    }
}
elseif ($_REQUEST['action'] == 'add_artist_batch') {
    foreach (explode("\n", $_REQUEST['urls']) as $u) {
        $u = trim($u);
        if (strlen($u)) {
            $URL[] = $u;
        }
    }
}

/* check that they correspond to artist pages */
$ID = [];
foreach ($URL as $u) {
    if (!preg_match('/^'.ARTIST_REGEX.'/i', $u, $match)) {
        $safe = htmlspecialchars($u);
        error("The entered url ($safe) does not correspond to an artist page on site.");
    }
    $ArtistID = end($match);
    try {
        $artist = new Gazelle\Artist($ArtistID);
    }
    catch (Exception $e) {
        $safe = htmlspecialchars($u);
        error("The entered url ($safe) does not correspond to an artist page on site.");
    }
    $ID[] = $ArtistID;
}

/* would the addition overshoot the allowed number of entries? */
if (!check_perms('site_collages_delete')) {
    $maxGroupsPerUser = $collage->maxGroupsPerUser();
    if ($maxGroupsPerUser > 0) {
        if ($collage->countByUser($LoggedUser['ID']) + count($ID) > $maxGroupsPerUser) {
            error("You may add no more than $maxGroupsPerUser entries to this collage.");
        }
    }

    $maxGroups = $collage->maxGroups();
    if ($maxGroups > 0 && ($collage->numEntries() + count($ID) > $maxGroups)) {
        error("This collage can hold only $maxGroups entries.");
    }
}

foreach ($ID as $artistId) {
    $collage->addArtist($artistId, $LoggedUser['ID']);
}
$collageMan->flushDefaultArtist($LoggedUser['ID']);
header("Location: collages.php?id=$collageId");
