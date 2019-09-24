<?php

/*
Plugin Name: Quiz Admin
Plugin URI: https://gusspencer.com
Description: Administrate practice quiz results
Version: 0.0.19
Author Gus Spencer
Author URI: https://gusspencer.com
Text Domain: education
*/

function q_adminTab() {
     add_menu_page( 
      'Quiz Result Q', 
      'Quiz Result Q', 
      'edit_posts', 
      'quiz_result', 
      'result_finder', 
      'dashicons-analytics' 

     );
}
add_action(admin_menu, q_adminTab);

function finder_form() {

global $wpdb;
$school_select = $wpdb->get_results( "SELECT DISTINCT meta_key, meta_value FROM {$wpdb->prefix}usermeta", ARRAY_A);
$exam_select = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_master", ARRAY_A);

   echo '
      <form class="fStyle" action="'.$_SERVER['REQUEST_URI'].'" method="POST">
      <div class="fField">
         <label>School Selector</label><br>
         <select class="fSelect" name="school">';

            foreach($school_select as $school_selects) {

               if($school_selects['meta_key'] == 'select_school') {
                  echo '<option class="fOption" value="'.$school_selects['meta_value'].'">'.$school_selects['meta_value'].'</option><br>';
               }

            }

   echo '</select>
      </div>
      <div class="fField">
         <label>Exam Selector</label><br>
         <select class="fSelect" name="exam">';

            foreach($exam_select as $exam_selects) {

               echo '<option class="fOption" value="'.$exam_selects['name'].'">'.$exam_selects['name'].'</option><br>';

            }

   echo '</select>
      </div>
      <button class="fButton" type="submit" name="submit">Submit</button>
      </form>
   ';
}

function get_exam() {

  $pName = $_POST['exam'];

  global $wpdb;

  $exam_sel = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}watupro_master WHERE name = '$pName'", ARRAY_A);

  $x_id = (int)$exam_sel[0]['ID'];

  $results = $wpdb->get_results( "SELECT date FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id", ARRAY_A);
  $topScore = $wpdb->get_results( "SELECT MAX(points) FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id", ARRAY_A);
  $minScore = $wpdb->get_results( "SELECT MIN(points) FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id", ARRAY_A);
  $maxScore = $wpdb->get_results( "SELECT MAX(max_points) FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id", ARRAY_A);
  $quest = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_question WHERE exam_id = $x_id", ARRAY_A);
  $sanswc = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE exam_id = $x_id AND is_correct = 1", ARRAY_A);
  $sanswt = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE exam_id = $x_id", ARRAY_A);
  $answ = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_answer", ARRAY_A);

  $examTakes = $wpdb->get_results( "SELECT user_id FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id", ARRAY_A);



  //Top 10 students sql query
  $topStudents = $wpdb->get_results( "SELECT user_id FROM {$wpdb->prefix}watupro_taken_exams WHERE exam_id = $x_id ORDER BY percent_points DESC LIMIT 10", ARRAY_A);

  $maxPoints = $maxScore[0]['MAX(max_points)'];

  $highScorePerc = ($topScore[0]['MAX(points)'] * 100) / $maxPoints;

  $highScorePercCl = number_format($highScorePerc, 2);

  $lowScorePerc = ($lowScore[0]['MIN(points)'] * 100) / $maxPoints;

  $lowScorePercCl = number_format($lowScorePerc, 2);

  echo '<h2><strong>'.$pName.'</strong></h2><br>';

echo '<div>';
  echo '<br>';
  echo '<strong>Exam Date:</strong> '.$results[0]['date'].'<br>';

  echo '<strong>Highest Score:</strong> '.$topScore[0]['MAX(points)'].' - ';
  echo $highScorePercCl.'%<br>';

  echo '<strong>Lowest Score:</strong> '.$minScore[0]['MIN(points)'].' - ';
  echo $lowScorePercCl.'%<br>';

  $answCountt = count($sanswt);
  $answCountc = count($sanswc);
  echo '<strong>Total Possible:</strong> '.$maxPoints.'<br>';
  //echo 'correct: '.$answCountc.'<br>';

  $classAver = ($answCountc * 100) /$answCountt;
  $classAverCl = number_format($classAver, 2);

  echo '<strong>Class Average:</strong> '.$classAverCl.'<br>';
