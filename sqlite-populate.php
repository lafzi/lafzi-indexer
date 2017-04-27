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

// QURAN DATA

define('QURAN_TABLE_NAME', "ayat_quran");
define('SURAH_NO', "surah_no");
define('SURAH_NAME', "surah_name");
define('AYAT_NO', "ayat_no");
define('AYAT_ARABIC', "ayat_arabic");
define('AYAT_INDONESIAN', "ayat_indonesian");
define('AYAT_MUQATHAAT', "ayat_muqathaat");
define('VOCAL_MAPPING', "vocal_mapping_position");
define('NONVOCAL_MAPPING', "nonvocal_mapping_position");

$sql = "CREATE TABLE " . QURAN_TABLE_NAME . " (" .
        _ID . " INTEGER PRIMARY KEY AUTOINCREMENT," .
        SURAH_NO . " INTEGER," .
        SURAH_NAME . " TEXT," .
        AYAT_NO . " INTEGER," .
        AYAT_ARABIC . " TEXT," .
        AYAT_INDONESIAN . " TEXT," .
        AYAT_MUQATHAAT . " TEXT," .
        VOCAL_MAPPING . " TEXT," .
        NONVOCAL_MAPPING . " TEXT)";

echo "Creating table " . QURAN_TABLE_NAME . "\n";
$db->exec($sql);

$muqathaat_map = array();
foreach (file("data/quran_muqathaat.txt", FILE_IGNORE_NEW_LINES) as $line) {
    list ($no_surah, $nama_surah, $no_ayat, $teks) = explode('|', $line);
    $muqathaat_map[$no_surah][$no_ayat] = $teks;
}

$quran_teks = file("data/quran_teks.txt", FILE_IGNORE_NEW_LINES);
$quran_trans = file("data/trans-indonesian.txt", FILE_IGNORE_NEW_LINES);
assert(count($quran_teks) == count($quran_trans));

$posmap_vocal = file("index/mapping_posisi_vokal.txt", FILE_IGNORE_NEW_LINES);
$posmap_nonvocal = file("index/mapping_posisi.txt", FILE_IGNORE_NEW_LINES);
assert(count($posmap_vocal) == count($posmap_nonvocal));

$total = count($quran_teks);

echo "Inserting into table " . QURAN_TABLE_NAME . "\n";

for ($i = 0; $i < $total; $i++) {

    list ($no_surah, $nama_surah, $no_ayat, $ayat_arabic) = explode('|', $quran_teks[$i]);
    list (,, $ayat_trans) = explode('|', $quran_trans[$i]);
    if (isset($muqathaat_map[$no_surah][$no_ayat]))
        $ayat_muqathaat = $muqathaat_map[$no_surah][$no_ayat];
    else
        $ayat_muqathaat = "";

    $nama_surah = $db->escapeString($nama_surah);
    $ayat_trans = $db->escapeString($ayat_trans);

    $sql = "INSERT INTO " . QURAN_TABLE_NAME . " VALUES(
                NULL,
                '$no_surah',
                '$nama_surah',
                '$no_ayat',
                '$ayat_arabic',
                '$ayat_trans',
                '$ayat_muqathaat',
                '$posmap_vocal[$i]',
                '$posmap_nonvocal[$i]'
            )";

    $db->exec($sql);
    echo ".";

}

$db->close();


