<?php
/**
 * Name: TicTac App
 * Description: The TicTacToe game application
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\DI;

function tictac_install()
{
	Hook::register('app_menu', 'addon/tictac/tictac.php', 'tictac_app_menu');
}

function tictac_app_menu(App $a, array &$b)
{
	$b['app_menu'][] = '<div class="app-title"><a href="tictac">' . DI::l10n()->t('Three Dimensional Tic-Tac-Toe') . '</a></div>';
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function tictac_module() {}

function tictac_content(App $a) {

	$o = '';

  if($_POST['move']) {
    $handicap = DI::args()->get(1);
    $mefirst = DI::args()->get(2);
    $dimen = DI::args()->get(3);
    $yours = DI::args()->get(4);
    $mine  = DI::args()->get(5);

    $yours .= $_POST['move'];
  }
  elseif(DI::args()->getArgc() > 1) {
    $handicap = DI::args()->get(1);
    $dimen = 3;
  }
  else {
   $dimen = 3;
  }

  $o .=  '<h3>' . DI::l10n()->t('3D Tic-Tac-Toe') . '</h3><br />';

  $t = new tictac($dimen,$handicap,$mefirst,$yours,$mine);
  $o .= $t->play();

  $o .=  '<a href="tictac">' . DI::l10n()->t('New game') . '</a><br />';
  $o .=  '<a href="tictac/1">' . DI::l10n()->t('New game with handicap') . '</a><br />';
  $o .=  '<p>' . DI::l10n()->t('Three dimensional tic-tac-toe is just like the traditional game except that it is played on multiple levels simultaneously. ');
  $o .= DI::l10n()->t('In this case there are three levels. You win by getting three in a row on any level, as well as up, down, and diagonally across the different levels.');
  $o .= '</p><p>';
  $o .= DI::l10n()->t('The handicap game disables the center position on the middle level because the player claiming this square often has an unfair advantage.');
  $o .= '</p>';

  return $o;

}

class tictac {
  private $dimen;
  private $first_move = true;
  private $handicap = 0;
  private $yours;
  private $mine;
  private $winning_play;
  private $you;
  private $me;
  private $debug = 1;
  private $crosses = ['011','101','110','112','121','211'];

/*
    '001','010','011','012','021',
    '101','110','111','112','121',
    '201','210','211','212','221');
*/

  private $corners = [
    '000','002','020','022',
    '200','202','220','222'];

  private $planes = [
    ['000','001','002','010','011','012','020','021','022'], // horiz 1
    ['100','101','102','110','111','112','120','121','122'], // 2
    ['200','201','202','210','211','212','220','221','222'], // 3
    ['000','010','020','100','110','120','200','210','220'], // vert left
    ['000','001','002','100','101','102','200','201','202'], // vert top
    ['002','012','022','102','112','122','202','212','222'], // vert right
    ['020','021','022','120','121','122','220','221','222'], // vert bot
    ['010','011','012','110','111','112','210','211','212'], // left vertx
    ['001','011','021','101','111','221','201','211','221'], // top vertx
    ['000','001','002','110','111','112','220','221','222'], // diag top
    ['020','021','022','110','111','112','200','201','202'], // diag bot
    ['000','010','020','101','111','121','202','212','222'], // diag left
    ['002','012','022','101','111','121','200','210','220'], // diag right
    ['002','011','020','102','111','120','202','211','220'], // diag x
    ['000','011','022','100','111','122','200','211','222']  // diag x

  ];


  private $winner = [
     ['000','001','002'],         // board 0 winners  - left corner across
     ['000','010','020'],         // down
     ['000','011','022'],         // diag
     ['001','011','021'],         // middle-top down
     ['010','011','012'],         // middle-left across
     ['002','011','020'],         // right-top diag
     ['002','012','022'],         // right-top down
     ['020','021','022'],        // bottom-left across
     ['100','101','102'],      // board 1 winners
     ['100','110','120'],
     ['100','111','122'],
     ['101','111','121'],
     ['110','111','112'],
     ['102','111','120'],
     ['102','112','122'],
     ['120','121','122'],
     ['200','201','202'],    // board 2 winners
     ['200','210','220'],
     ['200','211','222'],
     ['201','211','221'],
     ['210','211','212'],
     ['202','211','220'],
     ['202','212','222'],
     ['220','221','222'],
     ['000','100','200'],      // top-left corner 3d
     ['000','101','202'],
     ['000','110','220'],
     ['000','111','222'],
     ['001','101','201'],      // top-middle 3d
     ['001','111','221'],
     ['002','102','202'],      // top-right corner 3d
     ['002','101','200'],
     ['002','112','222'],
     ['002','111','220'],
     ['010','110','210'],      // left-middle 3d
     ['010','111','212'],
     ['011','111','211'],      // middle-middle 3d
     ['012','112','212'],      // right-middle 3d
     ['012','111','210'],
     ['020','120','220'],      // bottom-left corner 3d
     ['020','110','200'],
     ['020','121','222'],
     ['020','111','202'],
     ['021','121','221'],      // bottom-middle 3d
     ['021','111','201'],
     ['022','122','222'],      // bottom-right corner 3d
     ['022','121','220'],
     ['022','112','202'],
     ['022','111','200']

  ];

  function __construct($dimen,$handicap,$mefirst,$yours,$mine) {
    $this->dimen = 3;
    $this->handicap = (($handicap) ? 1 : 0);
    $this->mefirst = (($mefirst) ? 1 : 0);
    $this->yours = str_replace('XXX','',$yours);
    $this->mine  = $mine;
    $this->you = $this->parse_moves('you');
    $this->me  = $this->parse_moves('me');

    if(strlen($yours))
      $this->first_move = false;
  }

  function play() {

     if($this->first_move) {
       if(rand(0,1) == 1) {
         $o .=  '<div class="error-message">' . DI::l10n()->t('You go first...') . '</div><br />';
         $this->mefirst = 0;
         $o .= $this->draw_board();
         return $o;
       }
       $o .=  '<div class="error-message">' . DI::l10n()->t('I\'m going first this time...') . ' </div><br />';
       $this->mefirst = 1;

     }

     if($this->check_youwin()) {
       $o .=  '<div class="error-message">' . DI::l10n()->t('You won!') . '</div><br />';
       $o .= $this->draw_board();
       return $o;
     }

     if($this->fullboard())
       $o .=  '<div class="error-message">' . DI::l10n()->t('"Cat" game!') . '</div><br />';

     $move = $this->winning_move();
     if(strlen($move)) {
       $this->mine .= $move;
       $this->me = $this->parse_moves('me');
     }
     else {
       $move = $this->defensive_move();
       if(strlen($move)) {
         $this->mine .= $move;
         $this->me = $this->parse_moves('me');
       }
       else {
         $move = $this->offensive_move();
         if(strlen($move)) {
           $this->mine .= $move;
           $this->me = $this->parse_moves('me');
         }
       }
     }

     if($this->check_iwon())
       $o .=  '<div class="error-message">' . DI::l10n()->t('I won!') . '</div><br />';
     if($this->fullboard())
       $o .=  '<div class="error-message">' . DI::l10n()->t('"Cat" game!') . '</div><br />';
     $o .= $this->draw_board();
	return $o;
  }

  function parse_moves($player) {
    if($player == 'me')
      $str = $this->mine;
    if($player == 'you')
      $str = $this->yours;
    $ret = [];
      while(strlen($str)) {
         $ret[] = substr($str,0,3);
         $str = substr($str,3);
      }
    return $ret;
  }


  function check_youwin() {
    for($x = 0; $x < count($this->winner); $x ++) {
      if(in_array($this->winner[$x][0],$this->you) && in_array($this->winner[$x][1],$this->you) && in_array($this->winner[$x][2],$this->you)) {
        $this->winning_play = $this->winner[$x];
        return true;
      }
    }
    return false;
  }
  function check_iwon() {
    for($x = 0; $x < count($this->winner); $x ++) {
      if(in_array($this->winner[$x][0],$this->me) && in_array($this->winner[$x][1],$this->me) && in_array($this->winner[$x][2],$this->me)) {
        $this->winning_play = $this->winner[$x];
        return true;
      }
    }
    return false;
  }
  function defensive_move() {

    for($x = 0; $x < count($this->winner); $x ++) {
      if(($this->handicap) && in_array('111',$this->winner[$x]))
        continue;
      if(in_array($this->winner[$x][0],$this->you) && in_array($this->winner[$x][1],$this->you) && (! in_array($this->winner[$x][2],$this->me)))
        return($this->winner[$x][2]);
      if(in_array($this->winner[$x][0],$this->you) && in_array($this->winner[$x][2],$this->you) && (! in_array($this->winner[$x][1],$this->me)))
        return($this->winner[$x][1]);
      if(in_array($this->winner[$x][1],$this->you) && in_array($this->winner[$x][2],$this->you) && (! in_array($this->winner[$x][0],$this->me)))
        return($this->winner[$x][0]);
     }
     return '';
  }

