<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\MeetingRoom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase1SecurityAndBugFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_scheduling_meeting_with_subset_of_attendees_succeeds(): void
    {
        $organizer = User::where('role', 'organizer')->first();
        $participant = User::where('role', 'participant')->first();
        $room = MeetingRoom::where('code', 'HUDDLE-02')->first();
        $futureDate = Carbon::today()->addDays(10)->toDateString();

        $this->actingAs($organizer);

        $payload = [
            'title' => 'Product Architecture Alignment',
            'meeting_type' => 'in_person',
            'room_id' => $room->id,
            'meeting_date' => $futureDate,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'attendees' => [
                ['user_id' => $participant->id, 'role' => 'required'],
            ],
            'agendas' => [
                ['title' => 'Review Core Components', 'duration_minutes' => 30],
            ],
        ];

        $response = $this->post('/meetings', $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'title' => 'Product Architecture Alignment',
            'organizer_id' => $organizer->id,
        ]);
    }

    public function test_scope_past_does_not_leak_other_users_completed_meetings(): void
    {
        $participant = User::where('email', 'participant@company.local')->first();

        // Query past meetings for this participant
        $pastMeetings = Meeting::forUser($participant)->past()->get();

        // In seed data: Meeting 4 is completed, organized by admin.
        // Participant is attendee with status declined.
        // Let's create an unrelated completed meeting that participant has zero access to:
        $otherUser = User::where('role', 'admin')->first();
        $confidentialCompletedMeeting = Meeting::create([
            'meeting_code' => 'MTG-TOP-SECRET-01',
            'title' => 'Classified Board Evaluation',
            'meeting_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'completed',
            'is_confidential' => true,
            'organizer_id' => $otherUser->id,
        ]);

        $participantPast = Meeting::forUser($participant)->past()->pluck('id')->all();

        $this->assertNotContains($confidentialCompletedMeeting->id, $participantPast);
    }

    public function test_action_item_due_today_is_not_flagged_overdue(): void
    {
        $item = new ActionItem([
            'status' => 'pending',
            'due_date' => Carbon::today(),
        ]);

        $this->assertFalse($item->is_overdue);

        $overdueItem = new ActionItem([
            'status' => 'pending',
            'due_date' => Carbon::yesterday(),
        ]);

        $this->assertTrue($overdueItem->is_overdue);
    }

    public function test_non_organizer_cannot_create_or_delete_meeting_rooms(): void
    {
        $participant = User::where('role', 'participant')->first();
        $this->actingAs($participant);

        // Attempt to access room creation
        $response = $this->get('/rooms/create');
        $response->assertStatus(403);

        // Attempt to delete a room
        $room = MeetingRoom::first();
        $deleteResponse = $this->delete("/rooms/{$room->id}");
        $deleteResponse->assertStatus(403);
    }

    public function test_uninvited_user_cannot_view_confidential_meeting(): void
    {
        $unrelatedUser = User::where('email', 'michael.c@company.local')->first();
        $this->actingAs($unrelatedUser);

        // Meeting 1 is confidential and michael.c is not in the attendee list
        $meeting1 = Meeting::where('meeting_code', 'like', '%EXEC')->first();
        $this->assertTrue((bool) $meeting1->is_confidential);

        $response = $this->get("/meetings/{$meeting1->id}");
        $response->assertStatus(403);
    }

    public function test_non_organizer_cannot_modify_meeting_minutes(): void
    {
        $participant = User::where('role', 'participant')->first();
        $this->actingAs($participant);

        $meeting1 = Meeting::first();

        $response = $this->post("/meetings/{$meeting1->id}/minutes", [
            'summary' => 'Hacked minutes summary',
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_non_assignee_and_non_organizer_cannot_update_action_item_status(): void
    {
        $unrelatedUser = User::where('email', 'michael.c@company.local')->first();
        $this->actingAs($unrelatedUser);

        // Find an action item not assigned to michael.c
        $item = ActionItem::where('assigned_to_id', '!=', $unrelatedUser->id)->first();

        $response = $this->patch("/action-items/{$item->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertStatus(403);
    }

    public function test_unsupported_attachment_extensions_are_rejected(): void
    {
        $organizer = User::where('role', 'organizer')->first();
        $meeting = Meeting::where('organizer_id', $organizer->id)->first();
        $this->actingAs($organizer);

        $maliciousFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->post("/meetings/{$meeting->id}/attachments", [
            'file' => $maliciousFile,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_valid_attachment_upload_and_download_flow(): void
    {
        Storage::fake('local');

        $organizer = User::where('role', 'organizer')->first();
        $meeting = Meeting::where('organizer_id', $organizer->id)->first();
        $this->actingAs($organizer);

        $file = UploadedFile::fake()->create('agenda_notes.pdf', 500, 'application/pdf');

        $uploadResponse = $this->post("/meetings/{$meeting->id}/attachments", [
            'file' => $file,
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        $attachment = MeetingAttachment::where('original_name', 'agenda_notes.pdf')->first();
        $this->assertNotNull($attachment);

        // Organizer can download
        $downloadResponse = $this->get("/attachments/{$attachment->id}/download");
        $downloadResponse->assertStatus(200);
    }

    public function test_meeting_edit_syncs_agendas_and_attendees(): void
    {
        $organizer = User::where('role', 'organizer')->first();
        $this->actingAs($organizer);

        $meeting = Meeting::where('organizer_id', $organizer->id)->first();
        $anotherUser = User::where('id', '!=', $organizer->id)->first();

        $response = $this->put("/meetings/{$meeting->id}", [
            'title' => 'Updated Sprint Sync Title',
            'meeting_type' => 'in_person',
            'room_id' => $meeting->room_id,
            'meeting_date' => $meeting->meeting_date->format('Y-m-d'),
            'start_time' => substr($meeting->start_time, 0, 5),
            'end_time' => substr($meeting->end_time, 0, 5),
            'status' => 'scheduled',
            'attendees' => [
                ['user_id' => $anotherUser->id, 'role' => 'presenter'],
            ],
            'agendas' => [
                ['title' => 'Updated Agenda 1', 'duration_minutes' => 45],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/meetings/{$meeting->id}");

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'title' => 'Updated Sprint Sync Title',
        ]);

        $this->assertDatabaseHas('meeting_attendees', [
            'meeting_id' => $meeting->id,
            'user_id' => $anotherUser->id,
            'role' => 'presenter',
        ]);

        $this->assertDatabaseHas('meeting_agendas', [
            'meeting_id' => $meeting->id,
            'title' => 'Updated Agenda 1',
            'duration_minutes' => 45,
        ]);
    }
}
