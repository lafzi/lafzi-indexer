<?php

// membuat korpus, versi flat file

// profiling
$time_start = microtime(true);

include 'lib/fonetik.php';

if ($argc == 1) { echo 'Tambahkan argumen "V" atau "NV"' . "\n"; exit(1); }

if ($argv[1] == "V")
    $bervokal = true;
else if ($argv[1] == "NV")
    $bervokal = false;
else {
    echo 'Tambahkan argumen "V" atau "NV"' . "\n";
    exit(1);
}

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

$limit = 8000;
$i = 1;

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

    if ($i >= $limit) break;
    $i++;

}

fclose($f);
fclose($fm);

echo 'Total : ' . $count;
echo "\n\n";

// hasil profiling waktu eksekusi
$time_end = microtime(true);
$time = $time_end - $time_start;
 
echo "\nDiproses dalam $time detik\n";
echo "File disimpan di:\n";
echo "- $target_file\n";
echo "- $mapping_file\n\n";
