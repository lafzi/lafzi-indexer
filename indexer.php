<?php

// mengindeks korpus, versi flat file

// profiling
$time_start = microtime(true);

include 'lib/trigram.php';

if ($argc == 1) { echo 'Tambahkan argumen "V" atau "NV"' . "\n"; exit(1); }

if ($argv[1] == "V")
    $bervokal = true;
else if ($argv[1] == "NV")
    $bervokal = false;
else {
    echo 'Tambahkan argumen "V" atau "NV"' . "\n";
    exit(1);
}

if ($bervokal) {
    $doc_file = "index/fonetik_vokal.txt";

    $term_list_file    = "index/index_termlist_vokal.txt";
    $posting_list_file = "index/index_postlist_vokal.txt";

} else {
    $doc_file = "index/fonetik.txt";

    $term_list_file    = "index/index_termlist_nonvokal.txt";
    $posting_list_file = "index/index_postlist_nonvokal.txt";

}

// tahap I : mengekstrak seluruh term dari seluruh dokumen dan membangun indeks
echo "Tahap I ... "; 
 
// baca seluruh dokumen
$docs = file($doc_file);
$docs_count = count($docs);

// array besar penyimpan indeks
$index = array();

$limit = 8000;
$i = 1;

// untuk setiap dokumen
foreach ($docs as $doc) {
    
    // dipecah pada karakter |
    list($id, $text) = explode("|", $doc);
    
    //echo "Memproses dokumen $id : ";
        
    // ekstrak trigram
    $trigrams = trigram_frekuensi_posisi_all($text);
    
    foreach ($trigrams as $trigram => $fp) {
        
        // $fp[0] = frekuensi, $fp[1] = posisi trigram
        list($freq, $pos) = $fp;
        
        // masukkan entri ke array indeks
        $index[$trigram][] = array($id, $freq, $pos);
        
    }
    
    //echo "OK\n";
    //echo "(". round($id/$docs_count*100) ."%)";
    //echo "\n";
    
    if ($i >= $limit) break;
    $i++;
    
}

echo "OK\n";

unset($docs);

// tahap II : menulis inverted index
echo "Tahap II ... ";

// siapkan file untuk ditulisi
$fh_index    = fopen($term_list_file, "w");
$fh_postlist = fopen($posting_list_file, "w");

$offset = 0;

// urutkan key pada array indeks
ksort($index);

// untuk setiap term pada indeks
foreach ($index as $term => $postings) {
    
    $posting_list = array();
    $posting_list_string = "";
    
    // setiap value indeks adalah beberapa posting
    foreach ($postings as $posting) {
        
        // format id:frekuensi:posisi
        list($id, $freq, $pos) = $posting;
        $posting_string = "$id:$freq:" . implode(",", $pos);
        $posting_list[] = $posting_string;
        
    }
    
    $posting_list_string = implode(";", $posting_list);
 
    // tulis ke file
    fwrite($fh_index, $term."|".$offset."\n");
    fwrite($fh_postlist, $posting_list_string."\n");
    
    // offset indeks term untuk dicari di file posting list
    $offset += strlen($posting_list_string) + 1;
}

// selesai, hapus index di memory
unset($index);
fclose($fh_index);
fclose($fh_postlist);

echo "OK\n";

// hasil profiling waktu eksekusi
$time_end = microtime(true);
$time = $time_end - $time_start;
 
echo "\nTerindeks dalam $time detik\n";
echo "Memory peak usage : " . memory_get_peak_usage() . "\n\n";
echo "File disimpan di:\n";
echo "- $term_list_file\n";
echo "- $posting_list_file\n\n";