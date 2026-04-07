<?php

namespace Database\Seeders;

use App\Models\Access;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Access::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Access::insert([
            ['name' => 'Dashboard Access', 'url' => '/dashboard', 'page_name' => 'Dashboard', 'created_at' => now()],

            // Appointment
            ['name' => 'View Appointments', 'url' => '/appointments', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Detail Appointments', 'url' => '/appointments/view', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Add Appointments', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Edit Appointments', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Delete Appointments', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Copy Appointment', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Assign Appointment', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Reschedule Appointment', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Donot Cover', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Could Not Cover', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Cancel Appointment', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Check-in/Check-out', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Invite Interpreter', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Add Patient', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],
            ['name' => 'Extra Mileage', 'url' => '', 'page_name' => 'Appointments', 'created_at' => now()],

            // Clients

            ['name' => 'View Client Accounts', 'url' => '/clients/accounts', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Add Client Account', 'url' => '/clients/accounts/create', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Edit Client Account', 'url' => '/clients/accounts/update', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Delete Client Account', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Status Client Account', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'View Facilities', 'url' => '/clients/accounts/facilities', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Add Facilities', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Edit Facilities', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Delete Facilities', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'status Facilities', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'View Departments', 'url' => '/clients/accounts/departments', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Add Departments', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Edit Departments', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Delete Departments', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],
            ['name' => 'Status Departments', 'url' => '', 'page_name' => 'Clients accounts', 'created_at' => now()],

            // Billing
            ['name' => 'View Billing', 'url' => '/billings', 'page_name' => 'Billing', 'created_at' => now()],
            ['name' => 'Detail Billing', 'url' => '/billings/detail', 'page_name' => 'Billing', 'created_at' => now()],
            ['name' => 'Generate Invoices', 'url' => '/billings/pdf-view', 'page_name' => 'Billing', 'created_at' => now()],

            // Invoice
            ['name' => 'View Invoices', 'url' => '/invoices', 'page_name' => 'Invoices', 'created_at' => now()],
            ['name' => 'Delete Invoices', 'url' => '', 'page_name' => 'Invoices', 'created_at' => now()],
            ['name' => 'Change Status', 'url' => '', 'page_name' => 'Invoices', 'created_at' => now()],
            ['name' => 'Download Invoices', 'url' => '/invoices/pdf-view', 'page_name' => 'Invoices', 'created_at' => now()],

            // Vendors
            ['name' => 'View Vendor', 'url' => '/vendors', 'page_name' => 'Vendors', 'created_at' => now()],
            ['name' => 'Add Vendor', 'url' => '', 'page_name' => 'Vendors', 'created_at' => now()],
            ['name' => 'Edit Vendor', 'url' => '', 'page_name' => 'Vendors', 'created_at' => now()],
            ['name' => 'Delete Vendor', 'url' => '', 'page_name' => 'Vendors', 'created_at' => now()],
            ['name' => 'Status Vendor', 'url' => '', 'page_name' => 'Vendors', 'created_at' => now()],

            // Interpreters
            ['name' => 'View Interpreter', 'url' => '/interpreters', 'page_name' => 'Interpreters', 'created_at' => now()],
            ['name' => 'Add Interpreter', 'url' => '', 'page_name' => 'Interpreters', 'created_at' => now()],
            ['name' => 'Edit Interpreter', 'url' => '', 'page_name' => 'Interpreters', 'created_at' => now()],
            ['name' => 'Delete Interpreter', 'url' => '', 'page_name' => 'Interpreters', 'created_at' => now()],
            ['name' => 'Status Interpreter', 'url' => '', 'page_name' => 'Interpreters', 'created_at' => now()],
           
            // Interpreters Payment
            ['name' => 'View Interpreter Payments', 'url' => '/interpreter-payments', 'page_name' => 'Interpreter payments', 'created_at' => now()],
            ['name' => 'Change Interpreter Status', 'url' => '', 'page_name' => 'Interpreter payments', 'created_at' => now()],

            // Vendors Payment
            ['name' => 'View Vendor Payments', 'url' => '/vendor-payments', 'page_name' => 'Vendor payments', 'created_at' => now()],
            ['name' => 'Change Vendor Status', 'url' => '', 'page_name' => 'Vendor payments', 'created_at' => now()],

            // Tools 
            ['name' => 'View Languages', 'url' => '/tools/language', 'page_name' => 'Languages', 'created_at' => now()],
            ['name' => 'Create Languages', 'url' => '', 'page_name' => 'Languages', 'created_at' => now()],
            ['name' => 'Edit Languages', 'url' => '', 'page_name' => 'Languages', 'created_at' => now()],
            ['name' => 'Delete Languages', 'url' => '', 'page_name' => 'Languages', 'created_at' => now()],
            ['name' => 'Status Languages', 'url' => '', 'page_name' => 'Languages', 'created_at' => now()],

            ['name' => 'View States', 'url' => '/tools/state', 'page_name' => 'States', 'created_at' => now()],
            ['name' => 'Create States', 'url' => '', 'page_name' => 'States', 'created_at' => now()],
            ['name' => 'Edit States', 'url' => '', 'page_name' => 'States', 'created_at' => now()],
            ['name' => 'Delete States', 'url' => '', 'page_name' => 'States', 'created_at' => now()],
            ['name' => 'Status States', 'url' => '', 'page_name' => 'States', 'created_at' => now()],

            ['name' => 'View Cities', 'url' => '/tools/city', 'page_name' => 'Cities', 'created_at' => now()],
            ['name' => 'Create Cities', 'url' => '', 'page_name' => 'Cities', 'created_at' => now()],
            ['name' => 'Edit Cities', 'url' => '', 'page_name' => 'Cities', 'created_at' => now()],
            ['name' => 'Delete Cities', 'url' => '', 'page_name' => 'Cities', 'created_at' => now()],
            ['name' => 'Status Cities', 'url' => '', 'page_name' => 'Cities', 'created_at' => now()],

            ['name' => 'Change Password', 'url' => '/change-password', 'page_name' => 'Change Password', 'created_at' => now()],
        ]);
    }
}
