<?php
/**
 * Block class for the ilp
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

class block_ilp extends block_list {

    /**
     * Sets initial block variables. Part of the blocks API
     *
     * @return void
     */
    function init() {
        $this->title = get_string('blockname', 'block_ilp');
    }
    
    /**
     * Sets up the content for the block.
     *
     * @return object The content object
     */
    function get_content() {
        global $CFG, $USER, $COURSE, $SITE;

        // include  db class
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // include the parser class
        require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

        // include the lib file
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // db class manager
        $dbc = new ilp_db();

        // get the course id
        $course_id = optional_param('id', $SITE->id, PARAM_INT);

        //this is to handle the /user/view.php page where id is reserved for the userid ...
        //allow the current course to be course=XX
        $current_course_id = optional_param('course', null, PARAM_INT);
        if( !$current_course_id ){
            $current_course_id = optional_param('id', null, PARAM_INT); //if there's no explicit course id, id might be a course id
        }

        // get the course
        $course = $dbc->get_course($course_id);

        
        
        

        // cache the content of the block
        if($this->content !== null) {
            return $this->content;
        }
       
    	//get all course that the current user is enrolled in 
		$my_courses				=	$dbc->get_user_courses($USER->id);
		$access_viewilp			=	false;
		$access_viewotherilp	= 	false;
		
		if (empty($my_courses))	{
			$c			=	new stdClass();
			$c->id		=	$course_id;
			$my_courses	=	array($c);
		}
		
		//we are going to loop through all the courses the user is enrolled in so that we can 
		//choose which display they will see 
        $found_current_course = false;
		foreach($my_courses	as $c) {
			
					$sitecontext = get_context_instance(CONTEXT_SYSTEM);
        			$coursecontext = get_context_instance(CONTEXT_COURSE, $c->id);
                    $set_course_groups_link = false;       
			
			        //we need to get the capabilites of the current user so we can deceide what to display in the block 
        			if (!empty($coursecontext) && has_capability('block/ilp:viewilp', $coursecontext,$USER->id,false) ) {
        				$access_viewilp		=	true;
        				//I have removed the var below as we dont want the my course groups link to contain
        				//the id of a  course which the user is not a teacher in 
                        //$set_course_groups_link = true;       
        			}
        			
        			if (!empty($coursecontext) && has_capability('block/ilp:viewotherilp', $coursecontext,$USER->id,false) || has_capability('block/ilp:ilpviewall', $sitecontext,$USER->id,false) || ilp_is_siteadmin($USER)) {
        				$access_viewotherilp	=	true;
                        $set_course_groups_link = true;
        			}

                    if( $set_course_groups_link ){
	                    if( !$found_current_course ){
	        				$intial_course_id	=	$c->id;
	                        if( $c->id == $current_course_id ){
	                            //current course is part of my_courses, so this should be the preselection for the linked page
	                            //so stop changing the value for the link
	                            $found_current_course = true;
	                        }
	                    }
                    }
		}
        
		//
		$usertutees	=	$dbc->get_user_tutees($USER->id);

		
		$this->content = new stdClass;
        $this->content->footer = '';
		
        //check if the user has the viewotherilp capability
        if (!empty($access_viewotherilp) || !empty($usertutees)) {
        
        	if (!empty($access_viewotherilp)) {    	
			 	$label = get_string('mycoursegroups', 'block_ilp');
	         	$url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$intial_course_id}";
	         	$this->content->items[] = "<a href='{$url}'>{$label}</a>";
	         	$this->content->icons[] = "";
    		}
    		
        	if (!empty($usertutees)) {    	
			 	$label = get_string('mytutees', 'block_ilp');
	         	$url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
	         	$this->content->items[] = "<a href='{$url}'>{$label}</a>";
	         	$this->content->icons[] = "";
    		}
        	
        
        } else {
        	
        	
        	//---
        	

			
			
        	
        	//TODO all code for implementing percentage bars in the block is below it has been commented out so that it can be 
        	//implemented correctly 
			/*
			$percentagebars	=	array();

			
			//include the attendance 
			$misclassfile	=	$CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_attendance_percentbar_plugin.php';
			
			if (file_exists($misclassfile)) {
				
				$pbstatus	=	get_config('block_ilp','ilp_mis_attendance_percentbar_plugin_pluginstatus');
				
				if ($pbstatus == ILP_ENABLED) {
						//create an instance of the MIS class
						$misclass	=	new ilp_mis_attendance_percentbar_plugin();
						
						//set the data for the student in question
						$misclass->set_data($USER->idnumber);
						
						
						$punch_method1 = array($misclass, 'get_student_punchuality');
						$attend_method1 = array($misclass, 'get_student_attendance');
		
		        
							        //check whether the necessary functions have been defined
				        if (is_callable($punch_method1,true)) {
				        	$misinfo	=	new stdClass();
			    	        
		
			    	        if ($misclass->get_student_punctuality() != false) {
				    	        //calculate the percentage
				    	        
				    	        $misinfo->percentage	=	$misclass->get_student_punctuality();	
			    	        
			    		        $misinfo->name	=	get_string('punctuality','block_ilp');
			    	        	
			    		        //pass the object to the percentage bars array
			    	    	    $percentagebars[]	=	$misinfo;
			    	        }
			        	}
		
						//check whether the necessary functions have been defined
				        if (is_callable($attend_method1,true) ) {
				        	$misinfo	=	new stdClass();
			    	        
			    	        //if total_possible is empty then there will be nothing to report
				        	if ($misclass->get_student_attendance() != false) {
			    	        	//calculate the percentage
			    	        	$misinfo->percentage	=	$misclass->get_student_attendance();
			    	        
			    	        	$misinfo->name	=	get_string('attendance','block_ilp');
		
			    	        	$percentagebars[]	=	$misinfo;
			    	        }
			    	        
			        	}
				}

			}

			
			$misoverviewplugins	=	false;

			if ($dbc->get_mis_plugins() !== false) {
				
				$misoverviewplugins	=	array();
				
				//get all plugins that mis plugins
				$plugins = $CFG->dirroot.'/blocks/ilp/plugins/mis';
				
				$mis_plugins = ilp_records_to_menu($dbc->get_mis_plugins(), 'id', 'name');
				
				foreach ($mis_plugins as $plugin_file) {
					
				    require_once($plugins.'/'.$plugin_file.".php");
				    
				    // instantiate the object
				    $class = basename($plugin_file, ".php");
				    $pluginobj = new $class();
				    $method = array($pluginobj, 'plugin_type');
					
				    if (is_callable($method,true)) {
				    	//we only want mis plugins that are of type overview 
				        if ($pluginobj->plugin_type() == 'overview') {
				        	
				        	//get the actual overview plugin
				        	$misplug	=	$dbc->get_mis_plugin_by_name($plugin_file);
				        	
				        	//if the admin of the moodle has done there job properly then only one overview mis plugin will be enabled 
				        	//otherwise there may be more and they will all be displayed 
				        	
				        	$status =	get_config('block_ilp',$plugin_file.'_pluginstatus');
				        	
				        	$status	=	(!empty($status)) ?  $status: ILP_DISABLED;
				        	
				        	if (!empty($misplug) & $status == ILP_ENABLED ) {
								$misoverviewplugins[]	=	$pluginobj;
				        	}
				        }
				    }
			
				}
			}

			
        	//if the user has the capability to view others ilp and this ilp is not there own 
			//then they may change the students status otherwise they can only view 
			
			//get all enabled reports in this ilp
			$reports		=	$dbc->get_reports(ILP_ENABLED);
			
			
			//we are going to output the add any reports that have state fields to the percentagebar array 
			foreach ($reports as $r) {
				if ($dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {

					$reportinfo				=	new stdClass();
					$reportinfo->total		=	$dbc->count_report_entries($r->id,$USER->id);
					$reportinfo->actual		=	$dbc->count_report_entries_with_state($r->id,$USER->id,ILP_STATE_PASS);
					
					 //if total_possible is empty then there will be nothing to report
	    	        if (!empty($reportinfo->total)) {
	    	        	//calculate the percentage
	    	        	$reportinfo->percentage	=	$reportinfo->actual/$reportinfo->total	* 100;
	    	        
	    	        	$reportinfo->name	=	$r->name;

	    	        	$percentagebars[]	=	$reportinfo;
	    	        }
					
				}
			}
			
			require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_percentage_bar.class.php');
			
			$pbar	=	new ilp_percentage_bar();
			
			
        	
        	if (!empty($percentagebars)) {  
					foreach($percentagebars	as $p) {
         				$this->content->items[]	=	$pbar->display_bar($p->percentage,$p->name);
         			}
        	}
        	
        	
        	*/
        	
        	//additional check to stop users from being able to access the ilp in course context 
        	//from the front page
        	$courseurl	=	(!empty($course_id) && $course_id != 1) ? "&course_id={$course_id}" : '';
        	
	       	$this->content->text	= "";
	         
			$label = get_string('mypersonallearningplan', 'block_ilp');
	        $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$USER->id}$courseurl";
	        $this->content->items[] = "<p><a href='{$url}'>{$label}</a><p/>";
	        $this->content->icons[] = "";	
        	
        	

        	

        	
        }

		
		
         
        return $this->content;
    }
    
    
    
    /**
     * Allow the user to set sitewide configuration options for the block.
     *
     * @return bool true
     */
    function has_config() {
        return true;
    }
    

    /**
     * Allow the user to set specific configuration options for the instance of
     * the block attached to a course.
     *
     * @return bool true
     */
    function instance_allow_config() {
    	
        return false;
    }
    
    
 
    
    /**
     * Only allow this block to be mounted to a course or the home page.
     *
     * @return array
     */
    function applicable_formats() {
        return array(
            'site-index'  => true,
            'course-view' => true,
        );
    }
    
    /**
     * Prevent the user from having more than one instance of the block on each
     * course.
     *
     * @return bool false
     */
    function instance_allow_multiple() {
        return false;
    }
    
    /*
     * Functions that we want to run directly after the block has been installed 
     *  
     */
	function after_install() {
		
		global $CFG;
		
		//call the install.php script (used for moodle 2) that has the operations that need to be carried 
		//out after installation 
		require_once($CFG->dirroot.'/blocks/ilp/db/install.php');
		
		//call the block_ilp_install function used by moodle 2.0
		xmldb_block_ilp_install();
		
	}
    
	function cron() {
		global $CFG;
		require_once($CFG->dirroot."/blocks/ilp/classes/ilp_cron.class.php");
		$cron	=	 new ilp_cron();
		$cron->run();
	}
	
}
?>
