<?php
/**
 * Injects a title attribute into an iframe of an oembed item using the title attribute from the data object returned
 * from the oembed service
 */
/**
 * Injects a title attribute into an iframe of an oembed item using the title attribute from the data object returned
 * from the oembed service
 *
 * @package Wordpress
 * @subpackage MizzouMVC
 * @category framework
 * @category library
 * @author Paul F. Gilzow, Mizzou Creative, University of Missouri
 * @copyright 2016 Curators of the University of Missouri
 */
class IframeEmbed {
    /**
     * @var internal storage of our static instance
     */
    private static $objInstance;

    /**
     * adds IframeEmbed::injectTitleAttribute to add_filter oembed_dataparse
     */
	public function __construct()
	{
		add_filter('oembed_dataparse',array($this,'injectTitleAttribute'),10,3);
	}

    /**
     * Creates a new instance and/or returns the stored instance
     * @return IframeEmbed|internal
     */
    public static function getInstance()
	{
		if(null === self::$objInstance){
			self::$objInstance = new IframeEmbed();
		}

		return self::$objInstance;
	}

	/**
	 * Injects a title attribute into any iframe that is returned via automated oEmbed
	 *
	 * @param $strReturn string current html embed
	 * @param $objData data object returned from service provider
	 * @param $strUrl URL of service provider
	 *
	 * @return string html embed code
	 */
	public function injectTitleAttribute($strReturn,$objData,$strUrl)
	{
		if(1 === preg_match('/^<iframe (.*)><\/iframe>$/',$strReturn,$aryMatches) && isset($objData->title) && '' != $objData->title){

			//this is so that if the title has " contained it wont cause an issue
			$strTitle = htmlentities($objData->title,ENT_QUOTES,'UTF-8');
            $strIframePattern = '<iframe title="%s" ';
            $arySubs = array($strTitle);
            if(isset($objData->type) && '' != $objData->type){
                $strIframePattern .= 'class="%s" ';
                $arySubs[] = $objData->type . '-embed';
            }

            $strIframePattern .= '%s></iframe>';
            $arySubs[] = $aryMatches[1];

			$strReturn = vsprintf($strIframePattern,$arySubs);
		} else {
			_mizzou_log($strReturn,'either regex failed or title was empty. here is strReturn',false,array('line'=>__LINE__,'file'=>__FILE__));
			_mizzou_log($objData,'either regex failed or title was empty. here is objData',false,array('line'=>__LINE__,'file'=>__FILE__));
		}

		return $strReturn;
	}
}