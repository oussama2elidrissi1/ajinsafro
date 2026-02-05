<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Voyage;
use App\Models\TravelProgramDay;
use App\Services\WpTourSyncService;
use App\Repositories\WpRepository;
use App\Observers\VoyageObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for WordPress Bidirectional Sync
 * 
 * Run with: php artisan test --filter WpSyncTest
 */
class WpSyncTest extends TestCase
{
    protected WpTourSyncService $syncService;
    protected WpRepository $wpRepo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->syncService = app(WpTourSyncService::class);
        $this->wpRepo = app(WpRepository::class);
    }

    /**
     * Test: WordPress connection is working
     */
    public function test_wp_connection_works()
    {
        $siteUrl = $this->wpRepo->getOption('siteurl');
        
        $this->assertNotNull($siteUrl);
        $this->assertStringContainsString('http', $siteUrl);
    }

    /**
     * Test: Create voyage in Laravel → Push to WP
     */
    public function test_create_voyage_creates_wp_post()
    {
        // Disable observer for manual control
        VoyageObserver::withoutSync(function() {
            $voyage = Voyage::factory()->create([
                'name' => 'Test Sync Tour ' . time(),
                'slug' => 'test-sync-tour-' . time(),
                'description' => 'Test description',
                'min_people' => 2,
                'max_people' => 10,
            ]);

            // Manually sync
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);

            $this->assertTrue($result['success']);
            $this->assertNotNull($result['wp_post_id']);

            // Verify in WP
            $wpPost = $this->wpRepo->getPost($result['wp_post_id']);
            $this->assertEquals($voyage->name, $wpPost['post_title']);
            $this->assertEquals($voyage->slug, $wpPost['post_name']);

            // Cleanup
            $this->wpRepo->deletePost($result['wp_post_id']);
            $voyage->delete();
        });
    }

    /**
     * Test: Update voyage in Laravel → Push to WP
     */
    public function test_update_voyage_updates_wp_post()
    {
        VoyageObserver::withoutSync(function() {
            // Create
            $voyage = Voyage::factory()->create();
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Update
            $newName = 'Updated Name ' . time();
            $voyage->update(['name' => $newName]);
            
            $this->syncService->updateWpTourFromLaravel($voyage->id);

            // Verify
            $wpPost = $this->wpRepo->getPost($wpPostId);
            $this->assertEquals($newName, $wpPost['post_title']);

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->delete();
        });
    }

    /**
     * Test: Modify WP post → Pull to Laravel
     */
    public function test_wp_modification_pulls_to_laravel()
    {
        VoyageObserver::withoutSync(function() {
            // Create
            $voyage = Voyage::factory()->create();
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Modify in WP
            $newTitle = 'WP Modified Title ' . time();
            $this->wpRepo->updatePost($wpPostId, [
                'post_title' => $newTitle,
            ]);

            // Pull from WP
            $this->syncService->upsertLaravelVoyageFromWp($wpPostId);

            // Verify
            $voyage->refresh();
            $this->assertEquals($newTitle, $voyage->name);

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->delete();
        });
    }

    /**
     * Test: Conflict detection (WP wins)
     */
    public function test_conflict_detection_wp_wins()
    {
        VoyageObserver::withoutSync(function() {
            // Create and sync
            $voyage = Voyage::factory()->create(['name' => 'Original']);
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Modify WP (external change)
            sleep(1); // Ensure time difference
            $this->wpRepo->updatePost($wpPostId, [
                'post_title' => 'WP Title',
            ]);

            // Try to push from Laravel (should detect conflict and pull instead)
            $voyage->update(['name' => 'Laravel Title']);
            $updateResult = $this->syncService->updateWpTourFromLaravel($voyage->id);

            // Verify WP won
            $voyage->refresh();
            $this->assertEquals('WP Title', $voyage->name);

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->delete();
        });
    }

    /**
     * Test: Sync hash computation
     */
    public function test_sync_hash_computation()
    {
        VoyageObserver::withoutSync(function() {
            $voyage = Voyage::factory()->create();
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Compute hash
            $hash1 = $this->syncService->computeWpSnapshotHash($wpPostId);
            $this->assertNotEmpty($hash1);

            // Modify WP
            $this->wpRepo->updatePostMeta($wpPostId, 'min_people', 5);

            // Hash should change
            $hash2 = $this->syncService->computeWpSnapshotHash($wpPostId);
            $this->assertNotEquals($hash1, $hash2);

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->delete();
        });
    }

    /**
     * Test: Meta sync (Traveler fields)
     */
    public function test_meta_sync_bidirectional()
    {
        VoyageObserver::withoutSync(function() {
            // Create with metas
            $voyage = Voyage::factory()->create([
                'min_people' => 2,
                'max_people' => 15,
                'is_featured' => true,
                'tours_include' => ['WiFi', 'Breakfast', 'Guide'],
                'tours_exclude' => ['Flights', 'Visa'],
            ]);

            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Verify metas in WP
            $minPeople = $this->wpRepo->getPostMeta($wpPostId, 'min_people');
            $this->assertEquals(2, $minPeople);

            $isFeatured = $this->wpRepo->getPostMeta($wpPostId, 'is_featured');
            $this->assertEquals('on', $isFeatured);

            $toursInclude = $this->wpRepo->getPostMeta($wpPostId, 'tours_include');
            $this->assertStringContainsString('WiFi', $toursInclude);

            // Update meta in WP
            $this->wpRepo->updatePostMeta($wpPostId, 'max_people', 20);

            // Pull
            $this->syncService->upsertLaravelVoyageFromWp($wpPostId);
            $voyage->refresh();

            $this->assertEquals(20, $voyage->max_people);

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->delete();
        });
    }

    /**
     * Test: Observer auto-sync
     */
    public function test_observer_auto_syncs()
    {
        // Ensure observer is enabled
        VoyageObserver::$syncEnabled = true;

        $voyage = Voyage::factory()->create([
            'name' => 'Auto Sync Test ' . time(),
        ]);

        // Observer should have created WP post
        $this->assertNotNull($voyage->wp_post_id);

        // Verify in WP
        $wpPost = $this->wpRepo->getPost($voyage->wp_post_id);
        $this->assertEquals($voyage->name, $wpPost['post_title']);

        // Cleanup
        $this->wpRepo->deletePost($voyage->wp_post_id);
        $voyage->delete();
    }

    /**
     * Test: Program sync (tours_program <-> travel_program_days)
     */
    public function test_program_sync()
    {
        VoyageObserver::withoutSync(function() {
            $voyage = Voyage::factory()->create();
            
            // Create program days
            TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'day_number' => 1,
                'title' => 'Day 1: Arrival',
                'description' => 'Arrive at hotel',
            ]);

            TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'day_number' => 2,
                'title' => 'Day 2: Tour',
                'description' => 'City tour',
            ]);

            // Push to WP
            $result = $this->syncService->createWpTourFromLaravel($voyage->id);
            $wpPostId = $result['wp_post_id'];

            // Verify tours_program in WP
            $toursProgram = $this->wpRepo->getPostMeta($wpPostId, 'tours_program');
            $this->assertNotEmpty($toursProgram);

            if (is_array($toursProgram)) {
                $this->assertCount(2, $toursProgram);
                $this->assertStringContainsString('Arrival', $toursProgram[0]['title']);
            }

            // Cleanup
            $this->wpRepo->deletePost($wpPostId);
            $voyage->programDays()->delete();
            $voyage->delete();
        });
    }
}
