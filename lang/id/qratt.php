<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Indonesian strings for qratt
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Absensi QR';
$string['modulenameplural'] = 'Absensi QR';
$string['modulename_help'] = 'Gunakan modul Absensi QR untuk melacak kehadiran mahasiswa menggunakan kode QR. Dosen dapat membuat pertemuan dan menghasilkan kode QR yang dipindai mahasiswa untuk menandai kehadiran mereka.';
$string['qratt:addinstance'] = 'Tambah Absensi QR baru';
$string['qratt:view'] = 'Lihat Absensi QR';
$string['qratt:manage'] = 'Kelola Absensi QR';
$string['qratt:takeattendance'] = 'Ambil absensi';
$string['qratt:viewreports'] = 'Lihat laporan absensi';
$string['qratt:manageattendances'] = 'Kelola catatan absensi';
$string['qratt:canbelisted'] = 'Dapat terdaftar dalam absensi';
$string['qrattfieldset'] = 'Fieldset contoh kustom';
$string['qrattname'] = 'Nama Absensi QR';
$string['qrattname_help'] = 'Ini adalah konten tooltip bantuan yang terkait dengan field qrattname. Sintaks Markdown didukung.';
$string['qratt'] = 'qratt';
$string['pluginadministration'] = 'Administrasi Absensi QR';
$string['pluginname'] = 'Absensi QR';

// Navigation
$string['overview'] = 'Ringkasan';
$string['meetings'] = 'Pertemuan';
$string['reports'] = 'Laporan';

// Meeting management
$string['addmeeting'] = 'Tambah pertemuan';
$string['editmeeting'] = 'Edit pertemuan';
$string['deleteemeeting'] = 'Hapus pertemuan';
$string['meetingnumber'] = 'Nomor pertemuan';
$string['topic'] = 'Topik';
$string['date'] = 'Tanggal';
$string['location'] = 'Lokasi';
$string['status'] = 'Status';
$string['actions'] = 'Aksi';
$string['yourstatus'] = 'Status Anda';
$string['activate'] = 'Aktifkan';
$string['showqr'] = 'Tampilkan Kode QR';
$string['endmeeting'] = 'Akhiri pertemuan';
$string['active'] = 'Aktif';
$string['inactive'] = 'Tidak aktif';
$string['ended'] = 'Berakhir';

// Attendance statuses
$string['present'] = 'Hadir';
$string['absent'] = 'Tidak hadir';
$string['late'] = 'Terlambat';
$string['excused'] = 'Izin';

// Messages
$string['nomeetings'] = 'Belum ada pertemuan yang dibuat.';
$string['nomeetingsinfo'] = 'Untuk mulai mengambil absensi, Anda perlu membuat pertemuan terlebih dahulu.';
$string['attendancerecord'] = 'Hadir {$a->present} dari {$a->total} pertemuan';
$string['attendancesummary'] = 'Ringkasan Kehadiran';
$string['totalmeetings'] = 'Total pertemuan';
$string['attendancepercentage'] = 'Persentase kehadiran';

// QR Code
$string['qrcode'] = 'Kode QR';
$string['qrcodefor'] = 'Kode QR untuk {$a}';
$string['qrrefresh'] = 'Kode QR disegarkan setiap 60 detik';
$string['scanqr'] = 'Pindai Kode QR';
$string['qrexpired'] = 'Kode QR telah kedaluwarsa';
$string['qrinvalid'] = 'Kode QR tidak valid';
$string['attendancemarked'] = 'Absensi berhasil ditandai';
$string['alreadymarked'] = 'Absensi sudah ditandai untuk pertemuan ini';
$string['fullscreen'] = 'Tampilkan dalam layar penuh';

