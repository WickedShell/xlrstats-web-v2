<?php
/***************************************************************************
 * Xlrstats Webmodule
 * Webfront for XLRstats for B3 (www.bigbrotherbot.com)
 * (c) 2004-2010 www.xlr8or.com (mailto:xlr8or@xlr8or.com)
 ***************************************************************************/

/***************************************************************************
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 *  http://www.gnu.org/copyleft/gpl.html
 ***************************************************************************/

// no direct access
defined( '_XLREXEC' ) or die( 'Restricted access' );

class league {

  //--Support Functions-----------------------------------------------------------
  function draw_blocks($array, $title="League", $number=10, $blocks=4, $on="skill", $name_length=10)
  {
    global $main_width;
    $link = baselink();

    $width = $main_width/$blocks;
    echo "<table width='100%' class='outertable'><tr><td><div style='font-size:1.5em;'>".$title."</div></td></tr></table>";
    $blockcount = 1;
    echo "<table width='100%'>\n";
    echo "  <tr>\n";
    foreach($array as $k1 => $v1){
      echo "  <td width='$width' valign='top' class='outertable'>\n";
      $count = 1;
      //$width = 100/$blocks."%";
      echo "    <table width='$width' class='innertable'>\n";
      $division = $k1 + 1;
      echo "    <tr><td colspan='4'><p><strong>Division ".$division." (top ".$number.")</strong></p></td></tr>\n";
      //print_r($array);
      //echo $k1.": <br />";
      if (is_array($v1)) {
        foreach($v1 as $k2 => $v2){
          //echo $k2.": ".$v2."<br />";
          if (is_array($v2)) {
            foreach( $v2 as $k3 => $v3){
              if ($k3 == 'id')
                $id = $v3;
              if ($k3 == 'name')
                $name = $v3;
              if ($k3 == $on)
                {
                if ($on == 'skill')
                  $value = sprintf("%.1f",$v3);
                else
                  $value = $v3;
                }
              if ($k3 == 'ip')
              {
                $flag = $this->get_flag($v3);
                echo "    <tr><td>$count</td>\n";
                echo "    <td align=\"center\">".$flag."</td>\n";
                echo "    <td><a href='$link?func=player&playerid=$id'>$name</a></td>\n";
                echo "    <td>".$value."</td></tr>\n";
                $count += 1;
              }
              if ($count > $number)
                break;
            }
          }
        }
      }
      echo "    </table>\n";
      echo "  </td>\n";
      $fill = $blocks-$blockcount;
      $blockcount += 1;
      if ($blockcount > $blocks)
      {
        $blockcount = 1;
        echo "  </tr>\n  <tr>\n";
      }
    }
    if ($fill > 0 )
      for ($i = 0; $i < $fill; $i++)
        echo "<td>&nbsp;</td>\n";
    echo "  </tr>\n</table>\n";
  }
  
