<?php

namespace Tests\Feature;

use App\Jobs\ProcessListingPhoto;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\User;
use App\Services\Listings\DraftWriter;
use App\Services\Media\FileCleanup;
use App\Services\Media\ListingImageProcessor;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use Tests\TestCase;

class ListingPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, LocationSeeder::class]);
        // Linux temporary storage preserves permission bits; the Windows bind mount does not.
        $disk = Storage::build(['driver' => 'local', 'root' => sys_get_temp_dir().'/olxv2-photo-test-'.Str::uuid(), 'throw' => true]);
        Storage::set('s3', $disk);
        $this->beforeApplicationDestroyed(fn () => $disk->deleteDirectory(''));
        Queue::fake();
    }

    private function draft(?User $owner = null): Listing
    {
        return app(DraftWriter::class)->create($owner ?? User::factory()->create(), Category::where('slug', 'calculatoare')->value('id'), (string) Str::uuid());
    }

    private function url(Listing $listing, ?ListingPhoto $photo = null): string
    {
        return '/ro/account/drafts/'.$listing->public_id.'/photos'.($photo ? '/'.$photo->public_id : '');
    }

    private function upload(Listing $listing, array $files)
    {
        return $this->post($this->url($listing), ['version' => $listing->fresh()->version, 'photos' => $files]);
    }

    private function process(ListingPhoto $photo): void
    {
        (new ProcessListingPhoto($photo->id))->handle(app(ListingImageProcessor::class), app(FileCleanup::class));
    }

    public function test_upload_is_private_queued_and_produces_bounded_webp_variants(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('private-original.jpg', 1920, 1080)])->assertRedirect()->assertSessionHasNoErrors();
        $photo = ListingPhoto::sole();
        $this->assertSame('queued', $photo->status);
        $this->assertSame(0, $photo->position);
        $this->assertSame(2, $listing->fresh()->version);
        Storage::disk('s3')->assertExists($photo->sourcePath());
        $this->assertSame('private', Storage::disk('s3')->getVisibility($photo->sourcePath()));
        Queue::assertPushed(ProcessListingPhoto::class, fn ($job) => $job->photoId === $photo->id);
        $this->get('/media/listings/'.$photo->public_id.'/card')->assertNotFound();
        $this->process($photo);
        $photo->refresh();
        $this->assertSame('ready', $photo->status);
        Storage::disk('s3')->assertMissing($photo->sourcePath());
        foreach (['thumb' => [320, 180], 'card' => [640, 360], 'full' => [1280, 720]] as $variant => [$width,$height]) {
            $bytes = Storage::disk('s3')->get($photo->variantPath($variant));
            $info = getimagesizefromstring($bytes);
            $this->assertSame(IMAGETYPE_WEBP, $info[2]);
            $this->assertSame([$width, $height], [$info[0], $info[1]]);
            $this->assertSame(strlen($bytes), $photo->variants[$variant]['bytes']);
            $this->assertSame('private', Storage::disk('s3')->getVisibility($photo->variantPath($variant)));
        }
        $this->get('/media/listings/'.$photo->public_id.'/card')->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertOk()->assertSee('/media/listings/'.$photo->public_id.'/full')->assertSee('srcset=', false)->assertDontSee($photo->storage_prefix)->assertDontSee('private-original.jpg');
        $this->process($photo); // Duplicate delivery cannot read the now-retired original.
        $this->assertSame('ready', $photo->fresh()->status);
    }

    public function test_all_formats_are_decoded_and_original_names_and_metadata_are_discarded(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $raster = imagecreatetruecolor(256, 128);
        foreach (['png', 'jpeg', 'webp'] as $format) {
            ob_start();
            match ($format) {
                'png' => imagepng($raster), 'jpeg' => imagejpeg($raster), 'webp' => imagewebp($raster)
            };
            $bytes = ob_get_clean().'PRIVATE_TRAILING_METADATA';
            $file = UploadedFile::fake()->createWithContent('../../executable.php', $bytes);
            $this->upload($listing, ['arbitrary-key' => $file])->assertSessionHasNoErrors();
            $photo = ListingPhoto::where('listing_id', $listing->id)->latest('id')->first();
            $this->process($photo);
            $output = Storage::disk('s3')->get($photo->variantPath('full'));
            $this->assertStringNotContainsString('PRIVATE_TRAILING_METADATA', $output);
            $this->assertStringNotContainsString('executable', $photo->storage_prefix);
            $this->assertSame(256, $photo->fresh()->variants['full']['width']);
            $this->assertSame(1, substr_count($photo->fresh()->srcset(), '256w'));
        }
    }

    public function test_exif_orientation_is_normalized_and_exif_is_not_forwarded(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $source = imagecreatetruecolor(800, 400);
        imagefilledrectangle($source, 0, 0, 399, 399, imagecolorallocate($source, 240, 0, 0));
        imagefilledrectangle($source, 400, 0, 799, 399, imagecolorallocate($source, 0, 0, 240));
        ob_start();
        imagejpeg($source);
        $jpeg = ob_get_clean();
        $exif = "Exif\0\0II".pack('vV', 42, 8).pack('v', 1).pack('vvV', 0x0112, 3, 1).pack('v', 6)."\0\0".pack('V', 0);
        $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
        $this->upload($listing, [UploadedFile::fake()->createWithContent('rotated.jpg', $jpeg)])->assertSessionHasNoErrors();
        $photo = ListingPhoto::sole();
        $this->assertSame([400, 800], [$photo->width, $photo->height]);
        $this->process($photo);
        $bytes = Storage::disk('s3')->get($photo->variantPath('full'));
        $result = imagecreatefromstring($bytes);
        $this->assertSame([400, 800], [imagesx($result), imagesy($result)]);
        $top = imagecolorsforindex($result, imagecolorat($result, 200, 50));
        $bottom = imagecolorsforindex($result, imagecolorat($result, 200, 750));
        $this->assertGreaterThan(200, $top['red']);
        $this->assertGreaterThan(200, $bottom['blue']);
        $this->assertStringNotContainsString('Exif', $bytes);
    }

    public function test_invalid_files_dimensions_and_count_do_not_reserve_or_store_objects(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        foreach ([
            UploadedFile::fake()->createWithContent('pretend.jpg', '<script>bad</script>'),
            UploadedFile::fake()->create('video.mp4', 20, 'video/mp4'),
            UploadedFile::fake()->image('too-small.png', 30, 30),
            UploadedFile::fake()->image('too-wide.png', 6100, 64),
            UploadedFile::fake()->image('animation.gif', 100, 100),
            UploadedFile::fake()->create('oversized.jpg', 10241, 'image/jpeg'),
        ] as $file) {
            $this->upload($listing, [$file])->assertSessionHasErrors();
            $this->assertDatabaseCount('listing_photos', 0);
        }
        $this->upload($listing, array_map(fn () => UploadedFile::fake()->image('photo.png', 100, 100), range(1, 9)))->assertSessionHasErrors('photos');
        $this->assertSame([], Storage::disk('s3')->allFiles());
        $this->assertSame(1, $listing->fresh()->version);
    }

    public function test_eight_maximum_includes_queued_images_and_stale_uploads_cannot_overwrite(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, array_map(fn () => UploadedFile::fake()->image('photo.png', 100, 100), range(1, 8)))->assertSessionHasNoErrors();
        $this->upload($listing, [UploadedFile::fake()->image('ninth.png', 100, 100)])->assertSessionHasErrors('photos');
        $this->post($this->url($listing), ['version' => 1, 'photos' => [UploadedFile::fake()->image('stale.png', 100, 100)]])->assertStatus(409);
        $this->assertDatabaseCount('listing_photos', 8);
        $this->assertSame(range(0, 7), $listing->photos()->pluck('position')->all());
        $this->assertSame(2, $listing->fresh()->version);
        $this->post('/ro/account/drafts/'.$listing->public_id.'/steps/photos', ['version' => 2])->assertSessionHasErrors('photos');
    }

    public function test_guest_other_owner_and_cross_listing_photo_requests_are_denied(): void
    {
        $listing = $this->draft();
        $other = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('photo.png', 100, 100)]);
        $photo = ListingPhoto::sole();
        $this->process($photo);
        $this->actingAs($other->user);
        $this->get('/media/listings/'.$photo->public_id.'/thumb')->assertNotFound();
        $this->get($this->url($listing).'/status')->assertNotFound();
        $this->upload($listing, [UploadedFile::fake()->image('photo.png', 100, 100)])->assertNotFound();
        $this->put($this->url($listing, $photo), ['version' => 2, 'action' => 'primary'])->assertNotFound();
        $this->delete($this->url($listing, $photo), ['version' => 2])->assertNotFound();
        $this->put($this->url($other, $photo), ['version' => 1, 'action' => 'primary'])->assertNotFound();
        $this->delete($this->url($other, $photo), ['version' => 1])->assertNotFound();
        auth()->logout();
        $this->get('/media/listings/'.$photo->public_id.'/thumb')->assertNotFound();
        $this->get('/media/listings/'.$photo->public_id.'/source')->assertNotFound();
        $this->assertDatabaseCount('listing_photos', 1);
    }

    public function test_reorder_primary_delete_and_late_job_preserve_consistency(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, array_map(fn () => UploadedFile::fake()->image('photo.png', 100, 100), range(1, 3)));
        [$first,$second,$third] = $listing->photos()->get()->all();
        $this->put($this->url($listing, $third), ['version' => 2, 'action' => 'primary'])->assertSessionHasNoErrors();
        $this->assertSame([$third->id, $first->id, $second->id], $listing->photos()->pluck('id')->all());
        $this->put($this->url($listing, $third), ['version' => 2, 'action' => 'right'])->assertStatus(409);
        $this->put($this->url($listing, $third), ['version' => 3, 'action' => 'right'])->assertSessionHasNoErrors();
        $this->assertSame([$first->id, $third->id, $second->id], $listing->photos()->pluck('id')->all());
        $this->process($first);
        $this->delete($this->url($listing, $first), ['version' => 4])->assertSessionHasNoErrors();
        foreach ($first->paths() as $path) {
            Storage::disk('s3')->assertMissing($path);
        }
        $this->assertSame([0, 1], $listing->photos()->pluck('position')->all());
        $this->delete($this->url($listing, $third), ['version' => 5])->assertSessionHasNoErrors();
        $this->process($third);
        foreach ($third->paths() as $path) {
            Storage::disk('s3')->assertMissing($path);
        }
        $this->assertSame([$second->id], $listing->photos()->pluck('id')->all());
        $this->assertDatabaseCount('pending_file_deletions', 0);
    }

    public function test_failed_job_retires_original_and_partial_outputs_and_can_be_removed(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('photo.png', 100, 100)]);
        $photo = ListingPhoto::sole();
        Storage::disk('s3')->put($photo->variantPath('thumb'), 'partial output');
        $processor = $this->mock(ListingImageProcessor::class);
        $processor->shouldReceive('process')->once()->andThrow(UnableToWriteFile::atLocation('variant'));
        $job = new ProcessListingPhoto($photo->id);
        try {
            $job->handle($processor, app(FileCleanup::class));
            $this->fail('Storage failure must propagate to queue retry.');
        } catch (UnableToWriteFile $exception) {
            $this->assertSame('queued', $photo->fresh()->status);
            $job->failed($exception);
        }
        $this->assertSame('failed', $photo->fresh()->status);
        foreach ($photo->paths() as $path) {
            Storage::disk('s3')->assertMissing($path);
        }
        $this->get('/ro/account/drafts/'.$listing->public_id.'/edit/photos')->assertOk()->assertSee(__('photo.failed'));
        $this->delete($this->url($listing, $photo), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('listing_photos', 0);
    }

    public function test_interrupted_upload_and_lost_queue_dispatch_are_recovered_in_bounded_batches(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('photo.png', 100, 100), UploadedFile::fake()->image('other.png', 100, 100)]);
        [$stale,$queued] = $listing->photos()->get()->all();
        DB::table('listing_photos')->where('id', $stale->id)->update(['status' => 'uploading', 'updated_at' => now()->subMinutes(16)]);
        DB::table('listing_photos')->where('id', $queued->id)->update(['dispatched_at' => null, 'updated_at' => now()->subMinutes(3)]);
        Queue::fake();
        $this->artisan('media:recover-listings')->assertSuccessful();
        $this->assertSame('failed', $stale->fresh()->status);
        Storage::disk('s3')->assertMissing($stale->sourcePath());
        Queue::assertPushed(ProcessListingPhoto::class, fn ($job) => $job->photoId === $queued->id);
        $this->assertNotNull($queued->fresh()->dispatched_at);
    }

    public function test_banned_owners_and_non_drafts_cannot_serve_or_mutate_photos(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('photo.png', 100, 100)]);
        $photo = ListingPhoto::sole();
        $this->process($photo);
        $listing->forceFill(['status' => 'active'])->save();
        $this->get('/media/listings/'.$photo->public_id.'/card')->assertOk();
        $this->delete($this->url($listing, $photo), ['version' => 2])->assertNotFound();
        $listing->forceFill(['status' => 'draft'])->save();
        DB::table('users')->where('id', $listing->user_id)->update(['status' => 'banned']);
        $this->actingAs($listing->user->fresh())->get('/media/listings/'.$photo->public_id.'/card')->assertRedirect('/ro/login');
        $this->assertDatabaseCount('listing_photos', 1);
    }

    public function test_draft_cards_eager_load_one_ready_image_per_listing(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        foreach (range(1, 2) as $index) {
            $listing = $this->draft($owner);
            $this->upload($listing, [UploadedFile::fake()->image('one.png', 100, 100), UploadedFile::fake()->image('two.png', 100, 100)]);
            foreach ($listing->photos()->get() as $photo) {
                $this->process($photo);
            }
        }
        $this->get('/ro/account/drafts')->assertOk()->assertViewHas('drafts', function ($drafts) {
            foreach ($drafts as $listing) {
                if (! $listing->relationLoaded('photos') || $listing->photos->count() !== 1 || $listing->photos->first()->position !== 0) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_deletion_is_journaled_even_when_object_storage_is_temporarily_unavailable(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->upload($listing, [UploadedFile::fake()->image('one.png', 100, 100)]);
        $photo = ListingPhoto::sole();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->times(4)->andThrow(new \RuntimeException('Offline storage'));
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
        $this->delete($this->url($listing, $photo), ['version' => 2])->assertRedirect();
        $this->assertDatabaseCount('listing_photos', 0);
        $this->assertDatabaseCount('pending_file_deletions', 4);
        foreach ($photo->paths() as $path) {
            $this->assertDatabaseHas('pending_file_deletions', ['path' => $path, 'attempts' => 1]);
        }
    }
}
