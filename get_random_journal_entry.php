<?php

function surroundKeywordsInQuotes($keywordString) {
    $keywordArray = explode(" ", $keywordString);
    $keywordArrayWithQuotes = array_map(
        function ($word) {
            return '"' . $word . '"';
        }, 
        $keywordArray
    );
    return implode(" ", $keywordArrayWithQuotes);
}

if(isset($_POST['keywords'])){ 
    $keywords = surroundKeywordsInQuotes($_POST['keywords']);
}
if(isset($_POST['start_date'])){
    $start_date = $_POST['start_date'];
}
if(isset($_POST['end_date'])){
    $end_date = $_POST['end_date'];
}

$parameters = "random_entry";

if(isset($keywords) && "" != trim($keywords)){
    $parameters = $parameters . " --keywords " . $keywords;
}
if(isset($start_date) && "" != trim($start_date)){
    $parameters = $parameters . " --start_date " . $start_date;
}
if(isset($end_date) && "" != trim($end_date)){
    $parameters = $parameters . " --end_date " . $end_date;
}

passthru("../gratitude_journal_analysis/env/bin/python ../gratitude_journal_analysis/src/print_journal_entries.py $parameters");

?>