// Forms
$string['meetingform'] = 'Form pertemuan';
$string['meetingdate'] = 'Tanggal pertemuan';
$string['exitfullscreen'] = 'Keluar Layar Penuh';
$string['meetingtopic'] = 'Topik pertemuan';
$string['duration'] = 'Durasi (menit)';
$string['activeduration'] = 'Durasi aktif';
$string['activeduration_help'] = 'Berapa lama setelah waktu mulai pertemuan mahasiswa masih dapat ditandai sebagai Hadir. Setelah durasi ini, pemindaian terlambat akan ditandai sebagai status Terlambat.';

// Errors
$string['error:meetingnotfound'] = 'Pertemuan tidak ditemukan';
$string['error:cannotactivate'] = 'Tidak dapat mengaktifkan pertemuan';
$string['error:meetingnotactive'] = 'Pertemuan tidak aktif';
$string['error:alreadyended'] = 'Pertemuan sudah berakhir';
$string['error:rolenotfound'] = 'Peran mahasiswa tidak ditemukan dalam sistem';
$string['cannotmarkattendance'] = 'Tidak dapat menandai kehadiran. Silakan coba lagi.';

// Additional strings
$string['timeremaining'] = 'Waktu tersisa';
$string['refresh'] = 'Segarkan';
$string['meetingactivated'] = 'Pertemuan berhasil diaktifkan';
$string['meetingended'] = 'Pertemuan berhasil diakhiri';
$string['meetingdeleted'] = 'Pertemuan berhasil dihapus';
$string['meetingcreated'] = 'Pertemuan berhasil dibuat';
$string['meetingupdated'] = 'Pertemuan berhasil diperbarui';
$string['meetingnumberexists'] = 'Nomor pertemuan ini sudah ada';
$string['confirmdeletion'] = 'Apakah Anda yakin ingin menghapus pertemuan ini?';
$string['yourcurrentstatus'] = 'Status Anda saat ini';
$string['meetingdetails'] = 'Detail Pertemuan';
$string['scantime'] = 'Waktu scan';
$string['continueto'] = 'Lanjutkan ke {$a}';
$string['student'] = 'Mahasiswa';
$string['meeting'] = 'Pertemuan';
$string['bymeeting'] = 'Per Pertemuan';
$string['bystudent'] = 'Per Mahasiswa';
$string['reportbymeeting'] = 'Laporan per Pertemuan';
$string['reportbystudent'] = 'Laporan per Mahasiswa';
$string['summary'] = 'Ringkasan';
$string['nousers'] = 'Tidak ada pengguna ditemukan';
$string['attendanceoverview'] = 'Ringkasan Kehadiran';
$string['nodata'] = 'Tidak ada data tersedia';
$string['overallstatistics'] = 'Statistik Keseluruhan';
$string['totalstudents'] = 'Total mahasiswa';
$string['overallattendance'] = 'Kehadiran keseluruhan';
$string['attendancebreakdown'] = 'Rincian Kehadiran';
$string['meetingwisesummary'] = 'Ringkasan per Pertemuan';
$string['totalpresent'] = 'Total hadir';
$string['totalabsent'] = 'Total tidak hadir';
$string['percentage'] = 'Persentase';
$string['downloadcsv'] = 'Unduh CSV';
$string['reportdate'] = 'Tanggal Laporan';

// Scanner functionality
$string['scanqrcode'] = 'Pindai Kode QR';
$string['activemeetingfound'] = 'Pertemuan aktif ditemukan! Anda dapat memindai kode QR sekarang.';
$string['noactivemeetings'] = 'Tidak ada pertemuan aktif saat ini.';
$string['scannerinfo'] = 'Arahkan kamera Anda ke kode QR yang ditampilkan oleh dosen untuk mencatat kehadiran Anda.';
$string['scannerresult'] = 'Hasil pemindaian akan muncul di sini...';
$string['manualentry'] = 'Entri Manual';
$string['manualentryinfo'] = 'Jika pemindai kamera tidak bekerja, Anda dapat memasukkan URL kode QR secara manual:';
$string['invalidqrurl'] = 'URL kode QR tidak valid. Silakan periksa URL dan coba lagi.';

