<?php

if(isset($_POST['date'])){
    $date = $_POST['date'];
}

$parameters = "date_selection";

if(isset($date) && "" != trim($date)){
    $parameters = $parameters . " --date " . $date;
}

passthru("../gratitude_journal_analysis/env/bin/python ../gratitude_journal_analysis/src/print_journal_entries.py $parameters");

?>