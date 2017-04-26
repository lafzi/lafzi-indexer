<?php

$db_file = 'index/lafzi.sqlite';
if (file_exists($db_file)) unlink($db_file); // ensure freshness

$db = new SQLite3($db_file);

// INDICES

define('VOCAL_INDEX_TABLE_NAME', "vocal_index");
define('NONVOCAL_INDEX_TABLE_NAME', "nonvocal_index");
define('_ID', '_id');
define('TERM', "term");
define('POST', "post");
define('TERM_INDEX_NAME', "_term_index");

foreach(array(VOCAL_INDEX_TABLE_NAME, NONVOCAL_INDEX_TABLE_NAME) as $tableName) {

    echo "Creating table $tableName \n";

    $sql = "CREATE TABLE $tableName (" .
                _ID . " INTEGER PRIMARY KEY AUTOINCREMENT," .
                TERM . " TEXT," .
                POST . " TEXT)";

    $createIndexSql = "CREATE INDEX " . $tableName . TERM_INDEX_NAME .
            " ON " . $tableName . " (" . TERM . ")";

    $db->exec($sql);
    $db->exec($createIndexSql);

    echo "Inserting into table $tableName \n";

    $termlist_file = $tableName == VOCAL_INDEX_TABLE_NAME ? "index/index_termlist_vokal.txt" : "index/index_termlist_nonvokal.txt";
    $postlist_file = $tableName == VOCAL_INDEX_TABLE_NAME ? "index/index_postlist_vokal.txt" : "index/index_postlist_nonvokal.txt";

    $termlist = file($termlist_file, FILE_IGNORE_NEW_LINES);
    $postlist = file($postlist_file, FILE_IGNORE_NEW_LINES);

    $ct = count($termlist);
    $cp = count($postlist);
    assert($ct == $cp);

    for ($i = 0; $i < $ct; $i++) {
        $term = explode("|", $termlist[$i])[0];

        $posts = array();
        $posts_str = explode(";", $postlist[$i]);
        foreach ($posts_str as $p) {
            list($id, , $pos) = explode(":", $p); // not using frequency
            $posts[$id] = array_map('intval', explode(",", $pos));
        }
        $post = json_encode($posts);

        $sql = "INSERT INTO $tableName VALUES (NULL, '$term', '$post')";
        $db->exec($sql);
    }

}

$db->close();


