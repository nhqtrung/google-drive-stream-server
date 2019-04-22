<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\putFileInDirGoogleDrive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Helpers\GoogleDriveHelper;

class rewriteM3U8File implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $googleDriveFolder, $rootPathFolder, $disk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rootPathFolder ,$googleDriveFolder, $disk)
    {
        $this->rootPathFolder = $rootPathFolder;
        $this->googleDriveFolder = $googleDriveFolder;
        $this->disk = $disk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $googleDriveFolder = new GoogleDriveHelper;

        // Get the files inside the folder...
        $files = $googleDriveFolder->getAllFileFromOriginnalPath($this->googleDriveFolder, $this->disk);
        
        $listFile = $files->mapWithKeys(function($file) {
            $filename = $file['filename'].'.'.$file['extension'];
            $path = $file['path'];
            $parsePath = explode("/", $path);
            // dump($parsePath);
            $fileId = $parsePath[sizeof($parsePath) - 1];

            $streamingLink = "http://li1tv.vn/hls/$fileId";

            return [$filename => $streamingLink];
        });
        $hls_playlist = Storage::disk('converted_videos')->files($this->rootPathFolder);
        $storagePath = Storage::disk('converted_videos')->getAdapter()->getPathPrefix();
        foreach($hls_playlist as $item) {
            $filePath = $storagePath.$item;
            if (strpos($item, 'm3u8')) {
                $fileData = File::get($filePath);
                foreach($listFile as $key => $value) {
                    $fileData = str_replace($key, $value, $fileData);
                }

                Storage::disk('converted_videos')->put($item, $fileData);

                $folderPath = $googleDriveFolder->getFolderIdFromOriginalPath($this->rootPathFolder.'/M3U8', $this->disk);

                putFileInDirGoogleDrive::dispatch($folderPath, 'index.m3u8', $filePath, $this->disk);
            }
        }
    }
}
