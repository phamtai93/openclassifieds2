<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends Controller {

	public function action_index()
	{
        if(core::config('general.auto_locate'))
        {
            Theme::$scripts['footer'][] = '//maps.google.com/maps/api/js?sensor=false&libraries=geometry&v=3.7';
            Theme::$scripts['footer'][] = '//cdn.jsdelivr.net/gmaps/0.4.15/gmaps.min.js';
        }
        
	    //template header
	    $this->template->title            = '';
	    // $this->template->meta_keywords    = 'keywords';
	    if(core::config('general.site_description') != '')
			$this->template->meta_description = core::config('general.site_description');		    
	    else 
			$this->template->meta_description = core::config('general.site_name').' '.__('official homepage, get your post listed now.');		    

	    //setting main view/template and render pages  

	    // swith to decide on ads_in_home
	    $ads = new Model_Ad();
        $ads->where('status','=', Model_Ad::STATUS_PUBLISHED);

        $ads_in_home = core::config('advertisement.ads_in_home');

        //in case we do not count visits we cant show popular
        if(core::config('advertisement.count_visits')==0 AND $ads_in_home==2)
            $ads_in_home = 0;

        switch ($ads_in_home) 
        {
            case 2:
                $id_ads = array_keys(Model_Visit::popular_ads());
                if (count($id_ads)>0)
                    $ads->where('id_ad','IN', $id_ads);
                
                break;
            case 1:
                $ads->where('featured','IS NOT', NULL)
                ->where('featured', '>=', Date::unix2mysql())
                ->order_by('featured','desc');
                break;
            case 4:
                $ads->where('featured','IS NOT', NULL)
                ->where('featured', '>=', Date::unix2mysql())
                ->order_by(DB::expr('RAND()'));
                break;
            case 0:
            default:
                $ads->order_by('published','desc');
                break;
        }

        //if ad have passed expiration time dont show 
        if(core::config('advertisement.expire_date') > 0)
        {
            $ads->where(DB::expr('DATE_ADD( published, INTERVAL '.core::config('advertisement.expire_date').' DAY)'), '>', Date::unix2mysql());
        }

        $ads = $ads->limit(Theme::get('num_home_latest_ads', 4))->cached()->find_all();

		$categs = Model_Category::get_category_count();

        $locats = Model_Location::get_location_count();
        
        $auto_locats = NULL;
        if(core::config('general.auto_locate') AND Model_User::get_userlatlng()) {
                $auto_locats = new Model_Location();
                $auto_locats = $auto_locats ->select(array(DB::expr('degrees(acos(sin(radians('.$_COOKIE['mylat'].')) * sin(radians(`latitude`)) + cos(radians('.$_COOKIE['mylat'].')) * cos(radians(`latitude`)) * cos(radians(abs('.$_COOKIE['mylng'].' - `longitude`))))) * 111.321'), 'distance'))
                                            ->where('latitude','IS NOT',NULL)
                                            ->where('longitude','IS NOT',NULL)
                                            ->having('distance','<=','100')
                                            ->order_by('distance','desc')
                                            ->find_all()
                                            ->as_array();
        }
	
        $this->template->bind('content', $content);
        
        $this->template->content = View::factory('pages/home',array('ads'=>$ads, 
                                                                    'categs'=>$categs,
                                                                    'locats'=>$locats,
                                                                    'auto_locats'=>$auto_locats
        															));
		
	}

} // End Welcome
