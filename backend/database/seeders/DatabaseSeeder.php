<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create organization
        $org = Organization::create([
            'name' => 'TechCorp Inc.',
            'slug' => 'techcorp',
        ]);

        // Create admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@techcorp.com',
            'password' => 'password',
            'org_id' => $org->id,
            'role' => 'admin',
        ]);

        // Create agents
        $agent1 = User::create([
            'name' => 'Agent John',
            'email' => 'agent1@techcorp.com',
            'password' => 'password',
            'org_id' => $org->id,
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'name' => 'Agent Jane',
            'email' => 'agent2@techcorp.com',
            'password' => 'password',
            'org_id' => $org->id,
            'role' => 'agent',
        ]);

        // Create customers
        $customer1 = User::create([
            'name' => 'Customer Bob',
            'email' => 'customer1@techcorp.com',
            'password' => 'password',
            'org_id' => $org->id,
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'name' => 'Customer Alice',
            'email' => 'customer2@techcorp.com',
            'password' => 'password',
            'org_id' => $org->id,
            'role' => 'customer',
        ]);

        // Create sample tickets
        $tickets = [
            [
                'subject' => 'Login not working',
                'description' => 'I cannot login to my account. It shows incorrect password error.',
                'status' => 'open',
                'priority' => 'high',
                'requester_id' => $customer1->id,
                'assignee_id' => $agent1->id,
                'tags' => ['login', 'urgent'],
            ],
            [
                'subject' => 'Feature request: Dark mode',
                'description' => 'Please add dark mode to the dashboard.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'requester_id' => $customer2->id,
                'assignee_id' => $agent2->id,
                'tags' => ['feature', 'ui'],
            ],
            [
                'subject' => 'Server down',
                'description' => 'The application server is not responding.',
                'status' => 'resolved',
                'priority' => 'urgent',
                'requester_id' => $customer1->id,
                'assignee_id' => $admin->id,
                'tags' => ['server', 'critical'],
            ],
            [
                'subject' => 'Email notifications not sent',
                'description' => 'I am not receiving email notifications for updates.',
                'status' => 'open',
                'priority' => 'low',
                'requester_id' => $customer2->id,
                'assignee_id' => null,
                'tags' => ['email', 'notifications'],
            ],
            [
                'subject' => 'Payment gateway error',
                'description' => 'Payment fails with error code 500.',
                'status' => 'in_progress',
                'priority' => 'high',
                'requester_id' => $customer1->id,
                'assignee_id' => $agent1->id,
                'tags' => ['payment', 'billing'],
            ],
            [
                'subject' => 'Profile page loading slow',
                'description' => 'Profile page takes more than 10 seconds to load.',
                'status' => 'closed',
                'priority' => 'low',
                'requester_id' => $customer2->id,
                'assignee_id' => $agent2->id,
                'tags' => ['performance'],
            ],
            [
                'subject' => 'Cannot upload files',
                'description' => 'File upload button does not work.',
                'status' => 'open',
                'priority' => 'medium',
                'requester_id' => $customer1->id,
                'assignee_id' => $agent1->id,
                'tags' => ['upload', 'bug'],
            ],
            [
                'subject' => 'API rate limit exceeded',
                'description' => 'Getting 429 errors frequently.',
                'status' => 'in_progress',
                'priority' => 'high',
                'requester_id' => $customer2->id,
                'assignee_id' => $agent2->id,
                'tags' => ['api', 'rate-limit'],
            ],
            [
                'subject' => 'Dashboard charts not rendering',
                'description' => 'Charts on dashboard are not showing up.',
                'status' => 'resolved',
                'priority' => 'medium',
                'requester_id' => $customer1->id,
                'assignee_id' => $admin->id,
                'tags' => ['charts', 'ui'],
            ],
            [
                'subject' => 'Mobile responsive issues',
                'description' => 'Layout breaks on mobile devices.',
                'status' => 'open',
                'priority' => 'low',
                'requester_id' => $customer2->id,
                'assignee_id' => null,
                'tags' => ['mobile', 'responsive'],
            ],
            [
                'subject' => 'Database connection timeout',
                'description' => 'Frequent database connection timeouts.',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'requester_id' => $customer1->id,
                'assignee_id' => $agent1->id,
                'tags' => ['database', 'critical'],
            ],
            [
                'subject' => 'User permissions issue',
                'description' => 'Users cannot access their own tickets.',
                'status' => 'resolved',
                'priority' => 'high',
                'requester_id' => $customer2->id,
                'assignee_id' => $admin->id,
                'tags' => ['permissions', 'security'],
            ],
        ];

        foreach ($tickets as $ticketData) {
            $ticketData['org_id'] = $org->id;
            $ticket = Ticket::create($ticketData);

            // Add some sample replies
            if ($ticket->status !== 'open') {
                TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $ticket->assignee_id ?? $admin->id,
                    'body' => 'We are looking into this issue.',
                ]);

                if ($ticket->status === 'resolved' || $ticket->status === 'closed') {
                    TicketReply::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $ticket->assignee_id ?? $admin->id,
                        'body' => 'This issue has been ' . $ticket->status . '.',
                    ]);
                }
            }
        }
    }
}