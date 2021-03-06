<?php
require_once ($CFG->dirroot . '/course/renderer.php');

/**
 * The core course renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','course');
 */
class esco_course_renderer extends core_course_renderer {
	const COURSECAT_SHOW_COURSES_EXPANDED = 20;
	const COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT = 30;
	
	/**
	 * Override the constructor so that we can initialise the string cache
	 *
	 * @param moodle_page $page        	
	 * @param string $target        	
	 */
	public function __construct(moodle_page $page, $target) {
		parent::__construct ( $page, $target );
	}
	
	/**
	 * Fonction RECIA
	 * Display the content of a course
	 *
	 * @param
	 *        	the course to render content
	 *        	@ return string
	 */
	// Modif RECIA-CD - 20160201 => pour affichage des liens dans nouvel onglet avec la WebProxyPortlet
	// public function get_course_content(stdClass $course) {
	public function get_course_content(stdClass $course, $is_WPPortlet = false) {
		$content = '';
		$chelper = new coursecat_helper ();
		$chelper->set_show_courses ( self::COURSECAT_SHOW_COURSES_EXPANDED );
		// Modif RECIA-CD - 20160201 => pour affichage des liens dans nouvel onglet avec la WebProxyPortlet
		// $content .= $this->coursecat_coursebox_content($chelper, $course);
		$content .= $this->coursecat_coursebox_content ( $chelper, $course, $is_WPPortlet );
		return $content;
	}
	
	/**
	 * Returns HTML to display course content (summary, course contacts and optionally category name)
	 *
	 * This method is called from coursecat_coursebox() and may be re-used in AJAX
	 *
	 * @param coursecat_helper $chelper
	 *        	various display options
	 * @param stdClass|course_in_list $course        	
	 * @return string
	 */
	// Modif RECIA-CD - 20160201 => pour affichage des liens dans nouvel onglet avec la WebProxyPortlet
	// function coursecat_coursebox_content(coursecat_helper $chelper, $course) {
	function coursecat_coursebox_content(coursecat_helper $chelper, $course, $is_WPPortlet = false) {
		global $CFG;
		if ($chelper->get_show_courses () < self::COURSECAT_SHOW_COURSES_EXPANDED) {
			return '';
		}
		if ($course instanceof stdClass) {
			require_once ($CFG->libdir . '/coursecatlib.php');
			$course = new course_in_list ( $course );
		}
		$content = '';
		
		// Début modification RECIA - Cache le résumé de cours et affiche un bouton afficher/cacher résumé
		$content .= html_writer::start_tag ( 'div', array (
				'class' => 'summary_reply fold_reply plus' 
		) );
		$content .= html_writer::tag ( 'span', get_string ( 'summaryhide', 'block_course_overview_esco' ), array (
				'class' => 'block-hider-hide',
				'id' => $course->id,
				'onclick' => "controls(this)" 
		) );
		$content .= html_writer::tag ( 'span', get_string ( 'summaryshow', 'block_course_overview_esco' ), array (
				'class' => 'block-hider-show',
				'id' => $course->id,
				'onclick' => "controls(this)" 
		) );
		$content .= html_writer::end_tag ( 'div' );
		$content .= html_writer::start_tag ( 'div', array (
				'class' => 'summary folded' 
		) );
		
		$content .= html_writer::end_tag ( 'div' ); // .summary
		
		$content .= html_writer::start_tag ( 'div', array (
				'class' => 'teachers_reply fold_reply plus' 
		) );
		$content .= html_writer::tag ( 'span', get_string ( 'teachershide', 'block_course_overview_esco' ), array (
				'class' => 'block-hider-hide',
				'id' => $course->id,
				'onclick' => "controls(this)" 
		) );
		$content .= html_writer::tag ( 'span', get_string ( 'teachersshow', 'block_course_overview_esco' ), array (
				'class' => 'block-hider-show',
				'id' => $course->id,
				'onclick' => "controls(this)" 
		) );
		$content .= html_writer::end_tag ( 'div' );
		$content .= html_writer::start_tag ( 'ul', array (
				'class' => 'teachers folded' 
		) );
		
		
		$content .= html_writer::end_tag ( 'ul' ); // .teachers
		                                        // }
		                                        
		// display course category if necessary (for example in search results)
		if ($chelper->get_show_courses () == self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT) {
			require_once ($CFG->libdir . '/coursecatlib.php');
			if ($cat = coursecat::get ( $course->category, IGNORE_MISSING )) {
				$content .= html_writer::start_tag ( 'div', array (
						'class' => 'coursecat' 
				) );
				$content .= get_string ( 'category' ) . ': ' . html_writer::link ( new moodle_url ( '/course/index.php', array (
						'categoryid' => $cat->id 
				) ), $cat->get_formatted_name (), array (
						'class' => $cat->visible ? '' : 'dimmed' 
				) );
				$content .= html_writer::end_tag ( 'div' ); // .coursecat
			}
		}
		
		return $content;
	}
}
