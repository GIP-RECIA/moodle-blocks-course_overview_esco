<?php
require_once (dirname ( __FILE__ ) . '/../../config.php');
require_once ($CFG->dirroot . '/lib/moodlelib.php');
require_once ($CFG->dirroot . '/blocks/course_overview_esco/locallib.php');

// Si on est sur la page moodle-ent.php
if (preg_match ( "/moodle-ent.php/", $_SERVER ["SCRIPT_NAME"] )) {
	$selectCourses = "div#tabs-1 div.coursebox";
	$onchange = "sortCourses()";
	$sortOrder = 1;
	$is_WPP = 1;
	// Si on est sur la page block_course_overview_esco.php
} else {
	$selectCourses = "div.block_course_overview_esco div.coursebox";
	$onchange = "sortCoursesAndSave()";
	$sortOrder = optional_param ( 'sortOrder', '', PARAM_INT );
	if (! empty ( $sortOrder )) {
		block_course_overview_esco_update_sortcourses ( $sortOrder );
	} else {
		$sortOrder = block_course_overview_esco_get_sort_courses ();
	}
	$is_WPP = 0;
	
	// $this n'existe pas dans ce context...
	// $this->page->requires->jquery();
}

/*
 * Récupère les rôles de l'utilisateur pour chaque cours dans la base de données
 */
