<?php
/**
 * Diyetlenio - Excel/CSV Export
 * Basit CSV export (production'da PhpSpreadsheet kullanılmalı)
 */

class ExcelExport
{
    /**
     * CSV olarak export et
     */
    public static function exportCSV(array $data, array $headers, string $filename = 'export.csv'): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM ekle (Excel için Türkçe karakter desteği)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header satırı
        fputcsv($output, $headers, ';');
        
        // Data satırları
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Randevuları export et
     */
    public static function exportAppointments(array $appointments): void
    {
        $headers = ['Tarih', 'Saat', 'Danışan', 'Diyetisyen', 'Durum', 'Ücret', 'Ödendi'];
        
        $data = [];
        foreach ($appointments as $apt) {
            $data[] = [
                date('d.m.Y', strtotime($apt['appointment_date'])),
                substr($apt['start_time'], 0, 5),
                $apt['client_name'] ?? '',
                $apt['dietitian_name'] ?? '',
                $apt['status'],
                number_format($apt['payment_amount'] ?? 0, 2) . ' ₺',
                $apt['is_paid'] ? 'Evet' : 'Hayır'
            ];
        }
        
        self::exportCSV($data, $headers, 'randevular_' . date('Y-m-d') . '.csv');
    }

    /**
     * Ödemeleri export et
     */
    public static function exportPayments(array $payments): void
    {
        $headers = ['Tarih', 'Danışan', 'Diyetisyen', 'Tutar', 'Durum', 'Onay Tarihi'];
        
        $data = [];
        foreach ($payments as $p) {
            $data[] = [
                date('d.m.Y H:i', strtotime($p['created_at'])),
                $p['client_name'] ?? '',
                $p['dietitian_name'] ?? '',
                number_format($p['amount'], 2) . ' ₺',
                $p['status'],
                $p['approved_at'] ? date('d.m.Y H:i', strtotime($p['approved_at'])) : '-'
            ];
        }
        
        self::exportCSV($data, $headers, 'odemeler_' . date('Y-m-d') . '.csv');
    }

    /**
     * Kullanıcıları export et
     */
    public static function exportUsers(array $users): void
    {
        $headers = ['ID', 'Ad Soyad', 'Email', 'Telefon', 'Tip', 'Aktif', 'Kayıt Tarihi'];
        
        $data = [];
        foreach ($users as $u) {
            $data[] = [
                $u['id'],
                $u['full_name'],
                $u['email'],
                $u['phone'] ?? '',
                $u['user_type'],
                $u['is_active'] ? 'Evet' : 'Hayır',
                date('d.m.Y', strtotime($u['created_at']))
            ];
        }
        
        self::exportCSV($data, $headers, 'kullanicilar_' . date('Y-m-d') . '.csv');
    }
}
