<?php
/**
 * Availability Service
 * Diyetisyen müsaitlik yönetimi
 */

class AvailabilityService
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Diyetisyenin belirli bir tarih için müsait saatlerini getir
     *
     * @param int $dietitianId
     * @param string $date Format: Y-m-d
     * @return array Müsait saat slotları ['09:00:00', '09:45:00', ...]
     */
    public function getAvailableSlots($dietitianId, $date)
    {
        $conn = $this->db->getConnection();
        $dayOfWeek = date('w', strtotime($date)); // 0=Pazar, 6=Cumartesi

        // 1. Exception kontrolü (izinli veya özel çalışma günü)
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability_exceptions
            WHERE dietitian_id = ? AND exception_date = ?
        ");
        $stmt->execute([$dietitianId, $date]);
        $exception = $stmt->fetch();

        if ($exception && !$exception['is_available']) {
            // İzinli gün, boş array dön
            return [];
        }

        // 2. Normal müsaitlik bilgisini getir
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability
            WHERE dietitian_id = ? AND day_of_week = ? AND is_active = 1
            ORDER BY start_time
        ");
        $stmt->execute([$dietitianId, $dayOfWeek]);
        $availabilities = $stmt->fetchAll();

        if (empty($availabilities) && !$exception) {
            // Bu gün çalışmıyor
            return [];
        }

        // Exception varsa ve available ise, o saatleri kullan
        if ($exception && $exception['is_available']) {
            $availabilities = [[
                'start_time' => $exception['start_time'],
                'end_time' => $exception['end_time'],
                'slot_duration' => 45
            ]];
        }

        // 3. Tüm olası time slotları oluştur
        $allSlots = [];
        foreach ($availabilities as $avail) {
            $startTime = strtotime($avail['start_time']);
            $endTime = strtotime($avail['end_time']);
            $duration = ($avail['slot_duration'] ?? 45) * 60; // saniye

            $currentTime = $startTime;
            while ($currentTime + $duration <= $endTime) {
                $allSlots[] = date('H:i:s', $currentTime);
                $currentTime += $duration;
            }
        }

        // 4. Mevcut randevuları getir
        $stmt = $conn->prepare("
            SELECT start_time, end_time FROM appointments
            WHERE dietitian_id = ?
            AND appointment_date = ?
            AND status NOT IN ('cancelled', 'no-show')
        ");
        $stmt->execute([$dietitianId, $date]);
        $bookedAppointments = $stmt->fetchAll();

        // 5. Dolu slotları çıkar
        $availableSlots = array_filter($allSlots, function($slot) use ($bookedAppointments) {
            $slotTime = strtotime($slot);

            foreach ($bookedAppointments as $booked) {
                $bookedStart = strtotime($booked['start_time']);
                $bookedEnd = strtotime($booked['end_time']);

                // Slot çakışıyor mu?
                if ($slotTime >= $bookedStart && $slotTime < $bookedEnd) {
                    return false;
                }
            }

            return true;
        });

        // 6. Geçmiş saatleri filtrele (bugün ise)
        if ($date === date('Y-m-d')) {
            $now = time();
            $availableSlots = array_filter($availableSlots, function($slot) use ($date, $now) {
                $slotTimestamp = strtotime($date . ' ' . $slot);
                // En az 1 saat sonrası için randevu alınabilir
                return $slotTimestamp > ($now + 3600);
            });
        }

        return array_values($availableSlots);
    }

    /**
     * Diyetisyenin haftalık müsaitlik ayarlarını getir
     *
     * @param int $dietitianId
     * @return array Günlere göre gruplanmış müsaitlikler
     */
    public function getWeeklyAvailability($dietitianId)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability
            WHERE dietitian_id = ?
            ORDER BY day_of_week, start_time
        ");
        $stmt->execute([$dietitianId]);
        $all = $stmt->fetchAll();

        // Günlere göre grupla
        $weekly = [];
        foreach (range(0, 6) as $day) {
            $weekly[$day] = array_filter($all, function($item) use ($day) {
                return $item['day_of_week'] == $day;
            });
        }

        return $weekly;
    }

    /**
     * Haftalık müsaitlik güncelle
     *
     * @param int $dietitianId
     * @param array $schedule [['day_of_week' => 1, 'start_time' => '09:00', ...], ...]
     * @return bool
     */
    public function updateWeeklyAvailability($dietitianId, array $schedule)
    {
        $conn = $this->db->getConnection();

        try {
            $conn->beginTransaction();

            // Mevcut tüm müsaitlikleri sil
            $stmt = $conn->prepare("DELETE FROM dietitian_availability WHERE dietitian_id = ?");
            $stmt->execute([$dietitianId]);

            // Yeni müsaitleri ekle
            $stmt = $conn->prepare("
                INSERT INTO dietitian_availability
                (dietitian_id, day_of_week, start_time, end_time, slot_duration, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($schedule as $item) {
                $stmt->execute([
                    $dietitianId,
                    $item['day_of_week'],
                    $item['start_time'],
                    $item['end_time'],
                    $item['slot_duration'] ?? 45,
                    $item['is_active'] ?? 1
                ]);
            }

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Availability update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * İzin/tatil günü ekle
     *
     * @param int $dietitianId
     * @param string $date Format: Y-m-d
     * @param string $reason
     * @return bool
     */
    public function addException($dietitianId, $date, $reason = null)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO dietitian_availability_exceptions
            (dietitian_id, exception_date, is_available, reason)
            VALUES (?, ?, 0, ?)
            ON DUPLICATE KEY UPDATE
                is_available = 0,
                reason = VALUES(reason),
                start_time = NULL,
                end_time = NULL
        ");

        return $stmt->execute([$dietitianId, $date, $reason]);
    }

    /**
     * Özel çalışma günü ekle
     *
     * @param int $dietitianId
     * @param string $date Format: Y-m-d
     * @param string $startTime Format: H:i
     * @param string $endTime Format: H:i
     * @return bool
     */
    public function addSpecialWorkingDay($dietitianId, $date, $startTime, $endTime)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO dietitian_availability_exceptions
            (dietitian_id, exception_date, is_available, start_time, end_time)
            VALUES (?, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_available = 1,
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                reason = NULL
        ");

        return $stmt->execute([$dietitianId, $date, $startTime, $endTime]);
    }

    /**
     * Exception'ı sil
     *
     * @param int $dietitianId
     * @param string $date
     * @return bool
     */
    public function removeException($dietitianId, $date)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            DELETE FROM dietitian_availability_exceptions
            WHERE dietitian_id = ? AND exception_date = ?
        ");

        return $stmt->execute([$dietitianId, $date]);
    }

    /**
     * Belirli bir tarih aralığındaki exception'ları getir
     *
     * @param int $dietitianId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getExceptions($dietitianId, $startDate, $endDate)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability_exceptions
            WHERE dietitian_id = ?
            AND exception_date BETWEEN ? AND ?
            ORDER BY exception_date
        ");
        $stmt->execute([$dietitianId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Slot süresini güncelle
     *
     * @param int $dietitianId
     * @param int $duration (30, 45, 60 dakika)
     * @return bool
     */
    public function updateSlotDuration($dietitianId, $duration)
    {
        if (!in_array($duration, [30, 45, 60])) {
            throw new InvalidArgumentException('Slot süresi 30, 45 veya 60 dakika olmalıdır');
        }

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            UPDATE dietitian_availability
            SET slot_duration = ?
            WHERE dietitian_id = ?
        ");

        return $stmt->execute([$duration, $dietitianId]);
    }
}
