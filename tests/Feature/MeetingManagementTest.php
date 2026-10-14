<?php

namespace Tests\Feature;

use App\Models\ImportLog;
use App\Models\Meeting;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Services\BulkImportService;
use App\Services\MeetingRoomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MeetingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Enterprise Meeting Management System');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'admin@company.local')->first();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Meetings Today');
        $response->assertSee('Room Utilization');
    }

    public function test_room_conflict_engine_detects_overlapping_booking(): void
    {
        $roomService = app(MeetingRoomService::class);
        $room = MeetingRoom::first();
        $today = Carbon::today()->toDateString();

        // There is an existing booking in BOARD-01 from 10:00 to 12:00
        $result = $roomService->checkRoomAvailability($room->id, $today, '10:30:00', '11:30:00');
        $this->assertFalse($result['available']);
        $this->assertNotNull($result['conflict']);

        // Non-overlapping time slot
        $freeResult = $roomService->checkRoomAvailability($room->id, $today, '16:00:00', '17:00:00');
        $this->assertTrue($freeResult['available']);
    }

    public function test_admin_can_access_bulk_import_hub(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $response = $this->get('/admin/bulk-import');
        $response->assertStatus(200);
        $response->assertSee('Enterprise Bulk Data Import');
    }

    public function test_participant_cannot_access_admin_routes(): void
    {
        $participant = User::where('role', 'participant')->first();
        $this->actingAs($participant);

        $response = $this->get('/admin/bulk-import');
        $response->assertStatus(403);
    }

    public function test_bulk_import_service_template_generation(): void
    {
        $service = app(BulkImportService::class);

        $userTemplate = $service->generateTemplateCsv('users');
        $this->assertStringContainsString('name,email,role,department', $userTemplate);

        $roomTemplate = $service->generateTemplateCsv('rooms');
        $this->assertStringContainsString('name,code,floor_location,capacity', $roomTemplate);

        $meetingTemplate = $service->generateTemplateCsv('meetings');
        $this->assertStringContainsString('title,meeting_code,room_code', $meetingTemplate);
    }

    public function test_admin_can_bulk_import_users_from_csv(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $csvContent = "name,email,role,department,phone,job_title,status,password\n" .
                      "Jessica Alba,jessica.a@company.test,participant,Engineering,+1-555-8888,QA Engineer,active,secret123\n" .
                      "Tom Holland,tom.h@company.test,organizer,Design,+1-555-9999,Lead UI Designer,active,secret123\n";

        $file = UploadedFile::fake()->createWithContent('users_import.csv', $csvContent);

        $response = $this->post('/admin/bulk-import', [
            'entity_type' => 'users',
            'file' => $file,
        ]);

        $response->assertRedirect('/admin/bulk-import');
        $this->assertDatabaseHas('users', ['email' => 'jessica.a@company.test']);
        $this->assertDatabaseHas('users', ['email' => 'tom.h@company.test']);

        $this->assertDatabaseHas('import_logs', [
            'entity_type' => 'users',
            'success_rows' => 2,
            'failed_rows' => 0,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_bulk_import_rooms_from_csv(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $csvContent = "name,code,floor_location,capacity,equipment,hourly_rate,status,notes\n" .
                      "Skyline Conference Suite,SKY-99,Floor 15,30,\"Projector, Video Bar, 4K Screen\",100.00,available,Panoramic view\n";

        $file = UploadedFile::fake()->createWithContent('rooms_import.csv', $csvContent);

        $response = $this->post('/admin/bulk-import', [
            'entity_type' => 'rooms',
            'file' => $file,
        ]);

        $response->assertRedirect('/admin/bulk-import');
        $this->assertDatabaseHas('meeting_rooms', ['code' => 'SKY-99']);
    }
}
