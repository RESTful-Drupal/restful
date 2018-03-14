<?php

/**
 * @file
 * Default theme implementation to present a user's API token plus an
 * example with next steps.
 *
 * Available variables:
 * - $token: The API token.
 *
 * @ingroup themeable
 */
?>
<p>Your authentication token is: <code><?php print $token; ?></code></p>

<p>Discover the API by running the following command in a terminal and replacing
    [your-token] by the above string:</p>

<p>
    <code>
        curl -H 'access-token:<?php print $token; ?>' <?php print $GLOBALS['base_url'] ?>/api?all=true
    </code>
</p>
