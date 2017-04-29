<?php

/**
 * Script ini memproses data teks Quran dari tanzil (JSON)
 * menjadi bentuk teks dengan beberapa transformasi.
 *
 * Bismillah di awal surat dihilangkan supaya tidak masuk
 * hasil pencarian, kecuali awal al-fatihah.
 *
 * Huruf muqatha'at ditransformasi jadi bacaannya,
 * sementara aslinya dibuat di file terpisah.
 *
 */

$muqathaat = [
    "2 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "3 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "7 1" => ["الٓمٓصٓ", "أَلِفْ لَامْ مِيمْ صَادْ"],
    "10 1" => ["الٓر", "أَلِفْ لَامْ رَا"],
    "11 1" => ["الٓر", "أَلِفْ لَامْ رَا"],
    "12 1" => ["الٓر", "أَلِفْ لَامْ رَا"],
    "13 1" => ["الٓمٓر", "أَلِفْ لَامْ مِيمْ رَا"],
    "14 1" => ["الٓر", "أَلِفْ لَامْ رَا"],
    "15 1" => ["الٓر", "أَلِفْ لَامْ رَا"],
    "19 1" => ["كٓهيعٓصٓ", "كَافْ هَا يَا عَيْنْ صَادْ"],
    "20 1" => ["طه", "طَاهَا"],
    "26 1" => ["طسٓمٓ", "طَا سِينْ مِيمْ"],
    "27 1" => ["طسٓ", "طَا سِينْ"],
    "28 1" => ["طسٓمٓ", "طَا سِينْ مِيمْ"],
    "29 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "30 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "31 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "32 1" => ["الٓمٓ", "أَلِفْ لَامْ مِيمْ"],
    "36 1" => ["يسٓ", "يَاسِينْ"],
    "38 1" => ["صٓ", "صَادْ"],
    "40 1" => ["حمٓ", "حَامِيمْ"],
    "41 1" => ["حمٓ", "حَامِيمْ"],
    "42 1" => ["حمٓ", "حَامِيمْ"],
    "42 2" => ["عٓسٓقٓ", "عَيْنْ سِينْ قَافْ"],
    "43 1" => ["حمٓ", "حَامِيمْ"],
    "44 1" => ["حمٓ", "حَامِيمْ"],
    "45 1" => ["حمٓ", "حَامِيمْ"],
    "46 1" => ["حمٓ", "حَامِيمْ"],
    "50 1" => ["قٓ", "قَافْ"],
    "68 1" => ["نٓ", "نُونْ"]
];

function remove_basmalah($str) {
    $basmalah = array("بِسْمِ ٱللَّهِ ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ ", "بِّسْمِ ٱللَّهِ ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ ");
    return str_replace($basmalah, "", $str);
}

$quran_data = json_decode(file_get_contents("tanzil/quran.json"));
$surat_data = json_decode(file_get_contents("tanzil/surat.json"));

$out_quran = fopen("data/quran_teks.txt", "wb");
$out_muqat = fopen("data/quran_muqathaat.txt", "wb");
$out_trans = fopen("data/trans-indonesian.txt", "wb");

$num_ayat = count($quran_data->quran);
$num_surat = count($surat_data);

$i = 0;
for ($surat_no = 1; $surat_no < $num_surat; $surat_no++) {
    list($start_idx, $length, , , , $surat_name) = $surat_data[$surat_no];

    $ayat_no = 1;
    for ($i = $start_idx; $i < $start_idx + $length; $i++) {
        $quran_text = $quran_data->quran[$i];
        $trans_text = $quran_data->trans[$i];

        if ($ayat_no == 1 && $surat_no != 1) {
            $quran_text = remove_basmalah($quran_text);
        }

        if (array_key_exists("$surat_no $ayat_no", $muqathaat)) {
            fwrite($out_muqat, $surat_no . "|" . $surat_name . "|" . $ayat_no . "|" . $quran_text);
            $quran_text = str_replace($muqathaat["$surat_no $ayat_no"][0], $muqathaat["$surat_no $ayat_no"][1], $quran_text);
        }

        fwrite($out_quran, $surat_no . "|" . $surat_name . "|" . $ayat_no . "|" . $quran_text);
        fwrite($out_trans, $surat_no . "|" . $ayat_no . "|" . $trans_text);

        $ayat_no++;
    }

}

fclose($out_quran);
fclose($out_trans);




