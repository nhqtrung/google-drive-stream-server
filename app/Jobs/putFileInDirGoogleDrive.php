<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class putFileInDirGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $rootPathFolder, $fileName, $filePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rootPathFolder, $fileName, $filePath)
    {
        $this->rootPathFolder = $rootPathFolder;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $folderLevel = explode("/", $this->rootPathFolder);
        $previousDirectory = '/';
        $recursive = false; // Get subdirectories also?
        $previousContents = collect(Storage::cloud()->listContents($previousDirectory, $recursive));
        // dd($previousContents);
        for ($i = 0; $i < count($folderLevel); $i++) {
            $nextDirectory = $previousContents->where('type', '=', 'dir')
                                            ->where('filename', '=', $folderLevel[$i])
                                            ->first(); // There could be duplicate directory names!
            if ( ! $nextDirectory ) {
                $previousDirectoryPath = $previousDirectory['path'] ?? "" ;
                $folderPath = $previousDirectoryPath . '/' . $folderLevel[$i];

                Storage::cloud()->makeDirectory($folderPath);


                $previousContents = collect(Storage::cloud()->listContents($previousDirectoryPath, $recursive));
                $previousDirectory = $previousContents->where('type', '=', 'dir')
                                                ->where('filename', '=', $folderLevel[$i])
                                                ->first();
                $previousContents = collect(Storage::cloud()->listContents($previousDirectory['path'], $recursive));
            } else {
                $previousDirectory = $nextDirectory;
                $previousContents = collect(Storage::cloud()->listContents($previousDirectory['path'], $recursive));
            }
        }
        $fileData = File::get($this->filePath);
        Storage::cloud()->put($previousDirectory['path'].'/'.$this->fileName, $fileData);
    }
}
