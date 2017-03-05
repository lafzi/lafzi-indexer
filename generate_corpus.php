<?php

// membuat korpus, versi flat file

// profiling
$time_start = microtime(true);

error_reporting(E_ALL & ~E_NOTICE);

include 'lib/fonetik.php';

$bervokal = true;

// baca file, satu baris disimpan dalam satu array
$docs = file('data/quran_teks.txt');

$count = 0;
$id = 1;

if ($bervokal) {
    $target_file = "index/fonetik_vokal.txt";
    $mapping_file = "index/mapping_posisi_vokal.txt";
} else {
    $target_file = "index/fonetik.txt";
    $mapping_file = "index/mapping_posisi.txt";
}

$f = fopen($target_file, "w");
$fm = fopen($mapping_file, "w");

foreach ($docs as $doc) {
    
    // split pada karakter "|"
    // [0] = nomor surat
    // [1] = nama surat
    // [2] = nomor ayat
    // [3] = teks ayat
    $data = mb_split("\|", $doc);
    
    $fonetik = ar_fonetik($data[3], !$bervokal);
    $mapping_posisi = map_reduksi_ke_asli($data[3], !$bervokal);
    
    fwrite($f, $id."|".$fonetik."\n");
    fwrite($fm, implode(",", $mapping_posisi) ."\n");

    echo $id . ". Diproses surah {$data[0]} ayat {$data[2]}\n";
    $count++;
    $id++;
    
}

fclose($f);
fclose($fm);

echo 'Total : ' . $count;
echo "\n\n";

// hasil profiling waktu eksekusi
$time_end = microtime(true);
$time = $time_end - $time_start;
 
echo "\nDiproses dalam $time detik\n";
