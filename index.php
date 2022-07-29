<?php

class GISMeteo {
    public $name = "Плавающая средняя температура";
    public $url = "https://www.gismeteo.ru/diary/"; // URL gismeteo
    public $region_id = 167410; // Москва, Пушкино
    public $region_name = "Москва, Пушкино";
    public $year = 2021; // Год
    public $morning_or_evening = 1; // EVENING = 0, MORNING = 1
    public $array_after_parsing = Array();
    public $average_data_by_days = Array();
    public $average_data_by_weeks = Array();
    public $average_data_by_months = Array();

function parse_one_page($month){

  $result = Array();
  $html = file_get_contents($this->url.$this->region_id.'/'.$this->year.'/'.$month);
  $pattern = "/<td class='first_in_group.*?'>.*?<\/[\s]*td>/s";
  preg_match_all($pattern, $html, $matches);
  $count = 0;
  $count_by_evening_or_morning = 0;
  $intval = 0;
  foreach($matches[0] as $val){
    $count++;
    if($count % 2 == $this->morning_or_evening){
      $count_by_evening_or_morning ++;
      $result[$count_by_evening_or_morning] = (int)  strip_tags($val);
    }
  }
  return $result;
}

function parse_by_months(){
  $result = Array();
  for($i=1; $i<=12; $i++){
    $result[$i] = $this->parse_one_page($i);
  }
  return $result;
}



function get_average_data_by_days(){
    $count = 0;
    $avg = 0;
    $temperature_summ = 0;
    foreach($this->array_after_parsing as $month => $days_of_month){
      foreach($days_of_month as $day => $temperature){
        $temperature_summ += $temperature;
        $avg = $temperature_summ / ($count + 1);
        $this->average_data_by_days[$count] = Array(sprintf("%02s", $day)."-".sprintf("%02s", $month)."-".$this->year, $temperature, $avg);
        $count++;
      }

    }
}


function get_average_data_by_weeks(){
    $avg = 0;
    $temperature_summ = 0;
    $num_of_week = 0;
    $temperature_summ_in_week = 0;
    $count_in_tail = 0;
    $temperature_summ_in_tail = 0;
    $avg_in_tail = 0;
    foreach($this->array_after_parsing as $month => $days_of_month){
      foreach($days_of_month as $day => $temperature){
        $temperature_summ_in_week += $temperature;
        if(date('w', strtotime($day."-".$month."-".$this->year)) == 0){

          $num_of_week++;

          $avg_in_week = $temperature_summ_in_week / 7;
          $avg += $avg_in_week;
          $this->average_data_by_weeks[$num_of_week] = Array($num_of_week, $avg_in_week, $avg/$num_of_week);
          $temperature_summ_in_week = $temperature;
        }

        if($num_of_week == 51){
          if ($count_in_tail == 0){
            $count_in_tail++;

          }else{
            $count_in_tail++;
            $temperature_summ_in_tail += $temperature;

            if($day == 31){
              $avg_in_tail = $temperature_summ_in_tail/($count_in_tail-1);
              $avg += $avg_in_tail;
              $this->average_data_by_weeks[52] = Array(52, $avg_in_tail, $avg/52);
            }

          }

        }



      }

    }
}

function get_average_data_by_months(){
    $count_months = 0;
    $avg = 0;
    $temperature_summ = 0;
    $firstday = date('w', strtotime("01-01-".$this->year));
    $old_month = 1;
    $avg_in_month = 0;
    foreach($this->array_after_parsing as $month => $days_of_month){
      $temperature_summ_in_month = 0;
      $count_months++;
      $avg_in_month = 0;
      foreach($days_of_month as $day => $temperature){
        if ($old_month == $month){
          $temperature_summ_in_month  += $temperature;
          $avg_in_month = $temperature_summ_in_month / $day;
        }else{
          $old_month = $month;
          $temperature_summ += $temperature_summ_in_month;
          $temperature_summ_in_month = $temperature;
        }

      }
      $avg += $avg_in_month;
      $months_name = date("F", strtotime(sprintf("%02s", $day)."-".sprintf("%02s", $month)."-".$this->year));
      $this->average_data_by_months[$months_name] = Array($months_name, $avg_in_month, $avg/$month);


    }
}


function draw_header(){
  $echo = "<h1>".$this->name.", ".$this->region_name.", ".$this->year." г.</h1>";
  return $echo;
}

function draw_table($data, $header = null){
  $echo = "<hr><table>";
  if (is_array($header) && count($header) == 3)
  {
    $echo .= "<tr><td>".$header[0]."</td><td>".$header[1]."</td><td>".$header[2]."</td></tr>";
  }
  foreach($data as $key => $val){
    $echo .= "<tr>";
    foreach($val as $key2 => $val2){
      $echo .= "<td style='border: 1px solid black'>".$val2."</td>";
    }
    $echo .= "</tr>";
  }
  $echo .= "</table><hr>";
  return $echo;
}

function run(){
  $this->array_after_parsing = $this->parse_by_months();
  $this->get_average_data_by_days();
  $this->get_average_data_by_months();
  $this->get_average_data_by_weeks();
  echo $this->draw_header();
  echo $this->draw_table($this->average_data_by_days, Array('День', 'Температура', 'Скользящее среднее'));
  echo $this->draw_table($this->average_data_by_weeks, Array('Номер недели', 'Средняя температура за неделю', 'Скользящее среднее'));
  echo $this->draw_table($this->average_data_by_months, Array('Месяц', 'Средняя температура за месяц', 'Скользящее среднее'));
}

}



$gis = new GISMeteo();
$gis->run();



?>
