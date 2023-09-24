<?php

require_once("vendor/autoload.php");
use \Ds\Stack;

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
$output_text_splitter = "\n=====================";

$undo_stack = new Stack();
$redo_stack = new Stack();

$default_journal_message = "Use the fields and buttons at the bottom of the page to begin searching through my gratitude journal :). I hope you're doing well, future self!";

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

// Wait to do this until I'm running it on my Linux server
// $undoStack  //initialized using PHP session
// $redoStack  //initialized using PHP session

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
    global $random_entry, $date_selection, $undo, $redo, $undo_stack, $redo_stack, $default_journal_message;

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
        if($undo_stack->isEmpty()){
            echo "$default_journal_message";
            return;
        }
        $command = $undo_stack->pop();
        $command->undo();
        $redo_stack->push($command);
        return;
    }
    if($request_type == $redo) {
        if($redo_stack->isEmpty()){
            echo "$default_journal_message";
            return;
        }
        $command = $redo_stack->pop();
        $command->redo();
        $undo_stack->push($command);
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
        return;
    }

    $command->execute();
    $undo_stack->push($command);
    $redo_stack->clear();
}

function main() {
    global $request_type_parameter, $previous_text_parameter, $undo_stack, $redo_stack, $output_text_splitter;

    session_start();
    if(isset($_SESSION['undo_stack'])){
        $undo_stack = unserialize($_SESSION['undo_stack']);
    }
    if(isset($_SESSION['redo_stack'])){
        $redo_stack = unserialize($_SESSION['redo_stack']);
    }

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

    // Output variables I use are not important when processing the previous text.
    $previous_text = explode($output_text_splitter, $previous_text)[0];

    handleRequest($request_type, $previous_text);

    $undo_stack_empty = $undo_stack->isEmpty() ? 'true' : 'false';
    $redo_stack_empty = $redo_stack->isEmpty() ? 'true' : 'false';
    echo "$output_text_splitter";
    echo "\n$undo_stack_empty";
    echo "\n$redo_stack_empty";

    $_SESSION['undo_stack'] = serialize($undo_stack);
    $_SESSION['redo_stack'] = serialize($redo_stack);
}

main();

?>
