<?php
/**
 * @uses is_user_logged_in()
 * @uses comments_template()
 * @todo move function calls out of this view
 */
?>
<div class="flex span7">
<?php
    echo $strBreadCrumbs;
    echo $strInnerViewContent;
    //<div class="clear"></div>
    if (is_user_logged_in()) {
        comments_template();
    }
?>

</div> <!-- end flex span7