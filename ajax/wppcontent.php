<?php
// Decommenter en debug
error_reporting ( E_ALL );

require_once ('../../../config.php');
require_once ($CFG->libdir . '/tablelib.php');
require_once ($CFG->dirroot . '/course/renderer.php');
define ( 'AJAX_SCRIPT', true );
define ( 'NO_DEBUG_DISPLAY', true );
$contextid = optional_param ( 'contextid', SYSCONTEXTID, PARAM_INT );
$courseid = optional_param ( 'idCourse', 0, PARAM_INT );
$typeReq = optional_param ( 'typeReq', '', PARAM_STRINGID );
$is_WPP = optional_param ( 'isWPP', 0, PARAM_INT );
$PAGE->set_url ( '/blocks/course_overview_esco/ajax/wppcontent.php' );
if ($contextid == SYSCONTEXTID) {
	$course = $SITE;
}
if (isset ( $courseid ) && ! empty ( $courseid )) {
	// On est helas obligé (!) de recharger le cours dans la bdd !
	// Solution : mettre en cache les cours qu'on a deja chargé dans la page my ou ds le WPP
	// Peut-etre utile meme si il n'y a pas de ralentissement notable dans ce chargement
	// le cache servirait à alleger la charge de la bdd et la rendrait plus efficace (less overload)
	$selectedCourse = $DB->get_record ( 'course', array (
			'id' => $courseid 
	), '*', MUST_EXIST );
	if ($selectedCourse instanceof stdClass) {
		require_once ($CFG->libdir . '/coursecatlib.php');
		$selectedCourse = new course_in_list ( $selectedCourse );
	}
} else {
	die ();
}
if (isset ( $typeReq ) && ! empty ( $typeReq )) {
	if ($typeReq === 'summary') {
		$chelper = new coursecat_helper ();
		
		$text = $chelper->get_course_formatted_summary ( $selectedCourse, array (
				'overflowdiv' => true,
				'noclean' => true,
				'para' => false 
		) );
		
		$contentimages = $contentfiles = '';
		$files = array ();
		// Cette action peut retourner un 404 avant le rendu de cette page...
		// :/
		try {
			$files = $selectedCourse->get_course_overviewfiles ();
		} catch ( Exception $e ) {
			error_log ( "Error while getting overview files  " . print_r ( get_object_vars ( $e ), true ) );
		}
		foreach ( $files as $file ) {
			
			$isimage = $file->is_valid_image ();
			
			$url = file_encode_url ( "$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid () . '/' . $file->get_component () . '/' . $file->get_filearea () . $file->get_filepath () . $file->get_filename (), ! $isimage );
			if ($isimage) {
				$contentimages .= html_writer::tag ( 'div', html_writer::empty_tag ( 'img', array (
						'src' => $url 
				) ), array (
						'class' => 'courseimage' 
				) );
			} else {
				$image = $OUTPUT->pix_icon ( file_file_icon ( $file, 24 ), $file->get_filename (), 'moodle' );
				$filename = html_writer::tag ( 'span', $image, array (
						'class' => 'fp-icon' 
				) ) . html_writer::tag ( 'span', $file->get_filename (), array (
						'class' => 'fp-filename' 
				) );
				$contentfiles .= html_writer::tag ( 'span', html_writer::link ( $url, $filename ), array (
						'class' => 'coursefile fp-filename-icon' 
				) );
			}
		}
		if ($contentimages !== '' || $contentfiles != '') {
			$text .= '<h4>Fichiers de résumé des cours</h4>';
			$text .= $contentimages . $contentfiles;
		}
		if (! empty ( $text ) && $text !== "") {
			echo json_encode ( $text );
		} else {
			$text = "<span class='no-overflow'>Aucun résumé trouvé pour ce cours</span>";
			echo json_encode ( $text );
		}
		die ();
	} else if ($typeReq == 'teachers') {
		$text = "";
		if ($selectedCourse->has_course_contacts ()) {
			// Ce module charge les contacts et les met dans le cache pour une heure !!!
			// Si les enseignants sont modifié cette modification ne sera visible ici qu'apres qu'il ait renouvellé le cache
			// voir la definition de cache core / coursecontacts
			$teachers = $selectedCourse->get_course_contacts ();
			foreach ( $teachers as $userid => $coursecontact ) {
				if ($is_WPP) {
					$name = $coursecontact ['rolename'] . ': ' . html_writer::link ( new moodle_url ( '/user/view.php', array (
							'id' => $userid,
							'course' => SITEID 
					) ), $coursecontact ['username'], array (
							'target' => '_blank' 
					) );
				} else {
					$name = $coursecontact ['rolename'] . ': ' . html_writer::link ( new moodle_url ( '/user/view.php', array (
							'id' => $userid,
							'course' => SITEID 
					) ), $coursecontact ['username'], array (
					) );
				}
				$text .= html_writer::tag ( 'li', $name );
			}
			$text .= html_writer::end_tag ( 'ul' ); // .teachers
			echo json_encode ( $text );
			die ();
		} else {
			$text = '<li>Aucun enseignant trouvé pour ce cours</li>';
			echo json_encode ( $text );
			die ();
		}
	}
	exit ();
}