function add_roles($uid, $courses) {
	global $DB, $CFG;
	// Acces aux contact du cours via le cache !
	// $cache = cache::make('core', 'coursecontacts');
	// $cacheddata = $cache->get_many(array_merge(array('basic'), array_keys($courses)));
	// error_log("\n CACHED RECORDS : ".print_r($cacheddata, true),3,"/tmp/mycacheddata.log");
	
	// Chargement des roles via les fonctions moodle (permet de profiter des caches applicatifs )
	// Cependant ne charge qu'un seul role par cours celui le plus haut lvl de droit
	// $roles[] =array();
	// foreach ($courses as $selectedCourse){
	// if ($selectedCourse instanceof stdClass) {
	// require_once('../config.php');
	// require_once($CFG->libdir. '/coursecatlib.php');
	// $testCourse = new course_in_list($selectedCourse);
	// }
	// if($testCourse->has_course_contacts() ){
	// $teachers = $testCourse->get_course_contacts();
	// if (array_key_exists($uid,$teachers)){
	// ($teachers[$uid]['rolename'] === 'Enseignant') ? $the_role = 'editingteacher' : $the_role ='courseowner';
	// $selectedCourse->roles_esco = $the_role;
	// }else {
	// error_log(" KEY NOT IN ARRAY ",3,"/tmp/mycourseobject.log");
	// $selectedCourse->roles_esco = 'student';
	// }
	
	// }
	// }
	
	// return $courses;
	if (! empty ( $courses )) {
		// Test de performance de la requete suivante student, editingteacher, courseowner
		// error_log("\n DEBUT APPEL FONCTION ADD_ROLE ( AJOUT DES ROLES DANS LES COURS )", 3, "/tmp/myBenchmark.log");
		// $hDebut = microtime(true);
		
		// $duree = microtime(true) - $hDebut;
		// error_log ( " \n Duree de l'appel : ".sprintf('%01.4f', $duree)." Pour l'utilisateur : ".print_r($anuser->id, true),3,"/tmp/myBenchmark.log");
		// error_log ("\n FIN APPEL FONCTION ENROL_GET_USERS_COURSES ", 3,"/tmp/myBenchmark.log");
		
		$sql = "SELECT c.instanceid AS courseid, GROUP_CONCAT(r.shortname SEPARATOR ', ') AS roles
	    		FROM {role} r
	    		JOIN {role_assignments} ra ON ra.roleid = r.id
	    		JOIN {context} c ON c.id = ra.contextid
	    		WHERE ra.userid = ?
	    		AND c.instanceid in (";
		
		$params = array (
				$uid 
		);
		foreach ( $courses as $course ) {
			$sql .= ",?";
			$params [] = $course->id;
		} //
		  // Un expression reguliere pour enlever une virgule (:/)
		$sql = preg_replace ( '/\(,/', '(', $sql, 1 ) . ") GROUP BY c.instanceid";
		
		$roleassignments = $DB->get_records_sql ( $sql, $params );
		foreach ( $courses as $course ) {
			// error_log("\n ROLE DEF : ".print_r($roleassignments, true),3,"/tmp/mycourseobject.log");
			$course->roles_esco = $roleassignments [$course->id]->roles;
		}
	}
	
	return $courses;
}

?>

<!--<div id="courseOverviewSortAndRole"> -->
<!-- Tri -->
<!-- <<div id="courseOverviewSort"> -->
<select class="courseOverviewSortSelect" name="sortOrder" data-role="none"
	onchange=<?= '"'.$onchange.'"' ?>>
	<option value="1" <?= $sortOrder == 1 ? 'selected="selected"' : '' ?>><?= get_string('sortbyfullnameasc', 'block_course_overview_esco') ?></option>
	<option value="2" <?= $sortOrder == 2 ? 'selected="selected"' : '' ?>><?= get_string('sortbyfullnamedesc', 'block_course_overview_esco') ?></option>
	<option value="3" <?= $sortOrder == 3 ? 'selected="selected"' : '' ?>><?= get_string('sortbydateasc', 'block_course_overview_esco') ?></option>
	<option value="4" <?= $sortOrder == 4 ? 'selected="selected"' : '' ?>><?= get_string('sortbydatedesc', 'block_course_overview_esco') ?></option>
</select>
<!-- </div> -->

<!-- Filtre -->
<!-- <div id="courseOverviewRole"> -->
<span class="courseOverviewSpan"><?= get_string('roleType', 'block_course_overview_esco')?> :
<select class="courseOverviewRoleSelect" name="rolesFilter" data-role="none" onchange="filterCourses()">
	<option value="all" selected="selected"><?= get_string('roleAll', 'block_course_overview_esco') ?></option>
	<option value="owner"><?= get_string('roleOwner', 'block_course_overview_esco') ?></option>
	<option value="teacher"><?= get_string('roleTeacher', 'block_course_overview_esco') ?></option>
	<option value="student"><?= get_string('roleStudent', 'block_course_overview_esco') ?></option>
</select>
</span>
<!-- </div>
</div> -->
<script type="text/javascript">

var filterCourses;
var sortCoursesAndSave;
var sortCourses;
var controls;

	$( document ).ready( function( ){
		  
		controls = function(element){
			  var requestDone = true; 
			  var is_WPP = <?= $is_WPP ?>;
			  //var wwwroot = "<?= $CFG->wwwroot ?>";
var loctab = $(location).attr('href').split("/"); 
	  var wwwroot = loctab[0]+"//"+loctab[2];
	   wwwroot += "<?= $CFG->webpath?>";
// fin modif...
			  var eventSourceDiv = $(element).parent("div").parent("div").parent("div");
			  var typeSourceDiv =  $(element).parent("div");
			  var typeDiv = typeSourceDiv.attr('class').split("_");
			  // A-t'on déjà le resumé !?
			  var isOverviewLoaded = ($(element).parent('div').next().children().attr('class') === 'no-overflow');
			  // Charge-t'on les resumés ? 
			 var isLoadingOverviews = (typeDiv[0] === 'summary');
			 // Charge t-on les enseignants 
			 var isLoadingTeachers = (typeDiv[0] === 'teachers');
			 // A-t'on déjà les enseignants ?
			 if ($(element).parent('div').next().children().get(0) !== undefined){
				 var isTeachersLoaded = ($(element).parent('div').next().children().get(0).tagName === 'LI');
			 }else {
				 var isTeachersLoaded = false
			 }
			 // Faisons-nous une requete ? 
			 var isSendingRequest = ( isLoadingOverviews && !isOverviewLoaded) || (isLoadingTeachers && !isTeachersLoaded);
			 if (isSendingRequest){
				 resquestDone = false;
				 $.ajax({
					  method: "GET",
					  url: wwwroot+"/blocks/course_overview_esco/ajax/wppcontent.php",
					  data: { idCourse: $(element).attr('id'), typeReq: typeDiv[0], isWPP: is_WPP },
					  async : true,
					  dataType: 'json'
					}).done(function( msg ) {
							$(element).parent('div').next().html (msg);
							requestDone = true; 
					  });
			 } 
			var targetClass = '';
			isLoadingOverviews ? targetClass=".summary" : targetClass=".teachers";
			if(requestDone){
				if($(element).parent('div').hasClass('plus')){
					$(element).parent('div').removeClass('plus').addClass('minus');
					$(element).parent('div').next(targetClass).addClass('unfolded').removeClass('folded');
				}else if($(element).parent('div').hasClass('minus')) {
					$(element).parent('div').removeClass('minus').addClass('plus');
					$(element).parent('div').next(targetClass).addClass('folded').removeClass('unfolded');
					}
			}
			
		  }
	  /* 
		 * Tri les cours selon l'ordre selectionné et sauvegarde le choix dans les préférences utilisateur
		 */
		 sortCoursesAndSave = 	function sortCoursesAndSave() {
			var sortOrder = sortCourses();
			$.ajax({
				type: "GET",
			    url: "../blocks/course_overview_esco/sort_and_filter.php?sortOrder=" + sortOrder,
			    async: false
			});
			
		}
		  
	  /* 
		 * Tri les cours selon l'ordre selectionné
		 * Ordre de tri :
		 * 1 : Tri croissant par Nom complet du cours
		 * 2 : Tri décroissant par Nom complet du cours
		 * 3 : Tri croissant par Date de création du cours
		 * 4 : Tri décroissant par Date de création du cours 
		 */
		 sortCourses = function sortCourses() {
			var sortOrder = $("select[name=sortOrder] option:selected").attr("value");
			
			// On change l'ordre
			var courses = $(<?= '"'.$selectCourses.'"' ?>).clone();
			for(var i = 0 ; i < courses.length; i++) {
		        for(var j = i + 1; j < courses.length; j++){
		           if(isBefore(sortOrder, courses[j], courses[i])) {
		               var temp = courses[j];
		               courses[j]=courses[i];
		               courses[i]=temp;
		            }
		        }
		    }
			
			// On affiche le nouvel ordre
			for(var i = 0; i < courses.length; i++) {
				$("<?= $selectCourses ?>:eq(" + i + ")").html($(courses[i]).html());
			}

			// On filtre par rôle
			filterCourses();

			// Si on est sur la page moodle-ent.php
			if($("#tabs-1 .summary_reply.fold_reply").is(":visible")) {
				addSummaryEvent();
			}
	    	if($("#tabs-1 .teachers_reply.fold_reply").is(":visible")) {
	    		addTeachersEvent();
	    	}
	    	
			return sortOrder;
		}
	  /* 
		 * Renvoie true si courseA doit apparaitre avant courseB
		 * sortOrder : ordre de tri selectionné (1, 2, 3 ou 4) 
		 */
		function isBefore(sortOrder, courseA, courseB) {
			var titleA = $(courseA).find("a").text().toLowerCase();
			var titleB = $(courseB).find("a").text().toLowerCase();
			var dateA = $(courseA).find("input:last").attr("value");
			var dateB = $(courseB).find("input:last").attr("value");
			if(sortOrder == 1) {
				return titleA < titleB;
			} else if(sortOrder == 2) {
				return titleA > titleB;
			} else if(sortOrder == 3) {
				return dateA < dateB;
			} else if(sortOrder == 4) {
				return dateA > dateB;
			} 	
		}
		/*
		 * Filtre les cours selon le rôle selectionné (all, owner, teacher ou student)
		 */
		 filterCourses =  function filterCourses() {
			var role = $("select[name=rolesFilter] option:selected").attr("value");
			var courses = $(<?= '"'.$selectCourses.'"' ?>);
			for(var i = 0 ; i < courses.length; i++) {
				var courseRoles = $(courses[i]).find("input").attr("value");
				if(role == "all" || courseRoles.indexOf(role) > -1) {
					$(courses[i]).css("display", "");
				} else {
					$(courses[i]).css("display", "none");
				}
			}
		}
	});
	
</script>
