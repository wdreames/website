<?php

// use \Ds\Stack;

// global variables
$random_entry = "random_entry";
$date_selection = "date_selection";
$undo = "undo";
$redo = "redo";
$request_type_parameter = "request_type";
$previous_text_parameter = "previous_text";
$keywords_parameter = "keywords";
$start_date_parameter = "start_date";
$end_date_parameter = "end_date";
$date_parameter = "date";

interface Command {
    public function execute(): void;
    public function undo(): void;
    public function redo(): void;
}

class GetJournalEntry implements Command {
    private $parameters;
    private $previousText;
    private $newText;

    public function __construct(string $parameters, string $previousText) {
        $this->parameters = $parameters;
        $this->previousText = $previousText;
        $this->newText = "";
    }

    private function updateJournalText($text): void {
        echo $text;
    }

    private function invokePythonScript(): string {
        return shell_exec("../gratitude_journal_analysis/env/bin/python ../gratitude_journal_analysis/src/print_journal_entries.py $this->parameters");
    }

    public function execute(): void {
        $this->newText = $this->invokePythonScript();
        $this->updateJournalText($this->newText);
    }

    public function undo(): void {
        $this->updateJournalText($this->previousText);
    }

    public function redo(): void {
        $this->updateJournalText($this->newText);
    }
}

// Main
/*
 * Variables:
 * $undoStack  //initialized using PHP session
 * $redoStack  //initialized using PHP session
 * 
 * Obtain data from the POST request
 *   POST variables to expect:
 *   requestType: (random_entry | specific_date | undo | redo)
 *   based on requestType:
 *     if random_entry:
 *       $keywords
 *       $start_date
 *       $end_date
 *       $command = new Command($parameters);
 *     elif specific_date:
 *       $date
 *       $command = new Command($parameters);
 *     elif undo:
 *       $command = $undoStack.pop();
 *       $command.undo();
 *       $redoStack.push($command);
 *     elif redo:
 *       $command = redoStack.pop();
 *       $command.redo();
 *       $undoStack.push($command);
 *     else:
 *       // raise error
 * 
 * if not undo and not redo:
 *   // execute $command
 *   // add $command to $undoStack
 *   // wipe the $redoStack
 */

// Wait to do this until I'm running it on my Linux server
// $undoStack = new \Ds\Stack();
// $redoStack = new \Ds\Stack();

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

function getRandomEntryCommand($previous_text) {
    global $random_entry, $keywords_parameter, $start_date_parameter, $end_date_parameter;

    $parameters = $random_entry;

    if(isset($_POST[$keywords_parameter])){ 
        $keywords = surroundKeywordsInQuotes($_POST[$keywords_parameter]);
    }
    if(isset($_POST[$start_date_parameter])){
        $start_date = $_POST[$start_date_parameter];
    }
    if(isset($_POST[$end_date_parameter])){
        $end_date = $_POST[$end_date_parameter];
    }    
    
    if(isset($keywords) && "" != trim($keywords)){
        $parameters = $parameters . " --keywords " . $keywords;
    }
    if(isset($start_date) && "" != trim($start_date)){
        $parameters = $parameters . " --start_date " . $start_date;
    }
    if(isset($end_date) && "" != trim($end_date)){
        $parameters = $parameters . " --end_date " . $end_date;
    }

    return new GetJournalEntry($parameters, $previous_text);
}

function getDateSelectionCommand($previous_text) {
    global $date_selection, $date_parameter;

    $parameters = $date_selection;

    if(isset($_POST[$date_parameter])){
        $date = $_POST[$date_parameter];
    }

    if(isset($date) && "" != trim($date)){
        $parameters = $parameters . " --date " . $date;
    }

    return new GetJournalEntry($parameters, $previous_text);
}

function handleRequest($request_type, $previous_text) {
    global $random_entry, $date_selection, $undo, $redo;

    $request_type_options = [
        $random_entry,
        $date_selection,
        $undo,
        $redo
    ];

    $request_type_options_str = implode(', ', $request_type_options);
    $error_str = "ERROR: Invalid request type: '$request_type'. Available options are: $request_type_options_str.";
    if(!in_array($request_type, $request_type_options)) {
        echo "$error_str";
        return;
    }

    if($request_type == $undo) {
        echo "undo placeholder";
        return;
    }
    if($request_type == $redo) {
        echo "redo placeholder";
        return;
    }

    if($request_type == $random_entry) {
        $command = getRandomEntryCommand($previous_text);
    }
    elseif($request_type == $date_selection) {
        $command = GetDateSelectionCommand($previous_text);
    }
    else {
        echo "$error_str";
    }

    $command->execute();
    // add $command to $undoStack
    // clear $redoStack
}

function main() {
    global $request_type_parameter, $previous_text_parameter;

    if(isset($_POST[$request_type_parameter])){
        $request_type = $_POST[$request_type_parameter];
    }
    else {
        $request_type = "";
    }
    
    if(isset($_POST[$previous_text_parameter])){
        $previous_text = $_POST[$previous_text_parameter];
    }
    else {
        $previous_text = "";
    }

    handleRequest($request_type, $previous_text);
}

main();

?>