  function retrieve_players($limit = 0, $sortby = "skill", $direction = "DESC", $offset = 0, $name_length=10)
  {
    global $coddb;
    global $game;
    global $minkills;
    global $minrounds;
    global $t;
    global $maxdays;
    //global $separatorline;
    //global $MaxKillRatio;
    //global $ShowRatioGraph;
    //global $geoip_path;
    global $exclude_ban;
    //global $rss_sortby;
    global $currentconfignumber;
    global $text;
  
    $current_time = gmdate("U");

    $sortfields = array("kills", "deaths", "ratio", "skill", "rounds", "winstreak", "losestreak");
    $sortby = strtolower($sortby);
    if (!in_array($sortby, $sortfields))
      $sortby = $sortfields[0];
    if ( $direction != "ASC")
      $direction = "DESC";

    $rank = $offset + 1;
    $rank_step = ($direction == "ASC" ? -1 : 1);
    
    $query = "SELECT ${t['b3_clients']}.name, ${t['b3_clients']}.time_edit, ${t['players']}.id, kills, deaths, ratio, skill, winstreak, losestreak, rounds, fixed_name, ip
          FROM ${t['b3_clients']}, ${t['players']}
          WHERE (${t['b3_clients']}.id = ${t['players']}.client_id)
          AND ((${t['players']}.kills > $minkills)
            OR (${t['players']}.rounds > $minrounds))
          AND (${t['players']}.hide = 0)
          AND ($current_time - ${t['b3_clients']}.time_edit  < $maxdays*60*60*24)";
  
    if ($exclude_ban) {
      $query .= " AND ${t['b3_clients']}.id NOT IN (
        SELECT distinct(target.id)
        FROM ${t['b3_penalties']} as penalties, ${t['b3_clients']} as target
        WHERE (penalties.type = 'Ban' OR penalties.type = 'TempBan')
        AND inactive = 0
        AND penalties.client_id = target.id
        AND ( penalties.time_expire = -1 OR penalties.time_expire > UNIX_TIMESTAMP(NOW()) )
      )";
    }
    
      
    $query .= "ORDER BY $sortby $direction";
    if ($limit > 0)
      $query .= " LIMIT $offset, $limit";
              
    $result = $coddb->sql_query($query);
    $numRows = $coddb->sql_numrows($result);
  
    // build an array with all players and results
    $players = array();
    while ($row = $coddb->sql_fetchrow($result))
      {
      $name = $this->fix_name($name=$row['name'], $fixed_name=$row['fixed_name'], $name_length=$name_length);
      $players[] = array('id' => $row['id'], 'name' => $name, 'skill' => $row['skill'], 'kills' => $row['kills'], 'deaths' => $row['deaths'], 'ratio' => $row['ratio'], 'winstreak' => $row['winstreak'], 'losestreak' => $row['losestreak'], 'rounds' => $row['rounds'], 'fixed_name' => $row['fixed_name'], 'ip' => $row['ip'], 'time_edit' => $row['time_edit']);
      }
    
    return $players;
  }

  function fix_name($name, $fixed_name='', $name_length=10)
  {
    if ($fixed_name == '')
      unset($fixed_name);
    $name = htmlspecialchars(utf2iso($fixed_name ? $fixed_name : $name));
    if (strlen($name) > $name_length)
      $name = (substr($name, 0, $name_length) . '...');
    return $name;
  }

  function get_flag($ip)
  {
    global $geoip_path;

    if (file_exists($geoip_path."GeoIP.dat"))
    {
      $geocountry = $geoip_path."GeoIP.dat";
      //$ip = $row['ip'];
      $gi = geoip_open($geocountry,GEOIP_STANDARD);
      $countryid = strtolower (geoip_country_code_by_addr($gi, $ip));
      $country = geoip_country_name_by_addr($gi, $ip);
      if ( !is_null($countryid) and $countryid != "") 
        $flag = "<img src=\"images/flags/".$countryid.".gif\" title=\"".$country."\" alt=\"".$country."\">";
      else 
        $flag = "<img width=\"16\" height=\"11\" src=\"images/spacer.gif\" title=\"".$country."\" alt=\"".$country."\">"; 

      geoip_close($gi);
      return $flag;
    }
  }


  // sort an array on a key value (ie. on kills or ratio)
  // it prevents us from having to query the database again
  function array_sort($array, $on, $order=SORT_DESC)
  {
    $new_array = array();
    $sortable_array = array();
  
    if (count($array) > 0)
    {
      foreach ($array as $k => $v)
      {
        if (is_array($v))
        {
          foreach ($v as $k2 => $v2)
          {
            if ($k2 == $on)
              $sortable_array[$k] = $v2;
          }
        }
        else
          $sortable_array[$k] = $v;
      }
  
        switch ($order)
        {
          case SORT_ASC:
            asort($sortable_array);
          break;
          case SORT_DESC:
            arsort($sortable_array);
          break;
        }
  
        foreach ($sortable_array as $k => $v)
          $new_array[$k] = $array[$k];
    }
    return $new_array;
  }

  // this will filter an array based on a lower and upper value of a key
  // for instance to extract players within a skill range
  function filter_league($array, $on='skill', $lower=0, $upper=999999)
  {
    $new_array = array();
    $filterable_array = array();
  
    if (count($array) > 0)
    {
      foreach ($array as $k => $v)
      {
        if (is_array($v))
        {
          foreach ($v as $k2 => $v2)
          {
            if ($k2 == $on)
            {
              if ($v2 >= $lower && $v2 < $upper)  
                $filterable_array[$k] = $v2;
            }
          }
        } 
        else 
          $filterable_array[$k] = $v;
      }
  
      foreach ($filterable_array as $k => $v)
        $new_array[$k] = $array[$k];
    }
    return $new_array;
  }

  // this will devide an array into portions of the array nested in a new array.
  function limit_subleague($array, $limit=25)
  {
    $new_array = array();
    $start = 0;
    $nr = (int)count($array)/$limit;
    for ( $i = 0; $i <= $nr; $i++) 
    {
      $new_array[$i] = array_slice($array, $start, $start+$limit);
      $start += $limit;
    }
    return $new_array;
  }

}
//------------------------------------------------------------------------------

