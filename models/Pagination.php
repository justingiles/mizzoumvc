<?php
/**
 * Builds pagination links for an archive section
 */
namespace MizzouMVC\models;
use MizzouMVC\models\Base;
use \WP_Query;

/**
 * Builds pagination links for an archive section
 *
 * @package WordPress
 * @subpackage MizzouMVC
 * @category framework
 * @category model
 * @author Paul Gilzow, Mizzou Creative, University of Missouri
 * @copyright 2016 Curators of the University of Missouri
 */
class Pagination extends Base{
    /**
     * @var bool whether the section is paged
     */
    public $paged = false;
    /**
     * @var null|integer internal storage of wp_query->query_vars['paged']
     */
    protected $wpPaged = null;
    /**
     * @var array default pagination options
     */
    protected $aryDefaults = array(
		'pagination_width'          => 5,
		'pagination_next'           => false,
		'pagination_previous'       => false,
		'pagination_glue'           =>'&#8230;',
		'pagination_current_linked' =>true,
	);
    /**
     * Not use
     * @var array
     * @deprecated
     * @todo delete
     */
	protected $aryAdjacentItems = array();
    /**
     * @var array options for this specific instance
     */
	protected $aryOptions = array();
    /**
     * @var null|string the href pattern to be used for building links
     */
	protected $strHrefPattern = null;

    protected $boolIsSearch = false;

	//protected $OnPage,$MaxPages,$MidPoint,$LowerLimit,$UpperLimit;

    /**
     * Builds pagination links for an archive section
     * @param array $aryArgs
     */
    public function __construct($aryArgs)
    {
        if(isset($aryArgs['wp_query']) && $aryArgs['wp_query'] instanceof WP_Query){
            $this->wpPaged = $aryArgs['wp_query']->query_vars['paged'];
	        $this->add_data('MaxPages',(isset($aryArgs['wp_query']->max_num_pages)) ? $aryArgs['wp_query']->max_num_pages : 1);
	        $this->add_data('OnPage',($this->wpPaged != 0) ? $this->wpPaged :1);
            $this->boolIsSearch = $aryArgs['wp_query']->is_search;

	        unset($aryArgs['wp_query']);//we no longer need it, so no use storing it any longer

	        if($this->MaxPages > 1 ) {
		        $this->paged = true;

		        $this->aryOptions = array_merge($this->aryDefaults,$aryArgs);

		        $this->add_data('MidPoint',round(($this->aryOptions['pagination_width']/2),0,PHP_ROUND_HALF_DOWN));

		        $this->_determineLowerAndUpperLimits();
		        $this->_determineHrefPattern();
		        $this->_buildPagination();
	        }
		} else {
	        _mizzou_log(isset($aryArgs['wp_query']),'is wp_query set in the array?',false,array('line'=>__LINE__,'file'=>__FILE__));
	        _mizzou_log((isset($aryArgs['wp_query'])) ? $aryArgs['wp_query'] instanceof WP_Query : 'wp_query is not set','is wp_query an instance of WP_Query?',false,array('line'=>__LINE__,'file'=>__FILE__));
            _mizzou_log($aryArgs,'You either didnt set wp_query, or what you gave us wasnt wp_query',false,array('line'=>__LINE__,'file'=>__FILE__));
        }
    }

    /**
     * Determines the upper and lower limits to be used in the pagination links
     * @return void
     */
    protected function _determineLowerAndUpperLimits()
	{
		if($this->MaxPages - $this->OnPage < $this->MidPoint){
			//we're close to the end, give the extra to the low end
			$intLowerLimit = (1 > $intLower = $this->OnPage - $this->aryOptions['pagination_width'] + ($this->MaxPages - $this->OnPage)) ? 1 : $intLower;
			$intUpperLimit = $this->MaxPages;
		} elseif($this->OnPage - $this->MidPoint < 1 ){
			//we're near the bottom, give the extra to the top
			$intLowerLimit = 1;
			$intUpperLimit = min(($this->OnPage+$this->MidPoint+abs($this->OnPage - $this->MidPoint)),$this->MaxPages);
		} else {
			//we're in the middle somewhere
			$intLowerLimit = $this->OnPage - $this->MidPoint;
			$intUpperLimit = $this->OnPage + $this->MidPoint;
		}

		/**
		 * If intLowerLimit is 3 or 2 we end up with uneeded ellipsis
		 */
		if($intLowerLimit <= 3) $intLowerLimit = 1;
		/**
		 * If the offset between max and upper limit is 2 or less, we end up with
		 * uneeded ellipsis
		 */
		if(($this->MaxPages - $intUpperLimit) <=2 ) $intUpperLimit = $this->MaxPages;

		//now that we've figured them all out, lets set them
		$this->add_data('LowerLimit',$intLowerLimit);
		$this->add_data('UpperLimit',$intUpperLimit);
	}


