<html>
<body>

<?php

header('Access-Control-Allow-Origin: *');
$text = $_GET['text'];
passthru(`echo "$text" | ../../Fake_News_Detection/venv3.7/bin/python ../../Fake_News_Detection/prediction.py`);

?>

</body>
</html>
