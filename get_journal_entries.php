<?php

require_once("vendor/autoload.php");
use \Ds\Stack;

// global variables
$authentication_test = "authentication_test";
$random_entry = "random_entry";
$date_selection = "date_selection";
$undo = "undo";
$redo = "redo";
$request_type_parameter = "request_type";
$previous_text_parameter = "previous_text";
$query_parameter = "keywords";
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

function getRandomEntryCommand($previous_text) {
    global $random_entry, $query_parameter, $start_date_parameter, $end_date_parameter;

    $parameters = $random_entry;

    if (isset($_POST[$query_parameter])){ 
        $query = '"' . $_POST[$query_parameter] . '"';
    }
    if (isset($_POST[$start_date_parameter])){
        $start_date = $_POST[$start_date_parameter];
    }
    if (isset($_POST[$end_date_parameter])){
        $end_date = $_POST[$end_date_parameter];
    }    
    
    if (isset($query) && "" != trim($query)){
        $parameters = $parameters . " --query " . $query;
    }
    if (isset($start_date) && "" != trim($start_date)){
        $parameters = $parameters . " --start_date " . $start_date;
    }
    if (isset($end_date) && "" != trim($end_date)){
        $parameters = $parameters . " --end_date " . $end_date;
    }

    return new GetJournalEntry($parameters, $previous_text);
}

function getDateSelectionCommand($previous_text) {
    global $date_selection, $date_parameter;

    $parameters = $date_selection;

    if (isset($_POST[$date_parameter])){
        $date = $_POST[$date_parameter];
    }

    if (isset($date) && "" != trim($date)){
        $parameters = $parameters . " --date " . $date;
    }

    return new GetJournalEntry($parameters, $previous_text);
}

function handleRequest($request_type, $previous_text) {
    global $authentication_test, $random_entry, $date_selection, $undo, $redo, $undo_stack, $redo_stack, $default_journal_message;

    $request_type_options = [
        $authentication_test,
        $random_entry,
        $date_selection,
        $undo,
        $redo
    ];

    $request_type_options_str = implode(', ', $request_type_options);
    $error_str = "ERROR: Invalid request type: '$request_type'. Available options are: $request_type_options_str.";
    if (!in_array($request_type, $request_type_options)) {
        echo "$error_str";
        http_response_code(400);
        return;
    }

    http_response_code(200);
    if ($request_type == $authentication_test) {
        echo "Authentication successful.";
        return;
    }
    if ($request_type == $undo) {
        if ($undo_stack->isEmpty()){
            echo "$default_journal_message";
            return;
        }
        $command = $undo_stack->pop();
        $command->undo();
        $redo_stack->push($command);
        return;
    }
    if ($request_type == $redo) {
        if ($redo_stack->isEmpty()){
            echo "$default_journal_message";
            return;
        }
        $command = $redo_stack->pop();
        $command->redo();
        $undo_stack->push($command);
        return;
    }

    if ($request_type == $random_entry) {
        $command = getRandomEntryCommand($previous_text);
    }
    elseif ($request_type == $date_selection) {
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

    // TODO: Make this better
    $MAX_NUM_ATTEMPTS = 3;
    $num_failed_attempts = 0;
    if (isset($_SESSION['num_failed_attempts'])) {
        $num_failed_attempts = unserialize($_SESSION['num_failed_attempts']);
    }
    if ($num_failed_attempts >= $MAX_NUM_ATTEMPTS) {
        echo "ERROR: Too many failed authentication attempts.";
        http_response_code(429);
        exit;
    }

    // Use `echo -n '{token}' > ../.gratitude-token` to set the token value
    $pass_file = fopen('../.gratitude-token', 'r');
    $expected_hash = fread($pass_file, filesize('../.gratitude-token'));
    fclose($pass_file);

    if (!isset($_POST['token'])) {
        echo "ERROR: Unauthorized access. No token was provided.";
        http_response_code(401);
        exit;
    }
    if (!password_verify($_POST['token'], $expected_hash)) {
        echo "ERROR: Unauthorized access. Token was invalid.";
        $num_failed_attempts += 1;
        $_SESSION['num_failed_attempts'] = serialize($num_failed_attempts);
        http_response_code(401);
        exit;
    }
    
    if (isset($_SESSION['undo_stack'])){
        $undo_stack = unserialize($_SESSION['undo_stack']);
    }
    if (isset($_SESSION['redo_stack'])){
        $redo_stack = unserialize($_SESSION['redo_stack']);
    }

    if (isset($_POST[$request_type_parameter])){
        $request_type = $_POST[$request_type_parameter];
    }
    else {
        $request_type = "";
    }
    
    if (isset($_POST[$previous_text_parameter])){
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
