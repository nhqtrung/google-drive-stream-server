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

class rewriteM3U8File implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $googleDriveFolder, $rootPathFolder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rootPathFolder ,$googleDriveFolder)
    {
        $this->rootPathFolder = $rootPathFolder;
        $this->googleDriveFolder = $googleDriveFolder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $folderLevel = explode('/', $this->googleDriveFolder);
        $previousDirectory = '/';
        $recursive = false; // Get subdirectories also?
        $previousContents = collect(Storage::cloud()->listContents($previousDirectory, $recursive));

        for ($i = 0; $i < count($folderLevel); $i++) {
            $nextDirectory = $previousContents->where('type', '=', 'dir')
                            ->where('filename', '=', $folderLevel[$i])
                            ->first(); // There could be duplicate directory names!
            if ( ! $nextDirectory ) {
                return 'No directory name: '.$folderLevel[$i];
            } else {
                $previousDirectory = $nextDirectory;
                $previousContents = collect(Storage::cloud()->listContents($previousDirectory['path'], $recursive));
            }
        }


        // Get the files inside the folder...
        $files = $previousContents->where('type', '=', 'file');
        
        $listFile = $files->mapWithKeys(function($file) {
            $filename = $file['filename'].'.'.$file['extension'];
            $path = $file['path'];
            $parsePath = explode("/", $path);
            // dump($parsePath);
            $fileId = $parsePath[sizeof($parsePath) - 1];

            $streamingLink = "http://localhost:8080/li1tv-cdn/public/hls/$fileId";

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

                putFileInDirGoogleDrive::dispatch($this->rootPathFolder.'/M3U8', 'index.m3u8', $filePath);
            }
        }
    }
}
