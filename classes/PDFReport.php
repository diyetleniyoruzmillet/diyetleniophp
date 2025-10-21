<?php
/**
 * Diyetlenio - PDF Report Generator
 * Basit HTML to PDF dönüştürme (TCPDF olmadan)
 */

class PDFReport
{
    /**
     * HTML'i PDF'e çevir (basit versiyon - production'da TCPDF kullanılmalı)
     */
    public static function generate(string $html, string $filename = 'report.pdf'): void
    {
        // Header ayarları
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // HTML to PDF için basit implementasyon
        // Production'da TCPDF, DomPDF veya wkhtmltopdf kullanılmalı
        
        // Şimdilik HTML olarak indir (fallback)
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
        
        echo $html;
        exit;
    }

    /**
     * Randevu raporu oluştur
     */
    public static function appointmentReport(array $appointments): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Randevu Raporu</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { color: #11998e; border-bottom: 3px solid #11998e; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #11998e; color: white; }
                .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <h1>Randevu Raporu</h1>
            <p><strong>Tarih:</strong> ' . date('d.m.Y H:i') . '</p>
            <p><strong>Toplam Randevu:</strong> ' . count($appointments) . '</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Danışan</th>
                        <th>Diyetisyen</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($appointments as $apt) {
            $html .= '<tr>
                <td>' . date('d.m.Y', strtotime($apt['appointment_date'])) . '</td>
                <td>' . substr($apt['start_time'], 0, 5) . '</td>
                <td>' . htmlspecialchars($apt['client_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($apt['dietitian_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($apt['status']) . '</td>
            </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>© ' . date('Y') . ' Diyetlenio - Tüm hakları saklıdır.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Gelir raporu oluştur
     */
    public static function revenueReport(array $payments): string
    {
        $total = array_sum(array_column($payments, 'amount'));
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Gelir Raporu</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { color: #11998e; }
                .summary { background: #e6fffa; padding: 20px; border-radius: 10px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #11998e; color: white; }
            </style>
        </head>
        <body>
            <h1>Gelir Raporu</h1>
            <div class="summary">
                <h2>Toplam Gelir: ' . number_format($total, 2) . ' ₺</h2>
                <p>Toplam İşlem: ' . count($payments) . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Danışan</th>
                        <th>Diyetisyen</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($payments as $p) {
            $html .= '<tr>
                <td>' . date('d.m.Y', strtotime($p['created_at'])) . '</td>
                <td>' . htmlspecialchars($p['client_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($p['dietitian_name'] ?? '') . '</td>
                <td>' . number_format($p['amount'], 2) . ' ₺</td>
                <td>' . htmlspecialchars($p['status']) . '</td>
            </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </body>
        </html>';
        
        return $html;
    }
}