function winning_move() {

    for($x = 0; $x < count($this->winner); $x ++) {
      if(($this->handicap) && in_array('111',$this->winner[$x]))
        continue;
      if(in_array($this->winner[$x][0],$this->me) && in_array($this->winner[$x][1],$this->me) && (! in_array($this->winner[$x][2],$this->you)))
        return($this->winner[$x][2]);
      if(in_array($this->winner[$x][0],$this->me) && in_array($this->winner[$x][2],$this->me) && (! in_array($this->winner[$x][1],$this->you)))
        return($this->winner[$x][1]);
      if(in_array($this->winner[$x][1],$this->me) && in_array($this->winner[$x][2],$this->me) && (! in_array($this->winner[$x][0],$this->you)))
        return($this->winner[$x][0]);
     }

}

  function offensive_move() {

    shuffle($this->planes);
    shuffle($this->winner);
    shuffle($this->corners);
    shuffle($this->crosses);

    if(! count($this->me)) {
      if($this->handicap) {
        $p = $this->uncontested_plane();
        foreach($this->corners as $c)
          if((in_array($c,$p))
            && (! $this->is_yours($c)) && (! $this->is_mine($c)))
              return($c);
      }
      else {
        if((! $this->marked_yours(1,1,1)) && (! $this->marked_mine(1,1,1)))
          return '111';
        $p = $this->uncontested_plane();
        foreach($this->crosses as $c)
          if((in_array($c,$p))
            && (! $this->is_yours($c)) && (! $this->is_mine($c)))
            return($c);
      }
    }

    if($this->handicap) {
      if(count($this->me) >= 1) {
        if(count($this->get_corners($this->me)) == 1) {
          if(in_array($this->me[0],$this->corners)) {
            $p = $this->my_best_plane();
            foreach($this->winner as $w) {
              if((in_array($w[0],$this->you))
              || (in_array($w[1],$this->you))
              || (in_array($w[2],$this->you)))
                continue;
              if(in_array($w[0],$this->corners)
                && in_array($w[2],$this->corners)
                && in_array($w[0],$p) && in_array($w[2],$p)) {
                  if($this->me[0] == $w[0])
                    return($w[2]);
                  elseif($this->me[0] == $w[2])
                    return($w[0]);
              }
            }
          }
        }
        else {
          $r = $this->get_corners($this->me);
          if(count($r) > 1) {
            $w1 = []; $w2 = [];
            foreach($this->winner as $w) {
              if(in_array('111',$w))
                continue;
              if(($r[0] == $w[0]) || ($r[0] == $w[2]))
                $w1[] = $w;
              if(($r[1] == $w[0]) || ($r[1] == $w[2]))
                $w2[] = $w;
            }
            if(count($w1) && count($w2)) {
              foreach($w1 as $a) {
                foreach($w2 as $b) {
                  if((in_array($a[0],$this->you))
                  || (in_array($a[1],$this->you))
                  || (in_array($a[2],$this->you))
                  || (in_array($b[0],$this->you))
                  || (in_array($b[1],$this->you))
                  || (in_array($b[2],$this->you)))
                    continue;
                  if(($a[0] == $b[0]) && ! $this->is_mine($a[0])) {
                    return $a[0];
                  }
                  elseif(($a[2] == $b[2]) && ! $this->is_mine($a[2])) {
                    return $a[2];
                  }
                }
              }
            }
          }
        }
      }
    }

 //&& (count($this->me) == 1) && (count($this->you) == 1)
 //     && in_array($this->you[0],$this->corners)
 //     && $this->is_neighbor($this->me[0],$this->you[0])) {

      // Yuck. You foiled my plan. Since you obviously aren't playing to win,
      // I'll try again. You may keep me busy for a few rounds, but I'm
      // gonna' get you eventually.

//      $p = $this->uncontested_plane();
 //     foreach($this->crosses as $c)
   //     if(in_array($c,$p))
     //     return($c);

//    }


    // find all the winners containing my points.
    $mywinners = [];
    foreach($this->winner as $w)
      foreach($this->me as $m)
        if((in_array($m,$w)) && (! in_array($w,$mywinners)))
          $mywinners[] = $w;

    // find all the rules where my points are in the center.
      $trythese = [];
      if(count($mywinners)) {
        foreach($mywinners as $w) {
          foreach($this->me as $m) {
            if(($m == $w[1]) && ($this->uncontested_winner($w))
              && (! in_array($w,$trythese)))
            $trythese[] = $w;
          }
        }
      }

      $myplanes = [];
      for($p = 0; $p < count($this->planes); $p ++) {
        if($this->handicap && in_array('111',$this->planes[$p]))
          continue;
        foreach($this->me as $m)
          if((in_array($m,$this->planes[$p]))
            && (! in_array($this->planes[$p],$myplanes)))
              $myplanes[] = $this->planes[$p];
      }
      shuffle($myplanes);

    // find all winners which share an endpoint, and which are uncontested
      $candidates = [];
      if(count($trythese) && count($myplanes)) {
        foreach($trythese as $t) {
          foreach($this->winner as $w) {
            if(! $this->uncontested_winner($w))
              continue;
            if((in_array($t[0],$w)) || (in_array($t[2],$w))) {
              foreach($myplanes as $p)
                if(in_array($w[0],$p) && in_array($w[1],$p) && in_array($w[2],$p) && ($w[1] != $this->me[0]))
                  if(! in_array($w,$candidates))
                    $candidates[] = $w;
            }
          }
        }
      }

      // Find out if we are about to force a win.
      // Looking for two winning vectors with a common endpoint
      // and where we own the middle of both - we are now going to
      // grab the endpoint. The game isn't yet over but we've already won.

      if(count($candidates)) {
        foreach($candidates as $c) {
          if(in_array($c[1],$this->me)) {
            // return endpoint
            foreach($trythese as $t)
              if($t[0] == $c[0])
                return($t[0]);
              elseif($t[2] == $c[2])
                return($t[2]);
          }
       }

       // find opponents planes
      $yourplanes = [];
      for($p = 0; $p < count($this->planes); $p ++) {
        if($this->handicap && in_array('111',$this->planes[$p]))
          continue;
        if(in_array($this->you[0],$this->planes[$p]))
          $yourplanes[] = $this->planes[$p];
      }

      shuffle($this->winner);
      foreach($candidates as $c) {

         // We now have a list of winning strategy vectors for our second point
         // Pick one that will force you into defensive mode.
         // Pick a point close to you so we don't risk giving you two
         // in a row when you block us. That would force *us* into
         // defensive mode.
         // We want:        or:         not:
         //           X|O|     X| |       X| |
         //            |O|     O|O|        |O|
         //            | |      | |        |O|

         if(count($this->you) == 1) {
           foreach($this->winner as $w) {
             if(in_array($this->me[0], $w) && in_array($c[1],$w)
               && $this->uncontested_winner($w)
               && $this->is_neighbor($this->you[0],$c[1])) {
                 return($c[1]);
             }
           }
         }
       }

       // You're somewhere else entirely or have made more than one move
       // - any strategy vector which puts you on the defense will have to do

       foreach($candidates as $c) {
         foreach($this->winner as $w) {
           if(in_array($this->me[0], $w) && in_array($c[1],$w)
             && $this->uncontested_winner($w)) {
                   return($c[1]);
           }
         }
       }
     }

    // worst case scenario, no strategy we can play,
    // just find an empty space and take it

    for($x = 0; $x < $this->dimen; $x ++)
      for($y = 0; $y < $this->dimen; $y ++)
        for($z = 0; $z < $this->dimen; $z ++)
          if((! $this->marked_yours($x,$y,$z))
            && (! $this->marked_mine($x,$y,$z))) {
            if($this->handicap && $x == 1 && $y == 1 && $z == 1)
              continue;
            return(sprintf("%d%d%d",$x,$y,$z));
          }

  return '';
  }

  function marked_yours($x,$y,$z) {
   $str = sprintf("%d%d%d",$x,$y,$z);
   if(in_array($str,$this->you))
     return true;
   return false;
  }

  function marked_mine($x,$y,$z) {
   $str = sprintf("%d%d%d",$x,$y,$z);
   if(in_array($str,$this->me))
     return true;
   return false;
  }

  function is_yours($str) {
   if(in_array($str,$this->you))
     return true;
   return false;
  }

  function is_mine($str) {
   if(in_array($str,$this->me))
     return true;
   return false;
  }

  function get_corners($a) {
    $total = [];
    if(count($a))
      foreach($a as $b)
        if(in_array($b,$this->corners))
          $total[] = $b;
    return $total;
  }

  function uncontested_winner($w) {
    if($this->handicap && in_array('111',$w))
      return false;
    $contested = false;
    if(count($this->you)) {
      foreach($this->you as $you)
        if(in_array($you,$w))
          $contested = true;
    }
    return (($contested) ? false : true);
  }


  function is_neighbor($p1,$p2) {
   list($x1,$y1,$z1) = sscanf($p1, "%1d%1d%1d");
   list($x2,$y2,$z2) = sscanf($p2, "%1d%1d%1d");

   if((($x1 == $x2) || ($x1 == $x2+1) || ($x1 == $x2-1)) &&
      (($y1 == $y2) || ($y1 == $y2+1) || ($y1 == $y2-1)) &&
      (($z1 == $z2) || ($z1 == $z2+1) || ($z1 == $z2-1)))
     return true;
   return false;

  }

  function my_best_plane() {

    $second_choice = [];
    shuffle($this->planes);
    for($p = 0; $p < count($this->planes); $p ++ ) {
      $contested = 0;
      if($this->handicap && in_array('111',$this->planes[$p]))
        continue;
      if(! in_array($this->me[0],$this->planes[$p]))
        continue;
      foreach($this->you as $m) {
        if(in_array($m,$this->planes[$p]))
          $contested ++;
      }
      if(! $contested)
        return($this->planes[$p]);
      if($contested == 1)
        $second_choice = $this->planes[$p];
    }
    return $second_choice;
  }







  function uncontested_plane() {
    $freeplane = true;
    shuffle($this->planes);
    $pl = $this->planes;

    for($p = 0; $p < count($pl); $p ++ ) {
        if($this->handicap && in_array('111',$pl[$p]))
          continue;
       foreach($this->you as $m) {
         if(in_array($m,$pl[$p]))
           $freeplane = false;
       }
       if(! $freeplane) {
         $freeplane = true;
         continue;
       }
       if($freeplane)
         return($pl[$p]);
    }
    return [];
  }

  function fullboard() {
   return false;
  }

  function draw_board() {
    if(! strlen($this->yours))
      $this->yours = 'XXX';
    $o .=  "<form action=\"tictac/{$this->handicap}/{$this->mefirst}/{$this->dimen}/{$this->yours}/{$this->mine}\" method=\"post\" />";
    for($x = 0; $x < $this->dimen; $x ++) {
      $o .=  '<table>';
      for($y = 0; $y < $this->dimen; $y ++) {
        $o .=  '<tr>';
        for($z = 0; $z < $this->dimen; $z ++) {
          $s = sprintf("%d%d%d",$x,$y,$z);
          $winner = ((is_array($this->winning_play) && in_array($s,$this->winning_play)) ? " color: #FF0000; " : "");
          $bordertop = (($y != 0) ? " border-top: 2px solid #000;" : "");
          $borderleft = (($z != 0) ? " border-left: 2px solid #000;" : "");
          if($this->handicap && $x == 1 && $y == 1 && $z == 1)
            $o .=  "<td style=\"width: 25px; height: 25px; $bordertop $borderleft\" align=\"center\">&nbsp;</td>";
          elseif($this->marked_yours($x,$y,$z))
            $o .=  "<td style=\"width: 25px; height: 25px; $bordertop $borderleft $winner\" align=\"center\">X</td>";
          elseif($this->marked_mine($x,$y,$z))
            $o .=  "<td style=\"width: 25px; height: 25px; $bordertop $borderleft $winner\" align=\"center\">O</td>";
          else {
            $val = sprintf("%d%d%d",$x,$y,$z);
            $o .=  "<td style=\"width: 25px; height: 25px; $bordertop $borderleft\" align=\"center\"><input type=\"checkbox\" name=\"move\" value=\"$val\" onclick=\"this.form.submit();\" /></td>";
          }
        }
        $o .=  '</tr>';
      }
      $o .=  '</table><br />';
    }
    $o .=  '</form>';
	return $o;

  }


}

