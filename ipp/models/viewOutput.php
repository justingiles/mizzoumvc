<?php
/**
 * Created by PhpStorm.
 * User: Frank
 * Date: 5/16/14
 * Time: 9:31 AM
 */

function mizzouOutPutView($strInnerViewFileName,$aryData)
{
    //convert all the data for the inner view into variables
    extract($aryData);

    //outerView needs breadcrumbs and inner view data

    //get the contents for the breadcrumbs
    ob_start();
    breadcrumbs();
    $strBreadCrumbs = ob_get_clean();

    $strThemePath = get_template_directory() . DIRECTORY_SEPARATOR;
    $strViewsPath = $strThemePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR;
    $strInnerView = $strViewsPath . $strInnerViewFileName . '.php';
    //get contents from the inner view
    if(file_exists($strInnerView)){
        require_once $strInnerView;
        $strInnerViewContents = ob_get_clean();
    } else {
        $strInnerView = '<p>Unable to retrieve inner view.</p>';
    }

    ob_end_clean();

    //start actual output
    get_header();
    get_sidebar();
    require_once $strViewsPath . 'outerView.php';
    get_footer();
}