function show_leagues()
{
  // init the league class
  $league = new league;
  // retrieve an array with all playerinfo
  $playerbase = $league->retrieve_players();

  // copy the playerbase array
  $expertleague = $playerbase;
  // create a league by filtering a certain skill range
  $expertleague = $league->filter_league($expertleague, 'skill', 1400, 99999);
  $tmp = "<div style='float:right;font-style:italic;font-size:0.8em;'>(".count($expertleague)." players in this league)</div>";
  // Chop the league into divisions of # players
  $expertleague = $league->limit_subleague($expertleague, 10); // make this equal to the number of players drawn in a block to show all players in the league :) 
  // draw a block with a top # players of each division 
  $league->draw_blocks($expertleague, $title="Expert League", 10);
  echo $tmp;

  $premierleague = $playerbase;
  $premierleague = $league->filter_league($premierleague, 'skill', 1250, 1400);
  $tmp = "<div style='float:right;font-style:italic;font-size:0.8em;'>(".count($premierleague)." players in this league)</div>";
  $premierleague = $league->limit_subleague($premierleague, 25);
  $league->draw_blocks($premierleague, $title="Premier League");
  echo $tmp;

  $majorleague = $playerbase;
  $majorleague = $league->filter_league($majorleague, 'skill', 1100, 1250);
  $tmp = "<div style='float:right;font-style:italic;font-size:0.8em;'>(".count($majorleague)." players in this league)</div>";
  $majorleague = $league->limit_subleague($majorleague, 25);
  $league->draw_blocks($majorleague, $title="Major League");
  echo $tmp;

  $minorleague = $playerbase;
  $minorleague = $league->filter_league($minorleague, 'skill', 1000, 1100);
  $tmp = "<div style='float:right;font-style:italic;font-size:0.8em;'>(".count($minorleague)." players in this league)</div>";
  $minorleague = $league->limit_subleague($minorleague, 25);
  $league->draw_blocks($minorleague, $title="Minor League");
  echo $tmp;

  $motivationleague = $playerbase;
  $motivationleague = $league->filter_league($motivationleague, 'skill', 0, 1000);
  $tmp = "<div style='float:right;font-style:italic;font-size:0.8em;'>(".count($motivationleague)." players in this league)</div>";
  $motivationleague = $league->limit_subleague($motivationleague, 25);
  $league->draw_blocks($motivationleague, $title="Motivation League");
  echo $tmp;

  echo "<div style='font-size:0.7em;'>(Peak Mem usage: " . sprintf("%.2f",memory_get_peak_usage()/1024) . " Kb)</div>";
  
}

?>