echo '</div>';

  $totalTakes = count($examTakes);
  echo '<strong>Total Submissions:</strong> '.$totalTakes.'<br><br>';

  foreach($topStudents as $studs) {
    $studID = $studs['user_id'];
    $studentInfo = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}users WHERE ID = $studID", ARRAY_A);
    $studMetaFn = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE user_id = $studID AND meta_key = 'first_name'", ARRAY_A);
    $studMetaLn = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE user_id = $studID AND meta_key = 'last_name'", ARRAY_A);
    $topStudScore = $wpdb->get_results( "SELECT percent_correct FROM {$wpdb->prefix}watupro_taken_exams WHERE user_id = $studID", ARRAY_A);

    echo '<span style="float: left; padding: 4px; margin: 12px;"><strong>Student Name:</strong> '.$studMetaFn[0]['meta_value'].' ';
    echo $studMetaLn[0]['meta_value'].'<br>';
    echo '<strong>Correct:</strong> '.$topStudScore[0]['percent_correct'].'%';

    echo '<br><strong>Login Name:</strong> '.$studentInfo[0]['user_login'].'<br></span>';
  }

  foreach($quest as $quests) {

    $q_id = (int)$quests['ID'];
    $answ_perQuest = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE question_id = $q_id", ARRAY_A);
    $corrAnsw_perQuest = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE question_id = $q_id AND is_correct = 1", ARRAY_A);
    $totalAnsw_perQuest = count($answ_perQuest);
    $totalCorrAnsw_perQuest = count($corrAnsw_perQuest);

    $percentCorrPerQ = ($totalCorrAnsw_perQuest * 100) / $totalAnsw_perQuest;
    $percentWroPerQ = 100 - $percentCorrPerQ;

    $percWroPerQcl = number_format($percentWroPerQ, 2);
    $percCorrPerQcl = number_format($percentCorrPerQ, 2);

echo '<div style="float: left; padding: 4px;">';
    echo '<strong><h4>QUESTION: '.$quests['sort_order'].'</h4></strong>';
    echo '<h4>'.$quests['question'].'</h4>';


    echo '<h5><strong>Correct:</strong> '.$percCorrPerQcl.'%</h5>';
    echo '<h5><strong>Wrong:</strong> '.$percWroPerQcl.'%</h5>';

    //echo '<strong><h3></h3></strong>';

    $studAnsw = count($answ_perQuest);

    foreach($answ as $answs) {

      if($quests['ID'] == $answs['question_id']) {

        $studSelAnsw = count($sacount);

        echo '<span style="float: left; padding: 4px; margin: 12px;">';
          echo '<strong>answer: '.$answs['sort_order'].'</strong><br>';
          echo $answs['answer'];
          echo '<br>';

          $a_id = $answs['answer'];
          $ans_id = $answs['ID'];

          $sacount = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE exam_id = $x_id AND answer = '$a_id'", ARRAY_A);
          $sacountT = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}watupro_student_answers WHERE exam_id = $x_id AND question_id = '$ans_id'", ARRAY_A);

          $percSel = ($studSelAnsw * 100) / $studAnsw;
          $percSelCl = number_format($percSel, 2);

          //echo 'total answers for this question: '.$studAnsw.'<br>';

          echo 'chosen: '.$studSelAnsw.' time(s)<br>';

          echo $percSelCl.'%';

          echo '</span>';
      }

/**
         else {
            echo 'Looks like something went wrong <br>';
         }
**/
    }

  }
echo '</div>';
}

function result_finder() {

   finder_form();

   if(isset($_POST['submit'])) {
      get_exam();
   }
}

function qAdmin_shortcode() {

   ob_start();

      result_finder();

   return ob_get_clean();
}

add_shortcode( 's_exam', 'qAdmin_shortcode' );

?>