// Manual attendance
$string['manualattendance'] = 'Absensi Manual';
$string['selectmeeting'] = 'Pilih Pertemuan';
$string['selectmeetinginfo'] = 'Pilih pertemuan untuk mengelola absensi secara manual.';
$string['manageattendance'] = 'Kelola Absensi';
$string['manualattendancefor'] = 'Presensi untuk: {$a}';
$string['currentstatus'] = 'Status Saat Ini';
$string['setattendance'] = 'Atur Absensi';
$string['saveattendance'] = 'Simpan Absensi';
$string['attendanceupdated'] = 'Absensi diperbarui: {$a->saved} rekaman baru disimpan, {$a->updated} rekaman diperbarui.';
$string['notset'] = 'Belum Diatur';
$string['nostudents'] = 'Tidak ada mahasiswa yang ditemukan di kursus ini.';
$string['back'] = 'Kembali';
$string['managemeetings'] = 'Kelola Pertemuan';
$string['meetingsoverview'] = 'Ringkasan Pertemuan';
$string['bulkselection'] = 'Pilihan Massal';
$string['bulkselectionhelp'] = 'Pilih status kehadiran di bawah ini untuk mengatur semua mahasiswa ke status tersebut sekaligus. Anda kemudian dapat memodifikasi mahasiswa individu sesuai kebutuhan.';

// Admin Settings
$string['securitysettings'] = 'Pengaturan Keamanan';
$string['securitysettings_desc'] = 'Konfigurasi pengaturan keamanan untuk pembuatan dan validasi kode QR.';
$string['encryptionkey'] = 'Kunci Enkripsi Kode QR';
$string['encryptionkey_desc'] = 'Kunci enkripsi yang digunakan untuk pembuatan dan validasi token kode QR. Kosongkan untuk menggunakan kunci sistem default. Mengubah kunci ini akan membuat kode QR yang ada tidak valid.';

$string['institutionsettings'] = 'Informasi Institusi';
$string['institutionsettings_desc'] = 'Konfigurasi informasi institusi yang akan disertakan dalam laporan absensi.';
$string['institutionname'] = 'Nama Institusi';
$string['institutionname_desc'] = 'Nama institusi pendidikan';
$string['institutionaddress'] = 'Alamat Institusi';
$string['institutionaddress_desc'] = 'Alamat lengkap institusi';
$string['institutionphone'] = 'Telepon Institusi';
$string['institutionphone_desc'] = 'Nomor telepon institusi';
$string['institutionfax'] = 'Fax Institusi';
$string['institutionfax_desc'] = 'Nomor fax institusi';
$string['institutionlogo'] = 'Logo Institusi';
$string['institutionlogo_desc'] = 'Upload logo institusi untuk digunakan dalam laporan. Format yang didukung: PNG, JPG, JPEG, GIF';

$string['reportsettings'] = 'Pengaturan Laporan';
$string['reportsettings_desc'] = 'Konfigurasi informasi apa yang akan disertakan dalam laporan absensi.';
$string['includeinstitutioninfo'] = 'Sertakan Informasi Institusi';
$string['includeinstitutioninfo_desc'] = 'Sertakan nama institusi, alamat, dan informasi kontak dalam laporan yang dibuat';
$string['includelogoinreports'] = 'Sertakan Logo dalam Laporan';
$string['includelogoinreports_desc'] = 'Sertakan logo institusi dalam laporan yang dibuat';
$string['includeaddressinreports'] = 'Sertakan Alamat dalam Laporan';
$string['includeaddressinreports_desc'] = 'Sertakan alamat institusi dalam laporan yang dibuat';
$string['includewebsiteinreports'] = 'Sertakan Website dalam Laporan';
$string['includewebsiteinreports_desc'] = 'Sertakan website institusi dalam laporan yang dibuat';
$string['includeemailinreports'] = 'Sertakan Email dalam Laporan';
$string['includeemailinreports_desc'] = 'Sertakan email institusi dalam laporan yang dibuat';
$string['includecityinreports'] = 'Sertakan Kota dalam Laporan';
$string['includecityinreports_desc'] = 'Sertakan kota institusi dalam footer laporan';

