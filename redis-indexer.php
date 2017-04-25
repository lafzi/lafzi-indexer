<?php

// mengindeks korpus, versi flat file

// profiling
$time_start = microtime(true);

include 'lib/trigram.php';
include 'lib/predis/autoload.php';
Predis\Autoloader::register();

$redis = new Predis\Client();

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
    $key_prefix = "vocal-";

} else {

    $doc_file = "index/fonetik.txt";
    $key_prefix = "nonvocal-";

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
    
    /*
     * Resolving ambiguitas (https://github.com/lafzi/lafzi-web/issues/1)
     * Jika ada trigram "AXI" atau "AXU", maka tambahkan trigram baru:
     * - AXI ==> AY + karakter setelah I
     * - AXU ==> AW + karakter setelah U
     *
     * Begitu juga sebelum dan sesudahnya.
     * Misalnya "BIMAXUNZILA" akan ada trigram baru: MAW, AWN, WNZ
     * Dokumen tidak dimofidikasi. Cuma relevan buat yg bervokal.
     */

    $new_trigrams = array();

    foreach(array("AXI" => "AY", "AXU" => "AW") as $amb => $rep) {
        if (isset($trigrams[$amb])) {
            $poss = $trigrams[$amb][1];
            foreach ($poss as $p) {
                $nextchar = $text[$p + 2];
                $trigram  = $rep . $nextchar;
                if (!isset($new_trigrams[$trigram]))
                    $new_trigrams[$trigram] = array($p);
                else
                    $new_trigrams[$trigram][] = $p;

                // setelah setelahnya
                if (isset($text[$p + 3]) && $text[$p + 3] != "\n") { // tidak dijamin ada
                    $nextchar2 = $text[$p + 3];
                    $trigram  = $rep[1] . $nextchar . $nextchar2;
                    if (!isset($new_trigrams[$trigram]))
                        $new_trigrams[$trigram] = array($p + 2);
                    else
                        $new_trigrams[$trigram][] = $p + 2;
                }

                // sebelum yang ambigu
                $prevchar = $text[$p - 2];
                $trigram  = $prevchar . $rep;
                if (!isset($new_trigrams[$trigram]))
                    $new_trigrams[$trigram] = array($p - 1);
                else
                    $new_trigrams[$trigram][] = $p - 1;
            }
        }
    }



    foreach($new_trigrams as $trigram => $poss) {
        if (!isset($trigrams[$trigram]))
            $trigrams[$trigram] = array(count($poss), $poss); // freq, pos
        else {
            // kalau sudah ada
            $trigrams[$trigram][0] += count($poss); // freq ditambah
            $trigrams[$trigram][1]  = array_merge($trigrams[$trigram][1], $poss); // pos di-merge
        }
    }

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

// urutkan key pada array indeks
ksort($index);

// untuk setiap term pada indeks
foreach ($index as $term => $postings) {

    // set ke Redis
    $key = $key_prefix.$term;
    $redis->set($key, json_encode($postings));

}

// selesai, hapus index di memory
unset($index);

echo "OK\n";

// hasil profiling waktu eksekusi
$time_end = microtime(true);
$time = $time_end - $time_start;
 
echo "\nTerindeks dalam $time detik\n";
echo "Memory peak usage : " . memory_get_peak_usage() . "\n\n";
