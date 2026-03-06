<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IssueManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        // Seed Issue Categories
        $categories = [
            ['issue_category' => 'Electrical', 'description' => 'Electrical related issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Plumbing', 'description' => 'Water and plumbing issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Civil Work', 'description' => 'Construction and civil work issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Housekeeping', 'description' => 'Cleaning and housekeeping issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'IT & Networking', 'description' => 'IT, computer and network related issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Furniture', 'description' => 'Furniture related issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'HVAC', 'description' => 'Heating, Ventilation and Air Conditioning', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Security', 'description' => 'Security related issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category' => 'Others', 'description' => 'Other miscellaneous issues', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
        ];

        foreach ($categories as $category) {
            DB::table('issue_category_master')->insert($category);
        }

        // Get inserted category IDs
        $electricalId = DB::table('issue_category_master')->where('issue_category', 'Electrical')->value('pk');
        $plumbingId = DB::table('issue_category_master')->where('issue_category', 'Plumbing')->value('pk');
        $civilId = DB::table('issue_category_master')->where('issue_category', 'Civil Work')->value('pk');
        $housekeepingId = DB::table('issue_category_master')->where('issue_category', 'Housekeeping')->value('pk');
        $itId = DB::table('issue_category_master')->where('issue_category', 'IT & Networking')->value('pk');

        // Seed Issue Sub-Categories
        $subCategories = [
            // Electrical
            ['issue_category_master_pk' => $electricalId, 'issue_sub_category' => 'Light not working', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $electricalId, 'issue_sub_category' => 'Fan not working', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $electricalId, 'issue_sub_category' => 'Switch/Socket problem', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $electricalId, 'issue_sub_category' => 'Power supply issue', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            
            // Plumbing
            ['issue_category_master_pk' => $plumbingId, 'issue_sub_category' => 'Water leakage', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $plumbingId, 'issue_sub_category' => 'Tap not working', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $plumbingId, 'issue_sub_category' => 'Toilet blockage', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $plumbingId, 'issue_sub_category' => 'No water supply', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            
            // Civil Work
            ['issue_category_master_pk' => $civilId, 'issue_sub_category' => 'Wall damage', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $civilId, 'issue_sub_category' => 'Ceiling damage', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $civilId, 'issue_sub_category' => 'Floor damage', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $civilId, 'issue_sub_category' => 'Door/Window problem', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            
            // Housekeeping
            ['issue_category_master_pk' => $housekeepingId, 'issue_sub_category' => 'Room cleaning required', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $housekeepingId, 'issue_sub_category' => 'Garbage disposal', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $housekeepingId, 'issue_sub_category' => 'Pest control needed', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            
            // IT & Networking
            ['issue_category_master_pk' => $itId, 'issue_sub_category' => 'Internet not working', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $itId, 'issue_sub_category' => 'Computer issue', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['issue_category_master_pk' => $itId, 'issue_sub_category' => 'Printer not working', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
        ];

        foreach ($subCategories as $subCategory) {
            DB::table('issue_sub_category_master')->insert($subCategory);
        }

        // Seed Issue Priority
        $priorities = [
            ['priority' => 'Critical', 'description' => 'Critical priority - immediate attention required', 'priority_order' => 1, 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['priority' => 'High', 'description' => 'High priority - urgent', 'priority_order' => 2, 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['priority' => 'Medium', 'description' => 'Medium priority - normal', 'priority_order' => 3, 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['priority' => 'Low', 'description' => 'Low priority - can be delayed', 'priority_order' => 4, 'created_by' => 1, 'created_date' => $now, 'status' => 1],
        ];

        foreach ($priorities as $priority) {
            DB::table('issue_priority_master')->insert($priority);
        }

        // Seed Issue Reproducibility
        $reproducibilities = [
            ['reproducibility_name' => 'Always', 'reproducibility_description' => 'Issue occurs every time', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['reproducibility_name' => 'Sometimes', 'reproducibility_description' => 'Issue occurs occasionally', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['reproducibility_name' => 'Rarely', 'reproducibility_description' => 'Issue occurs rarely', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['reproducibility_name' => 'Once', 'reproducibility_description' => 'Issue occurred only once', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
            ['reproducibility_name' => 'N/A', 'reproducibility_description' => 'Not applicable', 'created_by' => 1, 'created_date' => $now, 'status' => 1],
        ];

        foreach ($reproducibilities as $reproducibility) {
            DB::table('issue_reproducibility_master')->insert($reproducibility);
        }

        $this->command->info('Issue Management master data seeded successfully!');
    }
}
