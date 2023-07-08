<?php

// Command interface:
/*
 * execute()
 * undo()
 * redo()
 */
interface Command {
    public function execute(): void;
    public function undo(): void;
    public function redo(): void;
}

// Concrete GetJournalEntry Command class
/*
 * Variables:
 * $parameters
 * $previousText
 * $newText
 * 
 * execute() {
 *   $this->newText = (invoke Python Script with parameters)
 *   updateJournalText($this->newText);
 * }
 * undo() {
 *   updateJournalText($this->previousText);
 * }
 * redo() {
 *   updateJournalText($this->newText);
 * }
 * 
 * private updateJournalText($text) {
 *   echo $text;
 * }
 */
class GetJournalEntry implements Command {
    private $parameters;
    private $previousText;
    private $newText;
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

?>