    /**
     * Determines the href to be used in the pagination links
     * Specifically built because we had situations where there query parameters in a paginated area, and we needed
     * to retain those parameters as we built the links
     * @uses get_option()
     * @return void
     */
    protected function _determineHrefPattern()
	{

	    //if we arent using pretty urls OR, we're on a search archive using internal search
	    if(!get_option('permalink_structure') || $this->boolIsSearch && isset($_GET['s'])){
            //why urldecode? It's entirely possible the URL contains URLEncoded values (%20), which then throws off the sprintf later on
	        $strRequestURI = htmlentities(urldecode($_SERVER['REQUEST_URI']),ENT_QUOTES,'UTF-8',false);
	        if(false === strpos($strRequestURI,'paged=')){
                //we must be on the very first page
                $this->strHrefPattern = $strRequestURI.'&paged=%d';
            } else {
                //we're on a later page
                $this->strHrefPattern = preg_replace('/paged=(\d+)/','paged=%d',$strRequestURI);

            }
        } else {
            //we're using pretty permalinks and an external search provider
            if(is_null($this->wpPaged) || $this->wpPaged == 0 || false === strpos($_SERVER['REQUEST_URI'],'/page/')){
                $strHrefBase = $_SERVER['REQUEST_URI'];
            } else {
                $strHrefBase = substr($_SERVER['REQUEST_URI'],0,(strpos($_SERVER['REQUEST_URI'],'/page/') + 1 ));
            }

            $this->strHrefPattern = $strHrefBase . 'page/%d/';

            //do we also have a query string we need to append?
            if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != ''){
                $this->strHrefPattern .= '?'.htmlentities($_SERVER['QUERY_STRING'],ENT_QUOTES,'UTF-8',false);
            }
        }

        _mizzou_log($this->strHrefPattern,'our href pattern');
	}

    /**
     * Builds out all the pagination pieces
     * @return void
     */
    protected function _buildPagination()
	{
		$aryPaginationParts = array();
		//first, do they even want prev?
		if(false !== $this->aryOptions['pagination_previous']){
			//they do, but is it needed?
			if($this->OnPage != 1){
				$objPrevious = $this->_buildPaginationLinkObject(
					array('text'=>$this->aryOptions['pagination_previous'],
						'page'=>($this->OnPage-1),
						'class'=>'pagination-previous'
					)
				);

				$aryPaginationParts[] = $objPrevious;
				$this->add_data('previous',$objPrevious);
			}
		}

		//now, do we need a first page?
		if($this->LowerLimit != 1){
			$aryPaginationParts[] = $this->_buildPaginationLinkObject(array('page'=>'1'));
			$aryPaginationParts[] = $this->_buildPaginationLinkObject(array('link'=>false,'text'=>$this->aryOptions['pagination_glue']));
		}

		//now we'll loop through the lower to upper limits
		for($i=$this->LowerLimit;$i<=$this->UpperLimit;++$i){
			$aryPaginationLinkOptions = array('page'=>$i);
			//if this is the current page, then we need to change the defaults
			if($i == $this->OnPage){
				$aryPaginationLinkOptions['class'] = 'current';
				if(true !== $this->aryOptions['pagination_current_linked']){
					$aryPaginationLinkOptions['link'] = false;
				}


			}

			//store it temporarily
			$objPaginationLink = $this->_buildPaginationLinkObject($aryPaginationLinkOptions);

			//if this is the current page, we want a separate copy
			if($i == $this->OnPage) $this->add_data('current',$objPaginationLink);

			$aryPaginationParts[] = $objPaginationLink;
		}

		//do we need a last page?
		if($this->UpperLimit != $this->MaxPages){
			$aryPaginationParts[] = $this->_buildPaginationLinkObject(array('link'=>false,'text'=>$this->aryOptions['pagination_glue']));
			$aryPaginationParts[] = $this->_buildPaginationLinkObject(array('page'=>$this->MaxPages));
		}

		//do they even want a next link?
		if(false !== $this->aryOptions['pagination_next']){
			//do we need a next link?
			if($this->OnPage != $this->MaxPages){

				$objNext = $this->_buildPaginationLinkObject(array(
					'text'  => $this->aryOptions['pagination_next'],
					'page'  => ($this->OnPage+1),
					'class' => 'pagination-next'
				));

				$this->add_data('next',$objNext);
				$aryPaginationParts[] = $objNext;
			}
		}

		$this->add_data('pages',$aryPaginationParts);

	}

    /**
     * Builds a pagination object
     *
     * Will contain public members
     *  - text: string, text to be displayed
     *  - link: string, href to be used
     *  - class: string, class to include
     * @param array $aryOptions
     * @return \stdClass
     */
    protected function _buildPaginationLinkObject($aryOptions)
	{
		$aryDefaults = array(
			'text'=>'',
			'page'=>null,
			'class'=>'',
			'link'=>true,
		);

		$aryOptions = array_merge($aryDefaults,$aryOptions);

		$objPage = new \stdClass();

		if($aryOptions['text'] == ''){
			//no text give, so we probably we want to use the page
			if(!is_null($aryOptions['page'])){
				$strText = $aryOptions['page'];
			} else {
				//they didnt give us text, nor page. are we linking this one?
				_mizzou_log($aryOptions,'you didnt give me text or a page number',false,array('line'=>__LINE__,'func'=>__FUNCTION__,'file'=>__FILE__));
				if(!$aryOptions['link']){
					//we're going to ASSUME that this is a glue piece and they just forgot to give it to us
					$strText = $this->aryOptions['pagination_glue'];
				} else {
					_mizzou_log($aryOptions,'you want to link the page, but didnt give me text or a page number',false,array('line'=>__LINE__,'func'=>__FUNCTION__,'file'=>__FILE__));
					$strText = "I'm really not sure";
				}
			}
		} else {
			$strText = $aryOptions['text'];
		}

		$objPage->text = $strText;

		if($aryOptions['link'] && !is_null($aryOptions['page'])){
			$objPage->href = sprintf($this->strHrefPattern,$aryOptions['page']);
		}

		if($aryOptions['class'] != ''){
			$objPage->class = $aryOptions['class'];
		}

		return $objPage;
	}

}