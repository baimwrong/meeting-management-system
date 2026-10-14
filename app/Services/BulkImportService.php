<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ImportLog;
use App\Models\Meeting;
use App\Models\MeetingRoom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BulkImportService
{
    protected MeetingRoomService $roomService;

    public function __construct(MeetingRoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Parse CSV file with automatic delimiter detection and UTF-8 BOM removal
     */
    public function parseCsv(UploadedFile|string $file): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $content = file_get_contents($filePath);

        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Determine line breaks
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if (empty($lines)) {
            return [];
        }

        // Auto-detect delimiter from the first line
        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        $headers = null;
        $rowNumber = 0;

        while (($data = fgetcsv($stream, 0, $bestDelimiter)) !== false) {
            $rowNumber++;
            // Skip empty rows
            if (empty(array_filter($data, fn($v) => trim($v) !== ''))) {
                continue;
            }

            if ($headers === null) {
                // Normalize headers: lowercase, trimmed, replace spaces with underscores
                $headers = array_map(function ($h) {
                    $clean = trim($h);
                    $clean = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $clean);
                    return strtolower(str_replace(' ', '_', $clean));
                }, $data);
                continue;
            }

            if (count($data) === count($headers)) {
                $rowAssoc = array_combine($headers, array_map('trim', $data));
                $rowAssoc['_row_number'] = $rowNumber;
                $rows[] = $rowAssoc;
            } else {
                $padded = array_pad($data, count($headers), '');
                $rowAssoc = array_combine($headers, array_slice($padded, 0, count($headers)));
                $rowAssoc['_row_number'] = $rowNumber;
                $rows[] = $rowAssoc;
            }
        }

        fclose($stream);
        return $rows;
    }

    /**
     * Bulk import Users
     */
    public function importUsers(UploadedFile $file, int $userId): ImportLog
    {
        $rows = $this->parseCsv($file);
        $totalRows = count($rows);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $rowNum = $row['_row_number'] ?? ($successCount + $failedCount + 2);

                $validator = Validator::make($row, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'role' => 'nullable|in:admin,organizer,participant',
                    'status' => 'nullable|in:active,inactive',
                    'phone' => 'nullable|string|max:50',
                    'job_title' => 'nullable|string|max:100',
                ]);

                if ($validator->fails()) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'email' => $row['email'] ?? 'N/A',
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Check existing user
                $existing = User::where('email', strtolower($row['email']))->first();
                if ($existing) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'email' => $row['email'],
                        'errors' => ["User with email {$row['email']} already exists."],
                    ];
                    continue;
                }

                // Department resolution
                $departmentId = null;
                $deptInput = $row['department'] ?? $row['department_name'] ?? $row['department_code'] ?? null;
                if (!empty($deptInput)) {
                    $dept = Department::where('code', strtoupper($deptInput))
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($deptInput)])
                        ->first();

                    if (!$dept) {
                        // Create department if missing
                        $deptCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $deptInput), 0, 5));
                        if (empty($deptCode)) {
                            $deptCode = 'DEPT' . rand(10, 99);
                        }
                        $dept = Department::firstOrCreate(
                            ['code' => $deptCode],
                            ['name' => $deptInput, 'is_active' => true]
                        );
                    }
                    $departmentId = $dept->id;
                }

                $password = !empty($row['password']) ? $row['password'] : 'password123';
                $role = !empty($row['role']) ? strtolower($row['role']) : 'participant';
                $status = !empty($row['status']) ? strtolower($row['status']) : 'active';

                User::create([
                    'name' => $row['name'],
                    'email' => strtolower($row['email']),
                    'password' => Hash::make($password),
                    'role' => $role,
                    'department_id' => $departmentId,
                    'phone' => $row['phone'] ?? null,
                    'job_title' => $row['job_title'] ?? null,
                    'status' => $status,
                ]);

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $logStatus = $failedCount === 0 ? 'completed' : ($successCount > 0 ? 'partially_failed' : 'failed');

        return ImportLog::create([
            'user_id' => $userId,
            'entity_type' => 'users',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
            'errors_json' => $errors,
            'status' => $logStatus,
        ]);
    }

    /**
     * Bulk import Meeting Rooms
     */
    public function importRooms(UploadedFile $file, int $userId): ImportLog
    {
        $rows = $this->parseCsv($file);
        $totalRows = count($rows);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $rowNum = $row['_row_number'] ?? ($successCount + $failedCount + 2);

                $validator = Validator::make($row, [
                    'name' => 'required|string|max:255',
                    'code' => 'required|string|max:50',
                    'floor_location' => 'required|string|max:255',
                    'capacity' => 'required|integer|min:1',
                    'hourly_rate' => 'nullable|numeric|min:0',
                    'status' => 'nullable|in:available,maintenance,inactive',
                ]);

                if ($validator->fails()) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $row['code'] ?? 'N/A',
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                $roomCode = strtoupper($row['code']);
                if (MeetingRoom::where('code', $roomCode)->exists()) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $roomCode,
                        'errors' => ["Room code '{$roomCode}' already exists."],
                    ];
                    continue;
                }

                // Parse equipment string e.g. "Projector, Whiteboard, Video Conference"
                $equipmentList = [];
                $rawEquipment = $row['equipment'] ?? $row['facilities'] ?? '';
                if (!empty($rawEquipment)) {
                    $parts = explode(',', $rawEquipment);
                    $equipmentList = array_values(array_filter(array_map('trim', $parts)));
                }

                $status = !empty($row['status']) ? strtolower($row['status']) : 'available';

                MeetingRoom::create([
                    'name' => $row['name'],
                    'code' => $roomCode,
                    'floor_location' => $row['floor_location'],
                    'capacity' => (int) $row['capacity'],
                    'equipment' => $equipmentList,
                    'hourly_rate' => !empty($row['hourly_rate']) ? (float) $row['hourly_rate'] : 0.00,
                    'status' => $status,
                    'notes' => $row['notes'] ?? null,
                ]);

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $logStatus = $failedCount === 0 ? 'completed' : ($successCount > 0 ? 'partially_failed' : 'failed');

        return ImportLog::create([
            'user_id' => $userId,
            'entity_type' => 'rooms',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
            'errors_json' => $errors,
            'status' => $logStatus,
        ]);
    }

    /**
     * Bulk import Meetings
     */
    public function importMeetings(UploadedFile $file, int $userId): ImportLog
    {
        $rows = $this->parseCsv($file);
        $totalRows = count($rows);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $rowNum = $row['_row_number'] ?? ($successCount + $failedCount + 2);

                $validator = Validator::make($row, [
                    'title' => 'required|string|max:255',
                    'meeting_date' => 'required|date_format:Y-m-d',
                    'start_time' => 'required',
                    'end_time' => 'required',
                    'organizer_email' => 'required|email',
                    'meeting_type' => 'nullable|in:in_person,virtual,hybrid',
                ]);

                if ($validator->fails()) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $row['title'] ?? 'N/A',
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Check start_time < end_time
                $startTime = Carbon::parse($row['start_time'])->format('H:i:s');
                $endTime = Carbon::parse($row['end_time'])->format('H:i:s');
                if ($startTime >= $endTime) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $row['title'],
                        'errors' => ["Start time ({$startTime}) must be earlier than end time ({$endTime})."],
                    ];
                    continue;
                }

                // Find organizer
                $organizer = User::where('email', strtolower($row['organizer_email']))->first();
                if (!$organizer) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $row['title'],
                        'errors' => ["Organizer with email '{$row['organizer_email']}' not found."],
                    ];
                    continue;
                }

                // Room resolution
                $roomId = null;
                $meetingType = !empty($row['meeting_type']) ? strtolower($row['meeting_type']) : 'in_person';

                if (!empty($row['room_code'])) {
                    $room = MeetingRoom::where('code', strtoupper($row['room_code']))->first();
                    if (!$room) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $rowNum,
                            'identifier' => $row['title'],
                            'errors' => ["Meeting room with code '{$row['room_code']}' not found."],
                        ];
                        continue;
                    }

                    // Conflict check
                    $check = $this->roomService->checkRoomAvailability($room->id, $row['meeting_date'], $startTime, $endTime);
                    if (!$check['available']) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $rowNum,
                            'identifier' => $row['title'],
                            'errors' => [$check['message']],
                        ];
                        continue;
                    }
                    $roomId = $room->id;
                }

                // Department resolution
                $departmentId = $organizer->department_id;
                if (!empty($row['department_code'])) {
                    $dept = Department::where('code', strtoupper($row['department_code']))->first();
                    if ($dept) {
                        $departmentId = $dept->id;
                    }
                }

                $meetingCode = !empty($row['meeting_code'])
                    ? strtoupper($row['meeting_code'])
                    : 'MTG-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                $meeting = Meeting::create([
                    'meeting_code' => $meetingCode,
                    'title' => $row['title'],
                    'description' => $row['description'] ?? null,
                    'room_id' => $roomId,
                    'organizer_id' => $organizer->id,
                    'department_id' => $departmentId,
                    'meeting_type' => $meetingType,
                    'virtual_link' => $row['virtual_link'] ?? null,
                    'meeting_date' => $row['meeting_date'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'scheduled',
                ]);

                // Automatically add organizer as attendee host
                $meeting->attendees()->create([
                    'user_id' => $organizer->id,
                    'role' => 'host',
                    'rsvp_status' => 'accepted',
                ]);

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $logStatus = $failedCount === 0 ? 'completed' : ($successCount > 0 ? 'partially_failed' : 'failed');

        return ImportLog::create([
            'user_id' => $userId,
            'entity_type' => 'meetings',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
            'errors_json' => $errors,
            'status' => $logStatus,
        ]);
    }

    /**
     * Generate template CSV content
     */
    public function generateTemplateCsv(string $type): string
    {
        $handle = fopen('php://memory', 'r+');

        switch ($type) {
            case 'users':
                $headers = ['name', 'email', 'role', 'department', 'phone', 'job_title', 'status', 'password'];
                fputcsv($handle, $headers);
                fputcsv($handle, ['Sarah Jenkins', 'sarah.j@company.local', 'organizer', 'Engineering', '+1-555-0101', 'Lead Architect', 'active', 'password123']);
                fputcsv($handle, ['Marcus Chen', 'marcus.c@company.local', 'participant', 'Product', '+1-555-0102', 'Senior Product Manager', 'active', 'password123']);
                fputcsv($handle, ['Elena Rostova', 'elena.r@company.local', 'admin', 'Management', '+1-555-0103', 'Operations Director', 'active', 'password123']);
                break;

            case 'rooms':
                $headers = ['name', 'code', 'floor_location', 'capacity', 'equipment', 'hourly_rate', 'status', 'notes'];
                fputcsv($handle, $headers);
                fputcsv($handle, ['Executive Boardroom Alpha', 'BOARD-01', 'Executive Floor 5', '24', 'Projector, Video Conference, Whiteboard, Polycom', '50.00', 'available', 'Catering permitted']);
                fputcsv($handle, ['Innovation Lab', 'LAB-02', 'East Wing 2nd Floor', '16', 'Smart TV 75 inch, Dual Whiteboard, Wireless Presentation', '0.00', 'available', 'Configurable layout']);
                fputcsv($handle, ['Focus Huddle Room C', 'HUDDLE-03', 'Building B 1st Floor', '6', 'Conference Webcam, 55 inch Display, Soundbar', '0.00', 'available', 'Quiet zone']);
                break;

            case 'meetings':
                $headers = ['title', 'meeting_code', 'room_code', 'organizer_email', 'meeting_date', 'start_time', 'end_time', 'meeting_type', 'virtual_link', 'department_code', 'description'];
                fputcsv($handle, $headers);
                fputcsv($handle, ['Quarterly Strategic Review', 'MTG-STRAT-01', 'BOARD-01', 'admin@company.local', date('Y-m-d', strtotime('+2 days')), '09:00', '11:00', 'hybrid', 'https://meet.company.local/strat-q4', 'MGMT', 'Review Q3 performance and Q4 goals']);
                fputcsv($handle, ['Sprint Architecture Sync', 'MTG-ARCH-02', 'LAB-02', 'organizer@company.local', date('Y-m-d', strtotime('+3 days')), '14:00', '15:30', 'in_person', '', 'ENG', 'Discuss cloud migration patterns']);
                break;
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