// Institution fields
$string['institutioncity'] = 'Kota Institusi';
$string['institutioncity_desc'] = 'Kota tempat institusi berada';
$string['institutionwebsite'] = 'Website Institusi';
$string['institutionwebsite_desc'] = 'URL website institusi';
$string['institutionemail'] = 'Email Institusi';
$string['institutionemail_desc'] = 'Alamat email resmi institusi';

// Course information fields
$string['courseinformation'] = 'Informasi Mata Kuliah';
$string['semester'] = 'Semester';
$string['semester_help'] = 'Semester atau periode akademik (misalnya: Genap 2024, Ganjil 2024)';
$string['department'] = 'Jurusan';
$string['department_help'] = 'Jurusan atau fakultas yang menawarkan mata kuliah ini';
$string['studyprogram'] = 'Program Studi';
$string['studyprogram_help'] = 'Program studi atau jurusan yang terkait dengan mata kuliah ini';
$string['subject'] = 'Mata Kuliah';
$string['subject_help'] = 'Nama mata kuliah';
$string['credits'] = 'SKS';
$string['credits_help'] = 'Jumlah satuan kredit semester untuk mata kuliah ini';
$string['classname'] = 'Kelas';
$string['classname_help'] = 'Nama kelas atau seksi (misalnya: A, B, Pagi, Sore)';
$string['lecturer'] = 'Dosen';
$string['lecturer_help'] = 'Nama dosen pengampu';
$string['dayofweek'] = 'Hari';
$string['dayofweek_help'] = 'Hari dalam seminggu ketika kelas ini dijadwalkan';
$string['scheduletime'] = 'Pukul';
$string['scheduletime_help'] = 'Waktu ketika kelas ini dijadwalkan (misalnya: 08:00-10:00)';
$string['room'] = 'Ruang';
$string['room_help'] = 'Ruang kelas atau lokasi tempat kelas ini berlangsung';

// Days of the week
$string['selectday'] = 'Pilih hari';
$string['monday'] = 'Senin';
$string['tuesday'] = 'Selasa';
$string['wednesday'] = 'Rabu';
$string['thursday'] = 'Kamis';
$string['friday'] = 'Jumat';
$string['saturday'] = 'Sabtu';
$string['sunday'] = 'Minggu';

// Report strings
$string['studentreport'] = 'Laporan Kehadiran Mahasiswa';
$string['teacherreport'] = 'Laporan Dosen';
$string['no'] = 'No.';
$string['nim'] = 'NIM';
$string['fullname'] = 'Nama Lengkap';
$string['numberpresent'] = 'Jumlah Hadir';
$string['numberabsent'] = 'Jumlah Tidak Hadir';
$string['print'] = 'Cetak';
$string['lecturer_in_charge'] = 'Dosen yang Bersangkutan';

// Events
$string['eventcoursemoduleviewed'] = 'Modul Absensi QR dilihat';

// Privacy
$string['privacy:metadata'] = 'Plugin Absensi QR menyimpan data kehadiran untuk pengguna.';
$string['privacy:metadata:qratt_attendance'] = 'Informasi tentang kehadiran pengguna dalam aktivitas Absensi QR.';
$string['privacy:metadata:qratt_attendance:userid'] = 'ID pengguna yang kehadirannya sedang dicatat.';
$string['privacy:metadata:qratt_attendance:status'] = 'Status kehadiran pengguna untuk pertemuan.';
$string['privacy:metadata:qratt_attendance:scantime'] = 'Waktu ketika pengguna memindai kode QR.';
$string['privacy:metadata:qratt_attendance:timecreated'] = 'Waktu ketika catatan kehadiran dibuat.';
$string['privacy:metadata:qratt_attendance:timemodified'] = 'Waktu ketika catatan kehadiran terakhir dimodifikasi.';
