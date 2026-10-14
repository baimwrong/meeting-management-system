<?php

namespace Database\Seeders;

use App\Models\ActionItem;
use App\Models\Department;
use App\Models\ImportLog;
use App\Models\Meeting;
use App\Models\MeetingAgenda;
use App\Models\MeetingAttendee;
use App\Models\MeetingMinute;
use App\Models\MeetingRoom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Departments
        $departmentsData = [
            ['name' => 'Executive Management', 'code' => 'EXEC', 'description' => 'Corporate leadership and executive board.'],
            ['name' => 'Engineering & Technology', 'code' => 'ENG', 'description' => 'Software development, DevOps, and IT infrastructure.'],
            ['name' => 'Product & Design', 'code' => 'PROD', 'description' => 'Product strategy, UI/UX research, and specifications.'],
            ['name' => 'Human Resources & Talent', 'code' => 'HR', 'description' => 'People operations, recruitment, and culture.'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN', 'description' => 'Corporate finance, budgeting, and audits.'],
            ['name' => 'Operations & Facilities', 'code' => 'OPS', 'description' => 'Global facilities management and supply chain.'],
        ];

        $departments = [];
        foreach ($departmentsData as $d) {
            $departments[$d['code']] = Department::create([
                'name' => $d['name'],
                'code' => $d['code'],
                'description' => $d['description'],
                'is_active' => true,
            ]);
        }

        // 2. Users (Admin, Organizer, Participant, and Team Members)
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@company.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department_id' => $departments['EXEC']->id,
            'job_title' => 'Chief Technology Officer',
            'phone' => '+1 (555) 010-0001',
            'status' => 'active',
        ]);

        $organizer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'organizer@company.local',
            'password' => Hash::make('password'),
            'role' => 'organizer',
            'department_id' => $departments['ENG']->id,
            'job_title' => 'Principal Engineering Manager',
            'phone' => '+1 (555) 010-0002',
            'status' => 'active',
        ]);

        $participant = User::create([
            'name' => 'David Kim',
            'email' => 'participant@company.local',
            'password' => Hash::make('password'),
            'role' => 'participant',
            'department_id' => $departments['PROD']->id,
            'job_title' => 'Senior Product Designer',
            'phone' => '+1 (555) 010-0003',
            'status' => 'active',
        ]);

        $teamUsers = [
            User::create([
                'name' => 'Elena Rostova',
                'email' => 'elena.r@company.local',
                'password' => Hash::make('password'),
                'role' => 'organizer',
                'department_id' => $departments['EXEC']->id,
                'job_title' => 'Chief Operating Officer',
                'phone' => '+1 (555) 010-0004',
                'status' => 'active',
            ]),
            User::create([
                'name' => 'Marcus Chen',
                'email' => 'marcus.c@company.local',
                'password' => Hash::make('password'),
                'role' => 'participant',
                'department_id' => $departments['ENG']->id,
                'job_title' => 'Cloud Solutions Architect',
                'phone' => '+1 (555) 010-0005',
                'status' => 'active',
            ]),
            User::create([
                'name' => 'Rachel Adams',
                'email' => 'rachel.a@company.local',
                'password' => Hash::make('password'),
                'role' => 'participant',
                'department_id' => $departments['FIN']->id,
                'job_title' => 'Financial Controller',
                'phone' => '+1 (555) 010-0006',
                'status' => 'active',
            ]),
            User::create([
                'name' => 'Michael Chang',
                'email' => 'michael.c@company.local',
                'password' => Hash::make('password'),
                'role' => 'participant',
                'department_id' => $departments['HR']->id,
                'job_title' => 'Head of People & Culture',
                'phone' => '+1 (555) 010-0007',
                'status' => 'active',
            ]),
            User::create([
                'name' => 'Sophia Patel',
                'email' => 'sophia.p@company.local',
                'password' => Hash::make('password'),
                'role' => 'participant',
                'department_id' => $departments['OPS']->id,
                'job_title' => 'Facilities Lead',
                'phone' => '+1 (555) 010-0008',
                'status' => 'active',
            ]),
        ];

        // 3. Meeting Rooms
        $roomsData = [
            [
                'name' => 'Executive Boardroom Alpha',
                'code' => 'BOARD-01',
                'floor_location' => 'Executive Floor 5 (East Wing)',
                'capacity' => 24,
                'equipment' => ['4K Laser Projector', 'Polycom 4K Video Conference', 'Smart Podium', 'Boundary Microphones', 'Video Wall', 'Executive Recliners'],
                'hourly_rate' => 75.00,
                'status' => 'available',
                'notes' => 'Reserved for Executive Committee and strategic partner meetings. Catering requires 24h prior notification.',
            ],
            [
                'name' => 'Innovation Lab',
                'code' => 'LAB-02',
                'floor_location' => 'Floor 2 (Tech Hub)',
                'capacity' => 16,
                'equipment' => ['75" Interactive Touch Screen', 'Dual Whiteboards', 'Wireless ClickShare', 'High-Speed Mesh Wi-Fi', 'Movable Desks'],
                'hourly_rate' => 0.00,
                'status' => 'available',
                'notes' => 'Configurable workspace suitable for workshops, design sprints, and agile planning.',
            ],
            [
                'name' => 'Focus Huddle Room A',
                'code' => 'HUDDLE-01',
                'floor_location' => 'Floor 3 (Engineering Area)',
                'capacity' => 6,
                'equipment' => ['55" 4K TV Display', 'Logitech MeetUp Webcam', 'Magnetic Whiteboard', 'Sound Dampening Wall Panels'],
                'hourly_rate' => 0.00,
                'status' => 'available',
                'notes' => 'Ideal for 1-on-1s, small group syncs, and remote interviews.',
            ],
            [
                'name' => 'Focus Huddle Room B',
                'code' => 'HUDDLE-02',
                'floor_location' => 'Floor 3 (Product Area)',
                'capacity' => 6,
                'equipment' => ['55" 4K TV Display', 'Jabra Speak 710 Speakerphone', 'Glass Whiteboard'],
                'hourly_rate' => 0.00,
                'status' => 'available',
                'notes' => 'Quiet zone room for remote collaboration.',
            ],
            [
                'name' => 'Town Hall Auditorium',
                'code' => 'AUD-01',
                'floor_location' => 'Ground Floor Plaza',
                'capacity' => 120,
                'equipment' => ['Dual 4K Laser Projectors', 'Auditorium Line-Array Sound System', 'Wireless Handheld Mics', 'Broadcast Camera Rig', 'Live Streaming Encoder'],
                'hourly_rate' => 150.00,
                'status' => 'available',
                'notes' => 'Whole-company events, quarterly all-hands, and press conferences. Requires AV technician booking.',
            ],
            [
                'name' => 'Strategy War Room',
                'code' => 'WAR-03',
                'floor_location' => 'Floor 4 (Operations Wing)',
                'capacity' => 10,
                'equipment' => ['Cisco Webex Room Bar', 'Dual 65" Displays', 'Full-Wall Magnetic Whiteboard'],
                'hourly_rate' => 0.00,
                'status' => 'maintenance',
                'notes' => 'Undergoing scheduled AV firmware upgrades and microphone recalibration.',
            ],
        ];

        $rooms = [];
        foreach ($roomsData as $r) {
            $rooms[$r['code']] = MeetingRoom::create($r);
        }

        // 4. Meetings & Schedules
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $inTwoDays = Carbon::today()->addDays(2)->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // Meeting 1: Today's Scheduled Meeting (Executive Strategy)
        $meeting1 = Meeting::create([
            'meeting_code' => 'MTG-' . Carbon::today()->format('Ymd') . '-EXEC',
            'title' => 'Q4 Strategic Planning & Resource Allocation',
            'description' => 'Comprehensive review of Q3 performance milestones, department budget allocations, and key enterprise objectives for Q4.',
            'room_id' => $rooms['BOARD-01']->id,
            'organizer_id' => $admin->id,
            'department_id' => $departments['EXEC']->id,
            'meeting_type' => 'hybrid',
            'virtual_link' => 'https://meet.company.local/strategic-q4-exec',
            'meeting_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'scheduled',
            'is_confidential' => true,
        ]);

        $meeting1->agendas()->createMany([
            ['title' => 'Executive Welcome & Macro Market Outlook', 'duration_minutes' => 20, 'presenter_id' => $admin->id, 'order_index' => 1, 'discussion_notes' => 'CTO opens with market trends.'],
            ['title' => 'Q3 Financial Performance & Budget Variance', 'duration_minutes' => 35, 'presenter_id' => $teamUsers[1]->id, 'order_index' => 2, 'discussion_notes' => 'Rachel to present balance sheet summaries.'],
            ['title' => 'Product Roadmap & Engineering Hiring Targets', 'duration_minutes' => 45, 'presenter_id' => $organizer->id, 'order_index' => 3, 'discussion_notes' => 'Sarah to detail cloud migration progress and headcount needs.'],
            ['title' => 'Open Board Discussion & Final Action Sign-Off', 'duration_minutes' => 20, 'presenter_id' => $admin->id, 'order_index' => 4, 'discussion_notes' => 'Consensus on Q4 milestones.'],
        ]);

        $meeting1->attendees()->createMany([
            ['user_id' => $admin->id, 'role' => 'host', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
            ['user_id' => $organizer->id, 'role' => 'presenter', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
            ['user_id' => $participant->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
            ['user_id' => $teamUsers[0]->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
            ['user_id' => $teamUsers[1]->id, 'role' => 'presenter', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
        ]);

        // Meeting 2: Today's In-Progress Meeting (Sprint Tech Sync)
        $meeting2 = Meeting::create([
            'meeting_code' => 'MTG-' . Carbon::today()->format('Ymd') . '-TECH',
            'title' => 'Engineering Architecture & Database Optimization',
            'description' => 'Review of PostgreSQL 18 partitioning strategies and internal API service mesh configurations.',
            'room_id' => $rooms['LAB-02']->id,
            'organizer_id' => $organizer->id,
            'department_id' => $departments['ENG']->id,
            'meeting_type' => 'in_person',
            'meeting_date' => $today,
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => 'in_progress',
            'is_confidential' => false,
        ]);

        $meeting2->agendas()->createMany([
            ['title' => 'PostgreSQL 18 Migration Readiness Review', 'duration_minutes' => 30, 'presenter_id' => $organizer->id, 'order_index' => 1],
            ['title' => 'Service Mesh Latency Profiling', 'duration_minutes' => 40, 'presenter_id' => $teamUsers[0]->id, 'order_index' => 2],
            ['title' => 'Wrap-up & Deployment Window Scheduling', 'duration_minutes' => 20, 'presenter_id' => $organizer->id, 'order_index' => 3],
        ]);

        $meeting2->attendees()->createMany([
            ['user_id' => $organizer->id, 'role' => 'host', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::now()->subMinutes(20)],
            ['user_id' => $participant->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::now()->subMinutes(18)],
            ['user_id' => $teamUsers[0]->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::now()->subMinutes(15)],
        ]);

        // Meeting 3: Upcoming Tomorrow (Product Design Sprint)
        $meeting3 = Meeting::create([
            'meeting_code' => 'MTG-' . Carbon::tomorrow()->format('Ymd') . '-DSGN',
            'title' => 'Design System 3.0 & Mobile UX Review',
            'description' => 'Interactive walkthrough of new component guidelines, responsive patterns, and accessibility contrast standards.',
            'room_id' => $rooms['HUDDLE-01']->id,
            'organizer_id' => $participant->id,
            'department_id' => $departments['PROD']->id,
            'meeting_type' => 'hybrid',
            'virtual_link' => 'https://meet.company.local/design-sync-30',
            'meeting_date' => $tomorrow,
            'start_time' => '11:00:00',
            'end_time' => '12:30:00',
            'status' => 'scheduled',
            'is_confidential' => false,
        ]);

        $meeting3->agendas()->createMany([
            ['title' => 'Design Tokens & Color Palette Standardization', 'duration_minutes' => 40, 'presenter_id' => $participant->id, 'order_index' => 1],
            ['title' => 'Mobile Component Sandbox Demos', 'duration_minutes' => 35, 'presenter_id' => $participant->id, 'order_index' => 2],
            ['title' => 'Feedback Collection & Developer Handoff Plan', 'duration_minutes' => 15, 'presenter_id' => $organizer->id, 'order_index' => 3],
        ]);

        $meeting3->attendees()->createMany([
            ['user_id' => $participant->id, 'role' => 'host', 'rsvp_status' => 'accepted', 'attendance_status' => 'unmarked'],
            ['user_id' => $organizer->id, 'role' => 'required', 'rsvp_status' => 'tentative', 'attendance_status' => 'unmarked', 'notes' => 'May join 15 mins late due to client call.'],
            ['user_id' => $teamUsers[0]->id, 'role' => 'optional', 'rsvp_status' => 'pending', 'attendance_status' => 'unmarked'],
        ]);

        // Meeting 4: Past Completed Meeting with Full MoM & Action Items
        $meeting4 = Meeting::create([
            'meeting_code' => 'MTG-' . Carbon::yesterday()->format('Ymd') . '-INFR',
            'title' => 'Data Center Infrastructure & Disaster Recovery Audit',
            'description' => 'Annual disaster recovery simulation findings, failover test verifications, and compliance sign-offs.',
            'room_id' => $rooms['BOARD-01']->id,
            'organizer_id' => $admin->id,
            'department_id' => $departments['OPS']->id,
            'meeting_type' => 'in_person',
            'meeting_date' => $yesterday,
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'status' => 'completed',
            'is_confidential' => false,
        ]);

        $meeting4->agendas()->createMany([
            ['title' => 'Disaster Recovery Failover Simulation Results', 'duration_minutes' => 45, 'presenter_id' => $admin->id, 'order_index' => 1],
            ['title' => 'Secondary Data Center Latency Analysis', 'duration_minutes' => 30, 'presenter_id' => $teamUsers[3]->id, 'order_index' => 2],
            ['title' => 'Compliance Documentation Review', 'duration_minutes' => 15, 'presenter_id' => $admin->id, 'order_index' => 3],
        ]);

        $meeting4->attendees()->createMany([
            ['user_id' => $admin->id, 'role' => 'host', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::yesterday()->setTime(12, 55)],
            ['user_id' => $organizer->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::yesterday()->setTime(12, 58)],
            ['user_id' => $teamUsers[3]->id, 'role' => 'required', 'rsvp_status' => 'accepted', 'attendance_status' => 'present', 'checked_in_at' => Carbon::yesterday()->setTime(13, 2)],
            ['user_id' => $participant->id, 'role' => 'optional', 'rsvp_status' => 'declined', 'attendance_status' => 'absent', 'notes' => 'Conflicting sprint demo.'],
        ]);

        // Minutes for Meeting 4
        MeetingMinute::create([
            'meeting_id' => $meeting4->id,
            'prepared_by_id' => $admin->id,
            'summary' => "The Committee convened to review the results of the automated DR failover drill executed over the weekend. All mission-critical databases successfully switched over to the secondary standby cluster within 42 seconds, well within the target SLA threshold of 120 seconds.\n\nSecondary storage replication health remains at 99.999% uptime with zero packet corruption. The Operations team reported full compliance with ISO-27001 disaster continuity standards.",
            'key_decisions' => "1. Approved transition to automated bi-monthly disaster recovery drill schedule.\n2. Allocated budget for upgrading standby cluster network throughput from 10Gbps to 40Gbps.\n3. Certified annual disaster recovery compliance report for board distribution.",
            'status' => 'approved',
            'approved_by_id' => $admin->id,
            'approved_at' => Carbon::yesterday()->setTime(16, 0),
        ]);

        // Action Items for Meeting 4
        ActionItem::create([
            'meeting_id' => $meeting4->id,
            'title' => 'Publish Secondary Cluster Bandwidth Upgrade RFP',
            'description' => 'Draft and circulate the procurement request for 40Gbps interconnect switches with vendors.',
            'assigned_to_id' => $teamUsers[3]->id,
            'due_date' => Carbon::today()->addDays(5),
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        ActionItem::create([
            'meeting_id' => $meeting4->id,
            'title' => 'Submit Signed ISO-27001 DR Audit Certificate',
            'description' => 'File official auditor certificates into the legal repository.',
            'assigned_to_id' => $admin->id,
            'due_date' => Carbon::today()->addDays(2),
            'priority' => 'medium',
            'status' => 'completed',
            'completed_at' => Carbon::yesterday()->setTime(17, 30),
        ]);

        ActionItem::create([
            'meeting_id' => $meeting4->id,
            'title' => 'Configure automated alert notifications for standby lag > 15s',
            'description' => 'Implement PagerDuty alert thresholds on replication stream monitor.',
            'assigned_to_id' => $organizer->id,
            'due_date' => Carbon::yesterday(), // Overdue
            'priority' => 'urgent',
            'status' => 'pending',
        ]);

        // Action Item assigned to Participant (so they see it on their dashboard)
        ActionItem::create([
            'meeting_id' => $meeting1->id,
            'title' => 'Prepare mobile design prototype for Q4 marketing campaign',
            'description' => 'Finalize Figma design components and flow charts for review.',
            'assigned_to_id' => $participant->id,
            'due_date' => Carbon::today()->addDays(4),
            'priority' => 'high',
            'status' => 'pending',
        ]);

        // 5. Sample Import Log
        ImportLog::create([
            'user_id' => $admin->id,
            'entity_type' => 'rooms',
            'file_name' => 'initial_facilities_q4.csv',
            'total_rows' => 6,
            'success_rows' => 6,
            'failed_rows' => 0,
            'errors_json' => [],
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(3),
        ]);
    }
}
