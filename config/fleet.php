<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Standar Plat Nomor Kendaraan (TNKB Indonesia)
    |--------------------------------------------------------------------------
    |
    | Format standar berdasarkan regulasi Kepolisian RI (Perpol No. 7 Tahun 2021).
    | Terdiri dari 1-2 huruf kode wilayah, 1-4 digit angka nomor polisi (tanpa
    | awalan 0), dan 1-4 huruf seri belakang.
    |
    */
    'plate' => [
        'regex' => '^[A-Z]{1,2}\s[1-9][0-9]{0,3}\s[A-Z]{1,4}$',
        'example' => 'B 1234 XYZ',
        'help_text' => 'Format: [1-2 Huruf Wilayah] [1-4 Angka] [1-4 Huruf Seri]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Kode Wilayah Registrasi Samsat se-Indonesia
    |--------------------------------------------------------------------------
    |
    | Daftar kode abjad tanda nomor kendaraan bermotor per wilayah administratif.
    |
    */
    'plate_regions' => [
        'B' => 'DKI Jakarta, Depok, Tangerang, Bekasi (Jadetabek)',
        'D' => 'Bandung, Cimahi',
        'F' => 'Bogor, Cianjur, Sukabumi',
        'E' => 'Cirebon, Indramayu, Majalengka, Kuningan',
        'Z' => 'Garut, Tasikmalaya, Ciamis, Banjar, Pangandaran',
        'T' => 'Purwakarta, Karawang, Subang',
        'A' => 'Banten (Serang, Cilegon, Pandeglang, Lebak)',
        'H' => 'Semarang, Salatiga, Kendal, Demak',
        'G' => 'Pekalongan, Tegal, Brebes, Batang, Pemalang',
        'K' => 'Pati, Kudus, Jepara, Rembang, Blora, Grobogan',
        'AA' => 'Kedu (Magelang, Kebumen, Purworejo, Wonosobo, Temanggung)',
        'AB' => 'DI Yogyakarta (Yogyakarta, Sleman, Bantul, Gunungkidul)',
        'AD' => 'Surakarta / Solo Raya (Boyolali, Klaten, Sukoharjo, Wonogiri, Karanganyar, Sragen)',
        'L' => 'Surabaya',
        'W' => 'Sidoarjo, Gresik',
        'N' => 'Malang, Pasuruan, Probolinggo, Lumajang, Batu',
        'P' => 'Besuki (Jember, Banyuwangi, Bondowoso, Situbondo)',
        'AG' => 'Kediri, Blitar, Tulungagung, Nganjuk, Trenggalek',
        'AE' => 'Madiun, Ngawi, Magetan, Ponorogo, Pacitan',
        'M' => 'Madura (Bangkalan, Sampang, Pamekasan, Sumenep)',
        'DK' => 'Bali (Denpasar, Badung, Gianyar, Tabanan, Buleleng)',
        'DR' => 'Lombok, Mataram',
        'DH' => 'Timor, Kupang, Rote Ndao',
        'KB' => 'Kalimantan Barat (Pontianak)',
        'DA' => 'Kalimantan Selatan (Banjarmasin, Banjarbaru)',
        'KH' => 'Kalimantan Tengah (Palangkaraya)',
        'KT' => 'Kalimantan Timur (Samarinda, Balikpapan)',
        'KU' => 'Kalimantan Utara (Tarakan)',
        'DB' => 'Sulawesi Utara (Manado, Minahasa, Tomohon)',
        'DN' => 'Sulawesi Tengah (Palu)',
        'DT' => 'Sulawesi Tenggara (Kendari)',
        'DD' => 'Sulawesi Selatan (Makassar, Gowa, Maros)',
        'DC' => 'Sulawesi Barat (Mamuju)',
        'BA' => 'Sumatera Barat (Padang, Bukittinggi)',
        'BK' => 'Sumatera Utara Bagian Timur (Medan, Binjai, Deli Serdang)',
        'BB' => 'Sumatera Utara Bagian Barat (Tapanuli, Sibolga)',
        'BL' => 'Aceh (Banda Aceh)',
        'BM' => 'Riau (Pekanbaru, Dumai)',
        'BP' => 'Kepulauan Riau (Batam, Tanjungpinang, Bintan)',
        'BG' => 'Sumatera Selatan (Palembang)',
        'BN' => 'Bangka Belitung (Pangkalpinang)',
        'BE' => 'Lampung (Bandar Lampung)',
        'BD' => 'Bengkulu',
        'BH' => 'Jambi',
        'PA' => 'Papua (Jayapura)',
        'PB' => 'Papua Barat (Manokwari, Sorong)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Batas Reminder Dokumen Legalitas (KIR & STNK)
    |--------------------------------------------------------------------------
    |
    | Jumlah sisa hari menuju jatuh tempo dokumen untuk memicu notifikasi peringatan.
    |
    */
    'reminder_threshold_days' => [30, 14, 7],

    /*
    |--------------------------------------------------------------------------
    | Daftar Pengemudi / Driver Default Armada (P2H)
    |--------------------------------------------------------------------------
    |
    | Daftar nama supir operasional yang dapat dipilih langsung atau diketik
    | manual pada form pemeriksaan P2H.
    |
    */
    'drivers' => [
        'Bambang supriyanto',
        'Khumedi',
        'Ismail',
        'Eka',
        'Andrico',
        'Azis',
        'Hansel',
        'Rendi',
        'Endang',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar Pembuat / Inspektor P2H Default (Future Config)
    |--------------------------------------------------------------------------
    |
    | Daftar nama pembuat / pemeriksa default. Jika kosong, user mengetik
    | nama secara manual atau dapat ditambahkan di kemudian hari.
    |
    */
    'inspectors' => [
        // Dapat dikonfigurasi / ditambahkan nama-nama pemeriksa default
    ],
];
